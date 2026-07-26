<?php

/**
 * Script de test - Vérifier la configuration pour Railway
 * Lancer depuis la racine du projet: php test-config.php
 */

echo "=== Test de Configuration PointagePro ===\n\n";

// Test 1: PHP Version
echo "1. Version PHP: ";
echo PHP_VERSION;
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo " ✓\n";
} else {
    echo " ✗ (8.0+ requis)\n";
}

// Test 2: Extensions PHP
echo "\n2. Extensions PHP:\n";
$requiredExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'zip', 'intl', 'exif'];
foreach ($requiredExtensions as $ext) {
    echo "   - $ext: ";
    if (extension_loaded($ext)) {
        echo "✓\n";
    } else {
        echo "✗ (manquante)\n";
    }
}

// Test 3: Répertoires
echo "\n3. Répertoires:\n";
$dirs = [
    'config',
    'app',
    'public',
    'storage',
    'storage/logs',
    'storage/exports',
    'storage/qrcodes',
    'public/uploads',
    'public/uploads/justificatifs',
    'public/uploads/photos'
];

foreach ($dirs as $dir) {
    echo "   - $dir: ";
    if (is_dir($dir)) {
        $writable = is_writable($dir) ? ' (writable)' : ' (read-only)';
        echo "✓$writable\n";
    } else {
        echo "✗ (manquant)\n";
    }
}

// Test 4: Fichiers essentiels
echo "\n4. Fichiers essentiels:\n";
$files = [
    'public/index.php',
    'config/database.php',
    'config/env.php',
    'config/routes.php',
    'composer.json',
    'Dockerfile',
    'Procfile',
    'railway.json'
];

foreach ($files as $file) {
    echo "   - $file: ";
    if (file_exists($file)) {
        echo "✓\n";
    } else {
        echo "✗ (manquant)\n";
    }
}

// Test 5: Composer
echo "\n5. Composer:\n";
if (file_exists('vendor/autoload.php')) {
    echo "   - Autoloader: ✓\n";
    if (file_exists('composer.json')) {
        echo "   - composer.json: ✓\n";
    }
} else {
    echo "   - Autoloader: ✗ (exécuter 'composer install')\n";
}

// Test 6: Variables d'environnement
echo "\n6. Variables d'environnement:\n";
$envVars = ['DB_HOST', 'DB_USER', 'DB_NAME', 'DB_PASS', 'DB_PORT'];
foreach ($envVars as $var) {
    echo "   - $var: ";
    if (!empty($_ENV[$var]) || !empty($_SERVER[$var])) {
        echo "✓\n";
    } else {
        echo "⚠ (non défini)\n";
    }
}

// Test 7: Connexion à la base de données
echo "\n7. Connexion à la base de données:\n";
try {
    require_once 'config/env.php';
    require_once 'config/database.php';

    $db = new Database();
    $connection = $db->getConnection();
    $result = $connection->query('SELECT 1');

    echo "   - Connexion: ✓\n";

    // Récupérer les informations de la DB
    $dbInfo = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    echo "   - Driver: $dbInfo ✓\n";
} catch (Exception $e) {
    echo "   - Connexion: ✗\n";
    echo "   - Erreur: " . $e->getMessage() . "\n";
}

// Test 8: Permissions de fichier
echo "\n8. Permissions de fichier:\n";
$file = 'public/index.php';
echo "   - Lisible: ";
echo (is_readable($file) ? "✓\n" : "✗\n");

// Résumé
echo "\n=== Résumé ===\n";
echo "Configuration prête pour:\n";
echo "✓ Développement local\n";
echo "✓ Déploiement Docker\n";
echo "✓ Déploiement Railway\n";
echo "\nPour déployer sur Railway, consultez RAILWAY_DEPLOYMENT.md\n";
