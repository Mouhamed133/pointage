# Configuration des CRON Jobs sur Railway

## Vue d'ensemble

L'application utilise des CRON jobs pour les tâches programmées:

- `cron/send_absence_alerts.php` - Envoyer les alertes d'absence

## Configuration sur Railway

### Option 1: Utiliser Railway CRON (Recommandé)

1. **Ajouter un service de CRON dans railway.json**

```json
{
  "services": {
    "app": {
      "build": { "dockerfile": "Dockerfile" },
      "start": "php -S 0.0.0.0:$PORT -t public"
    },
    "cron": {
      "build": { "dockerfile": "Dockerfile" },
      "start": "php /app/cron/scheduler.php"
    }
  }
}
```

### Option 2: Utiliser un service externe (ex: EasyCron)

1. **Créer un endpoint HTTP**

```php
// app/controllers/CronController.php
class CronController {
    public function sendAbsenceAlerts() {
        require_once __DIR__ . '/../../cron/send_absence_alerts.php';
    }
}
```

2. **Ajouter à la route**

```php
// config/routes.php
$pagesPubliques[] = 'cron/absence-alerts';
```

3. **Appeler depuis EasyCron**

```
https://your-app.up.railway.app/?route=cron/absence-alerts
```

### Option 3: Scheduler PHP (Robuste)

Créer `cron/scheduler.php`:

```php
<?php
/**
 * Scheduler PHP - Exécute les CRON jobs
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class Scheduler {
    private array $jobs = [];

    public function addJob($name, $callback, $interval) {
        $this->jobs[] = [
            'name' => $name,
            'callback' => $callback,
            'interval' => $interval, // en secondes
            'lastRun' => 0
        ];
    }

    public function run() {
        while (true) {
            $now = time();

            foreach ($this->jobs as &$job) {
                if ($now - $job['lastRun'] >= $job['interval']) {
                    echo "[" . date('Y-m-d H:i:s') . "] Exécution de {$job['name']}\n";

                    try {
                        call_user_func($job['callback']);
                        $job['lastRun'] = $now;
                        echo "[" . date('Y-m-d H:i:s') . "] {$job['name']} terminé\n";
                    } catch (Exception $e) {
                        echo "[" . date('Y-m-d H:i:s') . "] Erreur dans {$job['name']}: " . $e->getMessage() . "\n";
                    }
                }
            }

            sleep(60); // Vérifier toutes les minutes
        }
    }
}

// Initialiser le scheduler
$scheduler = new Scheduler();

// Ajouter les jobs
$scheduler->addJob(
    'send_absence_alerts',
    function() {
        require_once __DIR__ . '/send_absence_alerts.php';
    },
    3600 // Toutes les heures
);

// Démarrer le scheduler
$scheduler->run();
```

## Configuration des jobs

### Job: Envoyer les alertes d'absence

Fréquence: Quotidienne (6h du matin)

```php
// cron/send_absence_alerts.php
<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

// Récupérer les absences du jour
$today = date('Y-m-d');
$sql = "SELECT DISTINCT user_id FROM attendance
        WHERE DATE(date_absence) = ?
        AND status = 'absent'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$today]);
$absences = $stmt->fetchAll();

// Envoyer les alertes
foreach ($absences as $absence) {
    sendAlert($absence['user_id'], $today);
}

function sendAlert($userId, $date) {
    // Implémentez l'envoi d'email
    error_log("Alerte absence envoyée pour l'utilisateur $userId le $date");
}
```

## Monitoring des CRON jobs

### Logs

```bash
# Voir les logs du scheduler
railway logs -s cron -f
```

### Vérifier le statut

```bash
# Lister les processus en cours d'exécution
railway exec -s cron ps aux
```

## Problèmes courants

### 1. CRON ne s'exécute pas

- Vérifier que le service CRON est déployé
- Vérifier les logs: `railway logs -s cron`

### 2. Le scheduler s'arrête

- Ajouter un watchdog pour redémarrer
- Utiliser un service externe (EasyCron)

### 3. Tâche trop longue

- Diviser la tâche en parties plus petites
- Ajouter une pagination

## Alternative: Utiliser un service externe

### EasyCron (Gratuit)

1. Créer un compte sur [EasyCron.com](https://www.easycron.com)
2. Créer une tâche CRON:
   ```
   https://your-app.up.railway.app/?route=cron/send-alerts
   ```
3. Configurer la fréquence

### AWS CloudWatch + Lambda

Pour les projets plus complexes, utiliser Lambda + CloudWatch Events

## Bonnes pratiques

1. **Timeouts**
   - Définir des timeouts pour les CRON jobs
   - Ajouter des logs détaillés

2. **Erreur handling**

   ```php
   try {
       // Code CRON
   } catch (Exception $e) {
       error_log("CRON Error: " . $e->getMessage());
       // Envoyer une alerte au développeur
   }
   ```

3. **Idempotence**
   - Les jobs doivent être sûrs pour exécuter plusieurs fois
   - Utiliser des flags/locks pour éviter les doublons

4. **Monitoring**
   - Logger chaque exécution
   - Monitorer les erreurs
   - Ajouter des alertes

## Exemple complet avec tous les jobs

```php
<?php
// cron/scheduler.php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$jobs = [
    [
        'name' => 'send_absence_alerts',
        'file' => 'send_absence_alerts.php',
        'interval' => 3600, // Toutes les heures
        'lastRun' => 0
    ],
    // Ajouter d'autres jobs ici
];

while (true) {
    $now = time();

    foreach ($jobs as &$job) {
        if ($now - $job['lastRun'] >= $job['interval']) {
            echo "[" . date('Y-m-d H:i:s') . "] Exécution de {$job['name']}\n";

            try {
                require_once __DIR__ . '/' . $job['file'];
                $job['lastRun'] = $now;
                echo "[" . date('Y-m-d H:i:s') . "] {$job['name']} terminé\n";
            } catch (Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Erreur: " . $e->getMessage() . "\n";
            }
        }
    }

    sleep(60);
}
```

## Ressources

- [Documentation Railway](https://docs.railway.app)
- [EasyCron](https://www.easycron.com)
- [PHP CRON Jobs Best Practices](https://www.php.net/manual/en/features.commandline.php)
