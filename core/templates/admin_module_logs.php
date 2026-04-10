<?php
/**
 * Manganese OS - Module Détail des logs
 * Variables injectées : $key, $module
 */

$logFile = __DIR__ . '/../../logs/app.log';

// --- LOGIQUE DE SUPPRESSION ---
if (isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    if (file_exists($logFile)) {
        // On vide le fichier au lieu de le supprimer pour garder les permissions
        file_put_contents($logFile, "");
        // Petit message de succès pour le log système
        Logger::info("Log file cleared by administrator");
    }
    // Redirection pour éviter le renvoi du formulaire au refresh
    header("Location: ?key=$key&module=$module");
    exit;
}

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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'JetBrains Mono', monospace; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #111827; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-950 text-slate-300 p-6 custom-scroll">

    <div class="max-w-6xl mx-auto" x-data="{ confirming: false }">
        
        <div class="flex justify-between items-center mb-8 bg-gray-900 p-4 rounded-2xl border border-gray-800 shadow-xl">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-green-500/10 text-green-400 rounded-xl flex items-center justify-center border border-green-500/20">
                    <i class="fa-solid fa-terminal"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight">System Logs</h1>
                    <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest">app.log • <?= count($lines) ?> entrées</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    x-show="!confirming" 
                    @click="confirming = true"
                    class="bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white px-4 py-2 rounded-xl border border-red-500/20 transition-all flex items-center gap-2 font-bold text-sm"
                >
                    <i class="fa-solid fa-trash-can"></i> Effacer les logs
                </button>

                <div x-show="confirming" x-transition class="flex items-center gap-2">
                    <span class="text-xs font-bold text-red-400 mr-2 uppercase italic">Es-tu sûr ?</span>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow-lg shadow-red-900/20">
                            OUI
                        </button>
                    </form>
                    <button @click="confirming = false" class="bg-gray-800 text-slate-400 px-4 py-2 rounded-xl font-bold text-sm">
                        NON
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-black/40 border border-gray-800 rounded-3xl overflow-hidden backdrop-blur-sm">
            <?php if (empty($lines)): ?>
                <div class="p-20 text-center">
                    <i class="fa-solid fa-ghost text-4xl text-gray-800 mb-4 block"></i>
                    <p class="text-gray-600 font-bold italic uppercase tracking-widest">Le journal est vide</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-900">
                    <?php foreach ($lines as $line): ?>
                        <div class="p-3 hover:bg-white/5 transition group flex gap-4 items-start">
                            <span class="text-gray-700 font-bold text-[10px] pt-1 select-none"><?= sprintf('%03d', --$line_count_total ?? count($lines)) ?></span>
                            <div class="flex-1 break-all whitespace-pre-wrap leading-relaxed">
                                <?php 
                                    // Coloration basique des tags [ERROR] / [DEBUG]
                                    $line = htmlspecialchars($line);
                                    $line = str_replace('[ERROR]', '<span class="text-red-500 font-bold">[ERROR]</span>', $line);
                                    $line = str_replace('[DEBUG]', '<span class="text-blue-400 font-bold">[DEBUG]</span>', $line);
                                    $line = str_replace('[INFO]', '<span class="text-green-400 font-bold">[INFO]</span>', $line);
                                    echo $line;
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>