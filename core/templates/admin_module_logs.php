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
    // On récupère les lignes et on les inverse
    $lines = array_reverse(file($logFile));
}

// On initialise le compteur pour les numéros de ligne
$currentLineNum = count($lines);
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
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0a0a0a; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #222; }
    </style>
</head>
<body class="bg-[#050505] text-slate-400 p-4 custom-scroll">

    <div class="max-w-full mx-auto" x-data="{ confirming: false }">
        
        <div class="flex justify-between items-center mb-4 bg-gray-900/50 p-2 px-4 rounded-lg border border-gray-800">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-terminal text-blue-500 text-xs"></i>
                <h1 class="text-sm font-bold text-white uppercase tracking-tighter">System Logs</h1>
                <span class="text-[10px] text-slate-600 font-mono">/app.log (<?= count($lines) ?>)</span>
            </div>

            <div class="flex items-center gap-3">
                <button x-show="!confirming" @click="confirming = true" class="text-[10px] font-bold text-red-500/50 hover:text-red-500 transition">
                    <i class="fa-solid fa-trash-can"></i> Effacer les logs
                </button>
                <div x-show="confirming" class="flex items-center gap-2">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="text-[10px] font-bold text-red-500">[CONFIRM_DELETE]</button>
                    </form>
                    <button @click="confirming = false" class="text-[10px] font-bold text-slate-500">[CANCEL]</button>
                </div>
            </div>
        </div>

        <div class="bg-black/20 border border-gray-900 rounded-lg overflow-hidden">
            <?php if (empty($lines)): ?>
                <div class="p-20 text-center">
                    <i class="fa-solid fa-ghost text-4xl text-gray-800 mb-4 block"></i>
                    <p class="text-gray-600 font-bold italic uppercase tracking-widest">Le journal est vide</p>
                </div>
            <?php else: ?>
                <div class="font-mono text-[11px] leading-tight">
                    <?php foreach ($lines as $line): 
                        // 1. On nettoie les espaces avant/après (trim)
                        $cleanLine = trim($line); 
                        if (empty($cleanLine)) continue;
                    ?>
                        <div class="flex hover:bg-white/5 border-b border-gray-900/50 group">
                            <div class="w-12 shrink-0 text-right pr-3 py-0.5 text-slate-700 border-r border-gray-900 select-none group-hover:text-blue-900">
                                <?= $currentLineNum-- ?>
                            </div>
                            <div class="px-3 py-0.5 break-all whitespace-pre-wrap"><?php 
                                    $htmlLine = htmlspecialchars($cleanLine);
                                    $htmlLine = str_replace('[ERROR]', '<span class="text-red-600 font-bold">[ERROR]</span>', $htmlLine);
                                    $htmlLine = str_replace('[DEBUG]', '<span class="text-blue-500">[DEBUG]</span>', $htmlLine);
                                    $htmlLine = str_replace('[INFO]', '<span class="text-emerald-500">[INFO]</span>', $htmlLine);
                                    echo $htmlLine;
                            ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>