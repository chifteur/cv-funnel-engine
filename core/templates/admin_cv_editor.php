<?php
/**
 * MANGANESE OS - ÉDITEUR DE CV MASTER (CRUD COMPLET)
 * Gère Profil, Expériences, Compétences, Diplômes et Langues
 */

$db = get_db_connection();
$message = "";

// --- 1. LOGIQUE DE TRAITEMENT (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        // --- PROFIL ---
        if ($action === 'update_profile') {
            $stmt = $db->prepare("UPDATE profile_settings SET full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=? WHERE id=1");
            $stmt->execute([$_POST['full_name'], $_POST['job_title'], $_POST['bio'], $_POST['email'], $_POST['phone'], $_POST['linkedin_url'], $_POST['photo_path']]);
            $message = "✅ Profil Master mis à jour.";
        }

        // --- EXPÉRIENCES (C-U-D) ---
        if ($action === 'add_exp') {
            $stmt = $db->prepare("INSERT INTO cv_experiences (company, role, location, period, content, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category']]);
        }
        if ($action === 'update_exp') {
            $stmt = $db->prepare("UPDATE cv_experiences SET company=?, role=?, location=?, period=?, content=?, category=? WHERE id=?");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category'], $_POST['id']]);
            $message = "✅ Expérience mise à jour.";
        }
        if ($action === 'delete_exp') {
            $stmt = $db->prepare("DELETE FROM cv_experiences WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        // --- SKILLS (C-U-D) ---
        if ($action === 'add_skill') {
            $stmt = $db->prepare("INSERT INTO cv_skills (category, label, level_text) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['category'], $_POST['label'], $_POST['level_text']]);
        }
        if ($action === 'update_skill') {
            $stmt = $db->prepare("UPDATE cv_skills SET category=?, label=?, level_text=? WHERE id=?");
            $stmt->execute([$_POST['category'], $_POST['label'], $_POST['level_text'], $_POST['id']]);
            $message = "✅ Compétence mise à jour.";
        }
        if ($action === 'delete_skill') {
            $stmt = $db->prepare("DELETE FROM cv_skills WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        // --- EDUCATION (C-U-D) ---
        if ($action === 'add_edu') {
            $stmt = $db->prepare("INSERT INTO cv_education (degree, institution, year, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon']]);
        }
        if ($action === 'update_edu') {
            $stmt = $db->prepare("UPDATE cv_education SET degree=?, institution=?, year=?, icon=? WHERE id=?");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon'], $_POST['id']]);
            $message = "✅ Formation mise à jour.";
        }
        if ($action === 'delete_edu') {
            $stmt = $db->prepare("DELETE FROM cv_education WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        // --- LANGUES (C-D) ---
        if ($action === 'add_lang') {
            $stmt = $db->prepare("INSERT INTO cv_languages (label, level) VALUES (?, ?)");
            $stmt->execute([$_POST['label'], $_POST['level']]);
        }
        if ($action === 'delete_lang') {
            $stmt = $db->prepare("DELETE FROM cv_languages WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

    } catch (Exception $e) { $message = "❌ Erreur : " . $e->getMessage(); }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
$exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
?>

<div x-data="{ 
    section: 'profile', 
    editItem: null,
    openEditModal(type, data) {
        this.editItem = { type: type, ...data };
    }
}" class="max-w-5xl mx-auto space-y-10">
    
    <div class="flex gap-6 border-b border-slate-200">
        <button @click="section = 'profile'" :class="section === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-3 px-2 border-b-2 font-black uppercase text-xs tracking-widest transition">Identité</button>
        <button @click="section = 'parcours'" :class="section === 'parcours' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-3 px-2 border-b-2 font-black uppercase text-xs tracking-widest transition">Parcours</button>
        <button @click="section = 'skills'" :class="section === 'skills' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-3 px-2 border-b-2 font-black uppercase text-xs tracking-widest transition">Compétences</button>
        <button @click="section = 'edu'" :class="section === 'edu' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-3 px-2 border-b-2 font-black uppercase text-xs tracking-widest transition">Diplômes & Langues</button>
    </div>

    <div x-show="section === 'profile'" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <form method="POST" class="grid grid-cols-2 gap-6">
            <input type="hidden" name="action" value="update_profile">
            <div class="col-span-1">
                <label class="text-[10px] font-black uppercase text-slate-400">Nom Complet</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" class="w-full border-b py-2 outline-none focus:border-blue-600 font-bold">
            </div>
            <div class="col-span-1">
                <label class="text-[10px] font-black uppercase text-slate-400">Titre Principal</label>
                <input type="text" name="job_title" value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>" class="w-full border-b py-2 outline-none focus:border-blue-600 font-bold">
            </div>
            <div class="col-span-2">
                <label class="text-[10px] font-black uppercase text-slate-400">Executive Summary</label>
                <textarea name="bio" rows="4" class="w-full border rounded-xl p-4 mt-2 text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>
            <div class="col-span-1">
                <label class="text-[10px] font-black uppercase text-slate-400">Photo Path</label>
                <input type="text" name="photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" class="w-full border-b py-2 outline-none text-xs font-mono">
            </div>
            <button type="submit" class="col-span-2 bg-slate-900 text-white py-3 rounded-full font-bold">Enregistrer le profil master</button>
        </form>
    </div>

    <div x-show="section === 'parcours'" class="space-y-6">
        <div class="grid gap-4">
            <?php foreach($exps as $e): ?>
            <div class="bg-white p-4 rounded-xl border flex justify-between items-center group">
                <div class="flex items-center">
                    <span class="text-[9px] font-black px-2 py-1 bg-slate-100 rounded uppercase text-slate-500"><?= $e['category'] ?></span>
                    <h4 class="font-bold ml-3"><?= htmlspecialchars($e['role']) ?> @ <?= htmlspecialchars($e['company']) ?></h4>
                </div>
                <div class="flex gap-2">
                    <button @click="openEditModal('exp', <?= htmlspecialchars(json_encode($e)) ?>)" class="text-blue-500 hover:text-blue-700 p-2"><i class="fa-solid fa-pen-to-square"></i></button>
                    <form method="POST" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete_exp"><input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="openEditModal('exp', {id: '', company: '', role: '', location: '', period: '', content: '', category: 'ops'})" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:bg-white hover:text-blue-600 transition">+ Ajouter une expérience</button>
    </div>

    <div x-show="section === 'skills'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach($skills as $s): ?>
            <div class="bg-white p-3 rounded-lg border flex justify-between items-center text-sm">
                <span><strong><?= $s['label'] ?></strong> (<?= $s['level_text'] ?>) <span class="text-[9px] uppercase text-slate-400 ml-2"><?= $s['category'] ?></span></span>
                <div class="flex gap-1">
                    <button @click="openEditModal('skill', <?= htmlspecialchars(json_encode($s)) ?>)" class="text-blue-400 hover:text-blue-600 px-2"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST"><input type="hidden" name="action" value="delete_skill"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="text-slate-200 hover:text-red-500 px-2"><i class="fa-solid fa-times"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="openEditModal('skill', {id: '', label: '', level_text: '', category: 'management'})" class="w-full border-2 border-dashed border-slate-100 py-3 rounded-xl text-slate-300 text-xs font-bold hover:text-blue-400 transition">+ Ajouter Skill</button>
    </div>

    <div x-show="section === 'edu'" class="grid md:grid-cols-2 gap-10">
        <div class="space-y-4">
            <h3 class="font-black uppercase text-xs text-slate-400">Diplômes & Formations</h3>
            <div class="space-y-2">
                <?php foreach($edus as $edu): ?>
                    <div class="text-xs p-3 bg-white border rounded-lg flex justify-between items-center">
                        <span><strong><?= $edu['degree'] ?></strong> (<?= $edu['year'] ?>)</span>
                        <div class="flex gap-2">
                            <button @click="openEditModal('edu', <?= htmlspecialchars(json_encode($edu)) ?>)" class="text-blue-400"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST"><input type="hidden" name="action" value="delete_edu"><input type="hidden" name="id" value="<?= $edu['id'] ?>"><button type="submit" class="text-red-300">✕</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button @click="openEditModal('edu', {id: '', degree: '', institution: '', year: '', icon: 'fa-graduation-cap'})" class="w-full py-2 border border-dashed text-slate-300 text-[10px] uppercase font-bold hover:text-blue-400 transition">+ Nouveau Diplôme</button>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="font-black uppercase text-xs text-slate-400">Langues</h3>
            <div class="space-y-2 mb-6">
                <?php foreach($langs as $l): ?>
                    <div class="text-xs p-3 bg-white border rounded-lg flex justify-between">
                        <span><?= $l['label'] ?> : <strong><?= $l['level'] ?></strong></span>
                        <form method="POST"><input type="hidden" name="action" value="delete_lang"><input type="hidden" name="id" value="<?= $l['id'] ?>"><button type="submit" class="text-red-300">✕</button></form>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="bg-slate-50 p-4 rounded-xl space-y-3">
                <input type="hidden" name="action" value="add_lang">
                <input type="text" name="label" placeholder="Langue" class="w-full border-b p-2 text-sm outline-none bg-transparent">
                <input type="text" name="level" placeholder="Niveau" class="w-full border-b p-2 text-sm outline-none bg-transparent">
                <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded-full text-xs font-bold">Ajouter Langue</button>
            </form>
        </div>
    </div>

    <template x-if="editItem">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <div @click.away="editItem = null" class="bg-white w-full max-w-2xl rounded-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-black uppercase tracking-tighter" x-text="editItem.id ? 'Modifier' : 'Ajouter'"></h3>
                    <button @click="editItem = null" class="text-slate-300 hover:text-slate-600 text-2xl">&times;</button>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" :value="editItem.id ? 'update_' + editItem.type : 'add_' + editItem.type">
                    <input type="hidden" name="id" :value="editItem.id">

                    <template x-if="editItem.type === 'exp'">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" name="company" x-model="editItem.company" placeholder="Entreprise" class="border p-3 rounded-xl w-full">
                                <input type="text" name="role" x-model="editItem.role" placeholder="Poste" class="border p-3 rounded-xl w-full">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" name="location" x-model="editItem.location" placeholder="Lieu" class="border p-3 rounded-xl w-full">
                                <input type="text" name="period" x-model="editItem.period" placeholder="Période" class="border p-3 rounded-xl w-full">
                            </div>
                            <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full">
                                <option value="ops">Opérations</option>
                                <option value="management">Management</option>
                                <option value="tech">Technique</option>
                            </select>
                            <textarea name="content" x-model="editItem.content" placeholder="Description (puces)" rows="6" class="border p-3 rounded-xl w-full text-sm"></textarea>
                        </div>
                    </template>

                    <template x-if="editItem.type === 'skill'">
                        <div class="space-y-4">
                            <input type="text" name="label" x-model="editItem.label" placeholder="Compétence" class="border p-3 rounded-xl w-full">
                            <input type="text" name="level_text" x-model="editItem.level_text" placeholder="Niveau (Expert...)" class="border p-3 rounded-xl w-full">
                            <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full">
                                <option value="management">Management</option>
                                <option value="ops">Opérations</option>
                                <option value="tech">Technique</option>
                            </select>
                        </div>
                    </template>

                    <template x-if="editItem.type === 'edu'">
                        <div class="space-y-4">
                            <input type="text" name="degree" x-model="editItem.degree" placeholder="Diplôme" class="border p-3 rounded-xl w-full">
                            <input type="text" name="institution" x-model="editItem.institution" placeholder="École" class="border p-3 rounded-xl w-full">
                            <input type="text" name="year" x-model="editItem.year" placeholder="Année" class="border p-3 rounded-xl w-full">
                            <input type="text" name="icon" x-model="editItem.icon" placeholder="Icône FontAwesome" class="border p-3 rounded-xl w-full font-mono text-xs">
                        </div>
                    </template>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg shadow-blue-200" x-text="editItem.id ? 'Sauvegarder les modifications' : 'Créer l\'élément'"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>