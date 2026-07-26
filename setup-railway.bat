@echo off
REM Script de configuration pour Railway (Windows)

echo Configuration du projet pour Railway...

REM Vérifier si railway CLI est installe
where railway >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Railway CLI n'est pas installe. Installez-le avec: npm install -g @railway/cli
    exit /b 1
)

echo ✓ Railway CLI trouve

REM Verifier si railway.json existe
if not exist "railway.json" (
    echo Initialisation du projet Railway...
    call railway init
)

echo ✓ Projet Railway configure

REM Afficher les etapes suivantes
echo.
echo Prochaines etapes:
echo 1. railway add (pour ajouter MySQL)
echo 2. Configurez les variables d'environnement dans le dashboard Railway
echo 3. Commitez vos changements: git add . et git commit -m "Railway setup"
echo 4. Deployez: git push
echo.
echo Pour plus d'informations, consultez RAILWAY_DEPLOYMENT.md
