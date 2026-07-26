# Configuration Railway - Checklist

## Fichiers créés pour Railway

✓ `railway.json` - Configuration principale Railway
✓ `Procfile` - Commande de démarrage
✓ `Dockerfile` - Image Docker optimisée (FrankenPHP 8.4)
✓ `docker-compose.yml` - Configuration locale avec MySQL
✓ `.railwayignore` - Fichiers à exclure du déploiement
✓ `.env.example` - Exemple de variables d'environnement
✓ `.gitignore` - Fichiers à ignorer dans Git
✓ `README.md` - Documentation générale
✓ `RAILWAY_DEPLOYMENT.md` - Guide de déploiement complet
✓ `railway.config.json` - Configuration de monitoring

## Fichiers modifiés

✓ `Dockerfile` - Optimisé pour Railway (FrankenPHP)
✓ `config/database.php` - Support des variables d'environnement
✓ `public/index.php` - Chargement des variables d'environnement
✓ `config/env.php` - Nouveau fichier d'initialisation

## Fichiers ajoutés (utilitaires)

✓ `app/controllers/HealthController.php` - Endpoint /health
✓ `config/env.php` - Gestion des variables d'environnement
✓ `test-config.php` - Script de test de configuration
✓ `setup-railway.sh` - Script setup (Linux/Mac)
✓ `setup-railway.bat` - Script setup (Windows)

## Étapes de déploiement

### 1. Préparation local

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Tester localement avec Docker
docker-compose up -d

# Accéder à http://localhost:8000
```

### 2. Initialiser Railway

```bash
# Installer Railway CLI
npm install -g @railway/cli

# Se connecter
railway login

# Initialiser le projet
railway init
```

### 3. Ajouter une base de données MySQL

```bash
railway add
# Sélectionner MySQL dans le menu
```

### 4. Configurer les variables d'environnement

Dans le tableau de bord Railway:

1. Allez à votre projet
2. Onglet "Variables"
3. Ajoutez (les variables MySQL sont générées automatiquement):
   - `DB_HOST` - Récupéré du service MySQL
   - `DB_PORT` - Récupéré du service MySQL (3306)
   - `DB_USER` - Configuré dans le service MySQL
   - `DB_PASS` - Configuré dans le service MySQL
   - `DB_NAME` - pointagepro

### 5. Déployer

```bash
# Commitez les changements
git add .
git commit -m "Railway deployment configuration"
git push
```

Ou avec Railway CLI:

```bash
railway up
```

## Vérification après déploiement

1. **Accédez à votre application**
   - URL fournie par Railway

2. **Vérifiez la santé de l'application**

   ```bash
   curl https://your-app.up.railway.app/health
   ```

3. **Consultez les logs**

   ```bash
   railway logs -f
   ```

4. **Testez la connexion DB**
   - Essayez de vous connecter à l'application
   - Les logs doivent montrer une connexion réussie

## Variables d'environnement disponibles

| Variable  | Description             | Défaut      |
| --------- | ----------------------- | ----------- |
| `DB_HOST` | Hôte MySQL              | localhost   |
| `DB_PORT` | Port MySQL              | 3306        |
| `DB_USER` | Utilisateur MySQL       | root        |
| `DB_PASS` | Mot de passe MySQL      | (vide)      |
| `DB_NAME` | Nom de la base          | pointagepro |
| `PORT`    | Port HTTP (automatique) | 8000        |
| `APP_ENV` | Environnement           | production  |

## Troubleshooting

### Erreur: "Cannot find module"

- Vérifier que `composer install` a été exécuté
- Vérifier que vendor/ n'est pas dans .railwayignore

### Erreur de connexion DB

```bash
# Vérifier les logs
railway logs

# Vérifier les variables d'environnement
railway variables
```

### Port occupé

- Railway assigne automatiquement le port via `$PORT`
- Le Procfile utilise ce port

### Problèmes de permissions

```bash
# S'assurer que les répertoires sont créés
storage/logs
storage/exports
storage/qrcodes
public/uploads
```

## Support et documentation

- [Documentation Railway](https://docs.railway.app)
- [Guide complet RAILWAY_DEPLOYMENT.md](./RAILWAY_DEPLOYMENT.md)
- [Health check endpoint](/?route=health)

## Prochaines étapes

1. ✓ Configuration locale testée
2. ✓ Fichiers Railway créés
3. ✓ Documentation complète
4. → Déployer sur Railway (voir étapes ci-dessus)
5. → Configurer le domaine personnalisé
6. → Mettre en place des backups automatiques
7. → Configurer le monitoring et alertes
