<?php
/**
 * Manganese API - Reception de télémétrie JS
 */
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/tools.php';

// On démarre la session pour récupérer l'ID de session actif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On récupère les données JSON envoyées par le Beacon
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// On utilise l'ID envoyé par le JS (sid) s'il existe, sinon on prend la session
$session_id = $data['sid'] ?? $_SESSION['current_telemetry_id'] ?? null;

if ($data && $session_id) {
    $db = get_db_connection();    

    // 1. Log de l'événement précis
    log_event(
        $db, 
        $session_id, 
        $data['type'], 
        $data['el_id'] ?? '', 
        $data['data'] ?? ''
    );

    // 2. Mise à jour de la durée de vie de la session (en secondes)
    $stmt = $db->prepare("
        UPDATE telemetry_sessions 
        SET duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
            last_activity = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([uuid_to_bin($session_id)]);

    // Pas de réponse nécessaire pour un Beacon, on sort proprement
    http_response_code(204); // No Content
} else {
    http_response_code(400); // Bad Request
}
exit;