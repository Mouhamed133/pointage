<?php

/**
 * Fichier d'initialisation des variables d'environnement
 * Charge les variables depuis .env ou les variables de système
 */

// Charger le fichier .env s'il existe
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos($line, '#') === 0) {
            continue;
        }

        // Parser les variables d'environnement
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '\'"');

            // Définir la variable d'environnement
            if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// S'assurer que les variables essentielles sont définies
$required = ['DB_HOST', 'DB_USER', 'DB_NAME'];
$missing = [];

foreach ($required as $var) {
    if (empty($_ENV[$var]) && empty($_SERVER[$var])) {
        $missing[] = $var;
    }
}

if (!empty($missing)) {
    error_log("Variables d'environnement manquantes: " . implode(', ', $missing));
}
