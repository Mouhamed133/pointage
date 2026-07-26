# Guide de Déploiement sur Railway

## Prérequis

- Un compte [Railway.app](https://railway.app)
- [Railway CLI](https://docs.railway.app/cli/install) installé sur votre machine
- Git configuré

## Étapes de déploiement

### 1. Se connecter à Railway

```bash
railway login
```

### 2. Initialiser le projet Railway

```bash
railway init
```

### 3. Ajouter une base de données MySQL

```bash
railway add
```

Sélectionnez `MySQL` dans la liste des services.

### 4. Configurer les variables d'environnement

Dans le tableau de bord Railway:

1. Allez à votre projet
2. Cliquez sur l'onglet "Variables"
3. Ajoutez les variables suivantes:

```
DB_HOST=<generé automatiquement>
DB_PORT=<generé automatiquement>
DB_USER=<configuré dans le service MySQL>
DB_PASS=<configuré dans le service MySQL>
DB_NAME=pointagepro
```

Les variables du service MySQL sont générées automatiquement et linkées.

### 5. Déployer

```bash
git push
```

Ou si vous utilisez Railway CLI:

```bash
railway up
```

### 6. Voir les logs

```bash
railway logs
```

## Configuration automatique

Le projet contient:

- **railway.json** - Configuration Railway
- **Procfile** - Commande de démarrage
- **Dockerfile** - Image Docker optimisée
- **.railwayignore** - Fichiers à ignorer

## Volumes et stockage persistant

Pour les uploads et fichiers générés (photos, justificatifs, QR codes):

1. Dans Railway, configurez un volume pour `/app/storage`
2. Montez-le sur le chemin `/app/storage`

## Vérification du déploiement

1. Après le déploiement, allez sur l'URL fournie par Railway
2. Testez la connexion à la base de données
3. Consultez les logs en cas d'erreur:
   ```bash
   railway logs
   ```

## Troubleshooting

### Erreur de connexion à la base de données

- Vérifiez que le service MySQL est démarré dans Railway
- Vérifiez les variables d'environnement
- Consultez les logs: `railway logs`

### Port occupé

Le projet utilise le port 8000 par défaut. Railway configure automatiquement le routing.

### Fichiers manquants après déploiement

- Vérifiez que `.railwayignore` n'exclut pas les fichiers essentiels
- Assurez-vous que `storage/` a les permissions correctes

## Variables d'environnement disponibles

- `DB_HOST` - Hôte MySQL
- `DB_PORT` - Port MySQL (défaut: 3306)
- `DB_USER` - Utilisateur MySQL
- `DB_PASS` - Mot de passe MySQL
- `DB_NAME` - Nom de la base de données
- `PORT` - Port HTTP (fourni automatiquement par Railway)

## Support

Pour plus d'informations: [Documentation Railway](https://docs.railway.app)
