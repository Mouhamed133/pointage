#!/bin/bash
# Script de configuration pour Railway

echo "Configuration du projet pour Railway..."

# Vérifier si railway CLI est installé
if ! command -v railway &> /dev/null; then
    echo "Railway CLI n'est pas installé. Installez-le avec: npm install -g @railway/cli"
    exit 1
fi

echo "✓ Railway CLI trouvé"

# Initialiser le projet si necessaire
if [ ! -f "railway.json" ]; then
    echo "Initialisation du projet Railway..."
    railway init
fi

echo "✓ Projet Railway configuré"

# Afficher les étapes suivantes
echo ""
echo "Prochaines étapes:"
echo "1. railway add (pour ajouter MySQL)"
echo "2. Configurez les variables d'environnement dans le dashboard Railway"
echo "3. Commitez vos changements: git add . && git commit -m 'Railway setup'"
echo "4. Déployez: git push"
echo ""
echo "Pour plus d'informations, consultez RAILWAY_DEPLOYMENT.md"
