<?php
/**
 * Manganese OS - Module Détail de Session
 * Variables injectées : $id (UUID de la session), $key, $module
 */

$db = get_db_connection();

// 1. RÉCUPÉRATION DE LA SESSION ACTUELLE
$stmt = $db->prepare("
    SELECT s.*, a.company_name, a.slug, bin_to_uuid(s.id) as s_uuid
    FROM telemetry_sessions s
    JOIN applications a ON s.app_id = a.id
    WHERE s.id = ?
");
$stmt->execute([uuid_to_bin($id)]);
$session = $stmt->fetch();

if (!$session) {
    echo "<div class='p-10 text-center font-bold text-slate-400 uppercase'>Session introuvable.</div>";
    return;
}

// 2. RÉCUPÉRATION DES ÉVÉNEMENTS
$stmt = $db->prepare("SELECT * FROM telemetry_events WHERE session_id = ? ORDER BY created_at ASC");
$stmt->execute([uuid_to_bin($id)]);
$events = $stmt->fetchAll();

// 3. RÉCUPÉRATION DE L'HISTORIQUE DU VISITEUR (Sessions passées et futures)
$stmt = $db->prepare("
    SELECT bin_to_uuid(s.id) as other_uuid, s.started_at, a.slug 
    FROM telemetry_sessions s
    JOIN applications a ON s.app_id = a.id
    WHERE s.visitor_uuid = ? AND s.id != ? 
    ORDER BY s.started_at ASC
");
$stmt->execute([$session['visitor_uuid'], uuid_to_bin($id)]);
$history = $stmt->fetchAll();

// 4. CALCULS DE SCORE ET FOCUS
$score = 0;
$focus_stats = []; // Pour les gauges
foreach ($events as $e) {
    // Calcul du score
    $score += match($e['event_type']) {
        'download' => 5,
        'reading_focus' => 3,
        'copy_text' => 2,
        'view_section' => 0.5,
        default => 0.1
    };

    // Comptage pour les sections
    if ($e['event_type'] === 'reading_focus' && $e['element_id']) {
        if (!isset($focus_stats[$e['element_id']])) $focus_stats[$e['element_id']] = 0;
        $focus_stats[$e['element_id']]++;
    }
}
$total_focus = array_sum($focus_stats) ?: 1; // Éviter division par zéro

// On crée un nouveau tableau pour stocker les événements groupés
$grouped_events = [];
$current_group = null;

foreach ($events as $event) {
    // On définit ce qui rend deux événements "identiques" pour le groupement
    // Ici : même type, même élément et même donnée (ex: heartbeat + keep_alive)
    if ($current_group && 
        $current_group['event_type'] === $event['event_type'] && 
        $current_group['element_id'] === $event['element_id'] && 
        $current_group['event_data'] === $event['event_data']) {
        
        // C'est le même événement à la suite : on incrémente le compteur
        $current_group['count']++;
        $current_group['end_time'] = $event['created_at'];
    } else {
        // C'est un nouvel événement ou le premier : on enregistre le précédent s'il existe
        if ($current_group) {
            $grouped_events[] = $current_group;
        }
        
        // On initialise le nouveau groupe
        $event['count'] = 1;
        $event['start_time'] = $event['created_at'];
        $event['end_time'] = $event['created_at'];
        $current_group = $event;
    }
}
// On n'oublie pas d'ajouter le dernier groupe après la boucle
if ($current_group) {
    $grouped_events[] = $current_group;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manganese OS - Session Intelligence</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .timeline-line::before {
            content: '';
            position: absolute;
            left: 31px;
            top: 20px;
            bottom: 20px;
            width: 2px;
            background: #e2e8f0;
        }
        .custom-scroll::-webkit-scrollbar { width: 4px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="p-4 md:p-8"> 
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm flex items-center gap-5">
                <div class="w-14 h-14 bg-red-600 text-white rounded-2xl flex items-center justify-center text-xl shadow-lg shadow-red-100">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tighter text-slate-800"><?= htmlspecialchars($session['company_name']) ?></h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] font-black px-2 py-0.5 <?= $score > 15 ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600' ?> rounded-full italic">
                            <?= $score > 15 ? 'Engagement Brûlant' : 'Intérêt Modéré' ?>
                        </span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Score: <?= round($score) ?> / 50</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Attention captée</p>
                <p class="text-2xl font-black text-slate-800"><?= gmdate("i:s", $session['duration_seconds']) ?> <span class="text-xs text-slate-300 font-normal italic">min</span></p>
                <div class="w-full bg-slate-50 h-1 mt-2 rounded-full overflow-hidden border">
                    <div class="bg-green-500 h-full" style="width: <?= min(($session['duration_seconds']/600)*100, 100) ?>%"></div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Appareil</p>
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center border text-slate-400 shrink-0">
                        <?php 
                            $ua = $session['user_agent']; 
                            $icon = "fa-solid fa-laptop"; // Défaut
                            if (strpos($ua, 'Mobi') !== false) $icon = "fa-solid fa-mobile-screen-button";
                            if (strpos($ua, 'Android') !== false) $icon = "fa-brands fa-android";
                            if (strpos($ua, 'iPhone') !== false || strpos($ua, 'Macintosh') !== false) $icon = "fa-brands fa-apple";
                        ?>
                        <i class="<?= $icon ?> text-slate-400" title="<?= htmlspecialchars($session['user_agent']) ?>"></i>                        
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-bold text-slate-700 truncate"><?= explode(' ', $session['user_agent'])[0] ?></p>
                        <p class="text-[9px] font-mono text-slate-400 truncate tracking-tighter italic"><?= $session['browser_lang'] ?>-SYS</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between px-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-500">Flux d'activité</h2>
                    <span class="text-[10px] text-slate-300 font-mono">ID: <?= substr($id, 0, 18) ?>...</span>
                </div>

                <div class="relative timeline-line space-y-6">
                    <?php foreach ($grouped_events as $e): ?>
                        <div class="relative pl-16 group">
                            <?php 
                                $config = match($e['event_type']) {
                                    'view_section'  => ['icon' => 'fa-eye', 'color' => 'bg-blue-500', 'bg' => 'bg-white'],
                                    'reading_focus' => ['icon' => 'fa-book-open', 'color' => 'bg-purple-500', 'bg' => 'bg-purple-50/50 border-purple-100 border-l-purple-500'],
                                    'download'      => ['icon' => 'fa-file-pdf', 'color' => 'bg-red-500', 'bg' => 'bg-white border-l-red-100 border-l-red-500'],
                                    'heartbeat'     => ['icon' => 'fa-pulse', 'color' => 'bg-slate-300', 'bg' => 'bg-slate-50 opacity-60'],
                                    default         => ['icon' => 'fa-circle', 'color' => 'bg-slate-400', 'bg' => 'bg-white']
                                };
                            ?>
                            
                            <div class="absolute left-6 top-1 w-4 h-4 rounded-full <?= $config['color'] ?> border-4 border-white shadow-sm z-10"></div>
                            
                            <div class="<?= $config['bg'] ?> p-4 rounded-2xl border border-slate-100 shadow-sm border-l-4 transition hover:shadow-md">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <span class="text-[9px] font-black <?= str_replace('bg-', 'text-', $config['color']) ?> uppercase tracking-widest">
                                                <?= $e['event_type'] ?>
                                            </span>
                                            
                                            <?php if ($e['count'] > 1): ?>
                                                <span class="ml-2 px-2 py-0.5 bg-slate-900 text-white text-[10px] font-black rounded-full">
                                                    <?= $e['count'] ?>x
                                                </span>
                                            <?php endif; ?>

                                            <?php if($e['element_id']): ?>
                                                <span class="ml-2 px-1.5 py-0.5 bg-slate-200 text-[8px] font-black rounded text-slate-600 uppercase">#<?= $e['element_id'] ?></span>
                                            <?php endif; ?>
                                            
                                            <p class="text-sm font-bold text-slate-700 mt-1">
                                                <?= htmlspecialchars($e['event_data'] ?: 'Interaction active') ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <span class="text-[10px] font-mono font-bold text-slate-400">
                                            <?php if ($e['count'] > 1): ?>
                                                <?= date('H:i', strtotime($e['start_time'])) ?> → <?= date('H:i', strtotime($e['end_time'])) ?>
                                            <?php else: ?>
                                                <?= date('H:i:s', strtotime($e['start_time'])) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="bg-slate-900 rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden">
                    <div class="relative z-10 space-y-8">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-blue-500/20 flex items-center justify-center border border-blue-500/30">
                                <i class="fa-solid fa-user-check text-blue-400 text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-lg font-black italic tracking-tight leading-tight">Visiteur<br><?= count($history) > 0 ? 'récurrent' : 'nouveau' ?></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest"><?= count($history) + 1 ?> session(s)</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-slate-800/50">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-location-dot text-blue-400 mt-1"></i>
                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Localisation Estimée</p>
                                    <p class="text-xs font-bold text-blue-100">Suisse (CH)</p>
                                    <p class="text-[9px] font-mono text-slate-500 mt-1">IP: <?= $session['ip_address'] ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-slate-800/50">
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left"></i> Parcours chronologique
                            </p>
                            <div class="space-y-2 max-h-72 overflow-y-auto custom-scroll pr-2">
                                <?php 
                                $cur_date = $session['started_at'];
                                $displayed = false;

                                foreach ($history as $h): 
                                    // Insertion de la session active au bon moment
                                    if (!$displayed && $h['started_at'] > $cur_date): ?>
                                        <div class="p-3 bg-blue-500/20 rounded-2xl border border-blue-500/30 flex justify-between items-center">
                                            <div class="text-[10px]">
                                                <p class="font-black text-blue-300"><?= date('d.m.Y à H:i', strtotime($cur_date)) ?></p>
                                                <p class="text-[8px] text-blue-400 uppercase font-black tracking-tighter">Session Actuelle / <?= htmlspecialchars($session['slug']) ?></p>
                                            </div>
                                            <i class="fa-solid fa-eye text-blue-400 text-xs"></i>
                                        </div>
                                        <?php $displayed = true; ?>
                                    <?php endif; ?>

                                    <a href="?key=<?= $key ?>&module=session_detail&id=<?= $h['other_uuid'] ?>" target="_blank" 
                                    class="flex items-center justify-between p-3 bg-slate-800/30 hover:bg-slate-800 rounded-2xl border border-slate-800 transition group">
                                        <div class="text-[10px]">
                                            <p class="font-bold text-slate-400 group-hover:text-white"><?= date('d.m.Y à H:i', strtotime($h['started_at'])) ?></p>
                                            
                                            <p class="text-[8px] text-slate-600 uppercase flex items-center gap-1">
                                                Visite
                                                <?php if ($h['slug'] !== $session['slug']): ?>
                                                    <span class="px-1.5 py-0.5 bg-orange-500/20 text-orange-400 rounded-md font-black border border-orange-500/20">
                                                        / <?= htmlspecialchars($h['slug']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="opacity-50 italic">/ même cv</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-600 group-hover:text-blue-400"></i>
                                    </a>
                                <?php endforeach; 

                                // Si l'active est la plus récente
                                if (!$displayed): ?>
                                    <div class="p-3 bg-blue-500/20 rounded-2xl border border-blue-500/30 flex justify-between items-center">
                                        <div class="text-[10px]">
                                            <p class="font-black text-blue-300"><?= date('d.m.Y à H:i', strtotime($cur_date)) ?></p>
                                            <p class="text-[8px] text-blue-400 uppercase font-black tracking-tighter">Session Actuelle / <?= htmlspecialchars($session['slug']) ?></p>
                                        </div>
                                        <i class="fa-solid fa-eye text-blue-400 text-xs"></i>
                                    </div>
                                <?php endif; ?>
                            </div>                            
                        </div>                        
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-8 border-b pb-4">Focus comportemental</h4>
                    <div class="space-y-6">
                        <?php foreach ($focus_stats as $section => $count): ?>
                            <div class="space-y-2">
                                <div class="flex justify-between items-end">
                                    <span class="text-[11px] font-black text-slate-700 uppercase"><?= $section ?></span>
                                    <span class="text-[10px] font-black text-blue-600"><?= round(($count / $total_focus) * 100) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-50 h-2 rounded-full overflow-hidden border">
                                    <div class="bg-blue-500 h-full rounded-full" style="width: <?= ($count / $total_focus) * 100 ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if(empty($focus_stats)): ?>
                            <p class="text-[10px] text-center text-slate-300 italic uppercase">Aucune lecture focus détectée</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>