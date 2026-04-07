<?php
/**
 * MANGANESE OS - DASHBOARD CENTRALISÉ
 * Gère TOUTES les actions POST et la structure globale
 */

$db = get_db_connection();
$message = "";

// --- 1. TRAITEMENT DE TOUTES LES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // PROFIL MASTER
        if ($action === 'update_profile') {
            $stmt = $db->prepare("UPDATE profile_settings SET full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=? WHERE id=1");
            $stmt->execute([$_POST['full_name'], $_POST['job_title'], $_POST['bio'], $_POST['email'], $_POST['phone'], $_POST['linkedin_url'], $_POST['photo_path']]);
            $message = "✅ Profil Master mis à jour.";
        }

        // CANDIDATURES /GO/
        if ($action === 'add_app') {
            $stmt = $db->prepare("INSERT INTO applications (slug, company_name, job_title, custom_pitch, default_lens, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['custom_pitch'], $_POST['default_lens']]);
            $message = "🚀 Nouveau lien généré.";
        }

        // --- CRUD CV : EXPÉRIENCES ---
        if ($action === 'add_exp') {
            $stmt = $db->prepare("INSERT INTO cv_experiences (company, role, location, period, content, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category']]);
        }
        if ($action === 'update_exp') {
            $stmt = $db->prepare("UPDATE cv_experiences SET company=?, role=?, location=?, period=?, content=?, category=? WHERE id=?");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category'], $_POST['id']]);
        }
        if ($action === 'delete_exp') {
            $stmt = $db->prepare("DELETE FROM cv_experiences WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        // --- CRUD CV : SKILLS ---
        if ($action === 'add_skill') {
            $stmt = $db->prepare("INSERT INTO cv_skills (category, label, level_text) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['category'], $_POST['label'], $_POST['level_text']]);
        }
        if ($action === 'update_skill') {
            $stmt = $db->prepare("UPDATE cv_skills SET category=?, label=?, level_text=? WHERE id=?");
            $stmt->execute([$_POST['category'], $_POST['label'], $_POST['level_text'], $_POST['id']]);
        }
        if ($action === 'delete_skill') {
            $stmt = $db->prepare("DELETE FROM cv_skills WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        // --- CRUD CV : EDUCATION ---
        if ($action === 'add_edu') {
            $stmt = $db->prepare("INSERT INTO cv_education (degree, institution, year, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon']]);
        }
        if ($action === 'update_edu') {
            $stmt = $db->prepare("UPDATE cv_education SET degree=?, institution=?, year=?, icon=? WHERE id=?");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon'], $_POST['id']]);
        }
        if ($action === 'delete_edu') {
            $stmt = $db->prepare("DELETE FROM cv_education WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

    } catch (Exception $e) {
        $message = "❌ Erreur SQL : " . $e->getMessage();
    }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
$apps = $db->query("SELECT a.*, (SELECT COUNT(*) FROM telemetry_sessions WHERE app_id = a.id) as visits FROM applications a ORDER BY created_at DESC")->fetchAll();
$telemetry_logs = $db->query("
    SELECT e.*, a.company_name 
    FROM telemetry_events e 
    JOIN telemetry_sessions s ON e.session_id = s.id 
    JOIN applications a ON s.app_id = a.id 
    ORDER BY e.created_at DESC LIMIT 20
")->fetchAll();

// --- 3. DONNÉES CV EDITOR (fusionné) ---
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$cv_langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
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
<body class="bg-slate-50 font-sans text-slate-900" x-data="{ 
    tab: 'apps',
    section: 'profile',
    openModal: null, 
    editItem: null,
    prepEdit(type, data = {}) {
        this.editItem = { type: type, ...data };
    }
}">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-slate-900 text-white p-6 sticky top-0 h-screen">
            <div class="text-2xl font-black mb-12 tracking-tighter text-blue-500 italic">MANGANESE<span class="text-white">OS</span></div>
            <nav class="space-y-2">
                <button @click="tab = 'apps'" :class="tab === 'apps' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-paper-plane w-5"></i> Candidatures
                </button>
                <button @click="tab = 'cv'" :class="tab === 'cv' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-user-pen w-5"></i> Éditeur de CV
                </button>
                <button @click="tab = 'stats'" :class="tab === 'stats' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-bolt w-5"></i> Télémétrie
                </button>
            </nav>
        </aside>

        <main class="flex-1 p-10">
            <?php if ($message): ?>
                <div class="mb-6 p-4 bg-white border-l-4 border-blue-500 shadow-sm rounded font-bold text-sm"><?= $message ?></div>
            <?php endif; ?>

            <div x-show="tab === 'apps'" class="space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-3xl font-black uppercase">Postulations</h2>
                    <button @click="openModal = 'new_app'" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow-lg hover:bg-blue-700 transition">+ Nouveau Lien</button>
                </div>
                <div class="grid gap-4">
                    <?php foreach ($apps as $app): ?>
                    <div class="bg-white p-6 rounded-xl border flex justify-between items-center shadow-sm">
                        <div>
                            <h3 class="font-black text-lg"><?= htmlspecialchars($app['company_name']) ?></h3>
                            <p class="text-slate-400 text-sm"><?= htmlspecialchars($app['job_title']) ?></p>
                            <a href="/go/<?= $app['slug'] ?>" target="_blank" class="text-blue-500 font-mono text-xs">/go/<?= $app['slug'] ?></a>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-slate-800"><?= $app['visits'] ?></span>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sessions</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                        <form method="POST" class="grid grid-cols-2 gap-6">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Nom Complet</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none font-bold">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Titre</label>
                                <input type="text" name="job_title" value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none font-bold">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black uppercase text-slate-400">Bio / Summary</label>
                                <textarea name="bio" rows="4" class="w-full mt-1 border rounded-xl p-3 text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="col-span-2 bg-slate-900 text-white py-3 rounded-full font-bold hover:bg-blue-600 transition">Sauvegarder Profil</button>
                        </form>
                    </div>

                    <!-- EXPERIENCES -->
                    <div x-show="section === 'experiences'" class="space-y-4">
                        <div class="grid gap-3">
                            <?php foreach($cv_exps as $e): ?>
                            <div class="bg-white p-4 rounded-xl border flex justify-between items-center group shadow-sm">
                                <div class="flex items-center">
                                    <span class="text-[9px] font-black px-2 py-1 bg-slate-100 rounded uppercase text-slate-400"><?= $e['category'] ?></span>
                                    <h4 class="font-bold ml-3"><?= htmlspecialchars($e['role']) ?> @ <?= htmlspecialchars($e['company']) ?></h4>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="prepEdit('exp', <?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-500 hover:text-blue-700 p-2"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="delete_exp">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
                            <?php foreach ($cv_langs as $l): ?>
                                <div class="bg-white p-2 border rounded flex justify-between items-center">
                                    <span class="text-sm font-bold"><?= htmlspecialchars($l['label']) ?> : <?= htmlspecialchars($l['level']) ?></span>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="delete_lang">
                                        <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                        <button type="submit" class="text-red-200">✕</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
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
                                <div class="flex gap-2">
                                    <button @click="prepEdit('edu', <?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-500 hover:text-blue-700 p-2"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="delete_edu">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button @click="prepEdit('edu', {id:'', degree:'', institution:'', year:'', icon:''})" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:text-blue-600 transition">+ Ajouter Formation</button>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'stats'">
                <h2 class="text-3xl font-black uppercase mb-6">Activité Live</h2>
                <div class="bg-slate-900 text-blue-400 p-6 rounded-2xl font-mono text-xs shadow-2xl h-[600px] overflow-y-auto">
                    <?php foreach ($telemetry_logs as $log): ?>
                        <div class="mb-2 border-b border-slate-800 pb-2">
                            <span class="text-slate-500">[<?= substr($log['created_at'], 11, 8) ?>]</span> 
                            <span class="text-white font-bold"><?= $log['company_name'] ?> :</span> 
                            <?= htmlspecialchars($log['type']) ?> -> <?= htmlspecialchars($log['data']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <template x-if="openModal === 'new_app'">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-6">
            <div class="bg-white p-8 rounded-2xl w-full max-w-lg shadow-2xl">
                <h3 class="text-2xl font-bold mb-6">Créer un lien personnalisé</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_app">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="company_name" placeholder="Entreprise" class="border p-3 rounded-lg w-full" required>
                        <input type="text" name="slug" placeholder="Slug (ex: jenov)" class="border p-3 rounded-lg w-full font-mono" required>
                    </div>
                    <input type="text" name="job_title" placeholder="Titre du poste" class="border p-3 rounded-lg w-full">
                    <select name="default_lens" class="border p-3 rounded-lg w-full bg-slate-50">
                        <option value="ops">Angle : Opérations / Agile</option>
                        <option value="management">Angle : Management / Direction</option>
                        <option value="tech">Angle : Architecture / Technique</option>
                    </select>
                    <textarea name="custom_pitch" placeholder="Pitch personnalisé..." rows="4" class="border p-3 rounded-lg w-full text-sm"></textarea>
                    <div class="flex gap-4">
                        <button type="button" @click="openModal = null" class="flex-1 border p-3 rounded-lg font-bold">Annuler</button>
                        <button type="submit" class="flex-1 bg-blue-600 text-white p-3 rounded-lg font-bold">Générer</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- MODAL D'ÉDITION CV (fusionné) -->
    <template x-if="editItem">
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-[200] flex items-center justify-center p-4">
            <div @click.away="editItem = null" class="bg-white w-full max-w-2xl rounded-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-black uppercase tracking-tighter" x-text="editItem.id ? 'Modifier ' + editItem.type : 'Ajouter ' + editItem.type"></h3>
                    <button @click="editItem = null" class="text-slate-300 hover:text-slate-600 text-2xl font-bold">&times;</button>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" :value="editItem.id ? 'update_' + editItem.type : 'add_' + editItem.type">
                    <input type="hidden" name="id" :value="editItem.id">

                    <!-- Expérience -->
                    <template x-if="editItem.type === 'exp'">
                        <div class="space-y-4">
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
                    </template>

                    <!-- Skill -->
                    <template x-if="editItem.type === 'skill'">
                        <div class="space-y-4">
                            <input type="text" name="label" x-model="editItem.label" placeholder="Compétence" class="border p-3 rounded-xl w-full">
                            <input type="text" name="level_text" x-model="editItem.level_text" placeholder="Niveau" class="border p-3 rounded-xl w-full">
                            <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full bg-slate-50">
                                <option value="management">Management</option>
                                <option value="ops">Ops</option>
                                <option value="tech">Tech</option>
                            </select>
                        </div>
                    </template>

                    <!-- Education -->
                    <template x-if="editItem.type === 'edu'">
                        <div class="space-y-4">
                            <input type="text" name="degree" x-model="editItem.degree" placeholder="Diplôme" class="border p-3 rounded-xl w-full">
                            <input type="text" name="institution" x-model="editItem.institution" placeholder="École" class="border p-3 rounded-xl w-full">
                            <input type="text" name="year" x-model="editItem.year" placeholder="Année" class="border p-3 rounded-xl w-full">
                            <input type="hidden" name="icon" x-model="editItem.icon" value="">
                        </div>
                    </template>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg hover:bg-blue-700 transition" x-text="editItem.id ? 'Appliquer les modifications' : 'Créer'"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</body>
</html>