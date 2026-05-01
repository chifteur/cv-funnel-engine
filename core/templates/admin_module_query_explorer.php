<?php
/**
 * Manganese OS - SQL Query Explorer (Générique)
 */

$db = get_db_connection();
$query_key = $_GET['sql_key'] ?? $_POST['query_key'] ?? null;
$session_target = $_GET['session_id'] ?? null;

$results = [];
$query_title = "SQL Explorer";
$query_description = "";
$params = [];
$error = "";

// Dictionnaire des requêtes disponibles
$available_queries = [
    'orphan_events' => [
        'title' => 'Événements de la Session Orpheline',
        'description' => 'Affiche tous les événements télémétrie associés à une session spécifique',
        'requires_param' => 'session_id',
        'param_label' => 'ID Session',
    ],
    'applications_summary' => [
        'title' => 'Résumé des Candidatures',
        'description' => 'Affiche un résumé de toutes les candidatures avec leur statut',
        'requires_param' => false,
        'param_label' => null,
    ],
    'profile_info' => [
        'title' => 'Profil Master',
        'description' => 'Affiche les informations du profil master',
        'requires_param' => false,
        'param_label' => null,
    ],
    'db_tables' => [
        'title' => 'Liste des Tableaux',
        'description' => 'Affiche tous les tableaux de la base de données',
        'requires_param' => false,
        'param_label' => null,
    ],
    'orphaned_telemetry_events' => [
        'title' => 'Événements Orphelins de Télémétrie',
        'description' => 'Affiche tous les événements télémétrie qui n\'ont pas de session valide associée',
        'requires_param' => false,
        'param_label' => null,
    ],
];

