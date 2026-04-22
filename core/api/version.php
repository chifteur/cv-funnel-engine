<?php
/**
 * API Observabilité - Retourne la version de l'application et de la base de données
 * Exécuté via le routeur (Environnement déjà chargé)
 */

// Sécurité : On vide la mémoire tampon pour éviter que des espaces vides 
// ou du code HTML provenant d'ailleurs ne corrompent le format JSON
if (ob_get_length()) ob_clean();

header('Content-Type: application/json');

try {
    // La fonction get_db_connection() est déjà disponible grâce à index.php
    $db = get_db_connection();
    
    // Récupération de la version DB
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'db_version'");
    $result = $stmt->fetch();
    $db_version = $result ? $result['setting_value'] : '0.0.0';

    // Vérification de la version Core
    // Si APP_VERSION n'est pas encore défini par index.php, on tente de le lire
    $versionFile = __DIR__ . '/../../core/version.php';
    if (!defined('APP_VERSION') && file_exists($versionFile)) {
        require_once $versionFile;
    }

    echo json_encode([
        'status' => 'online',
        'core_version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
        'db_version' => $db_version
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erreur DB : ' . $e->getMessage()
    ]);
}