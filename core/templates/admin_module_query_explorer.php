<?php
/**
 * Manganese OS - SQL Query Explorer (Générique)
 */

$db = get_db_connection();
$query_key = $_GET['sql_key'] ?? $_POST['query_key'] ?? null;
$session_target = $_GET['session_id'] ?? $_POST['session_id'] ?? null;

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
    'all_events_by_application' => [
        'title' => 'Tous les Événements d\'une candidature',
        'description' => 'Affiche tous les événements télémétrie d\'une candidature spécifique',
        'requires_param' => 'session_id',
        'param_label' => 'ID Application',
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

            case 'all_events_by_application':
                if (!$session_target) {
                    $error = "❌ Un ID d'application est requis pour cette requête.";
                    break;
                }
                $params = ['app_id' => $session_target];
                $stmt = $db->prepare("
                    SELECT * 
                    FROM telemetry_events te 
                    JOIN telemetry_sessions ts ON te.session_id = ts.id 
                    WHERE ts.app_id = ?
                ");
                $stmt->execute([$session_target]);
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
    <style>
        /* Styles pour le tri */
        th.sortable:hover { cursor: pointer; background-color: #1e293b; }
        th.sort-asc::after { content: " \f0de"; font-family: "Font Awesome 6 Free"; font-weight: 900; opacity: 0.8; margin-left: 4px; }
        th.sort-desc::after { content: " \f0dd"; font-family: "Font Awesome 6 Free"; font-weight: 900; opacity: 0.8; margin-left: 4px; }
        
        /* NOUVEAU : Styles pour le Redimensionnement et le Drag&Drop */
        th { position: relative; }
        .resizer {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            cursor: col-resize;
            user-select: none;
            z-index: 10;
        }
        .resizer:hover, .resizer.resizing {
            background-color: #10b981; /* emerald-500 */
        }
        .dragging {
            opacity: 0.5;
            background-color: #334155 !important;
        }
        .drag-over {
            border-left: 3px solid #10b981 !important;
        }
    </style>
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

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
                <p class="text-sm text-red-900 font-bold">
                    <?= $error ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($query_key && isset($available_queries[$query_key])): ?>
            <div class="bg-white border border-slate-200 rounded-3xl shadow-xl overflow-visible">
                
                <div class="bg-slate-900 text-white p-4 px-6 border-b border-slate-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 rounded-t-3xl">
                    <p class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2 whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span id="rowCount"><?= count($results) ?></span> Ligne<?= count($results) > 1 ? 's' : '' ?>
                    </p>
                    
                    <?php if (!empty($results)): ?>
                    <div class="flex flex-wrap gap-3 w-full md:w-auto">
                        <div class="relative flex-grow md:flex-grow-0">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 text-xs"></i>
                            <input type="text" id="jsSearchInput" placeholder="Filtrer..." class="w-full md:w-48 pl-8 pr-3 py-1.5 bg-slate-800 border border-slate-700 text-white text-xs rounded-lg focus:outline-none focus:border-emerald-500 transition-colors">
                        </div>

                        <div class="relative group">
                            <button type="button" class="bg-slate-800 border border-slate-700 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center h-full">
                                <i class="fa-solid fa-eye mr-2"></i> Colonnes
                            </button>
                            <div class="absolute right-0 mt-1 w-48 bg-white border border-slate-200 rounded-lg shadow-xl hidden group-hover:block z-20 overflow-hidden">
                                <div class="p-2 space-y-1" id="jsColumnToggles"></div>
                            </div>
                        </div>

                        <button id="jsExportCsvBtn" type="button" class="bg-emerald-600/20 text-emerald-400 border border-emerald-600/30 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center h-full">
                            <i class="fa-solid fa-download mr-2"></i> CSV
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($results) && empty($error)): ?>
                    <div class="p-20 text-center">
                        <i class="fa-solid fa-inbox text-slate-100 text-5xl mb-4"></i>
                        <p class="text-slate-400 font-bold italic text-sm">Aucun résultat pour cette requête.</p>
                    </div>
                <?php elseif (!empty($results)): ?>
                    <div class="overflow-x-auto relative">
                        <table id="jsDataTable" class="w-full text-left border-collapse" style="table-layout: auto;">
                            <thead>
                                <tr class="bg-slate-900 text-white">
                                    <?php 
                                    $columns = array_keys($results[0]);
                                    foreach ($columns as $index => $col): ?>
                                        <th data-index="<?= $index ?>" class="sortable p-4 px-6 text-[10px] font-black uppercase tracking-widest border-r border-slate-800 last:border-0 select-none transition-colors">
                                            <?= str_replace('_', ' ', $col) ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($results as $row): ?>
                                    <tr class="hover:bg-blue-50/50 transition-colors group data-row">
                                        <?php foreach ($row as $key => $value): ?>
                                            <td class="p-4 px-6 text-xs text-slate-600 border-r border-slate-50 last:border-0 font-mono">
                                                <?php 
                                                // Détection automatique et conversion des données binaires (ex: UUID)
                                                if (is_string($value) && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
                                                    $value = '0x' . strtoupper(bin2hex($value));
                                                }
                                                ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('jsDataTable');
            if (!table) return;

            const searchInput = document.getElementById('jsSearchInput');
            const exportBtn = document.getElementById('jsExportCsvBtn');
            const columnTogglesContainer = document.getElementById('jsColumnToggles');
            const rowCountEl = document.getElementById('rowCount');
            
            const tbody = table.querySelector('tbody');
            const initialHeaders = Array.from(table.querySelectorAll('th'));
            const rows = Array.from(tbody.querySelectorAll('tr.data-row'));

            // 1. RECHERCHE GLOBALE (FILTRE)
            searchInput.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                let visibleCount = 0;

                rows.forEach(row => {
                    const visibleText = Array.from(row.children)
                        .filter(td => td.style.display !== 'none')
                        .map(td => td.textContent.toLowerCase())
                        .join(' ');
                        
                    if (visibleText.includes(term)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                rowCountEl.textContent = visibleCount;
            });

            // 2. MASQUER/AFFICHER LES COLONNES
            initialHeaders.forEach((th) => {
                const colName = th.textContent.trim();
                
                const label = document.createElement('label');
                label.className = 'flex items-center gap-2 px-2 py-1.5 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 rounded';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = true;
                checkbox.className = 'accent-emerald-500 rounded';
                
                const span = document.createElement('span');
                span.className = 'font-bold uppercase tracking-wider text-[10px] truncate';
                span.textContent = colName;

                label.appendChild(checkbox);
                label.appendChild(span);
                columnTogglesContainer.appendChild(label);

                checkbox.addEventListener('change', function() {
                    const isVisible = checkbox.checked;
                    th.style.display = isVisible ? '' : 'none';
                    
                    // On recalcule l'index dynamiquement au cas où la colonne a été déplacée par Drag&Drop
                    const currentIndex = Array.from(th.parentNode.children).indexOf(th);
                    
                    rows.forEach(row => {
                        if (row.children[currentIndex]) {
                            row.children[currentIndex].style.display = isVisible ? '' : 'none';
                        }
                    });
                });
            });

            // 3. TRIER LES COLONNES
            let currentSortCol = -1;
            let currentSortAsc = true;

            initialHeaders.forEach(th => {
                th.addEventListener('click', function(e) {
                    // Ignorer le clic si l'utilisateur est en train de redimensionner
                    if (e.target.classList.contains('resizer')) return;

                    // Index dynamique pour supporter le Drag & Drop
                    const colIndex = Array.from(th.parentNode.children).indexOf(th);
                    
                    const currentHeaders = Array.from(table.querySelectorAll('th'));
                    currentHeaders.forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });

                    if (currentSortCol === colIndex) {
                        currentSortAsc = !currentSortAsc;
                    } else {
                        currentSortAsc = true;
                        currentSortCol = colIndex;
                    }

                    th.classList.add(currentSortAsc ? 'sort-asc' : 'sort-desc');

                    const visibleRows = Array.from(tbody.querySelectorAll('tr.data-row'));
                    visibleRows.sort((trA, trB) => {
                        const tdA = trA.children[colIndex].textContent.trim();
                        const tdB = trB.children[colIndex].textContent.trim();
                        
                        const numA = parseFloat(tdA);
                        const numB = parseFloat(tdB);
                        
                        let comparison = 0;
                        if (!isNaN(numA) && !isNaN(numB)) {
                            comparison = numA - numB;
                        } else {
                            comparison = tdA.localeCompare(tdB);
                        }

                        return currentSortAsc ? comparison : -comparison;
                    });

                    visibleRows.forEach(row => tbody.appendChild(row));
                });
            });

            // 4. EXPORT CSV
            exportBtn.addEventListener('click', function() {
                let csvContent = [];
                const escapeCsv = (str) => '"' + str.replace(/"/g, '""') + '"';

                // Utilisation des headers actuels (après un potentiel Drag & Drop)
                const currentHeaders = Array.from(table.querySelectorAll('th'));
                
                const headerRow = currentHeaders
                    .filter(th => th.style.display !== 'none')
                    .map(th => escapeCsv(th.textContent.trim()));
                csvContent.push(headerRow.join(','));

                rows.forEach(row => {
                    if (row.style.display !== 'none') {
                        const rowData = Array.from(row.children)
                            .filter(td => td.style.display !== 'none')
                            .map(td => escapeCsv(td.textContent.trim()));
                        csvContent.push(rowData.join(','));
                    }
                });

                const csvString = "\uFEFF" + csvContent.join("\n");
                const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                
                const dateIso = new Date().toISOString().slice(0,10);
                link.setAttribute("download", `export_requete_${dateIso}.csv`);
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });

            // 5. REDIMENSIONNEMENT DES COLONNES (RESIZER)
            initialHeaders.forEach(th => {
                const resizer = document.createElement('div');
                resizer.classList.add('resizer');
                th.appendChild(resizer);

                let x = 0;
                let w = 0;

                const mouseDownHandler = function(e) {
                    x = e.clientX;
                    const styles = window.getComputedStyle(th);
                    w = parseInt(styles.width, 10);
                    
                    document.addEventListener('mousemove', mouseMoveHandler);
                    document.addEventListener('mouseup', mouseUpHandler);
                    resizer.classList.add('resizing');
                    e.stopPropagation(); // Empêche de déclencher le Tri ou le Drag&Drop
                };

                const mouseMoveHandler = function(e) {
                    const dx = e.clientX - x;
                    th.style.width = `${w + dx}px`;
                    th.style.minWidth = `${w + dx}px`;
                };

                const mouseUpHandler = function() {
                    resizer.classList.remove('resizing');
                    document.removeEventListener('mousemove', mouseMoveHandler);
                    document.removeEventListener('mouseup', mouseUpHandler);
                };

                resizer.addEventListener('mousedown', mouseDownHandler);
            });

            // 6. DÉPLACEMENT DES COLONNES (DRAG & DROP)
            let draggedTh = null;

            initialHeaders.forEach(th => {
                th.setAttribute('draggable', 'true');

                th.addEventListener('dragstart', function(e) {
                    // Si on clique sur le resizer, on ne lance pas le Drag&Drop
                    if (e.target.classList.contains('resizer')) {
                        e.preventDefault();
                        return;
                    }
                    draggedTh = th;
                    th.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', ''); // Requis par Firefox
                });

                th.addEventListener('dragover', function(e) {
                    e.preventDefault(); // Autorise le drop
                    e.dataTransfer.dropEffect = 'move';
                    if (th !== draggedTh) {
                        th.classList.add('drag-over');
                    }
                });

                th.addEventListener('dragleave', function() {
                    th.classList.remove('drag-over');
                });

                th.addEventListener('dragend', function() {
                    th.classList.remove('dragging');
                    const allThs = Array.from(table.querySelectorAll('th'));
                    allThs.forEach(h => h.classList.remove('drag-over'));
                });

                th.addEventListener('drop', function(e) {
                    e.stopPropagation();
                    th.classList.remove('drag-over');
                    
                    if (draggedTh && draggedTh !== th) {
                        const allThs = Array.from(th.parentNode.children);
                        const sourceIndex = allThs.indexOf(draggedTh);
                        const targetIndex = allThs.indexOf(th);

                        // Déplacer l'en-tête (TH)
                        if (sourceIndex < targetIndex) {
                            th.parentNode.insertBefore(draggedTh, th.nextSibling);
                        } else {
                            th.parentNode.insertBefore(draggedTh, th);
                        }

                        // Déplacer toutes les cellules correspondantes (TD)
                        Array.from(tbody.querySelectorAll('tr.data-row')).forEach(row => {
                            const tds = Array.from(row.children);
                            const sourceTd = tds[sourceIndex];
                            const targetTd = tds[targetIndex];
                            
                            if (sourceIndex < targetIndex) {
                                row.insertBefore(sourceTd, targetTd.nextSibling);
                            } else {
                                row.insertBefore(sourceTd, targetTd);
                            }
                        });
                        
                        // Réinitialiser le tri après un mouvement pour éviter la confusion
                        currentSortCol = -1;
                        Array.from(table.querySelectorAll('th')).forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                    }
                    return false;
                });
            });
        });
    </script>
</body>
</html>