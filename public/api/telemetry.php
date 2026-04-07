<?php
require_once '../../core/config.php';

$app_id = $_POST['app_id'] ?? 0;
$type = $_POST['type'] ?? 'ping';
$data = $_POST['data'] ?? '';
$uid = $_COOKIE['m_uid'] ?? 'guest';

$db = get_db_connection();

// 1. Log de l'événement
$stmt = $db->prepare("INSERT INTO telemetry_events (app_id, visitor_uid, event_type, event_details) VALUES (?, ?, ?, ?)");
$stmt->execute([$app_id, $uid, $type, $data]);

// 2. Notification Telegram sur "Session Start"
if ($type === 'session_start') {
    $company_name = get_company_name($app_id); // Petite fonction helper
    $message = "🚀 *CV Ouvert !*\n🏢 Entreprise : {$company_name}\n👤 ID Visiteur : `{$uid}`\n📍 [Voir le Dashboard](https://manganese.ch/manage/...)";
    
    $url = "https://api.telegram.org/bot".TELEGRAM_TOKEN."/sendMessage?chat_id=".TELEGRAM_CHAT_ID."&text=".urlencode($message)."&parse_mode=Markdown";
    file_get_contents($url);
}