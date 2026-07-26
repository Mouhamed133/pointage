<?php

/**
 * Health Check - À utiliser pour le monitoring sur Railway
 * URL: /health ou ?route=health
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => $_ENV['APP_ENV'] ?? 'production',
];

try {
    // Vérifier la connexion à la base de données
    require_once __DIR__ . '/config/Database.php';
    require_once __DIR__ . '/config/env.php';

    $db = new Database();
    $connection = $db->getConnection();

    // Faire une requête simple pour vérifier la connexion
    $result = $connection->query('SELECT 1');

    $health['database'] = 'connected';
    $health['version'] = PHP_VERSION;
} catch (Exception $e) {
    http_response_code(503);
    $health['status'] = 'error';
    $health['database'] = 'disconnected';
    $health['error'] = $e->getMessage();
}

// Vérifier les répertoires essentiels
$requiredDirs = [
    'storage/logs',
    'storage/exports',
    'storage/qrcodes',
    'public/uploads/justificatifs',
    'public/uploads/photos'
];

$health['directories'] = [];
foreach ($requiredDirs as $dir) {
    $dirPath = __DIR__ . '/../' . $dir;
    $exists = is_dir($dirPath);
    $writable = $exists && is_writable($dirPath);

    $health['directories'][$dir] = [
        'exists' => $exists,
        'writable' => $writable
    ];
}

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
