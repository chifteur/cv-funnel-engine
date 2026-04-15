<?php
/**
 * MANGANESE OS - DASHBOARD CENTRALISÉ
 * Gère TOUTES les actions POST et la structure globale
 */

$db = get_db_connection();
$message = "";
$debug_action = ""; // DEBUG LOG

// --- 1. TRAITEMENT DE TOUTES LES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $debug_action = $action;

    try {
        // PROFIL MASTER
        if ($action === 'update_profile') {
            // Par défaut, on garde l'ancien chemin de la photo
            $photoPath = $_POST['existing_photo_path'] ?? ''; 

            // Gestion de l'upload de la nouvelle photo
            if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
                // Chemin absolu vers le dossier (à ajuster selon la position de ton script par rapport au dossier public)
                $uploadDir = __DIR__ . '/../../public/assets/images/';
                
                // On s'assure que le dossier existe (comme on l'a fait pour le storage)
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileTmpPath = $_FILES['photo_upload']['tmp_name'];
                $fileName = $_FILES['photo_upload']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Sécurité : on limite les extensions autorisées
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    // On génère un nom unique pour éviter les conflits et forcer le rafraîchissement du cache navigateur
                    $newFileName = 'profile_' . time() . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        // Le chemin relatif web à enregistrer en base de données
                        $photoPath = '/public/assets/images/' . $newFileName;
                    } else {
                        $message = "❌ Erreur lors de l'enregistrement de l'image sur le serveur.";
                    }
                } else {
                    $message = "❌ Format d'image non supporté (utilisez JPG, PNG ou WEBP).";
                }
            }
        
            // On exécute la mise à jour seulement s'il n'y a pas eu d'erreur d'upload
            if (empty($message) || strpos($message, '❌') === false) {
                // 1. On stocke les valeurs dans un tableau
                $data = [
                    $_POST['full_name'] ?? '',
                    $_POST['job_title'] ?? '',
                    $_POST['bio'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['phone'] ?? '',
                    $_POST['linkedin_url'] ?? '',
                    $photoPath // On utilise la variable traitée ci-dessus
                ];            
                // 2. La requête d'Upsert (Insert si l'ID 1 n'existe pas, Update s'il existe)
                $sql = "INSERT INTO profile_settings 
                            (id, full_name, job_title, bio, email, phone, linkedin_url, photo_path) 
                        VALUES 
                            (1, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                            full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=?";
                
                $stmt = $db->prepare($sql);
                
                // 3. On exécute la requête. 
                // On utilise array_merge($data, $data) car il y a 7 "?" pour le INSERT et 7 "?" pour le UPDATE.
                    $stmt->execute(array_merge($data, $data));
                $message = "✅ Profil Master mis à jour.";
            }
        }
        // CANDIDATURES /GO/
        if ($action === 'add_app') {
            try {
                $db->beginTransaction();
                $stmt = $db->prepare("INSERT INTO applications (slug, company_name, job_title, job_url, custom_pitch, why_me, strengths, perfect_match, default_lens, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['job_url'] ?? '', $_POST['custom_pitch'] ?? '', $_POST['why_me'] ?? '', $_POST['strengths'] ?? '', $_POST['perfect_match'] ?? '', $_POST['default_lens'], $_POST['status'] ?? 'sent']);
                // Si c'est un INSERT, récupère le nouvel ID
                $app_id = $db->lastInsertId();

                // 1. On insère les nouveaux si sélectionnés
                if (!empty($_POST['selected_docs'])) {
                    $stmtInsert = $db->prepare("INSERT INTO rel_app_doc (app_id, doc_id) VALUES (?, ?)");
                    foreach ($_POST['selected_docs'] as $doc_id) {
                        $stmtInsert->execute([$app_id, (int)$doc_id]);
                    }
                }

                $message = "🚀 Candidature et documents liés créées.";
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $message = "❌ Erreur critique : " . $e->getMessage();
                Logger::error("Error in add_app action", ['exception' => $e->getMessage(), 'post_data' => $_POST]);
            }
        }
        if ($action === 'update_app') {
            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE applications SET slug=?, company_name=?, job_title=?, job_url=?, custom_pitch=?, why_me=?, strengths=?, perfect_match=?, default_lens=?, status=? WHERE id=?");
                $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['job_url'] ?? '', $_POST['custom_pitch'] ?? '', $_POST['why_me'] ?? '', $_POST['strengths'] ?? '', $_POST['perfect_match'] ?? '', $_POST['default_lens'], $_POST['status'], $_POST['id']]);
            
                if (!$app_id) 
                    $app_id = $_POST['id'];

                // SYNCHRONISATION DES DOCUMENTS
                // 1. On supprime les anciens liens
                $stmt = $db->prepare("DELETE FROM rel_app_doc WHERE app_id = ?");
                $stmt->execute([$app_id]);

                // 2. On insère les nouveaux si sélectionnés
                if (!empty($_POST['selected_docs'])) {
                    $stmtInsert = $db->prepare("INSERT INTO rel_app_doc (app_id, doc_id) VALUES (?, ?)");
                    foreach ($_POST['selected_docs'] as $doc_id) {
                        $stmtInsert->execute([$app_id, (int)$doc_id]);
                    }
                }            
                $message = "✅ Candidature et documents liés mise à jour.";
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $message = "❌ Erreur critique : " . $e->getMessage();
                Logger::error("Error in update_app action", ['exception' => $e->getMessage(), 'post_data' => $_POST]);
            }
        }
        if ($action === 'delete_app') {
            try {
                $db->beginTransaction();            
                // On supprime les evénements du CRM liés à cette application
                $stmt = $db->prepare("DELETE FROM crm_events WHERE app_id = ?");
                $stmt->execute([$_POST['id']]);

                // On supprime les anciens liens
                $stmt = $db->prepare("DELETE FROM rel_app_doc WHERE app_id = ?");
                $stmt->execute([$_POST['id']]);

                $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $message = "✅ Candidature, documents et événements CRM liés supprimés.";
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $message = "❌ Erreur critique : " . $e->getMessage();
                Logger::error("Error in delete_app action", ['exception' => $e->getMessage(), 'post_data' => $_POST]);
            }

        }

        // --- CRUD CV : EXPÉRIENCES ---
        if ($action === 'add_exp') {
            $stmt = $db->prepare("INSERT INTO cv_experiences (company, role, location, period, content, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category']]);
            $message = "✅ Expérience ajoutée.";
        }
        if ($action === 'update_exp') {
            $stmt = $db->prepare("UPDATE cv_experiences SET company=?, role=?, location=?, period=?, content=?, category=? WHERE id=?");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category'], $_POST['id']]);
            $role = htmlspecialchars($_POST['role'] ?? 'Inconnue');
            $message = "✅ Expérience {$role} mise à jour.";            
        }
        if ($action === 'delete_exp') {
            $stmt = $db->prepare("DELETE FROM cv_experiences WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $role = htmlspecialchars($_POST['role'] ?? 'Inconnue');
            $message = "✅ Expérience {$role} supprimée.";
        }
        if ($action === 'reorder_exp') {
            // Reçoit un JSON array: [{id: 1, order: 0}, {id: 2, order: 1}, ...]
            $orders = json_decode($_POST['orders'] ?? '[]', true);
            foreach ($orders as $item) {
                $stmt = $db->prepare("UPDATE cv_experiences SET display_order=? WHERE id=?");
                $stmt->execute([$item['order'], $item['id']]);
            }
            $message = "✅ Ordre mis à jour.";
        }

        // --- CRUD CV : SKILLS ---
        if ($action === 'add_skill') {
            $stmt = $db->prepare("INSERT INTO cv_skills (category, label, level_text) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['category'], $_POST['label'], $_POST['level_text']]);
            $message = "✅ Skill ajoutée.";
        }
        if ($action === 'update_skill') {
            $stmt = $db->prepare("UPDATE cv_skills SET category=?, label=?, level_text=? WHERE id=?");
            $stmt->execute([$_POST['category'], $_POST['label'], $_POST['level_text'], $_POST['id']]);
            $message = "✅ Skill mise à jour.";
        }
        if ($action === 'delete_skill') {
            $stmt = $db->prepare("DELETE FROM cv_skills WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "✅ Skill supprimée.";
        }

        // --- CRUD CV : EDUCATION ---
        if ($action === 'add_edu') {
            $stmt = $db->prepare("INSERT INTO cv_education (degree, institution, year, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon'] ?? '']);
            $message = "✅ Formation ajoutée.";
        }
        if ($action === 'update_edu') {
            $stmt = $db->prepare("UPDATE cv_education SET degree=?, institution=?, year=?, icon=? WHERE id=?");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon'] ?? '', $_POST['id']]);
            $message = "✅ Formation mise à jour.";
        }
        if ($action === 'delete_edu') {
            $stmt = $db->prepare("DELETE FROM cv_education WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "✅ Formation supprimée.";
        }

        // --- CRUD CV : LANGUES ---
        if ($action === 'add_lang') {
            $stmt = $db->prepare("INSERT INTO cv_languages (label, level) VALUES (?, ?)");
            $stmt->execute([$_POST['label'], $_POST['level'] ?? '']);
            $message = "✅ Langue ajoutée.";
        }
        if ($action === 'update_lang') {
            $stmt = $db->prepare("UPDATE cv_languages SET label=?, level=? WHERE id=?");
            $stmt->execute([$_POST['label'], $_POST['level'] ?? '', $_POST['id']]);
            $message = "✅ Langue mise à jour.";
        }
        if ($action === 'delete_lang') {
            $stmt = $db->prepare("DELETE FROM cv_languages WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "✅ Langue supprimée.";
        }

        // --- gestion des documents uploadés ---
        if ($action === 'add_doc') {
            $label = $_POST['label'] ?? 'Sans titre';
            $category = $_POST['category'] ?? 'reference';
            $file = $_FILES['doc_file'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                // 1. Nettoyage du nom de fichier (Ingénieur Style)
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($file['name'], PATHINFO_FILENAME)));
                $filename = $cleanName . '_' . time() . '.' . $extension; // On ajoute le timestamp pour éviter les doublons
                
                $targetPath = __DIR__ . '/../../storage/docs/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $stmt = $db->prepare("INSERT INTO documents (label, filename, category) VALUES (?, ?, ?)");
                    $stmt->execute([$label, $filename, $category]);
                    $message = "✅ Document {$filename} ajouté avec succès.";
                }
            }
        }

        if ($action === 'delete_doc') {
            $id = $_POST['id'];
            
            // 1. On récupère le nom du fichier pour le supprimer du disque
            $stmt = $db->prepare("SELECT filename FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch();

            if ($doc) {
                $filePath = __DIR__ . '/../../storage/docs/' . $doc['filename'];
                if (file_exists($filePath)) {
                    unlink($filePath); // Efface physiquement le fichier
                }

                $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
                $stmt->execute([$id]);

                // On supprime également les liens
                $stmt = $db->prepare("DELETE FROM rel_app_doc WHERE doc_id = ?");
                $stmt->execute([$id]);

                $message = "🗑️ Le fichier et ces liens ont été supprimés.";
            }
        }

        // Nettoyer la BDD (Supprimer l'entrée si le fichier n'existe plus)
        if ($action === 'sync_clean_db') {
            $stmt = $db->prepare("DELETE FROM documents WHERE filename = ?");
            $stmt->execute([$_POST['filename']]);
            $message = "✅ Entrée BDD nettoyée pour " . $_POST['filename'];
        }

        // Nettoyer le Disque (Supprimer le fichier s'il n'est pas en BDD)
        if ($action === 'sync_clean_disk') {
            $filePath = __DIR__ . '/../../storage/docs/' . $_POST['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
                $message = "🗑️ Fichier orphelin supprimé du disque : " . $_POST['filename'];
            }
        }

        if ($action === 'delete_session') {
            try {
                $db->beginTransaction();
                // 1. Supprimer tous les événements liés à cette session
                $stmt = $db->prepare("DELETE FROM telemetry_events WHERE session_id = UUID_TO_BIN(?)");
                $stmt->execute([$_POST['id']]);
                //2. Supprimer la session elle-même
                $stmt = $db->prepare("DELETE FROM telemetry_sessions WHERE id = UUID_TO_BIN(?)");
                $stmt->execute([$_POST['id']]);
                $message = "✅ Session supprimée.";
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                $message = "❌ Erreur critique lors de la suppression de la session : " . $e->getMessage();
                Logger::error("Error in delete_session action", ['exception' => $e->getMessage(), 'post_data' => $_POST]);
            }
        }

        } catch (Exception $e) {
        $message = "❌ Erreur SQL : " . $e->getMessage();
        Logger::error("SQL Error", ['exception' => $e->getMessage(), 'post_data' => $_POST]);
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
$apps = $db->query("SELECT a.*, (SELECT GROUP_CONCAT(doc_id) FROM rel_app_doc WHERE app_id = a.id) as doc_ids, (SELECT COUNT(*) FROM telemetry_sessions WHERE app_id = a.id) as visits FROM applications a ORDER BY a.created_at DESC")->fetchAll();
$telemetry_sessions = $db->query("
    SELECT 
        bin_to_uuid(s.id) as s_id_text,
        s.started_at,
        s.duration_seconds,
        s.user_agent,
        a.company_name,
        MAX(e.created_at) as last_event_at,
        -- Calcul du Heat Score
        SUM(CASE 
            WHEN e.event_type = 'download' THEN 5
            WHEN e.event_type = 'reading_focus' THEN 3
            WHEN e.event_type = 'scroll_depth' AND e.element_id = '100%' THEN 4 -- Bonus fin de lecture
            WHEN e.event_type = 'scroll_depth' THEN 1 -- Petit bonus pour chaque palier
            WHEN e.event_type = 'copy_text' THEN 2
            WHEN e.event_type = 'view_section' THEN 0.5
            ELSE 0.1 
        END) as heat_score,
        SUM(CASE WHEN e.event_type = 'download' THEN 1 ELSE 0 END) as download_count,
        (SELECT COUNT(*) FROM telemetry_sessions s2 WHERE s2.visitor_uuid = s.visitor_uuid) as total_visits
    FROM telemetry_sessions s
    JOIN applications a ON s.app_id = a.id
    LEFT JOIN telemetry_events e ON s.id = e.session_id
    GROUP BY s.id
    ORDER BY started_at DESC 
    LIMIT 50
")->fetchAll();

$orphelines_sessions = $db->query("
SELECT 
    bin_to_uuid(s.id) as s_id_text,
    s.started_at,
    s.duration_seconds,
    s.app_id as orphan_app_id, -- L'ID qui n'existe plus
    s.user_agent,
    (SELECT COUNT(*) FROM telemetry_events e WHERE e.session_id = s.id) as events_count
FROM telemetry_sessions s
LEFT JOIN applications a ON s.app_id = a.id
WHERE a.id IS NULL
ORDER BY s.started_at DESC;
")->fetchAll();

// --- 3. DONNÉES CV EDITOR (fusionné) ---
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY display_order ASC, id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$cv_langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
$allDocs = $db->query("SELECT * FROM documents ORDER BY category")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Manganese OS | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans text-slate-900" x-data="appData()">
    <script>
        function appData() {
            return {
                // 1. Initialisation intelligente : on pioche dans le sessionStorage ou valeur par défaut
                tab: sessionStorage.getItem('cvcrm_tab') || 'apps',
                section: sessionStorage.getItem('cvcrm_section') || 'profile',
                phpMessage: <?= json_encode($message ?? '') ?>,
                toastMessage: '',
                openModal: null, 
                editItem: { doc_ids: [] }, // Initialisation de l'objet pour éviter les erreurs d'accès aux propriétés
                draggedExpId: null,
                allExps: <?= json_encode($cv_exps) ?>,
                allApps: <?= json_encode($apps) ?>,
                allLangs: <?= json_encode($cv_langs) ?>,
                allDocs: <?= json_encode($allDocs) ?>,
                // 2. La fonction magique qui s'exécute au chargement d'Alpine
                init() {
                    // On surveille 'tab' : dès qu'il change, on enregistre
                    this.$watch('tab', value => sessionStorage.setItem('cvcrm_tab', value));
                    
                    // On surveille 'section' : dès qu'il change, on enregistre
                    this.$watch('section', value => sessionStorage.setItem('cvcrm_section', value));

                    // Si un message PHP existe, on l'active avec un micro-délai pour lancer l'animation et on le fait disparaître après 5s
                    if (this.phpMessage) {
                        setTimeout(() => {
                            this.toastMessage = this.phpMessage;
                            this.autoHideToast();
                        }, 10); // 10ms suffisent pour qu'Alpine détecte le changement
                    }
                },

                autoHideToast() {
                    setTimeout(() => {
                        this.toastMessage = '';
                    }, 5000);
                },

                prepEdit(type, data = {}) {
                    let docIds = [];
                    // On transforme la chaîne "5,8" en tableau d'entiers [5, 8]
                    if (type === 'app') {
                            if (data.doc_ids) {
                                // Si c'est une chaîne "1,2", on split. Sinon si c'est déjà un array, on le prend.
                                docIds = typeof data.doc_ids === 'string' 
                                    ? data.doc_ids.split(',').map(Number) 
                                    : [...data.doc_ids]; 
                            }
                        }

                    // 2. On fusionne proprement
                    this.editItem = { 
                        type: type, 
                        ...data, 
                        doc_ids: docIds // On garantit que c'est TOUJOURS un tableau ici
                    };
                    
                    // Debug : décommente la ligne suivante pour vérifier dans la console
                    console.log('Edit Item initialized:', this.editItem);
                },
                saveDragOrder() {
                    const orders = this.allExps.map((e, idx) => ({id: e.id, order: idx}));
                    const formData = new FormData();
                    formData.append('action', 'reorder_exp');
                    formData.append('orders', JSON.stringify(orders));
                    fetch(window.location.href, { method: 'POST', body: formData })
                        .then(() => {
                            this.toastMessage = '✅ Ordre enregistré !';
                            this.autoHideToast();
                            setTimeout(() => location.reload(), 1000);
                        })
                        .catch(e => {
                            this.toastMessage = '❌ Erreur: ' + e;
                            this.autoHideToast();
                        });
                },
                moveExp(fromIdx, toIdx) {
                    if (fromIdx === toIdx) return;
                    const arr = [...this.allExps];
                    arr.splice(toIdx, 0, arr.splice(fromIdx, 1)[0]);
                    this.allExps = arr;
                }
            }
        }
    </script>
    <div class="flex min-h-screen">
        <aside class="w-64 bg-slate-900 text-white p-6 sticky top-0 h-screen">
            <div class="text-2xl font-black mb-12 tracking-tighter text-blue-500 italic">MANGANESE<span class="text-white">OS</span></div>
            <div class="text-xs text-slate-400 mt-8 text-center">
                Core v<?= APP_VERSION ?> | DB v<?= htmlspecialchars(get_db_version($db)) ?>
            </div>            
            <nav class="space-y-2">
                <button @click="tab = 'apps'" :class="tab === 'apps' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-paper-plane w-5"></i> Candidatures
                </button>
                <button @click="tab = 'cv'" :class="tab === 'cv' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-user-pen w-5"></i> Éditeur de CV
                </button>
                <button @click="tab = 'documents'" :class="tab === 'documents' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-folder-open w-5"></i> Documents
                </button>
                <button @click="tab = 'stats'" :class="tab === 'stats' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-bolt w-5"></i> Télémétrie
                </button>
                <div class="pt-4 pb-2 border-t border-slate-800 mt-4">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest px-3">Système & Debug</p>
                </div>

                <a href="?key=<?= $key ?>&module=logs" target="_blank" 
                class="w-full text-slate-400 hover:bg-slate-800 hover:text-white p-3 rounded-lg transition flex items-center justify-between font-bold group">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-terminal w-5 text-blue-400"></i> Journal Logs
                    </div>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-0 group-hover:opacity-100 transition"></i>
                </a>

                <button @click="tab = 'debug'" :class="tab === 'debug' ? 'bg-red-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-bug w-5"></i> Debug Interne
                </button>       
            </nav>
        </aside>

        <main class="flex-1 p-10">
            <div x-show="toastMessage"
                x-cloak
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 transform translate-x-full"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 transform translate-x-0"
                x-transition:leave-end="opacity-0 transform translate-x-full"
                class="fixed bottom-6 right-6 z-[100] flex items-center gap-3 bg-white border-l-4 border-blue-500 shadow-2xl p-4 rounded-xl min-w-[320px]">
                <div class="bg-blue-50 p-2 rounded-full">
                    <i class="fa-solid fa-check-circle text-blue-500"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-slate-800" x-text="toastMessage"></p>
                </div>
                <button @click="toastMessage = ''" class="text-slate-300 hover:text-slate-500 transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div x-show="tab === 'apps'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-3xl font-black uppercase">Postulations</h2>
                    <button @click="prepEdit('app')" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow-lg hover:bg-blue-700 transition">+ Nouvelle Candidature</button>
                </div>
                <div class="grid gap-4">
                    <template x-for="app in allApps" :key="app.id">
                        <div class="bg-white p-6 rounded-xl border flex justify-between items-center shadow-sm hover:border-blue-300 transition">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="font-black text-lg" x-text="app.company_name"></h3>
                                    <span class="text-[9px] font-bold px-2 py-1 rounded uppercase" 
                                        :class="{
                                            'bg-green-100 text-green-700': app.status === 'accepted',
                                            'bg-blue-100 text-blue-700': app.status === 'interview',
                                            'bg-red-100 text-red-700': app.status === 'rejected',
                                            'bg-slate-100 text-slate-700': app.status === 'sent'
                                        }"
                                        x-text="app.status.toUpperCase()"></span>
                                </div>
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="text-slate-600 text-sm font-bold" x-text="app.job_title"></p>
                                    
                                    <template x-if="app.job_url">
                                        <a :href="app.job_url" 
                                        target="_blank" 
                                        class="flex items-center gap-1 text-[10px] font-black text-slate-400 bg-slate-50 px-2 py-0.5 rounded-lg border border-slate-100 hover:text-blue-600 hover:border-blue-200 transition"
                                        title="Voir l'annonce originale">
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[8px]"></i>
                                            <span x-text="new URL(app.job_url).hostname.replace('www.', '')"></span>
                                        </a>
                                    </template>
                                </div>
                                <a :href="'/go/' + app.slug" target="_blank" class="text-blue-500 font-mono text-xs" x-text="'/go/' + app.slug"></a>
                            </div>
                            <div class="text-right flex items-center gap-4">
                                <div>
                                    <span class="text-2xl font-black text-slate-800" x-text="app.visits ?? 0"></span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sessions</p>
                                </div>

                                <div class="flex items-center gap-2" x-data="{ confirming: false }">
                                    <template x-if="!confirming">
                                        <div class="flex gap-1 items-center">
                                            <a :href="'?key=<?= $key ?>&module=crm&app_id=' + app.id" 
                                                target="_blank" 
                                                class="text-emerald-600 hover:text-emerald-700 p-2 transition-colors"
                                                title="Ouvrir le suivi CRM">
                                                <i class="fa-solid fa-briefcase"></i>
                                            </a>
                                            <button @click="prepEdit('app', app)" class="text-blue-500 hover:text-blue-700 p-2">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button @click="confirming = true" class="text-slate-200 hover:text-red-500 p-2">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="confirming">
                                        <div x-transition class="flex items-center gap-2 bg-red-50 border border-red-100 p-1 rounded-xl">
                                            <span class="text-[9px] font-black text-red-600 uppercase px-2">Supprimer ?</span>
                                            
                                            <form method="POST" action="?key=<?= $key ?>" style="display:inline">
                                                <input type="hidden" name="action" value="delete_app">
                                                <input type="hidden" name="id" :value="app.id">
                                                <button type="submit" class="bg-red-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-red-700 transition shadow-sm shadow-red-200">
                                                    OUI
                                                </button>
                                            </form>

                                            <button @click="confirming = false" class="text-slate-400 text-[10px] font-bold hover:text-slate-600 px-2 uppercase tracking-tighter">
                                                NON
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="tab === 'cv'">
                <!-- CV EDITOR INLINED -->
                <div class="space-y-8">
                    <h2 class="text-3xl font-black uppercase tracking-tight">Éditeur de CV</h2>

                    <div class="flex gap-4 border-b border-slate-200 mb-8 font-bold">
                        <button @click="section = 'profile'" :class="section === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Identité</button>
                        <button @click="section = 'experiences'" :class="section === 'experiences' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Parcours</button>
                        <button @click="section = 'skills'" :class="section === 'skills' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Skills & Langues</button>
                        <button @click="section = 'education'" :class="section === 'education' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Éducation</button>
                    </div>

                    <!-- PROFIL -->
                    <div x-show="section === 'profile'" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-6">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <input type="hidden" name="existing_photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>">

                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Nom Complet</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none font-bold focus:border-blue-500">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Titre Professionnel</label>
                                <input type="text" name="job_title" value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none font-bold focus:border-blue-500">
                            </div>

                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none font-bold focus:border-blue-500">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Téléphone</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none font-bold focus:border-blue-500">
                            </div>

                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">URL LinkedIn</label>
                                <input type="url" name="linkedin_url" value="<?= htmlspecialchars($profile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/..." class="w-full mt-1 border-b py-2 outline-none font-bold focus:border-blue-500 text-sm">
                            </div>
                            
                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Photo de profil</label>
                                <?php if (!empty($profile['photo_path'])): ?>
                                    <div class="mt-2 mb-1">
                                        <img src="<?= htmlspecialchars($profile['photo_path']) ?>" alt="Aperçu" class="h-10 w-10 rounded-full object-cover border border-slate-200">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="photo_upload" accept="image/jpeg, image/png, image/webp" class="w-full mt-1 border-b py-1 outline-none text-sm">
                                <p class="text-[10px] text-slate-400 mt-1">Laissez vide pour conserver la photo actuelle.</p>
                            </div>

                            <div class="col-span-2">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Bio / Summary</label>
                                <textarea name="bio" rows="4" class="w-full mt-1 border rounded-xl p-3 text-sm focus:border-blue-500 outline-none"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="col-span-2 bg-slate-900 text-white py-3 rounded-full font-bold hover:bg-blue-600 transition shadow-lg">Sauvegarder Profil</button>
                        </form>
                    </div>
                    <!-- EXPERIENCES -->
                    <div x-show="section === 'experiences'" class="space-y-4">
                        <div class="grid gap-3">
                            <template x-for="(exp, idx) in allExps" :key="exp.id">
                                <div 
                                    draggable="true"
                                    @dragstart="draggedExpId = idx"
                                    @dragover.prevent="(e) => e.dataTransfer.dropEffect = 'move'"
                                    @drop.prevent="moveExp(draggedExpId, idx); draggedExpId = null"
                                    @dragend="draggedExpId = null"
                                    :class="draggedExpId === idx ? 'opacity-50 bg-blue-50' : ''"
                                    class="bg-white p-4 rounded-xl border flex justify-between items-center group shadow-sm cursor-grab hover:shadow-md hover:border-blue-300 active:cursor-grabbing transition">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-grip text-slate-300 text-lg"></i>
                                        <div class="flex items-center">
                                            <span class="text-[9px] font-black px-2 py-1 bg-slate-100 rounded uppercase text-slate-400" x-text="exp.category"></span>
                                            <h4 class="font-bold ml-3" x-text="`${exp.role} @ ${exp.company}`"></h4>                                            
                                        </div>
                                        <span class="text-sm font-bold text-slate-400" x-text="exp.period"></span>
                                    </div>
                                    <div class="flex items-center gap-2" x-data="{ confirming: false }">
                                        
                                        <template x-if="!confirming">
                                            <div class="flex gap-2">
                                                <button @click="prepEdit('exp', exp)" class="text-blue-500 hover:text-blue-700 p-2 transition">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                
                                                <button @click="confirming = true" class="text-slate-200 hover:text-red-500 p-2 transition">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </template>

                                        <template x-if="confirming">
                                            <div x-transition class="flex items-center gap-2 bg-red-50 border border-red-100 p-1 rounded-xl shadow-inner">
                                                <span class="text-[9px] font-black text-red-600 uppercase px-2 tracking-tighter">Supprimer ?</span>
                                                
                                                <form method="POST" action="?key=<?= $key ?>" style="display:inline">
                                                    <input type="hidden" name="action" value="delete_exp">
                                                    <input type="hidden" name="id" :value="exp.id">
                                                    <input type="hidden" name="role" :value="exp.role">
                                                    
                                                    <button type="submit" class="bg-red-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-red-700 transition shadow-sm">
                                                        OUI
                                                    </button>
                                                </form>

                                                <button @click="confirming = false" class="text-slate-400 text-[10px] font-bold hover:text-slate-600 px-2">
                                                    NON
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <button @click="saveDragOrder()" x-show="allExps.length > 0" class="w-full bg-green-500 text-white py-2 rounded-lg font-bold hover:bg-green-600 transition mb-4">💾 Enregistrer l'ordre</button>
                        <button @click="prepEdit('exp', {id:'', company:'', role:'', location:'', period:'', content:'', category:'ops'})" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:text-blue-600 transition">+ Ajouter Experience</button>
                    </div>

                    <!-- SKILLS & LANGUAGES -->
                    <div x-show="section === 'skills'" class="grid grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <h3 class="font-bold text-slate-400 uppercase text-xs">Compétences</h3>
                            <?php foreach ($cv_skills as $s): ?>
                                <div class="bg-white p-2 border rounded flex justify-between items-center">
                                    <span class="text-sm font-bold"><?= htmlspecialchars($s['label']) ?></span>
                                    <div class="flex gap-2">
                                        <button @click="prepEdit('skill', <?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-400"><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="delete_skill">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="text-red-200">✕</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button @click="prepEdit('skill', {id:'', label:'', level_text:'', category:'management'})" class="w-full border border-dashed p-2 text-xs text-slate-400 font-bold hover:text-blue-600">+ Ajouter Skill</button>
                        </div>
                        <div class="space-y-4">
                            <h3 class="font-bold text-slate-400 uppercase text-xs">Langues</h3>
                            <template x-for="lang in allLangs" :key="lang.id">
                                <div class="bg-white p-2 border rounded flex justify-between items-center">
                                    <span class="text-sm font-bold" x-text="`${lang.label} : ${lang.level}`"></span>
                                    <div class="flex gap-2">
                                        <button @click="editItem = {type: 'lang', ...lang}" class="text-blue-400"><i class="fa-solid fa-pen"></i></button>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="delete_lang">
                                            <input type="hidden" name="id" :value="lang.id">
                                            <button type="submit" class="text-red-200">✕</button>
                                        </form>
                                    </div>
                                </div>
                            </template>
                            <button @click="editItem = {type: 'lang', id:'', label:'', level:''}" class="w-full border border-dashed p-2 text-xs text-slate-400 font-bold hover:text-blue-600">+ Ajouter Langue</button>
                        </div>
                    </div>

                    <!-- EDUCATION -->
                    <div x-show="section === 'education'" class="space-y-4">
                        <div class="grid gap-3">
                            <?php foreach($cv_edus as $e): ?>
                            <div class="bg-white p-4 rounded-xl border flex justify-between items-center group shadow-sm">
                                <div class="flex items-center">
                                    <span class="text-lg font-black text-slate-600"><?= htmlspecialchars($e['degree']) ?></span>
                                    <span class="text-slate-400 text-sm ml-3">@ <?= htmlspecialchars($e['institution']) ?></span>
                                    <span class="text-slate-300 text-xs ml-2"><?= htmlspecialchars($e['year']) ?></span>
                                </div>
                                <div class="flex items-center gap-2" x-data="{ confirming: false }">
                                    
                                    <template x-if="!confirming">
                                        <div class="flex gap-2">
                                            <button @click="prepEdit('edu', <?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" 
                                                    class="text-blue-500 hover:text-blue-700 p-2 transition">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            
                                            <button @click="confirming = true" class="text-slate-200 hover:text-red-500 p-2 transition">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="confirming">
                                        <div x-transition class="flex items-center gap-2 bg-red-50 border border-red-100 p-1 rounded-xl">
                                            <span class="text-[9px] font-black text-red-600 uppercase px-2 tracking-tighter">Supprimer ?</span>
                                            
                                            <form method="POST" action="?key=<?= $key ?>" style="display:inline">
                                                <input type="hidden" name="action" value="delete_edu">
                                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                                
                                                <button type="submit" class="bg-red-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-red-700 transition shadow-sm">
                                                    OUI
                                                </button>
                                            </form>

                                            <button @click="confirming = false" class="text-slate-400 text-[10px] font-bold hover:text-slate-600 px-2">
                                                NON
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button @click="prepEdit('edu', {id:'', degree:'', institution:'', year:'', icon:''})" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:text-blue-600 transition">+ Ajouter Formation</button>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'documents'" class="space-y-8" x-cloak>
                <?php
                // 1. Récupération des données BDD
                $dbDocs = $db->query("SELECT * FROM documents")->fetchAll();
                $dbFilenames = array_column($dbDocs, 'filename');

                // 2. Récupération des fichiers sur le Disque
                $docsPath = __DIR__ . '/../../storage/docs/';
                // Création "à la volée" si le dossier n'existe pas
                if (!is_dir($docsPath)) {
                    mkdir($docsPath, 0755, true);
                }
                $diskFiles = array_diff(scandir($docsPath), array('.', '..'));

                // 3. Calcul des anomalies
                // Dans la BDD mais absent du disque (Ghosts)
                $ghostDocs = array_filter($dbDocs, fn($d) => !in_array($d['filename'], $diskFiles));

                // Sur le disque mais absent de la BDD (Orphans)
                $orphanFiles = array_diff($diskFiles, $dbFilenames);

                if (!empty($ghostDocs) || !empty($orphanFiles)): ?>
                <div class="mb-10 p-6 bg-amber-50 border-2 border-amber-200 rounded-2xl">
                    <h4 class="text-amber-800 font-black flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        Diagnostic de Synchronisation
                    </h4>

                    <div class="space-y-4">
                        <?php foreach ($ghostDocs as $ghost): ?>
                        <div class="flex items-center justify-between bg-white/50 p-3 rounded-xl border border-amber-200">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-black rounded uppercase">Manquant sur Disque</span>
                                <span class="text-sm font-bold text-slate-700"><?= $ghost['label'] ?> (<?= $ghost['filename'] ?>)</span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="sync_clean_db">
                                <input type="hidden" name="filename" value="<?= $ghost['filename'] ?>">
                                <button type="submit" class="text-xs font-bold text-red-600 hover:underline">Supprimer l'entrée BDD</button>
                            </form>
                        </div>
                        <?php endforeach; ?>

                        <?php foreach ($orphanFiles as $orphan): ?>
                        <div class="flex items-center justify-between bg-white/50 p-3 rounded-xl border border-amber-200">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-black rounded uppercase">Orphelin sur Disque</span>
                                <span class="text-sm font-bold text-slate-700"><?= $orphan ?></span>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="action" value="sync_clean_disk">
                                <input type="hidden" name="filename" value="<?= $orphan ?>">
                                <button type="submit" class="text-xs font-bold text-blue-600 hover:underline">Supprimer le fichier</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-blue-500"></i> Ajouter un nouveau document
                    </h3>
                    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                        <input type="hidden" name="action" value="add_doc">
                        
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400">Libellé (ex: Diplôme HES)</label>
                            <input type="text" name="label" required class="w-full mt-1 border-b py-2 outline-none font-bold">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400">Catégorie</label>
                            <select name="category" class="w-full mt-1 border-b py-2 outline-none font-bold">
                                <option value="diploma">Diplôme / Certification</option>
                                <option value="reference">Référence Professionnelle</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400">Fichier (PDF uniquement)</label>
                            <input type="file" name="doc_file" accept=".pdf" required class="w-full mt-1 text-xs">
                        </div>

                        <button type="submit" class="md:col-span-3 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                            Télécharger le document
                        </button>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="doc in allDocs" :key="doc.id">
                        <div class="bg-white p-4 rounded-xl border flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-slate-50 rounded-lg group-hover:bg-blue-50 transition">
                                    <i :class="doc.category === 'diploma' ? 'fa-graduation-cap' : 'fa-file-signature'" class="fa-solid text-slate-400 group-hover:text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800" x-text="doc.label"></p>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest" x-text="doc.filename"></p>
                                </div>
                            </div>
                            
                            <div x-data="{ confirming: false }" class="flex items-center">
                                
                                <template x-if="!confirming">
                                    <button @click="confirming = true" class="text-slate-300 hover:text-red-500 p-2 transition">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </template>

                                <template x-if="confirming">
                                    <div x-transition class="flex items-center gap-2 bg-red-50 border border-red-100 p-1 rounded-xl shadow-inner">
                                        <span class="text-[9px] font-black text-red-600 uppercase px-2 tracking-tighter">Détruire ?</span>
                                        
                                        <form method="POST" action="?key=<?= $key ?>" style="display:inline">
                                            <input type="hidden" name="action" value="delete_doc">
                                            <input type="hidden" name="id" :value="doc.id">
                                            
                                            <button type="submit" class="bg-red-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-red-700 transition shadow-sm">
                                                OUI
                                            </button>
                                        </form>

                                        <button @click="confirming = false" class="text-slate-400 text-[10px] font-bold hover:text-slate-600 px-2 uppercase">
                                            NON
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div x-show="tab === 'stats'">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-black uppercase">Sessions de Consultation</h2>
                    <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Temps Réel</span>
                </div>

                <div class="grid gap-4">
                    <?php foreach ($telemetry_sessions as $s): ?>
                        <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:border-blue-300 transition flex items-center justify-between group">
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-7 gap-4 items-center">
                                
                                <div class="flex flex-col items-center border-r border-slate-50 pr-4">
                                    <?php 
                                        $score = $s['heat_score'];
                                        $colorClass = 'bg-slate-100 text-slate-400'; // Froid
                                        $label = 'Froid';

                                        if ($score > 25) {
                                            $colorClass = 'bg-red-500 text-white animate-pulse'; // Brûlant
                                            $label = 'Brûlant';
                                        } elseif ($score > 12) {
                                            $colorClass = 'bg-orange-100 text-orange-600'; // Chaud
                                            $label = 'Chaud';
                                        } elseif ($score > 5) {
                                            $colorClass = 'bg-blue-100 text-blue-600'; // Tiède
                                            $label = 'Tiède';
                                        }
                                    ?>
                                    <div class="<?= $colorClass ?> w-12 h-12 rounded-full flex items-center justify-center text-lg mb-1">
                                        <i class="fa-solid fa-fire"></i>
                                    </div>
                                    <span class="text-[8px] font-black uppercase tracking-tighter"><?= $label ?> (<?= round($score) ?>)</span>
                                </div>

                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Consulté le</p>
                                    <p class="text-xs font-bold text-slate-700"><?= date('d F Y / H:i', strtotime($s['started_at'])) ?></p>
                                    <?php if ($s['total_visits'] > 1): ?>
                                        <span class="bg-purple-100 text-purple-700 text-[9px] font-black px-2 py-0.5 rounded-full ml-2">
                                            FIDÈLE (<?= $s['total_visits'] ?> vis.)
                                        </span>
                                    <?php endif; ?>                                    
                                </div>

                                <div class="col-span-1">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Entreprise</p>
                                    <p class="text-sm font-black text-slate-800"><?= htmlspecialchars($s['company_name']) ?></p>
                                </div>
                                <div class="hidden md:block">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Appareil</p>
                                    <div class="flex items-center gap-2" title="<?= htmlspecialchars($s['user_agent']) ?>">
                                        <?php 
                                            $ua = $s['user_agent']; 
                                            $icon = "fa-solid fa-laptop"; // Défaut
                                            if (strpos($ua, 'Mobi') !== false) $icon = "fa-solid fa-mobile-screen-button";
                                            if (strpos($ua, 'Android') !== false) $icon = "fa-brands fa-android text-green-500";
                                            if (strpos($ua, 'iPhone') !== false || strpos($ua, 'Macintosh') !== false) $icon = "fa-brands fa-apple";
                                        ?>
                                        <i class="<?= $icon ?> text-slate-400"></i>
                                        <span class="text-[9px] text-slate-500 font-mono truncate max-w-[80px]">
                                            <?= explode(' ', $ua)[0] ?> </span>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Engagement</p>
                                    <div class="flex gap-2 justify-center">
                                        <span class="px-2 py-1 bg-slate-50 text-[10px] font-bold rounded-lg">
                                            <?= $s['download_count'] ?> <i class="fa-solid fa-file-pdf ml-1"></i>
                                        </span>
                                        <span class="px-2 py-1 bg-slate-50 text-[10px] font-bold rounded-lg">
                                            <?= gmdate("i:s", $s['duration_seconds']) ?> <i class="fa-solid fa-clock ml-1"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right pr-4">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Dernier signe de vie</p>
                                    <p class="text-xs font-bold text-slate-400 italic"><?= date('H:i:s', strtotime($s['last_event_at'])) ?></p>
                                </div>

                                <div>
                                    <div>
                                        <a href="?key=<?= $key ?>&module=session_detail&id=<?= $s['s_id_text'] ?>" target="_blank"
                                        class="flex items-center justify-center gap-2 bg-slate-900 text-white hover:bg-blue-600 px-4 py-3 rounded-xl transition font-bold text-xs uppercase">
                                            Analyse <i class="fa-solid fa-magnifying-glass-chart ml-1"></i>
                                        </a>
                                    </div>
                                    <div class="flex items-center justify-end" x-data="{ confirming: false }">
                                        <template x-if="!confirming">
                                            <div class="flex gap-2">
                                                <button @click="confirming = true" class="text-slate-200 hover:text-red-500 p-2">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="confirming">
                                            <div x-transition class="flex items-center gap-2 bg-red-50 border border-red-100 p-1 rounded-xl">
                                                <span class="text-[9px] font-black text-red-600 uppercase px-2">Supprimer ?</span>
                                                
                                                <form method="POST" action="?key=<?= $key ?>" style="display:inline">
                                                    <input type="hidden" name="action" value="delete_session">
                                                    <input type="hidden" name="id" value="<?= $s['s_id_text'] ?>">
                                                    <button type="submit" class="bg-red-600 text-white text-[10px] px-3 py-1.5 rounded-lg font-bold hover:bg-red-700 transition shadow-sm shadow-red-200">
                                                        OUI
                                                    </button>
                                                </form>

                                                <button @click="confirming = false" class="text-slate-400 text-[10px] font-bold hover:text-slate-600 px-2 uppercase tracking-tighter">
                                                    NON
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                </div>
                            </div>
                        </div>                        
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($orphelines_sessions)): ?>
                <div class="mt-12 pt-8 border-t border-slate-200">
                    <div class="flex items-center gap-3 mb-6 ml-2">
                        <h2 class="text-xs font-black text-amber-600 uppercase tracking-[0.3em]">Sessions Orphelines</h2>
                        <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-full border border-amber-200">
                            APPS SUPPRIMÉES
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-2">
                        <?php foreach ($orphelines_sessions as $s): ?>
                            <div class="flex items-center justify-between bg-white border border-slate-200 p-3 px-6 rounded-2xl hover:bg-amber-50/30 transition group shadow-sm">
                                <div class="flex items-center gap-8">
                                    <div class="w-24">
                                        <div class="text-[11px] font-black text-slate-900"><?= date('d.m.Y', strtotime($s['started_at'])) ?></div>
                                        <div class="text-[10px] font-bold text-slate-400 italic"><?= date('H:i:s', strtotime($s['started_at'])) ?></div>
                                    </div>

                                    <div class="hidden md:block">
                                        <p class="text-[9px] font-black text-slate-400 uppercase mb-0.5">Ancien ID App</p>
                                        <code class="text-[10px] bg-slate-100 px-2 py-1 rounded text-slate-600 font-mono">
                                            <?= $s['orphan_app_id'] ?>
                                        </code>
                                    </div>

                                    <div class="flex gap-3">
                                        <div class="text-[10px] font-black text-amber-600 bg-amber-50 px-3 py-1 rounded-full border border-amber-100">
                                            <?= $s['duration_seconds'] ?>s
                                        </div>
                                        <div class="text-[10px] font-black text-slate-500 bg-slate-100 px-3 py-1 rounded-full border border-slate-200">
                                            <?= $s['events_count'] ?> events
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4">
                                    <span class="hidden group-hover:block text-[9px] font-bold text-amber-600 uppercase tracking-tighter">Candidature inexistante</span>
                                    <a href="?key=<?= $key ?>&module=query_explorer&sql_key=orphan_events&session_id=<?= $s['s_id_text'] ?>" 
                                    target="_blank"
                                    class="text-[10px] font-black text-amber-600 hover:text-blue-600 uppercase tracking-widest transition flex items-center gap-2">
                                        Voir Événements <i class="fa-solid fa-magnifying-glass-chart text-[9px]"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="mt-4 ml-4 text-[10px] text-slate-400 italic">
                        <i class="fa-solid fa-info-circle mr-1"></i> 
                        Ces sessions appartiennent à des entreprises que tu as supprimées de ta base de données.
                    </p>
                </div>
                <?php endif; ?>                
            </div>

            <!-- DEBUG TAB -->
            <div x-show="tab === 'debug'" class="space-y-6">
                <h2 class="text-3xl font-black uppercase mb-6">🔧 Debug Console</h2>
                
                <!-- Last POST -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-xl font-bold mb-4">Last POST Request</h3>
                    <div class="bg-slate-900 text-green-400 p-4 rounded font-mono text-xs overflow-auto max-h-96">
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <div class="text-yellow-400"><strong>✓ POST Received</strong></div>
                            <div>Action: <span class="text-blue-400"><?= htmlspecialchars($_POST['action'] ?? 'N/A') ?></span></div>
                            <div>Message: <span class="text-green-300"><?= htmlspecialchars($message) ?></span></div>
                            <div class="mt-3 border-t border-slate-700 pt-3">
                                <strong>POST Data:</strong>
                                <pre><?= htmlspecialchars(json_encode($_POST, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-400">No POST request yet...</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alpine.js State -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-xl font-bold mb-4">Alpine.js State</h3>
                    <div class="bg-slate-900 text-blue-400 p-4 rounded font-mono text-xs">
                        <div>Tab: <span class="text-green-400" x-text="tab"></span></div>
                        <div>Section: <span class="text-green-400" x-text="section"></span></div>
                        <div>EditItem: <span class="text-green-400" x-text="JSON.stringify(editItem, null, 2)"></span></div>
                        <div>OpenModal: <span class="text-green-400" x-text="openModal"></span></div>
                    </div>
                </div>

                <!-- Quick Test -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h3 class="text-xl font-bold mb-4">Quick Test Functions</h3>
                    <div class="space-y-2">
                        <button @click="console.log('Alpine working'); alert('Alpine working ✓')" class="w-full bg-green-600 text-white py-2 rounded font-bold">Test Alpine.js</button>
                        <button @click="prepEdit('test', {id: 'DEBUG', label: 'Test Item', category: 'test'}); console.log('Modal should open'); console.log(editItem);" class="w-full bg-blue-600 text-white py-2 rounded font-bold">Test prepEdit()</button>
                        <button @click="editItem = {}; console.log('Modal closed')" class="w-full bg-gray-600 text-white py-2 rounded font-bold">Close Modal</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL D'ÉDITION CV (fusionné) -->
    <div x-show="editItem && editItem.type" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-[200] flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white w-full max-w-2xl rounded-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black uppercase tracking-tighter" x-text="editItem ? (editItem.id ? 'Modifier ' + editItem.type : 'Ajouter ' + editItem.type) : ''"></h3>
                <button type="button" @click="editItem = {}" class="text-slate-300 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" :value="editItem ? (editItem.id ? 'update_' + editItem.type : 'add_' + editItem.type) : ''">
                <input type="hidden" name="id" :value="editItem ? editItem.id : ''">

                <!-- Expérience -->
                <div x-show="editItem && editItem.type === 'exp'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="company" x-model="editItem.company" placeholder="Entreprise" class="border p-3 rounded-xl w-full focus:border-blue-500 outline-none">
                        <input type="text" name="role" x-model="editItem.role" placeholder="Poste" class="border p-3 rounded-xl w-full focus:border-blue-500 outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="location" x-model="editItem.location" placeholder="Lieu" class="border p-3 rounded-xl w-full">
                        <input type="text" name="period" x-model="editItem.period" placeholder="Période" class="border p-3 rounded-xl w-full">
                    </div>
                    <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full bg-slate-50">
                        <option value="ops">Opérations</option>
                        <option value="management">Management</option>
                        <option value="tech">Technique</option>
                    </select>
                    <textarea name="content" x-model="editItem.content" placeholder="Description (une puce par ligne)..." rows="6" class="border p-3 rounded-xl w-full text-sm focus:border-blue-500 outline-none"></textarea>
                </div>

                <!-- Skill -->
                <div x-show="editItem && editItem.type === 'skill'" class="space-y-4">
                    <input type="text" name="label" x-model="editItem.label" placeholder="Compétence" class="border p-3 rounded-xl w-full">
                    <input type="text" name="level_text" x-model="editItem.level_text" placeholder="Niveau" class="border p-3 rounded-xl w-full">
                    <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full bg-slate-50">
                        <option value="management">Management</option>
                        <option value="ops">Opérations</option>
                        <option value="tech">Technique</option>
                    </select>
                </div>

                <!-- Education -->
                <div x-show="editItem && editItem.type === 'edu'" class="space-y-4">
                    <input type="text" name="degree" x-model="editItem.degree" placeholder="Diplôme" class="border p-3 rounded-xl w-full">
                    <input type="text" name="institution" x-model="editItem.institution" placeholder="École" class="border p-3 rounded-xl w-full">
                    <input type="text" name="year" x-model="editItem.year" placeholder="Année" class="border p-3 rounded-xl w-full">
                    <input type="hidden" name="icon" x-model="editItem.icon" value="">
                </div>

                <!-- Langue -->
                <div x-show="editItem && editItem.type === 'lang'" class="space-y-4">
                    <input type="text" name="label" x-model="editItem.label" placeholder="Langue (ex: Français)" class="border p-3 rounded-xl w-full">
                    <input type="text" name="level" x-model="editItem.level" placeholder="Niveau (ex: Natif)" class="border p-3 rounded-xl w-full">
                </div>

                <!-- Application -->
                <div x-show="editItem && editItem.type === 'app'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="company_name" x-model="editItem.company_name" placeholder="Entreprise" class="border p-3 rounded-xl w-full focus:border-blue-500 outline-none">
                        <input type="text" name="slug" x-model="editItem.slug" placeholder="Slug (/go/...)" class="border p-3 rounded-xl w-full focus:border-blue-500 outline-none">
                    </div>
                    <input type="text" name="job_title" x-model="editItem.job_title" placeholder="Poste" class="border p-3 rounded-xl w-full">
                    <input type="url" name="job_url" x-model="editItem.job_url" placeholder="URL de l'offre (optionnel)" class="border p-3 rounded-xl w-full">
                    <textarea name="custom_pitch" x-model="editItem.custom_pitch" placeholder="Pitch personnalisé..." rows="4" class="border p-3 rounded-xl w-full text-sm"></textarea>
                    <div class="space-y-3">
                        <textarea name="why_me" x-model="editItem.why_me" placeholder="Pourquoi moi ? (icône:Présentation personnelle)" rows="4" class="border p-3 rounded-xl w-full text-sm focus:border-blue-500 outline-none"></textarea>
                        
                        <textarea name="strengths" x-model="editItem.strengths" placeholder="Mes forces et compétences clés..." rows="4" class="border p-3 rounded-xl w-full text-sm focus:border-blue-500 outline-none"></textarea>
                        
                        <textarea name="perfect_match" x-model="editItem.perfect_match" placeholder="Pourquoi nous sommes un bon match..." rows="4" class="border p-3 rounded-xl w-full text-sm focus:border-blue-500 outline-none"></textarea>
                    </div>
                    <div class="mt-8 border-t pt-6">
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest">
                            Documents joints à ce dossier
                        </label>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <template x-for="doc in allDocs" :key="doc.id">
                                <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition"
                                    :class="editItem.doc_ids.includes(doc.id) ? 'bg-blue-50 border-blue-200' : 'bg-white border-slate-100 hover:border-slate-300'">
                                    
                                    <input type="checkbox" :value="doc.id" x-model="editItem.doc_ids" name="selected_docs[]" class="w-4 h-4 rounded text-blue-600 border-slate-300 focus:ring-blue-500">
                                    
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid text-sm" :class="doc.category === 'diploma' ? 'fa-graduation-cap text-blue-500' : 'fa-file-signature text-slate-400'"></i>
                                        <span class="text-xs font-bold text-slate-700" x-text="doc.label"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>                    
                    <div class="mt-8 border-t pt-6">
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest">
                            Statut du dossier
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <select name="default_lens" x-model="editItem.default_lens" class="border p-3 rounded-xl w-full bg-slate-50">
                                <option value="ops">Opérations</option>
                                <option value="management">Management</option>
                                <option value="tech">Technique</option>
                            </select>
                            <select name="status" x-model="editItem.status" class="border p-3 rounded-xl w-full bg-slate-50">
                                <option value="sent">Envoyé</option>
                                <option value="interview">Entretien</option>
                                <option value="rejected">Rejeté</option>
                                <option value="accepted">Accepté</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t pt-6">
                    <div class="grid grid-cols-2 gap-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg hover:bg-blue-700 transition" x-text="editItem ? (editItem.id ? 'Appliquer les modifications' : 'Créer') : ''"></button>
                        <button type="button" @click="editItem = {}" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg hover:bg-blue-700 transition">Annuler</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html>