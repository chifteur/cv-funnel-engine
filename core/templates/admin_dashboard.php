<?php
/**
 * MANGANESE OS - DASHBOARD CENTRALISÉ
 * Gère les candidatures, le CRM et inclut l'éditeur de CV
 */

$db = get_db_connection();
$message = "";

// --- 1. TRAITEMENT DES ACTIONS GLOBALES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // PROFIL MASTER
        if ($action === 'update_profile') {
            $stmt = $db->prepare("UPDATE profile_settings SET full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=? WHERE id=1");
            $stmt->execute([$_POST['full_name'], $_POST['job_title'], $_POST['bio'], $_POST['email'], $_POST['phone'], $_POST['linkedin_url'], $_POST['photo_path']]);
            $message = "✅ Profil Master mis à jour.";
        }

        // AJOUT CANDIDATURE
        if ($action === 'add_app') {
            $stmt = $db->prepare("INSERT INTO applications (slug, company_name, job_title, custom_pitch, default_lens, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['custom_pitch'], $_POST['default_lens']]);
            $message = "🚀 Nouveau lien /go/ généré.";
        }

        // AJOUT CRM
        if ($action === 'add_crm') {
            $stmt = $db->prepare("INSERT INTO crm_events (app_id, type, comment, event_date) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_POST['app_id'], $_POST['type'], $_POST['comment']]);
            $message = "📅 Événement CRM enregistré.";
        }
    } catch (Exception $e) {
        $message = "❌ Erreur : " . $e->getMessage();
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
$apps = $db->query("SELECT a.*, (SELECT COUNT(*) FROM telemetry_sessions WHERE app_id = a.id) as visits FROM applications a ORDER BY created_at DESC")->fetchAll();
$telemetry_logs = $db->query("SELECT e.*, a.company_name FROM telemetry_events e JOIN telemetry_sessions s ON e.session_id = s.id JOIN applications a ON s.app_id = a.id ORDER BY e.created_at DESC LIMIT 20")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" x-data="{ tab: 'apps', openCrm: null }">
<head>
    <meta charset="UTF-8">
    <title>Manganese OS | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans text-slate-900">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-slate-900 text-white p-6 sticky top-0 h-screen shadow-xl">
            <div class="text-2xl font-black mb-12 tracking-tighter text-blue-500 italic">MANGANESE<span class="text-white">OS</span></div>
            <nav class="space-y-2">
                <button @click="tab = 'apps'" :class="tab === 'apps' ? 'bg-blue-600' : 'hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3">
                    <i class="fa-solid fa-paper-plane w-5"></i> Candidatures
                </button>
                <button @click="tab = 'cv'" :class="tab === 'cv' ? 'bg-blue-600' : 'hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3">
                    <i class="fa-solid fa-user-pen w-5"></i> Éditeur de CV
                </button>
                <button @click="tab = 'stats'" :class="tab === 'stats' ? 'bg-blue-600' : 'hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3">
                    <i class="fa-solid fa-bolt w-5"></i> Télémétrie
                </button>
            </nav>
        </aside>

        <main class="flex-1 p-10">
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-white border-l-4 border-blue-500 shadow-md rounded font-bold text-sm"><?= $message ?></div>
            <?php endif; ?>

            <div x-show="tab === 'apps'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-3xl font-black uppercase tracking-tight">Postulations</h2>
                    <button @click="openCrm = 'new_app'" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold">+ Nouveau Lien</button>
                </div>
                <div class="grid gap-4">
                    <?php foreach ($apps as $app): ?>
                    <div class="bg-white p-6 rounded-xl border shadow-sm flex justify-between items-center">
                        <div>
                            <h3 class="font-black text-lg"><?= htmlspecialchars($app['company_name']) ?></h3>
                            <p class="text-slate-400 text-sm"><?= htmlspecialchars($app['job_title']) ?></p>
                            <a href="/go/<?= $app['slug'] ?>" target="_blank" class="text-blue-500 font-mono text-xs">/go/<?= $app['slug'] ?></a>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black"><?= $app['visits'] ?></span>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Sessions</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div x-show="tab === 'cv'">
                <?php include 'admin_cv_editor.php'; ?>
            </div>

            <div x-show="tab === 'stats'">
                <h2 class="text-3xl font-black uppercase mb-6">Télémétrie Live</h2>
                <div class="bg-slate-900 text-blue-400 p-6 rounded-2xl font-mono text-xs shadow-2xl h-[600px] overflow-y-auto">
                    <?php foreach ($telemetry_logs as $log): ?>
                        <div class="mb-2 border-b border-slate-800 pb-2">
                            <span class="text-slate-500">[<?= $log['created_at'] ?>]</span> 
                            <span class="text-white font-bold"><?= $log['company_name'] ?> :</span> 
                            <?= htmlspecialchars($log['type']) ?> -> <?= htmlspecialchars($log['data']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <template x-if="openCrm === 'new_app'">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-6">
            <div class="bg-white p-8 rounded-2xl w-full max-w-lg shadow-2xl">
                <h3 class="text-2xl font-black mb-6">Générer un lien personnalisé</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_app">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="company_name" placeholder="Entreprise" class="border p-3 rounded-lg w-full" required>
                        <input type="text" name="slug" placeholder="Slug (ex: jenov)" class="border p-3 rounded-lg w-full font-mono" required>
                    </div>
                    <input type="text" name="job_title" placeholder="Titre du poste" class="border p-3 rounded-lg w-full">
                    <select name="default_lens" class="border p-3 rounded-lg w-full bg-slate-50">
                        <option value="ops">Ops / Agile</option>
                        <option value="management">Management</option>
                        <option value="tech">Technique</option>
                    </select>
                    <textarea name="custom_pitch" placeholder="Pitch..." rows="4" class="border p-3 rounded-lg w-full text-sm"></textarea>
                    <div class="flex gap-4">
                        <button type="button" @click="openCrm = null" class="flex-1 border p-3 rounded-lg font-bold">Annuler</button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white p-3 rounded-lg font-bold">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</body>
</html>