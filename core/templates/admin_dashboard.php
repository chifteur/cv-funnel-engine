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
            $stmt = $db->prepare("UPDATE profile_settings SET full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=? WHERE id=1");
            $stmt->execute([$_POST['full_name'] ?? '', $_POST['job_title'] ?? '', $_POST['bio'] ?? '', $_POST['email'] ?? '', $_POST['phone'] ?? '', $_POST['linkedin_url'] ?? '', $_POST['photo_path'] ?? '']);
            $message = "✅ Profil Master mis à jour.";
        }

        // CANDIDATURES /GO/
        if ($action === 'add_app') {
            $stmt = $db->prepare("INSERT INTO applications (slug, company_name, job_title, job_url, custom_pitch, why_me, strengths, perfect_match, default_lens, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['job_url'] ?? '', $_POST['custom_pitch'] ?? '', $_POST['why_me'] ?? '', $_POST['strengths'] ?? '', $_POST['perfect_match'] ?? '', $_POST['default_lens'], $_POST['status'] ?? 'sent']);
            $message = "🚀 Candidature créée.";
        }
        if ($action === 'update_app') {
            $stmt = $db->prepare("UPDATE applications SET slug=?, company_name=?, job_title=?, job_url=?, custom_pitch=?, why_me=?, strengths=?, perfect_match=?, default_lens=?, status=? WHERE id=?");
            $stmt->execute([$_POST['slug'], $_POST['company_name'], $_POST['job_title'], $_POST['job_url'] ?? '', $_POST['custom_pitch'] ?? '', $_POST['why_me'] ?? '', $_POST['strengths'] ?? '', $_POST['perfect_match'] ?? '', $_POST['default_lens'], $_POST['status'], $_POST['id']]);
            $message = "✅ Candidature mise à jour.";
        }
        if ($action === 'delete_app') {
            $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "✅ Candidature supprimée.";
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
            $message = "✅ Expérience mise à jour.";
        }
        if ($action === 'delete_exp') {
            $stmt = $db->prepare("DELETE FROM cv_experiences WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $message = "✅ Expérience supprimée.";
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
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY display_order ASC, id DESC")->fetchAll();
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
<body class="bg-slate-50 font-sans text-slate-900" x-data="appData()">
    <script>
        function appData() {
            return {
                tab: 'apps',
                section: 'profile',
                openModal: null, 
                editItem: {},
                draggedExpId: null,
                allExps: <?= json_encode($cv_exps) ?>,
                allApps: <?= json_encode($apps) ?>,
                allLangs: <?= json_encode($cv_langs) ?>,
                prepEdit(type, data = {}) {
                    this.editItem = { type: type, ...data };
                },
                saveDragOrder() {
                    const orders = this.allExps.map((e, idx) => ({id: e.id, order: idx}));
                    const formData = new FormData();
                    formData.append('action', 'reorder_exp');
                    formData.append('orders', JSON.stringify(orders));
                    fetch(window.location.href, { method: 'POST', body: formData })
                        .then(() => {
                            alert('✅ Ordre enregistré !');
                            setTimeout(() => location.reload(), 500);
                        })
                        .catch(e => alert('❌ Erreur: ' + e));
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
                <button @click="tab = 'debug'" :class="tab === 'debug' ? 'bg-red-600 text-white' : 'text-slate-400 hover:bg-slate-800'" class="w-full text-left p-3 rounded-lg transition flex items-center gap-3 font-bold">
                    <i class="fa-solid fa-bug w-5"></i> Debug
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
                    <button @click="editItem = {type: 'app', id: '', slug: '', company_name: '', job_title: '', job_url: '', custom_pitch: '', why_me: '', strengths: '', perfect_match: '',  default_lens: 'ops', status: 'sent'}" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow-lg hover:bg-blue-700 transition">+ Nouvelle Candidature</button>
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
                                <p class="text-slate-400 text-sm" x-text="app.job_title"></p>
                                <a :href="'/go/' + app.slug" target="_blank" class="text-blue-500 font-mono text-xs" x-text="'/go/' + app.slug"></a>
                            </div>
                            <div class="text-right flex items-center gap-4">
                                <div>
                                    <span class="text-2xl font-black text-slate-800" x-text="app.visits ?? 0"></span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sessions</p>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="editItem = {type: 'app', ...app}" class="text-blue-500 hover:text-blue-700 p-2"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form method="POST" style="display:inline" @submit.prevent="if (confirm('Voulez-vous vraiment supprimer la candidature pour ' + app.company_name + ' ?')) $el.submit()" >
                                        <input type="hidden" name="action" value="delete_app">
                                        <input type="hidden" name="id" :value="app.id">
                                        <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                                    </form>
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
                        <form method="POST" class="grid grid-cols-2 gap-6">
                            <input type="hidden" name="action" value="update_profile">

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
                                <label class="block text-[10px] font-black uppercase text-slate-400">Chemin de la photo (Photo Path)</label>
                                <input type="text" name="photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" placeholder="images/photo.jpg" class="w-full mt-1 border-b py-2 outline-none font-bold focus:border-blue-500 text-sm">
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
                                    <div class="flex gap-2">
                                        <button @click="prepEdit('exp', exp)" class="text-blue-500 hover:text-blue-700 p-2"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="delete_exp">
                                            <input type="hidden" name="id" :value="exp.id">
                                            <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                                        </form>
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
    <div x-show="editItem && editItem.type" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm z-[200] flex items-center justify-center p-4" style="display: none;">
        <div @click.away="editItem = {}" class="bg-white w-full max-w-2xl rounded-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-black uppercase tracking-tighter" x-text="editItem ? (editItem.id ? 'Modifier ' + editItem.type : 'Ajouter ' + editItem.type) : ''"></h3>
                <button @click="editItem = {}" class="text-slate-300 hover:text-slate-600 text-2xl font-bold">&times;</button>
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
                        <option value="ops">Ops</option>
                        <option value="tech">Tech</option>
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
                    <div class="grid grid-cols-2 gap-4">
                        <select name="default_lens" x-model="editItem.default_lens" class="border p-3 rounded-xl w-full bg-slate-50">
                            <option value="ops">Ops</option>
                            <option value="management">Management</option>
                            <option value="tech">Tech</option>
                        </select>
                        <select name="status" x-model="editItem.status" class="border p-3 rounded-xl w-full bg-slate-50">
                            <option value="sent">Envoyé</option>
                            <option value="interview">Entretien</option>
                            <option value="rejected">Rejeté</option>
                            <option value="accepted">Accepté</option>
                        </select>
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg hover:bg-blue-700 transition" x-text="editItem ? (editItem.id ? 'Appliquer les modifications' : 'Créer') : ''"></button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>