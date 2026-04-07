<?php
/**
 * MANGANESE OS - BACK-OFFICE COMPLET
 * Gestion des candidatures, CRM, Éditeur de CV & Télémétrie
 */

$db = get_db_connection();
$message = "";

// --- 1. TRAITEMENT DES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // Sauvegarde du Profil Master
        if ($action === 'update_profile') {
            $stmt = $db->prepare("UPDATE profile_settings SET full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=? WHERE id=1");
            $stmt->execute([$_POST['full_name'], $_POST['job_title'], $_POST['bio'], $_POST['email'], $_POST['phone'], $_POST['linkedin_url'], $_POST['photo_path']]);
            $message = "✅ Profil mis à jour avec succès.";
        }

        // Ajout d'une Candidature (/go/...)
        if ($action === 'add_app') {
            $stmt = $db->prepare("INSERT INTO applications (slug, company_name, job_title, custom_pitch, default_lens, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['custom_pitch'], $_POST['default_lens']]);
            $message = "🚀 Nouveau lien /go/ généré.";
        }

        // Ajout d'un événement CRM
        if ($action === 'add_crm') {
            $stmt = $db->prepare("INSERT INTO crm_events (app_id, type, comment, event_date) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_POST['app_id'], $_POST['type'], $_POST['comment']]);
            $message = "📅 Événement CRM enregistré.";
        }

        // Ajout d'une Expérience
        if ($action === 'add_exp') {
            $stmt = $db->prepare("INSERT INTO cv_experiences (company, role, location, period, content, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category']]);
            $message = "💼 Expérience ajoutée au CV Master.";
        }
    } catch (Exception $e) {
        $message = "❌ Erreur : " . $e->getMessage();
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
// Candidatures avec stats de sessions réelles
$apps = $db->query("
    SELECT a.*, COUNT(s.id) as session_count 
    FROM applications a 
    LEFT JOIN telemetry_sessions s ON a.id = s.app_id 
    GROUP BY a.id ORDER BY a.created_at DESC
")->fetchAll();

// Éléments du CV pour l'éditeur
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();

// Télémétrie : On récupère les 20 derniers événements avec le nom de la boîte
$telemetry_feed = $db->query("
    SELECT e.*, a.company_name 
    FROM telemetry_events e 
    JOIN telemetry_sessions s ON e.session_id = s.id 
    JOIN applications a ON s.app_id = a.id 
    ORDER BY e.created_at DESC 
    LIMIT 50
")->fetchAll();?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Manganese OS | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans text-slate-900" x-data="{ tab: 'apps', openCrm: null }">

    <div class="flex min-h-screen">
        
        <aside class="w-64 bg-slate-900 text-white p-6 sticky top-0 h-screen">
            <div class="text-2xl font-black mb-12 tracking-tighter text-blue-500">MANGANESE<span class="text-white">OS</span></div>
            <nav class="space-y-2">
                <button @click="tab = 'apps'" :class="tab === 'apps' ? 'bg-blue-600' : 'hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3">
                    <i class="fa-solid fa-paper-plane w-5"></i> Candidatures
                </button>
                <button @click="tab = 'cv'" :class="tab === 'cv' ? 'bg-blue-600' : 'hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3">
                    <i class="fa-solid fa-file-signature w-5"></i> Éditeur de CV
                </button>
                <button @click="tab = 'stats'" :class="tab === 'stats' ? 'bg-blue-600' : 'hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3">
                    <i class="fa-solid fa-chart-line w-5"></i> Télémétrie
                </button>
            </nav>
            <div class="absolute bottom-6 left-6 text-xs text-slate-500 font-mono">v1.0-stable</div>
        </aside>

        <main class="flex-1 p-10">
            
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-white border-l-4 border-green-500 shadow-sm rounded-r flex justify-between items-center">
                    <span class="font-medium"><?= $message ?></span>
                    <button class="text-slate-400" onclick="this.parentElement.remove()">✕</button>
                </div>
            <?php endif; ?>

            <div x-show="tab === 'apps'" class="space-y-8">
                <div class="flex justify-between items-center">
                    <h2 class="text-3xl font-black uppercase tracking-tight">Suivi des Postulations</h2>
                    <button @click="openCrm = 'new_app'" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold hover:bg-blue-700 transition">+ Nouveau Lien /go/</button>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($apps as $app): ?>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold"><?= htmlspecialchars($app['company_name']) ?></h3>
                                <p class="text-slate-500 text-sm"><?= htmlspecialchars($app['job_title']) ?></p>
                                <a href="/go/<?= $app['slug'] ?>" target="_blank" class="text-blue-500 text-xs font-mono font-bold hover:underline">manganese.ch/go/<?= $app['slug'] ?></a>
                            </div>
                            <div class="text-right">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-black"><?= $app['visits'] ?> VISITES</span>
                                <div class="mt-4 flex gap-2">
                                    <button @click="openCrm = <?= $app['id'] ?>" class="text-xs font-bold uppercase text-slate-400 hover:text-blue-600"><i class="fa-solid fa-calendar-plus mr-1"></i> Log CRM</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div x-show="tab === 'cv'" class="space-y-8">
                <h2 class="text-3xl font-black uppercase tracking-tight">Éditeur de CV Dynamique</h2>
                
                <form method="POST" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                    <input type="hidden" name="action" value="update_profile">
                    <h3 class="text-lg font-bold mb-6 border-b pb-2">Identité & Photo</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase">Nom Complet</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" class="w-full mt-1 border-b p-2 focus:border-blue-600 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase">Titre Professionnel</label>
                            <input type="text" name="job_title" value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>" class="w-full mt-1 border-b p-2 focus:border-blue-600 outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase">Bio / Executive Summary</label>
                            <textarea name="bio" rows="3" class="w-full mt-1 border p-3 rounded-lg text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase">Email de contact</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" class="w-full mt-1 border-b p-2 focus:border-blue-600 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase">Photo (chemin FTP)</label>
                            <input type="text" name="photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" class="w-full mt-1 border-b p-2 text-xs font-mono">
                        </div>
                        <div class="col-span-2 text-right">
                            <button type="submit" class="bg-slate-900 text-white px-8 py-2 rounded-full font-bold hover:bg-blue-600 transition">Sauvegarder mon profil</button>
                        </div>
                    </div>
                </form>

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold">Parcours Professionnel</h3>
                        <button @click="openCrm = 'new_exp'" class="text-xs bg-blue-50 text-blue-600 px-3 py-1 rounded-lg font-bold hover:bg-blue-100">+ Ajouter</button>
                    </div>

                    <div class="grid gap-3">
                        <?php if (!empty($exps)): ?>
                            <?php foreach ($exps as $exp): ?>
                                <div class="bg-white p-4 rounded-lg border border-slate-200 flex justify-between items-center group">
                                    <div class="flex items-center gap-4">
                                        <span class="text-[10px] font-bold px-2 py-1 bg-slate-100 rounded uppercase text-slate-500">
                                            <?= htmlspecialchars($exp['category'] ?? 'ops') ?>
                                        </span>
                                        <div>
                                            <h4 class="font-bold text-slate-800"><?= htmlspecialchars($exp['role'] ?? $exp['title']) ?></h4>
                                            <p class="text-xs text-slate-400"><?= htmlspecialchars($exp['company']) ?> | <?= htmlspecialchars($exp['period'] ?? '') ?></p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST" onsubmit="return confirm('Supprimer cette expérience ?');">
                                            <input type="hidden" name="action" value="delete_exp">
                                            <input type="hidden" name="exp_id" value="<?= $exp['id'] ?>">
                                            <button type="submit" class="text-slate-300 hover:text-red-500 transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-slate-400 italic">Aucune expérience enregistrée.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'stats'" class="space-y-8">
                <h2 class="text-3xl font-black uppercase tracking-tight">Télémétrie Avancée</h2>
                <div class="grid grid-cols-3 gap-6">
                    <div class="col-span-2 bg-white p-8 rounded-2xl shadow-sm border">
                        <canvas id="mainStatsChart"></canvas>
                    </div>
                    <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-xl overflow-hidden">
                        <h4 class="text-xs font-bold uppercase text-slate-500 mb-4">Flux en temps réel</h4>
                        <div class="space-y-4 text-[10px] font-mono opacity-80 overflow-y-auto max-h-[400px]">
                            <?php foreach ($telemetry_feed as $log): ?>
                                <div class="border-b border-slate-800 pb-2">
                                    <span class="text-blue-400">[<?= substr($log['created_at'], 11, 5) ?>]</span> 
                                    <span class="text-slate-200"><?= htmlspecialchars($log['company_name']) ?> :</span> 
                                    <?= htmlspecialchars($log['type']) ?> (<?= htmlspecialchars($log['data']) ?>)
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <template x-if="openCrm === 'new_app'">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center p-6">
            <div class="bg-white w-full max-w-lg rounded-2xl p-8 shadow-2xl">
                <h3 class="text-2xl font-bold mb-6">Créer un lien personnalisé</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_app">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="company_name" placeholder="Entreprise (ex: J-eNOV)" class="border p-3 rounded-lg w-full" required>
                        <input type="text" name="slug" placeholder="Slug (ex: jenov)" class="border p-3 rounded-lg w-full font-mono" required>
                    </div>
                    <input type="text" name="job_title" placeholder="Titre du poste exact" class="border p-3 rounded-lg w-full">
                    <select name="default_lens" class="border p-3 rounded-lg w-full bg-slate-50">
                        <option value="ops">Angle : Opérations / Agile</option>
                        <option value="management">Angle : Management / Direction</option>
                        <option value="tech">Angle : Architecture / Technique</option>
                    </select>
                    <textarea name="custom_pitch" placeholder="Pitch personnalisé pour cette entreprise..." rows="4" class="border p-3 rounded-lg w-full text-sm"></textarea>
                    <div class="flex gap-4">
                        <button type="button" @click="openCrm = null" class="flex-1 border p-3 rounded-lg font-bold">Annuler</button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white p-3 rounded-lg font-bold">Générer le lien</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <script>
        // Graphique de Télémétrie
        const ctx = document.getElementById('mainStatsChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'],
                datasets: [{
                    label: 'Visites cumulées',
                    data: [12, 19, 3, 5, 2, 3, 10],
                    borderColor: '#2563eb',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(37, 99, 235, 0.05)'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>