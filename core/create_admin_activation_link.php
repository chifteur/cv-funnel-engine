<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tools.php';
require_once __DIR__ . '/admin_recovery.php';

$db = get_db_connection();
if (get_admin_access_key_hash($db) !== null) {
    fwrite(STDERR, "Cette instance est déjà activée. Utilise la récupération par email pour remplacer une clé oubliée.\n");
    exit(1);
}

$db->beginTransaction();
$invalidateTokens = $db->prepare(
    "UPDATE admin_recovery_tokens SET used_at = NOW()
     WHERE purpose = 'activation' AND used_at IS NULL"
);
$invalidateTokens->execute();
$token = create_admin_recovery_token($db, 'activation', 2880);
$db->commit();

$activationUrl = rtrim(SITE_URL, '/') . '/manage?activate=' . rawurlencode($token);
fwrite(STDOUT, "Lien d’activation à transmettre une seule fois au propriétaire (valable 48 heures) :\n");
fwrite(STDOUT, $activationUrl . PHP_EOL);
