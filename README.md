# PointagePro - Système de Gestion des Absences

Application PHP pour gérer les absences et présences des étudiants.

## Installation Locale

### Prérequis

- Docker & Docker Compose
- Ou: PHP 8.0+, MySQL 5.7+, Composer

### Avec Docker (Recommandé)

1. **Cloner le projet**

```bash
git clone <url-du-repo>
cd pointagepro
```

2. **Créer le fichier .env**

```bash
cp .env.example .env
```

3. **Lancer les conteneurs**

```bash
docker-compose up -d
```

4. **Accéder à l'application**

- Application: http://localhost:8000
- PhpMyAdmin: http://localhost:8080
- Identifiants DB: root/password (ou voir .env)

### Installation Manuelle

1. **Prérequis**

```bash
php --version  # >= 8.0
mysql --version  # >= 5.7
composer --version
```

2. **Installer les dépendances**

```bash
composer install
```

3. **Créer la base de données**

```bash
mysql -u root -p < database.sql
```

4. **Configurer le fichier .env**

```bash
cp .env.example .env
# Éditer .env avec vos paramètres
```

5. **Lancer le serveur**

```bash
php -S localhost:8000 -t public/
```

## Déploiement sur Railway

Voir [RAILWAY_DEPLOYMENT.md](./RAILWAY_DEPLOYMENT.md) pour les instructions complètes.

### Résumé rapide

```bash
railway login
railway init
railway add  # Ajouter MySQL
git push     # Déployer
```

## Structure du Projet

```
pointagepro/
├── app/
│   ├── controllers/       # Contrôleurs
│   ├── models/           # Modèles de données
│   └── views/            # Templates PHP
├── config/
│   ├── database.php      # Configuration DB
│   ├── routes.php        # Routes
│   └── constants.php     # Constantes
├── public/
│   ├── index.php         # Point d'entrée
│   ├── assets/           # CSS, JS, images
│   └── uploads/          # Fichiers uploadés
├── storage/
│   ├── logs/             # Fichiers journaux
│   ├── exports/          # Exports
│   └── qrcodes/          # Codes QR générés
├── cron/
│   └── send_absence_alerts.php  # Tâches planifiées
└── Dockerfile            # Configuration Docker
```

## Endpoints Principaux

- `GET /` - Tableau de bord
- `GET /?route=login` - Connexion
- `GET /?route=etudiant/dashboard` - Tableau de bord étudiant
- `GET /health` - Health check (monitoring)

## Utilisation

### Connexion

- Aller à `/?route=login`
- Utiliser vos identifiants

### Dashboard

- Après connexion, accédez au tableau de bord
- Consultez les absences
- Générez les rapports

### Rapports

- Générez des rapports d'absence
- Exportez en PDF/Excel

## Variables d'Environnement

```
DB_HOST=localhost        # Hôte MySQL
DB_PORT=3306            # Port MySQL
DB_NAME=pointagepro     # Nom de la DB
DB_USER=root            # Utilisateur
DB_PASS=                # Mot de passe
APP_ENV=development     # Mode application
APP_DEBUG=true          # Mode debug
```

## API de Santé

Pour monitorer l'application:

```bash
curl http://localhost:8000/?route=health
```

Réponse:

```json
{
  "status": "ok",
  "timestamp": "2024-01-15 10:30:45",
  "database": "connected",
  "version": "8.x.x"
}
```

## Dépendances

- **mpdf/mpdf** ^8.3 - Génération PDF
- **phpmailer/phpmailer** ^7.1 - Envoi d'emails
- **phpoffice/phpspreadsheet** ^5.7 - Export Excel

## Logs

Les logs sont sauvegardés dans `storage/logs/`

```bash
# Voir les logs locaux
tail -f storage/logs/*.log

# Sur Railway
railway logs
```

## Troubleshooting

### Erreur de connexion à la base de données

1. Vérifier que MySQL est en cours d'exécution
2. Vérifier les identifiants dans .env
3. Vérifier la permission des répertoires storage/

### Ports occupés

- Si le port 8000 est occupé: `php -S localhost:8001 -t public/`
- Ou configurer dans docker-compose.yml

### Fichiers manquants

```bash
# Vérifier les permissions
chmod -R 755 storage/
chmod -R 755 public/uploads/
```

## Support

Pour toute question ou problème:

- Consultez [RAILWAY_DEPLOYMENT.md](./RAILWAY_DEPLOYMENT.md)
- Vérifiez les logs: `storage/logs/`
- Utilisez le health check: `/?route=health`

## License

À définir

## Auteur

Projet développé pour la gestion des absences.
