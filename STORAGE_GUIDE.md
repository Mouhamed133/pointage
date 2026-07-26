# Gestion du Stockage Persistant sur Railway

## Vue d'ensemble

L'application utilise plusieurs répertoires pour stocker des données:

- `storage/logs/` - Fichiers journaux
- `storage/exports/` - Fichiers exportés (PDF, Excel)
- `storage/qrcodes/` - Codes QR générés
- `public/uploads/` - Uploads utilisateurs

## Configuration sur Railway

### Option 1: Utiliser les Volumes Railway (Recommandé)

1. **Dans le tableau de bord Railway**
   - Allez à votre projet → Services
   - Sélectionnez votre service PHP
   - Onglet "Volumes"
   - Créer un nouveau volume

2. **Créer des volumes pour chaque répertoire**

   ```
   Volume 1: /app/storage → storage
   Volume 2: /app/public/uploads → uploads
   ```

3. **Configuration persistante**
   Les données restent même après redéploiement

### Option 2: Stockage temporaire (Défaut)

- Les fichiers dans `/tmp` sont supprimés après chaque déploiement
- Non recommandé pour l'application

## Configuration locale (Docker Compose)

Le fichier `docker-compose.yml` inclut déjà les volumes:

```yaml
volumes:
  - ./storage:/app/storage # Données persistantes
  - ./public/uploads:/app/uploads # Uploads persistants
```

Pour tester:

```bash
docker-compose up -d
# Les fichiers créés restent dans ./storage
```

## Bonnes pratiques

### 1. Permissions de répertoire

```bash
# S'assurer que les répertoires ont les bonnes permissions
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

### 2. Nettoyage des logs

```php
// Dans un CRON job (cron/send_absence_alerts.php)
$logDir = __DIR__ . '/../storage/logs/';
$files = glob($logDir . '*.log');

foreach ($files as $file) {
    // Supprimer les fichiers de plus de 30 jours
    if (time() - filemtime($file) > 30 * 24 * 60 * 60) {
        unlink($file);
    }
}
```

### 3. Archivage des exports

```bash
# Archiver les exports mensuels
tar -czf storage/exports/archive_$(date +%Y%m).tar.gz storage/exports/*.pdf
```

## Limitations sur Railway

1. **Espace disque limité**
   - Le stockage par défaut est limité à la taille du dyno
   - Utilisez les volumes Railway pour l'extension

2. **Pas de persistance sans volumes**
   - Les fichiers temporaires sont supprimés
   - Utilisez les volumes pour le stockage permanent

3. **Nettoyage automatique**
   - Implémentez un CRON pour nettoyer les anciens fichiers
   - Archivez les données volumineuses

## Monitoring du stockage

### Vérifier l'utilisation

```bash
# Depuis SSH dans le conteneur
df -h
du -sh storage/
du -sh public/uploads/
```

### Script de monitoring

```php
$stats = [
    'storage' => [
        'size' => disk_usage_recursive('storage/'),
        'files' => count_files('storage/')
    ],
    'uploads' => [
        'size' => disk_usage_recursive('public/uploads/'),
        'files' => count_files('public/uploads/')
    ]
];

function disk_usage_recursive($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $file) {
        if (is_file($file)) {
            $size += filesize($file);
        } elseif (is_dir($file)) {
            $size += disk_usage_recursive($file);
        }
    }
    return $size;
}

function count_files($dir) {
    $count = 0;
    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $file) {
        if (is_file($file)) {
            $count++;
        } elseif (is_dir($file)) {
            $count += count_files($file);
        }
    }
    return $count;
}
```

## Stratégies de sauvegarde

### 1. Backups automatiques de la DB

```bash
# Dans un CRON Railway (configuration à faire)
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > backup_$(date +%Y%m%d).sql
```

### 2. Export des données critiques

```php
// Exporter régulièrement les données importantes
// De public/uploads/ vers un service externe (S3, etc.)
```

### 3. Archivage S3 (Optionnel)

```php
// Utiliser AWS SDK pour archiver les fichiers volumineux
$s3Client = new Aws\S3\S3Client([...]);
$s3Client->putObject([
    'Bucket' => 'my-bucket',
    'Key' => 'exports/' . basename($file),
    'Body' => fopen($file, 'r')
]);
```

## Troubleshooting

### Erreur: "Disk space exceeded"

1. Vérifier l'utilisation: `du -sh storage/`
2. Nettoyer les anciens fichiers
3. Augmenter les volumes Railway
4. Archiver vers S3

### Fichiers non persistants après déploiement

1. Vérifier que les volumes sont configurés
2. Vérifier les chemins d'accès
3. Consulter les logs: `railway logs`

### Permissions refusées

```bash
chmod -R 755 storage/
chmod -R 755 public/uploads/
chown -R www-data:www-data storage/
```

## Ressources

- [Documentation Railway - Volumes](https://docs.railway.app/deploy/volumes)
- [Docker Compose - Volumes](https://docs.docker.com/compose/compose-file/compose-file-v3/#volumes)
- [AWS S3 - PHP SDK](https://docs.aws.amazon.com/sdk-for-php/)