// On exécute la requête si elle est sélectionnée
if ($query_key && isset($available_queries[$query_key])) {
    $query_config = $available_queries[$query_key];
    $query_title = $query_config['title'];
    $query_description = $query_config['description'];
    
    try {
        switch ($query_key) {
            case 'orphan_events':
                if (!$session_target) {
                    $error = "❌ Un ID session est requis pour cette requête.";
                    break;
                }
                $params = ['session_id' => $session_target];
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
                break;
                
            case 'applications_summary':
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        slug,
                        company_name,
                        job_title,
                        status,
                        created_at
                    FROM applications
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'profile_info':
                $stmt = $db->prepare("
                    SELECT 
                        full_name,
                        job_title,
                        email,
                        phone,
                        linkedin_url,
                        photo_path,
                        bio
                    FROM profile_settings
                    WHERE id = 1
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'db_tables':
                $stmt = $db->prepare("
                    SELECT 
                        TABLE_NAME,
                        TABLE_TYPE,
                        TABLE_ROWS
                    FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = DATABASE()
                    ORDER BY TABLE_NAME ASC
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
                
            case 'orphaned_telemetry_events':
                $stmt = $db->prepare("
                    SELECT 
                        te.id,
                        te.event_type,
                        te.element_id,
                        te.event_data,
                        te.created_at
                    FROM telemetry_events te
                    LEFT JOIN telemetry_sessions s ON te.session_id = s.id
                    WHERE s.id IS NULL
                    ORDER BY te.created_at DESC
                ");
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    } catch (Exception $e) {
        $error = "❌ Erreur d'exécution : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $query_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body class="bg-slate-50 p-8 font-sans text-slate-900">

    <div class="max-w-5xl mx-auto">
        
        <header class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-black tracking-tighter uppercase italic text-slate-900">
                    <i class="fa-solid fa-database mr-3 text-emerald-500"></i>Query Explorer
                </h1>
                <p class="text-slate-500 text-sm mt-2">Sélectionnez une requête et exécutez-la pour explorer vos données</p>
            </div>

            <button onclick="window.close()" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-[10px] font-black transition uppercase tracking-widest shadow-lg shadow-slate-200">
                <i class="fa-solid fa-x mr-2"></i> Fermer
            </button>
        </header>

        <!-- Section Sélection de la Requête -->
        <div class="bg-white border border-slate-200 rounded-3xl shadow-lg p-6 mb-6">
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-600 uppercase tracking-widest block mb-2">
                            <i class="fa-solid fa-list mr-2"></i>Requête Disponible
                        </label>
                        <select name="query_key" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white font-mono text-sm">
                            <option value="">-- Sélectionnez une requête --</option>
                            <?php foreach ($available_queries as $key => $config): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $query_key === $key ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($config['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($query_key && isset($available_queries[$query_key]) && $available_queries[$query_key]['requires_param']): ?>
                        <div>
                            <label class="text-[10px] font-black text-slate-600 uppercase tracking-widest block mb-2">
                                <i class="fa-solid fa-info-circle mr-2"></i><?= $available_queries[$query_key]['param_label'] ?>
                            </label>
                            <input type="text" name="session_id" value="<?= htmlspecialchars($session_target ?? '') ?>" placeholder="Entrez la valeur..." class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono text-sm">
                        </div>
                    <?php endif; ?>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg text-sm font-black transition uppercase tracking-widest shadow-lg">
                            <i class="fa-solid fa-play mr-2"></i>Exécuter la Requête
                        </button>
                        <a href="?module=query_explorer" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-900 px-6 py-3 rounded-lg text-sm font-black transition uppercase tracking-widest text-center">
                            <i class="fa-solid fa-rotate-left mr-2"></i>Réinitialiser
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Info Query Courante -->
        <?php if ($query_key && isset($available_queries[$query_key])): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 mb-6">
                <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">
                    <i class="fa-solid fa-check-circle mr-2"></i>Requête Sélectionnée
                </p>
                <p class="text-sm text-emerald-900 font-bold mt-2"><?= htmlspecialchars($query_description) ?></p>
                <?php if (!empty($params)): ?>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <?php foreach ($params as $key => $val): ?>
                            <span class="inline-flex items-center bg-white border border-emerald-300 rounded-lg px-3 py-1 text-xs font-mono">
                                <span class="font-black text-emerald-700 mr-2"><?= str_replace('_', ' ', $key) ?>:</span>
                                <span class="text-emerald-600"><?= htmlspecialchars($val) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Messages d'Erreur -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
                <p class="text-sm text-red-900 font-bold">
                    <?= $error ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Résultats -->
        <?php if ($query_key && isset($available_queries[$query_key])): ?>
            <div class="bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden">
                <div class="bg-slate-900 text-white p-4 px-6 border-b border-slate-800">
                    <p class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <?= count($results) ?> Ligne<?= count($results) > 1 ? 's' : '' ?>
                    </p>
                </div>

                <?php if (empty($results) && empty($error)): ?>
                    <div class="p-20 text-center">
                        <i class="fa-solid fa-inbox text-slate-100 text-5xl mb-4"></i>
                        <p class="text-slate-400 font-bold italic text-sm">Aucun résultat pour cette requête.</p>
                    </div>
                <?php elseif (!empty($results)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900 text-white">
                                    <?php 
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
                                            <td class="p-4 px-6 text-xs text-slate-600 border-r border-slate-50 last:border-0 font-mono">
                                                <?php if ($key === 'event_type' || $key === 'status'): ?>
                                                    <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded text-[9px] font-black uppercase border border-slate-200">
                                                        <?= htmlspecialchars($value) ?>
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
        <?php else: ?>
            <div class="bg-white border border-slate-200 rounded-3xl shadow-xl p-20 text-center">
                <i class="fa-solid fa-wand-magic-sparkles text-slate-100 text-6xl mb-4"></i>
                <p class="text-slate-400 font-bold italic text-lg">Sélectionnez une requête pour commencer</p>
                <p class="text-slate-300 text-sm mt-2">Les requêtes disponibles sont listées dans le formulaire ci-dessus</p>
            </div>
        <?php endif; ?>

        <p class="mt-12 text-center text-[9px] text-slate-300 font-black uppercase tracking-[0.2em]">
            <i class="fa-solid fa-microchip mr-2"></i> Manganese OS Engine
        </p>
    </div>

</body>
</html>