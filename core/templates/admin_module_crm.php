<?php
/**
 * Manganese OS - CRM avec Archive Télémétrie intégrée
 */

require_once __DIR__ . "/../move_uploaded_file.php";

$db = get_db_connection();
$app_id = $_GET['app_id'] ?? null;

// 1. RÉCUPÉRATION DIRECTE DE L'APP (On ne passe plus par une liste)
$current_app = null;
if ($app_id) {
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$app_id]);
    $current_app = $stmt->fetch();
}

$slug = $current_app['slug'];

// 2. CHANGEMENT DE STATUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['status'] ?? null;
    if ($new_status && in_array($new_status, ['sent', 'interview', 'rejected', 'accepted'])) {
        $stmt = $db->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $app_id]);
        Logger::info("CRM: Status updated to '$new_status' for " . ($current_app['company_name'] ?? $app_id));
        header("Location: ?key=$key&module=crm&app_id=$app_id");
        exit;
    }
}

// 3. SAUVEGARDE D'UN ÉVÉNEMENT AVEC PIÈCES JOINTES MULTIPLES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    // On convertit le format HTML (T) vers le format SQL (Espace)
    $event_date = str_replace('T', ' ', $_POST['event_date']);
    $type = $_POST['type'] ?? 'note';

    $stmt = $db->prepare("INSERT INTO crm_events (app_id, event_date, type, comment, next_action) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $app_id,
        $event_date,
        $type,
        $_POST['comment'],
        $_POST['next_action']
    ]);
    $event_id = $db->lastInsertId();

    // Traitement des pièces jointes dynamiques
    if (isset($_POST['attachments'])) {
        Logger::debug("CRM Upload Debug", ['post_data' => $_POST, 'files_data' => $_FILES]);

        foreach ($_POST['attachments'] as $index => $attach) {
            $type_attach = $attach['type'];
            
            if ($type_attach === 'url' && !empty($attach['value'])) {
                $stmtAtt = $db->prepare("INSERT INTO crm_events_attached (event_id, link, attached_type, label) VALUES (?, ?, 'url', ?)");
                $stmtAtt->execute([$event_id, $attach['value'], $attach['label'] ?? null]);
            } 
            elseif ($type_attach === 'file' && !empty($_FILES['attachment_files']['name'][$index])) {
                $file_path = handleCrmFileUpload($_FILES['attachment_files'], $index, $slug);
                if ($file_path) {
                    // 1. On vérifie si l'utilisateur a tapé un label
                    $custom_label = !empty($attach['label']) ? $attach['label'] : $_FILES['attachment_files']['name'][$index];
                    
                    // 2. On insère ce label en base de données                    
                    $stmtAtt = $db->prepare("INSERT INTO crm_events_attached (event_id, link, attached_type, label) VALUES (?, ?, 'file', ?)");
                    $stmtAtt->execute([$event_id, $file_path, $custom_label]);
                }
            }
        }
    }
    Logger::info("CRM: New event for " . ($current_app['company_name'] ?? $app_id));
    header("Location: ?key=$key&module=crm&app_id=$app_id");
    exit;
}

