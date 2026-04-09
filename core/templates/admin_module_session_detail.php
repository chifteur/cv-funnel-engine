<?php
/**
 * Module Admin : Détails de Session
 * Paramètres : $key, $id (UUID de la session)
 */
$db = get_db_connection();
$session_uuid = $id; // L'UUID passé en paramètre via le router

// 1. Récupération des infos de session
$stmt = $db->prepare("
    SELECT s.*, a.company_name 
    FROM telemetry_sessions s 
    JOIN applications a ON s.app_id = a.id 
    WHERE s.id = ?
");
$stmt->execute([uuid_to_bin($session_uuid)]);
$session = $stmt->fetch();

if (!$session) {
    echo "Session introuvable.";
    return;
}

// 2. Récupération des événements chronologiques
$stmt = $db->prepare("SELECT * FROM telemetry_events WHERE session_id = ? ORDER BY created_at ASC");
$stmt->execute([uuid_to_bin($session_uuid)]);
$events = $stmt->fetchAll();
?>

<div class="space-y-8">
    <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm flex justify-between items-end">
        <div>
            <h2 class="text-4xl font-black uppercase tracking-tighter text-slate-800">
                Parcours : <span class="text-blue-600"><?= htmlspecialchars($session['company_name']) ?></span>
            </h2>
            <p class="text-slate-400 font-mono text-xs mt-2">
                ID: <?= $session_uuid ?> | IP: <?= $session['ip_address'] ?> | <?= $session['user_agent'] ?>
            </p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-black uppercase text-slate-300 tracking-widest">Temps total</p>
            <p class="text-3xl font-black text-slate-700"><?= gmdate("H:i:s", $session['duration_seconds']) ?></p>
        </div>
    </div>

    <div class="relative pl-8 before:content-[''] before:absolute before:left-0 before:top-0 before:bottom-0 before:w-px before:bg-slate-200">
        <?php foreach ($events as $e): ?>
            <div class="mb-8 relative group">
                <div class="absolute -left-[36px] top-1 w-4 h-4 rounded-full bg-white border-2 border-slate-300 group-hover:border-blue-500 transition"></div>
                
                <div class="flex items-baseline gap-4">
                    <span class="text-[10px] font-mono text-slate-400"><?= date('H:i:s', strtotime($e['created_at'])) ?></span>
                    
                    <div class="flex-1 bg-white p-4 rounded-2xl border border-slate-50 shadow-sm">
                        <div class="flex items-center gap-3 mb-1">
                            <?php 
                                // Mapping des icônes par type
                                $icon = match($e['event_type']) {
                                    'reading_focus' => 'fa-book-open text-purple-500',
                                    'download' => 'fa-file-arrow-down text-red-500',
                                    'copy_text' => 'fa-copy text-blue-500',
                                    'view_section' => 'fa-eye text-green-500',
                                    default => 'fa-circle-dot text-slate-300'
                                };
                            ?>
                            <i class="fa-solid <?= $icon ?> text-xs"></i>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-400"><?= $e['event_type'] ?></span>
                            <?php if ($e['element_id']): ?>
                                <span class="px-2 py-0.5 bg-slate-100 text-[9px] font-bold rounded text-slate-500 uppercase"><?= $e['element_id'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($e['event_data']): ?>
                            <p class="text-sm text-slate-700 italic border-l-2 border-slate-100 pl-3 py-1">
                                "<?= htmlspecialchars($e['event_data']) ?>"
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>