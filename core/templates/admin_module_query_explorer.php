<?php
/**
 * Manganese OS - SQL Query Explorer (Générique)
 */

$db = get_db_connection();
$query_param = $_GET['sql_key'] ?? null;
$session_target = $_GET['session_id'] ?? null;

$results = [];
$title = "SQL Explorer";
$params = [];

// On définit ici les requêtes autorisées pour éviter d'envoyer du SQL brut en URL (Sécurité)
if ($query_param === 'orphan_events' && $session_target) {
    $title = "Événements de la Session Orpheline";
    $params = [
        'session_id' => $session_target
    ];
    // On retire 'event_value' qui causait l'erreur
    $stmt = $db->prepare("
        SELECT 
            event_type, 
            element_id, 
            created_at 
        FROM telemetry_events 
        WHERE session_id = uuid_to_bin(?) 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$session_target]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 p-8 font-sans text-slate-900">

    <div class="max-w-4xl mx-auto">
        
        <header class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black tracking-tighter uppercase italic text-slate-900"><?= $title ?></h1>
                
                <?php if (!empty($params)): ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <?php foreach ($params as $key => $val): ?>
                            <div class="flex items-center bg-white border border-slate-200 rounded-lg px-3 py-1 shadow-sm">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mr-2 italic">
                                    <?= str_replace('_', ' ', $key) ?>
                                </span>
                                <span class="text-[10px] font-mono text-blue-600 font-bold">
                                    <?= htmlspecialchars($val) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest mt-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <?= count($results) ?> lignes retournées
                </p>
            </div>

            <button onclick="window.close()" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-[10px] font-black transition uppercase tracking-widest shadow-lg shadow-slate-200">
                Fermer l'explorateur
            </button>
        </header>

        <div class="bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden">
            <?php if (empty($results)): ?>
                <div class="p-20 text-center">
                    <i class="fa-solid fa-database text-slate-100 text-5xl mb-4"></i>
                    <p class="text-slate-400 font-bold italic text-sm">Aucun événement enregistré pour cette session.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-900 text-white">
                                <?php 
                                // On génère les colonnes dynamiquement à partir des clés du premier résultat
                                $columns = array_keys($results[0]);
                                foreach ($columns as $col): ?>
                                    <th class="p-4 px-6 text-[10px] font-black uppercase tracking-widest border-r border-slate-800 last:border-0">
                                        <?= str_replace('_', ' ', $col) ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($results as $row): ?>
                                <tr class="hover:bg-blue-50/50 transition-colors group">
                                    <?php foreach ($row as $key => $value): ?>
                                        <td class="p-4 px-6 text-xs text-slate-600 border-r border-slate-50 last:border-0">
                                            <?php if ($key === 'event_type'): ?>
                                                <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded text-[9px] font-black uppercase border border-slate-200">
                                                    <?= $value ?>
                                                </span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($value ?? '—') ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <p class="mt-8 text-center text-[9px] text-slate-300 font-black uppercase tracking-[0.2em]">
            <i class="fa-solid fa-microchip mr-2"></i> Manganese OS Engine
        </p>
    </div>

</body>
</html>