// 3. RÉCUPÉRATION DE L'HISTORIQUE
$crm_events = [];
if ($app_id) {
    // 1 SEULE REQUÊTE : On ramène l'événement ET ses pièces jointes
    $stmt = $db->prepare("
        SELECT 
            e.*, 
            a.id AS attachment_id, 
            a.link, 
            a.attached_type, 
            a.label 
        FROM crm_events e
        LEFT JOIN crm_events_attached a ON e.id = a.event_id
        WHERE e.app_id = ?
        ORDER BY e.event_date DESC, a.id ASC
    ");
    $stmt->execute([$app_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. REGROUPEMENT EN PHP
    $grouped_events = [];
    
    foreach ($results as $row) {
        $eventId = $row['id'];
        
        // Si on croise cet événement pour la première fois, on le prépare
        if (!isset($grouped_events[$eventId])) {
            $grouped_events[$eventId] = $row; // Copie toutes les infos de l'event (type, comment, event_date...)
            $grouped_events[$eventId]['attachments'] = []; // Initialise la liste des pièces jointes
        }

        // S'il y a une pièce jointe (LEFT JOIN renvoie NULL s'il n'y en a pas), on l'ajoute
        if (!empty($row['attachment_id'])) {
            $grouped_events[$eventId]['attachments'][] = [
                'link' => $row['link'],
                'attached_type' => $row['attached_type'],
                'label' => $row['label']
            ];
        }
    }
    
    // On réindexe proprement le tableau pour que le HTML le lise facilement
    $crm_events = array_values($grouped_events);
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
    'Email'       => 'fa-solid fa-envelope text-blue-600 bg-blue-50',
    'Téléphone'   => 'fa-solid fa-phone text-emerald-600 bg-emerald-50',
    'LinkedIn'    => 'fa-brands fa-linkedin text-blue-700 bg-blue-50',
    'Entretien 1' => 'fa-solid fa-comments text-purple-600 bg-purple-50',
    'Entretien 2' => 'fa-solid fa-users text-purple-700 bg-purple-50',
    'Assessment'  => 'fa-solid fa-file-code text-orange-600 bg-orange-50',
    'Offre'       => 'fa-solid fa-handshake text-yellow-600 bg-yellow-50',
    'Refus'       => 'fa-solid fa-circle-xmark text-red-600 bg-red-50'
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
        body { background: #f8fafc; color: #1e293b; font-family: ui-sans-serif, system-ui; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="p-6 custom-scroll">

    <div class="max-w-4xl mx-auto" x-data="{ showForm: <?= empty($crm_events) ? 'true' : 'false' ?> }">
        
        <?php if (!$current_app): ?>
            <div class="p-12 text-center border border-red-500/20 bg-red-500/5 rounded-3xl">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl mb-4"></i>
                <p class="font-bold text-slate-900">ID de candidature invalide</p>
                <p class="text-xs text-slate-500 mt-2">Veuillez retourner au dashboard et cliquer sur le bouton CRM d'une candidature valide.</p>
            </div>
        <?php else: ?>
            <div class="max-w-7xl mx-auto p-8" x-data="{ 
                attachments: [{ type: 'url', value: '', label: '' }],
                addNext(index) {
                    if (index === this.attachments.length - 1 && (this.attachments[index].value || this.attachments[index].file)) {
                        this.attachments.push({ type: 'url', value: '', label: '' });
                    }
                }
            }">
            <header class="flex justify-between items-end mb-8">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-blue-600/20 text-blue-500 text-[10px] font-black px-2 py-0.5 rounded border border-blue-500/20 uppercase tracking-widest">CRM Pipeline</span>
                        <?php if (($telemetry_stats['total_visits'] ?? 0) > 0): ?>
                            <span class="bg-emerald-500/10 text-emerald-500 text-[10px] font-black px-2 py-0.5 rounded border border-emerald-500/20 uppercase animate-pulse">● Live Activity</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 tracking-tighter italic"><?= htmlspecialchars($current_app['company_name']) ?></h1>
                    
                    <div class="flex gap-6 mt-4">
                        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-3 px-4">
                            <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Visites</p>
                            <p class="text-xl font-black text-slate-900"><?= $telemetry_stats['total_visits'] ?? 0 ?></p>
                        </div>
                        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-3 px-4">
                            <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Temps total</p>
                            <p class="text-xl font-black text-slate-900"><?= ceil(($telemetry_stats['total_time'] ?? 0) / 60) ?> <span class="text-xs text-slate-600">min</span></p>
                        </div>
                        <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-3 px-4">
                            <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Dernière vue</p>
                            <p class="text-sm font-bold text-blue-400 mt-1">
                                <?= $telemetry_stats['last_seen'] ? date('d.m.Y @ H:i', strtotime($telemetry_stats['last_seen'])) : 'Jamais' ?>
                            </p>
                        </div>
                        <?php if (!empty($current_app['job_url'])):
                                // Extraction du domaine (ex: www.linkedin.com)
                                $host = parse_url($current_app['job_url'], PHP_URL_HOST);
                                // Nettoyage du www.
                                $display_host = str_replace('www.', '', $host);
                            ?>
                            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-3 px-4">
                                <p class="text-[9px] font-black text-slate-500 uppercase mb-1">Annonce originale</p>
                                <a href="<?= htmlspecialchars($current_app['job_url']) ?>" 
                                    target="_blank" 
                                    class="inline-flex items-center gap-1 text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100 hover:text-blue-600 hover:border-blue-200 transition"
                                    title="Voir l'annonce originale">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[8px]"></i>
                                        <?= htmlspecialchars($display_host) ?>
                                </a>
                            </div>                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <button @click="showForm = !showForm" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-black transition-all flex items-center gap-3 shadow-xl shadow-blue-900/20">
                    <i class="fa-solid fa-plus"></i> <span x-text="showForm ? 'ANNULER' : 'LOG INTERACTION'"></span>
                </button>
            </header>

            <!-- STATUS SELECTOR -->
            <div class="mb-8 bg-white border border-slate-200 shadow-sm rounded-2xl p-4">
                <form method="POST" class="flex items-center gap-4">
                    <input type="hidden" name="action" value="update_status">
                    <label class="text-[10px] font-black text-slate-500 uppercase">Statut de la postulation</label>
                    <select name="status" onchange="this.form.submit()" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg p-2 text-slate-900 font-bold outline-none hover:border-blue-500 cursor-pointer">
                        <option value="sent" <?= ($current_app['status'] === 'sent') ? 'selected' : '' ?>>📤 Envoyé</option>
                        <option value="interview" <?= ($current_app['status'] === 'interview') ? 'selected' : '' ?>>🎤 Entretien</option>
                        <option value="accepted" <?= ($current_app['status'] === 'accepted') ? 'selected' : '' ?>>✅ Accepté</option>
                        <option value="rejected" <?= ($current_app['status'] === 'rejected') ? 'selected' : '' ?>>❌ Rejeté</option>
                    </select>
                </form>
            </div>

            <div x-show="showForm" x-transition class="mb-10 bg-white shadow-lg border-slate-200 p-6 rounded-3xl border border-blue-500/20 shadow-2xl">
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-5">
                    <input type="hidden" name="action" value="add_event">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase px-2">Type</label>
                            <select name="type" class="w-full bg-slate-50 border border-slate-200 shadow-sm rounded-xl p-3 text-slate-900 focus:border-blue-500 outline-none">
                            <?php foreach ($type_icons as $type => $icon): ?>
                                <option value="<?= $type ?>"><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase px-2">Date</label>
                        <input type="datetime-local" name="event_date" x-init="$el.value = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16)" class="w-full bg-slate-50 border border-slate-200 shadow-sm rounded-xl p-3 text-slate-900 outline-none">
                    </div>
                    <div class="col-span-2 space-y-1">
                        <label class="text-[10px] font-black text-slate-500 uppercase px-2">Commentaires</label>
                        <textarea name="comment" rows="3" class="w-full bg-slate-50 border border-slate-200 shadow-sm rounded-xl p-3 text-slate-900 outline-none" placeholder="Qu'est-ce qui s'est dit ?"></textarea>
                    </div>
                    <div class="col-span-2 space-y-1">
                        <label class="text-[10px] font-black text-blue-500 uppercase px-2">Prochaine étape</label>
                        <input type="text" name="next_action" class="w-full bg-slate-50 border border-blue-500/20 rounded-xl p-3 text-slate-900 outline-none" placeholder="Relancer dans 3 jours...">
                    </div>

                    <div class="col-span-2 space-y-1">
                        <template x-for="(attach, index) in attachments" :key="index">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-3">
                                <div class="flex gap-2">
                                    <select :name="'attachments['+index+'][type]'" x-model="attach.type" class="text-[10px] font-black uppercase bg-white border rounded-lg px-2">
                                        <option value="url">URL</option>
                                        <option value="file">DOC</option>
                                    </select>
                                    <input type="text" :name="'attachments['+index+'][label]'" placeholder="Label..." class="w-full bg-slate-50 border border-blue-500/20 rounded-xl p-3 text-slate-900 outline-none">
                                </div>
                                <template x-if="attach.type === 'url'">
                                    <input type="url" :name="'attachments['+index+'][value]'" x-model="attach.value" @input="addNext(index)" placeholder="https://..." class="w-full bg-slate-50 border border-blue-500/20 rounded-xl p-3 text-slate-900 outline-none">
                                </template>
                                <template x-if="attach.type === 'file'">
                                    <input type="file" :name="'attachment_files['+index+']'" @change="attach.file = $event.target.value; addNext(index)" class="w-full bg-slate-50 border border-blue-500/20 file:bg-blue-50 file:border-0 file:rounded-lg file:px-3 file:py-1 file:font-black">
                                </template>
                            </div>
                        </template>
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
                        <div class="flex items-center gap-4 bg-slate-900/20 border border-dashed border-slate-200 shadow-sm p-3 rounded-2xl ml-4">
                            <div class="text-blue-500 text-xs w-5 text-center <?= (strtotime($te['started_at']) > strtotime('-10 minutes')) ? 'animate-pulse' : '' ?>">
                                <i class="fa-solid <?= $te['event_type'] === 'download' ? 'fa-file-arrow-down' : 'fa-eye' ?>"></i>
                            </div>
                            <div class="flex-1 text-[11px]">
                                <span class="text-slate-500">Le recruteur a </span>
                                <span class="font-bold text-slate-200">
                                    <?= $te['event_type'] === 'download' ? "téléchargé {$te['element_id']}" : "consulté la section {$te['element_id']}" ?>
                                </span>
                            </div>
                            <div class="text-[9px] font-mono text-slate-700"><?= date('H:i', strtotime($te['started_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (empty($crm_events)): ?>
                <div class="p-16 text-center border-2 border-dashed border-slate-900 rounded-3xl">
                    <i class="fa-solid fa-box-open text-slate-800 text-3xl mb-4"></i>
                    <p class="text-slate-600 font-bold italic tracking-tight">Aucune interaction enregistrée pour <?= htmlspecialchars($current_app['company_name']) ?>.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($crm_events as $event): 
                        // On récupère la classe de l'icône et on sépare la couleur de fond
                        $iconClass = $type_icons[$event['type']] ?? 'fa-solid fa-calendar text-slate-500 bg-slate-50';
                    ?>
                        <div class="flex gap-6 items-start group">
                            
                            <div class="shrink-0 w-14 h-14 <?= strpos($iconClass, 'bg-') !== false ? explode(' ', strstr($iconClass, 'bg-'))[0] : 'bg-slate-50' ?> rounded-2xl flex items-center justify-center border border-slate-100 shadow-sm group-hover:scale-110 transition-transform">
                                <i class="<?= $iconClass ?> text-xl"></i>
                            </div>

                            <div class="flex-1 bg-white border border-slate-200 rounded-3xl p-5 shadow-sm group-hover:border-blue-200 transition-colors">
                                
                                <div class="flex justify-between items-center mb-3">
                                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-tighter">
                                        <?= $event['type'] ?>
                                    </h3>
                                    <span class="text-[10px] font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">
                                        <?= date('d.m.Y @ H:i', strtotime($event['event_date'])) ?>
                                    </span>
                                </div>

                                <div class="text-sm text-slate-600 leading-relaxed">
                                    <?= nl2br(htmlspecialchars($event['comment'])) ?>
                                </div>

                                <?php if ($event['next_action']): ?>
                                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center gap-3">
                                        <div class="flex items-center gap-2 bg-blue-600 text-white text-[9px] font-black px-2 py-1 rounded uppercase tracking-tighter">
                                            <i class="fa-solid fa-star text-[8px]"></i> NEXT STEP
                                        </div>
                                        <span class="text-xs font-bold text-blue-600">
                                            <?= htmlspecialchars($event['next_action']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($event['attachments'])): ?>
                                    <div class="flex flex-wrap gap-3 pt-6 border-t border-slate-50">
                                        <?php foreach ($event['attachments'] as $a): ?>
                                            <a href="<?= $a['attached_type'] === 'file' ? '/'.$a['link'] : $a['link'] ?>" target="_blank" 
                                            class="inline-flex items-center gap-2 px-5 py-2.5 <?= $a['attached_type'] === 'url' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-700' ?> rounded-2xl text-[10px] font-black hover:scale-105 transition transform duration-200">
                                                <i class="fa-solid <?= $a['attached_type'] === 'url' ? 'fa-link' : 'fa-file-pdf' ?>"></i>
                                                <?= htmlspecialchars($a['label'] ?? 'Document') ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>                                
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="mt-16 pt-8 border-t border-slate-900">
            <h2 class="text-xs font-black text-emerald-500 uppercase tracking-[0.3em] ml-2 mb-6 text-center">Historique complet des sessions</h2>
            <div class="grid grid-cols-1 gap-2">
                <?php foreach ($all_sessions as $s): ?>
                    <div class="flex items-center justify-between bg-slate-50 border border-slate-200 p-3 px-6 rounded-2xl hover:border-slate-700 transition group">
                        <div class="flex items-center gap-6">
                            <div class="text-[11px] font-black text-slate-900 w-24"><?= date('d.m.Y', strtotime($s['started_at'])) ?></div>
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