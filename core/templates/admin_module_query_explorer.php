<?php
/**
 * Manganese OS - SQL Query Explorer (Générique)
 */

$db = get_db_connection();
$query_param = $_GET['sql_key'] ?? null;
$session_target = $_GET['session_id'] ?? null;

$results = [];
$title = "SQL Explorer";

// On définit ici les requêtes autorisées pour éviter d'envoyer du SQL brut en URL (Sécurité)
if ($query_param === 'orphan_events' && $session_target) {
    $title = "Événements de la Session Orpheline";
    $stmt = $db->prepare("
        SELECT 
            event_type, 
            element_id, 
            event_value, 
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
<body class="bg-slate-50 p-8 font-sans">

    <div class="max-w-6xl mx-auto">
        
        <header class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tighter uppercase italic"><?= $title ?></h1>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1">
                    <?= count($results) ?> lignes retournées
                </p>
            </div>
            <button onclick="window.close()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-xl text-xs font-black transition">
                FERMER L'EXPLORATEUR
            </button>
        </header>

        <div class="bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden">
            <?php if (empty($results)): ?>
                <div class="p-20 text-center">
                    <i class="fa-solid fa-database text-slate-200 text-4xl mb-4"></i>
                    <p class="text-slate-400 font-bold italic">Aucune donnée trouvée pour cette requête.</p>
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
                                        <td class="p-4 px-6 text-sm text-slate-600 border-r border-slate-50 last:border-0">
                                            <?php if ($key === 'event_type'): ?>
                                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-[10px] font-black uppercase border border-slate-200">
                                                    <?= $value ?>
                                                </span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($value ?? 'NULL') ?>
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

        <p class="mt-6 text-center text-[10px] text-slate-400 font-bold uppercase tracking-widest">
            <i class="fa-solid fa-shield-halved mr-2"></i> Mode Lecture Seule | Manganese Query Engine
        </p>
    </div>

</body>
</html>