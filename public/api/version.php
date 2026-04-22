<?php
/**
 * API Observabilité - Project Manganese
 */
require_once __DIR__ . '/../config.php';
$versionFile = __DIR__ . '/../version.php';
if (file_exists($versionFile)) require_once $versionFile;
require_once __DIR__ . '/../tools.php'; 

// Sécurité basique
$key = $_GET['key'] ?? '';
if ($key !== ADMIN_ACCESS_KEY) {
    http_response_code(403);
    die(json_encode(['error' => 'Accès refusé']));
}

header('Content-Type: application/json');

try {
    $db = get_db_connection();
    // On extrait la version DB si la table existe, sinon fallback
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
    $result = $stmt->fetch();
    $db_version = $result ? $result['setting_value'] : '0.0.0';

    echo json_encode([
        'status' => 'online',
        'core_version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
        'db_version' => $db_version
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}