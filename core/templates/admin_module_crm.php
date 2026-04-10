<?php
/**
 * Manganese OS - CRM avec Archive Télémétrie intégrée
 */

$db = get_db_connection();
$app_id = $_GET['app_id'] ?? null;

// 1. RÉCUPÉRATION DIRECTE DE L'APP (On ne passe plus par une liste)
$current_app = null;
if ($app_id) {
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$app_id]);
    $current_app = $stmt->fetch();
}

// 2. SAUVEGARDE D'UN ÉVÉNEMENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $stmt = $db->prepare("INSERT INTO crm_events (app_id, event_date, type, comment, next_action) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $app_id,
        $_POST['event_date'] ?: date('Y-m-d H:i:s'),
        $_POST['type'],
        $_POST['comment'],
        $_POST['next_action']
    ]);
    Logger::info("CRM: New event for " . ($current_app['company_name'] ?? $app_id));
    header("Location: ?key=$key&module=crm&app_id=$app_id");
    exit;
}

// 3. RÉCUPÉRATION DE L'HISTORIQUE
$crm_events = [];
if ($app_id) {
    $stmt = $db->prepare("SELECT * FROM crm_events WHERE app_id = ? ORDER BY event_date DESC");
    $stmt->execute([$app_id]);
    $crm_events = $stmt->fetchAll();
}

// --- STATS TÉLÉMÉTRIE POUR CETTE APP ---
$telemetry_stats = null;
$last_telemetry_events = [];
$all_sessions = [];

