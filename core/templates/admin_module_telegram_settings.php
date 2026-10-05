<?php
/**
 * Configuration et diagnostic des notifications Telegram.
 */

$db = get_db_connection();
$message = '';
$message_type = 'info';

$loadSettings = static function () use ($db): array {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('tg_bot_token', 'tg_chat_id')");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
};
$saveSetting = static function (string $settingKey, string $settingValue) use ($db): void {
    $exists = $db->prepare('SELECT 1 FROM settings WHERE setting_key = ? LIMIT 1');
    $exists->execute([$settingKey]);

    if ($exists->fetchColumn()) {
        $save = $db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
        $save->execute([$settingValue, $settingKey]);
    } else {
        $save = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
        $save->execute([$settingKey, $settingValue]);
    }
};

$settings = $loadSettings();
$chatId = $settings['tg_chat_id'] ?? '';
$tokenConfigured = !empty($settings['tg_bot_token']);

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        if ($action === 'save_telegram_settings') {
            $submittedToken = trim($_POST['tg_bot_token'] ?? '');
            $token = $submittedToken !== '' ? $submittedToken : ($settings['tg_bot_token'] ?? '');
            $chatId = trim($_POST['tg_chat_id'] ?? '');

            $saveSetting('tg_bot_token', $token);
            $saveSetting('tg_chat_id', $chatId);

            $message = 'Configuration Telegram enregistrée.';
            $message_type = 'success';
        } elseif ($action === 'clear_telegram_settings') {
            $saveSetting('tg_bot_token', '');
            $saveSetting('tg_chat_id', '');
            $chatId = '';
            $message = 'Configuration Telegram effacée.';
            $message_type = 'success';
        } elseif ($action === 'test_telegram') {
            $submittedToken = trim($_POST['tg_bot_token'] ?? '');
            $token = $submittedToken !== '' ? $submittedToken : ($settings['tg_bot_token'] ?? '');
            $chatId = trim($_POST['tg_chat_id'] ?? ($settings['tg_chat_id'] ?? ''));

            if ($token === '' || $chatId === '') {
                throw new RuntimeException('Renseigne un token et un chat ID avant de lancer le test.');
            }

            $apiUrl = 'https://api.telegram.org/bot' . $token . '/sendMessage';
            $payload = http_build_query([
                'chat_id' => $chatId,
                'text' => '✅ Message de test Telegram envoyé depuis CV Funnel Engine.'
            ]);
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $payload,
                    'timeout' => 8,
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents($apiUrl, false, $context);
            $result = is_string($response) ? json_decode($response, true) : null;

            if (is_array($result) && ($result['ok'] ?? false) === true) {
                $message = 'Test réussi : le message a été envoyé dans la conversation Telegram.';
                $message_type = 'success';
            } elseif (is_array($result)) {
                $description = $result['description'] ?? 'Telegram a refusé la requête.';
                throw new RuntimeException('Échec du test Telegram : ' . $description);
            } else {
                throw new RuntimeException(
                    'Aucune réponse exploitable de Telegram. Vérifie la connexion HTTPS sortante et que allow_url_fopen est activé sur PHP.'
                );
            }
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }

    $settings = $loadSettings();
    $tokenConfigured = !empty($settings['tg_bot_token']);
    if ($action !== 'test_telegram' && $action !== 'save_telegram_settings') {
        $chatId = $settings['tg_chat_id'] ?? '';
    }
}

