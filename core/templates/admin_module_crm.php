<?php
/**
 * Manganese OS - Module CRM Standalone
 */

$db = get_db_connection();
$selected_app_id = $_GET['app_id'] ?? null;

// --- 1. SAUVEGARDE (Idem précédent) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $stmt = $db->prepare("INSERT INTO crm_events (app_id, event_date, type, comment, next_action) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['app_id'],
        $_POST['event_date'] ?: date('Y-m-d H:i:s'),
        $_POST['type'],
        $_POST['comment'],
        $_POST['next_action']
    ]);
    header("Location: ?key=$key&module=crm&app_id=" . $_POST['app_id']);
    exit;
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$apps = $db->query("SELECT id, company_name, job_title FROM applications ORDER BY company_name ASC")->fetchAll();
$events = [];
$current_app = null;

if ($selected_app_id) {
    $stmt = $db->prepare("SELECT * FROM crm_events WHERE app_id = ? ORDER BY event_date DESC");
    $stmt->execute([$selected_app_id]);
    $events = $stmt->fetchAll();
    foreach ($apps as $a) if ($a['id'] == $selected_app_id) $current_app = $a;
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
    <title>CRM | <?= $current_app ? htmlspecialchars($current_app['company_name']) : 'Manganese OS' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: #0f172a; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#050505] text-slate-300 font-sans p-6 custom-scroll">

    <div class="max-w-6xl mx-auto" x-data="{ showForm: false }">
        
        <?php if (!$current_app): ?>
            <div class="text-center py-20 bg-slate-900 rounded-3xl border border-slate-800">
                <i class="fa-solid fa-circle-exclamation text-4xl text-slate-700 mb-4"></i>
                <p class="text-slate-500 font-bold uppercase tracking-widest">Candidature introuvable</p>
            </div>
        <?php else: ?>

            <div class="flex justify-between items-end mb-8">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-blue-600/20 text-blue-500 text-[10px] font-black px-2 py-1 rounded uppercase tracking-widest">CRM Pipeline</span>
                        <span class="text-slate-600 text-[10px] font-mono">ID: <?= $selected_app_id ?></span>
                    </div>
                    <h1 class="text-4xl font-black text-white tracking-tighter italic">
                        <?= htmlspecialchars($current_app['company_name']) ?>
                    </h1>
                    <p class="text-slate-500 font-bold text-lg"><?= htmlspecialchars($current_app['job_title']) ?></p>
                </div>
                
                <div class="flex gap-3">
                    <button @click="showForm = !showForm" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-3 shadow-xl shadow-blue-900/20">
                        <i class="fa-solid fa-plus"></i> NOUVEL ÉVÉNEMENT
                    </button>
                </div>
            </div>

            <div x-show="showForm" x-transition.scale.origin.top class="mb-8 bg-slate-900 p-8 rounded-3xl border border-blue-500/30 shadow-2xl">
                <form method="POST" class="grid grid-cols-2 gap-6">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="app_id" value="<?= $selected_app_id ?>">
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-2">Type d'interaction</label>
                        <select name="type" class="w-full bg-black border border-slate-800 rounded-xl p-4 text-white focus:border-blue-500 outline-none transition">
                            <?php foreach ($type_icons as $type => $icon): ?>
                                <option value="<?= $type ?>"><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-2">Date de l'événement</label>
                        <input type="datetime-local" name="event_date" value="<?= date('Y-m-d\TH:i') ?>" class="w-full bg-black border border-slate-800 rounded-xl p-4 text-white outline-none focus:border-blue-500 transition">
                    </div>

                    <div class="col-span-2 space-y-2">
                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-2">Compte-rendu</label>
                        <textarea name="comment" rows="4" placeholder="Points clés de la discussion, feedback reçu..." class="w-full bg-black border border-slate-800 rounded-xl p-4 text-white outline-none focus:border-blue-500 transition"></textarea>
                    </div>

                    <div class="col-span-2 space-y-2">
                        <label class="text-[10px] font-black text-blue-500 uppercase tracking-widest ml-2">Next Step</label>
                        <input type="text" name="next_action" placeholder="Ce qu'il reste à faire..." class="w-full bg-black border border-blue-500/20 rounded-xl p-4 text-white outline-none focus:border-blue-500 transition">
                    </div>

                    <div class="col-span-2 flex justify-end gap-4 mt-4">
                        <button type="button" @click="showForm = false" class="text-slate-500 font-bold px-4">Annuler</button>
                        <button type="submit" class="bg-white text-black px-12 py-4 rounded-2xl font-black hover:bg-blue-400 transition">SAUVEGARDER</button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <?php if (empty($events)): ?>
                    <div class="p-12 text-center border-2 border-dashed border-slate-800 rounded-3xl">
                        <p class="text-slate-600 font-bold italic">Aucun événement enregistré pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <div class="bg-slate-900/50 border border-slate-800 p-6 rounded-3xl flex gap-6 group hover:border-slate-700 transition">
                            <div class="shrink-0">
                                <div class="w-14 h-14 bg-black rounded-2xl flex items-center justify-center border border-slate-800">
                                    <i class="fa-solid <?= $type_icons[$event['type']] ?? 'fa-calendar-day' ?> text-xl"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="text-lg font-black text-white"><?= $event['type'] ?></h3>
                                    <span class="text-xs font-mono text-slate-600"><?= date('d.m.Y H:i', strtotime($event['event_date'])) ?></span>
                                </div>
                                <div class="text-slate-400 leading-relaxed text-sm mb-4">
                                    <?= nl2br(htmlspecialchars($event['comment'])) ?>
                                </div>
                                <?php if ($event['next_action']): ?>
                                    <div class="inline-flex items-center gap-3 bg-blue-500/5 border border-blue-500/10 px-4 py-2 rounded-full">
                                        <i class="fa-solid fa-bullseye text-blue-500 text-[10px]"></i>
                                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Prochaine action : <?= htmlspecialchars($event['next_action']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>