if ($app_id) {
    // 1. Stats globales (Nombre de visites, temps total)
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_visits,
            SUM(duration_seconds) as total_time,
            MAX(started_at) as last_seen
        FROM telemetry_sessions 
        WHERE app_id = ?
    ");
    $stmt->execute([$app_id]);
    $telemetry_stats = $stmt->fetch();

    // 2. Les 3 dernières actions "fortes" (Téléchargements ou focus)
    $stmt = $db->prepare("
        SELECT e.*, s.started_at 
        FROM telemetry_events e
        JOIN telemetry_sessions s ON e.session_id = s.id
        WHERE s.app_id = ? AND e.event_type IN ('download', 'view_section', 'copy_text')
        ORDER BY e.id DESC LIMIT 3
    ");
    $stmt->execute([$app_id]);
    $last_telemetry_events = $stmt->fetchAll();

    // TOUTES LES SESSIONS (La nouvelle liste que tu voulais)
    $stmt = $db->prepare("SELECT bin_to_uuid(s.id) as s_id_text, s.* FROM telemetry_sessions s WHERE app_id = ? ORDER BY started_at DESC");
    $stmt->execute([$app_id]);
    $all_sessions = $stmt->fetchAll();
}

$type_icons = [
    'Email' => 'fa-envelope text-blue-400',
    'Téléphone' => 'fa-phone text-green-400',
    'LinkedIn' => 'fa-brands fa-linkedin text-blue-600',
    'Entretien 1' => 'fa-comments text-purple-400',
    'Entretien 2' => 'fa-users text-purple-500',
    'Assessment' => 'fa-file-code text-orange-400',
    'Offre' => 'fa-handshake text-yellow-400',
    'Refus' => 'fa-circle-xmark text-red-500'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CRM | <?= htmlspecialchars($current_app['company_name'] ?? 'Manganese') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #050505; color: #cbd5e1; font-family: ui-sans-serif, system-ui; }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="p-6 custom-scroll">

    <div class="max-w-4xl mx-auto" x-data="{ showForm: <?= empty($crm_events) ? 'true' : 'false' ?> }">
        
        <?php if (!$current_app): ?>
            <div class="p-12 text-center border border-red-500/20 bg-red-500/5 rounded-3xl">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl mb-4"></i>
                <p class="font-bold text-white">ID de candidature invalide</p>
                <p class="text-xs text-slate-500 mt-2">Veuillez retourner au dashboard et cliquer sur le bouton CRM d'une candidature valide.</p>
            </div>
        <?php else: ?>
            <header class="flex justify-between items-end mb-8">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-blue-600/20 text-blue-500 text-[10px] font-black px-2 py-0.5 rounded border border-blue-500/20 uppercase tracking-widest">CRM Pipeline</span>
                        <?php if (($telemetry_stats['total_visits'] ?? 0) > 0): ?>
                            <span class="bg-emerald-500/10 text-emerald-500 text-[10px] font-black px-2 py-0.5 rounded border border-emerald-500/20 uppercase animate-pulse">● Live Activity</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-4xl font-black text-white tracking-tighter italic"><?= htmlspecialchars($current_app['company_name']) ?></h1>
                    
                    <div class="flex gap-6 mt-4">
                        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-3 px-4">
                            <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Visites</p>
                            <p class="text-xl font-black text-white"><?= $telemetry_stats['total_visits'] ?? 0 ?></p>
                        </div>
                        <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-3 px-4">
                            <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Temps total</p>
                            <p class="text-xl font-black text-white"><?= ceil(($telemetry_stats['total_time'] ?? 0) / 60) ?> <span class="text-xs text-slate-600">min</span></p>
                            </div>
                            <div class="bg-slate-900/50 border border-slate-800 rounded-2xl p-3 px-4">
                                <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Dernière vue</p>
                                <p class="text-sm font-bold text-blue-400 mt-1">
                                    <?= $telemetry_stats['last_seen'] ? date('d.m.Y @ H:i', strtotime($telemetry_stats['last_seen'])) : 'Jamais' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                <button @click="showForm = !showForm" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-3 shadow-xl shadow-blue-900/20">
                    <i class="fa-solid fa-plus"></i> <span x-text="showForm ? 'ANNULER' : 'LOG INTERACTION'"></span>
                </button>
            </header>

            <div x-show="showForm" x-transition class="mb-10 bg-slate-900/80 p-6 rounded-3xl border border-blue-500/20 shadow-2xl">
                    <form method="POST" class="grid grid-cols-2 gap-5">
                    <input type="hidden" name="action" value="add_event">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase px-2">Type</label>
                            <select name="type" class="w-full bg-black border border-slate-800 rounded-xl p-3 text-white focus:border-blue-500 outline-none">
                            <?php foreach ($type_icons as $type => $icon): ?>
                                <option value="<?= $type ?>"><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase px-2">Date</label>
                        <input type="datetime-local" name="event_date" value="<?= date('Y-m-d\TH:i') ?>" class="w-full bg-black border border-slate-800 rounded-xl p-3 text-white outline-none">
                    </div>
                    <div class="col-span-2 space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase px-2">Commentaires</label>
                        <textarea name="comment" rows="3" class="w-full bg-black border border-slate-800 rounded-xl p-3 text-white outline-none" placeholder="Qu'est-ce qui s'est dit ?"></textarea>
                    </div>
                    <div class="col-span-2 space-y-1">
                        <label class="text-[10px] font-black text-blue-500 uppercase px-2">Prochaine étape</label>
                        <input type="text" name="next_action" class="w-full bg-black border border-blue-500/20 rounded-xl p-3 text-white outline-none" placeholder="Relancer dans 3 jours...">
                    </div>
                    <div class="col-span-2 flex justify-end gap-3">
                        <button type="submit" class="bg-white text-black px-8 py-3 rounded-xl font-black hover:bg-blue-400 transition">ENREGISTRER</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($last_telemetry_events)): ?>
                        <div class="mb-8 space-y-2 opacity-60 hover:opacity-100 transition-opacity">
                            <p class="text-[10px] font-black text-slate-600 uppercase tracking-[0.2em] ml-4 mb-3">Activités récentes détectées</p>
                    <?php foreach ($last_telemetry_events as $te): ?>
                        <div class="flex items-center gap-4 bg-slate-900/20 border border-dashed border-slate-800 p-3 rounded-2xl ml-4">
                            <div class="text-blue-500 text-xs w-5 text-center <?= (strtotime($te['started_at']) > strtotime('-10 minutes')) ? 'animate-pulse' : '' ?>">
                                <i class="fa-solid <?= $te['event_type'] === 'download' ? 'fa-file-arrow-down' : 'fa-eye' ?>"></i>
                            </div>
                            <div class="flex-1 text-[11px]">
                                <span class="text-slate-400">Le recruteur a </span>
                                <span class="font-bold text-slate-200">
                                    <?= $te['event_type'] === 'download' ? "téléchargé {$te['element_id']}" : "consulté la section {$te['element_id']}" ?>
                                </span>
                            </div>
                            <div class="text-[9px] font-mono text-slate-700"><?= date('H:i', strtotime($te['started_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (empty($events)): ?>
                <div class="p-16 text-center border-2 border-dashed border-slate-900 rounded-3xl">
                    <i class="fa-solid fa-box-open text-slate-800 text-3xl mb-4"></i>
                    <p class="text-slate-600 font-bold italic tracking-tight">Aucune interaction enregistrée pour <?= htmlspecialchars($current_app['company_name']) ?>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>                        
                    <div class="bg-slate-900/40 border border-slate-800/60 p-5 rounded-3xl hover:border-slate-700 transition group">
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-black rounded-xl flex items-center justify-center border border-slate-800">
                                <i class="fa-solid <?= $type_icons[$event['type']] ?? 'fa-calendar' ?>"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-white text-sm"><?= $event['type'] ?></h3>
                                <p class="text-[10px] text-slate-600 font-mono"><?= date('d.m.Y @ H:i', strtotime($event['event_date'])) ?></p>
                            </div>
                            </div>
                        </div>
                        <div class="text-sm text-slate-400 mb-4 px-2">
                            <?= nl2br(htmlspecialchars($event['comment'])) ?>
                        </div>
                    <?php if ($event['next_action']): ?>
                        <div class="bg-blue-500/5 border border-blue-500/10 p-3 rounded-2xl flex items-center gap-3">
                            <i class="fa-solid fa-arrow-right text-blue-600 text-[10px]"></i>
                            <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">NEXT : <?= htmlspecialchars($event['next_action']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="mt-16 pt-8 border-t border-slate-900">
            <h2 class="text-xs font-black text-emerald-500 uppercase tracking-[0.3em] ml-2 mb-6 text-center">Historique complet des sessions</h2>
            <div class="grid grid-cols-1 gap-2">
                <?php foreach ($all_sessions as $s): ?>
                    <div class="flex items-center justify-between bg-black border border-slate-900 p-3 px-6 rounded-2xl hover:border-slate-700 transition group">
                        <div class="flex items-center gap-6">
                            <div class="text-[11px] font-black text-white w-24"><?= date('d.m.Y', strtotime($s['started_at'])) ?></div>
                            <div class="text-[10px] font-mono text-slate-600 italic"><?= date('H:i:s', strtotime($s['started_at'])) ?></div>
                            <div class="text-[10px] font-bold text-emerald-500 bg-emerald-500/5 px-3 py-1 rounded-full"><?= $s['duration_seconds'] ?>s</div>
                        </div>
                        
                        <a href="?key=<?= $key ?>&module=session_detail&id=<?= $s['s_id_text'] ?>" 
                           target="_blank"
                           class="text-[10px] font-black text-slate-500 hover:text-blue-400 uppercase tracking-widest transition flex items-center gap-2">
                            Détails <i class="fa-solid fa-up-right-from-square text-[9px]"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</body>
</html>