$key = $key ?? ($_GET['key'] ?? '');
$escapedKey = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuration Telegram | CV Funnel Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 p-4 text-slate-900 md:p-8">
    <main class="mx-auto max-w-5xl">
        <header class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-700">CV Funnel Engine / Administration</p>
                <h1 class="mt-2 text-3xl font-black">Notifications Telegram</h1>
                <p class="mt-2 text-sm text-slate-600">Configure les identifiants utilisés par cette instance.</p>
            </div>
            <a href="?key=<?= $escapedKey ?>" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-bold hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left mr-2"></i>Dashboard
            </a>
        </header>

        <?php if ($message !== ''): ?>
            <?php
            $messageClasses = $message_type === 'success'
                ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                : ($message_type === 'error' ? 'border-red-300 bg-red-50 text-red-900' : 'border-sky-300 bg-sky-50 text-sky-900');
            ?>
            <div role="status" class="mb-6 rounded-lg border p-4 text-sm font-semibold <?= $messageClasses ?>">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-extrabold">Identifiants du bot</h2>
                        <p class="mt-1 text-sm text-slate-600">Ces valeurs sont enregistrées dans la table <code class="rounded bg-slate-100 px-1">settings</code> de cette base.</p>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold <?= $tokenConfigured && $chatId !== '' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' ?>">
                        <?= $tokenConfigured && $chatId !== '' ? 'Configuration présente' : 'Configuration incomplète' ?>
                    </span>
                </div>

                <form method="POST" action="?key=<?= $escapedKey ?>&amp;module=telegram_settings" class="space-y-5">
                    <div>
                        <label for="tg_bot_token" class="mb-2 block text-sm font-bold">Token BotFather</label>
                        <input id="tg_bot_token" name="tg_bot_token" type="password" autocomplete="new-password" spellcheck="false"
                            placeholder="<?= $tokenConfigured ? 'Token enregistré : laisser vide pour le conserver' : '123456789:AA...' ?>"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <p class="mt-1 text-xs text-slate-500">Le token enregistré n’est jamais réaffiché. Saisis-en un nouveau uniquement pour le remplacer.</p>
                    </div>

                    <div>
                        <label for="tg_chat_id" class="mb-2 block text-sm font-bold">Chat ID</label>
                        <input id="tg_chat_id" name="tg_chat_id" type="text" inputmode="numeric" value="<?= htmlspecialchars($chatId, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="123456789 ou -100..." required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-sm focus:border-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <p class="mt-1 text-xs text-slate-500">Pour un groupe, le chat ID est souvent négatif et commence par <code>-100</code>.</p>
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
                        <button type="submit" name="action" value="save_telegram_settings" class="rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-800">
                            <i class="fa-solid fa-floppy-disk mr-2"></i>Enregistrer
                        </button>
                        <button type="submit" name="action" value="test_telegram" class="rounded-lg border border-emerald-700 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-50">
                            <i class="fa-solid fa-paper-plane mr-2"></i>Envoyer un test
                        </button>
                    </div>
                </form>

                <form method="POST" action="?key=<?= $escapedKey ?>&amp;module=telegram_settings" class="mt-4 border-t border-slate-100 pt-4"
                    onsubmit="return confirm('Effacer le token et le chat ID enregistrés pour cette instance ?')">
                    <input type="hidden" name="action" value="clear_telegram_settings">
                    <button type="submit" class="text-sm font-semibold text-red-700 hover:text-red-900">
                        <i class="fa-solid fa-trash-can mr-2"></i>Effacer la configuration
                    </button>
                </form>
            </div>

            <aside class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-extrabold">Activation dans Telegram</h2>
                <ol class="mt-4 list-inside list-decimal space-y-3 text-sm leading-6 text-slate-700">
                    <li>Dans Telegram, ouvre <strong>@BotFather</strong>, envoie <code>/newbot</code> et suis les instructions. Copie le token communiqué.</li>
                    <li>Ouvre la conversation avec ton nouveau bot et envoie <code>/start</code>. Telegram ne laisse pas un bot démarrer une conversation privée à ta place.</li>
                    <li>Pour obtenir ton chat ID, envoie d’abord un message au bot, puis ouvre <code class="break-all">https://api.telegram.org/botTON_TOKEN/getUpdates</code>. Repère <code>message.chat.id</code> dans le JSON.</li>
                    <li>Colle le token et le chat ID dans le formulaire, enregistre, puis utilise <strong>Envoyer un test</strong>.</li>
                </ol>
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                    <strong>À savoir</strong>
                    <p class="mt-1">Le token permet de contrôler le bot : garde-le secret. Le test envoie un vrai message. Les alertes automatiques sont ensuite envoyées lors de la création d’une nouvelle session de visite sur un CV.</p>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>