<?php
/**
 * Manganese OS - Module Détail des logs
 * Variables injectées : $key, $module
 */

$logFile = __DIR__ . '/../../logs/app.log';

$lines = [];

if (file_exists($logFile)) {
    $lines = array_reverse(file($logFile));
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Manganese OS | Logs details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-green-400 p-4">

<h1 class="text-xl mb-4">Logs Viewer</h1>

<div class="space-y-1 text-sm font-mono">
<?php foreach ($lines as $line): ?>
    <div class="border-b border-gray-700 py-1">
        <?= htmlspecialchars($line) ?>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>