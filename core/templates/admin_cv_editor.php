<?php
/**
 * MANGANESE OS - ÉDITEUR DE CV MASTER
 * Gère le CRUD complet (Create, Read, Update, Delete) pour le contenu du CV
 */

$db = get_db_connection();

// --- 1. TRAITEMENT DES ACTIONS CV (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // --- EXPÉRIENCES ---
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

        // --- SKILLS ---
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

        // --- EDUCATION ---
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

        // --- LANGUES ---
        if ($action === 'add_lang') {
            $stmt = $db->prepare("INSERT INTO cv_languages (label, level) VALUES (?, ?)");
            $stmt->execute([$_POST['label'], $_POST['level']]);
        }
        if ($action === 'delete_lang') {
            $stmt = $db->prepare("DELETE FROM cv_languages WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }
    } catch (Exception $e) {
        echo "<script>alert('Erreur SQL : " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- 2. RÉCUPÉRATION DATA ---
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$cv_langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
?>

<div x-data="{ 
    section: 'profile', 
    editItem: null,
    prepModal(type, data = {}) {
        this.editItem = { type: type, ...data };
    }
}" class="space-y-8">

    <div class="flex gap-4 border-b border-slate-200 mb-8">
        <button @click="section = 'profile'" :class="section === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Identité</button>
        <button @click="section = 'experiences'" :class="section === 'experiences' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Parcours</button>
        <button @click="section = 'skills'" :class="section === 'skills' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Skills & Langues</button>
        <button @click="section = 'education'" :class="section === 'education' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Éducation</button>
    </div>

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
                <label class="block text-[10px] font-black uppercase text-slate-400">Bio Executive Summary</label>
                <textarea name="bio" rows="4" class="w-full mt-1 border rounded-xl p-3 text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>
            <div class="col-span-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Email Contact</label>
                <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" class="w-full mt-1 border-b py-2 outline-none">
            </div>
            <div class="col-span-1">
                <label class="block text-[10px] font-black uppercase text-slate-400">Lien Photo</label>
                <input type="text" name="photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" class="w-full mt-1 border-b py-2 font-mono text-xs">
            </div>
            <div class="col-span-2 text-right">
                <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-full font-bold hover:bg-blue-600 transition">Sauvegarder Profil Master</button>
            </div>
        </form>
    </div>

    <div x-show="section === 'experiences'" class="space-y-4">
        <div class="grid gap-3">
            <?php foreach($cv_exps as $e): ?>
            <div class="bg-white p-4 rounded-xl border flex justify-between items-center group">
                <div class="flex items-center">
                    <span class="text-[9px] font-black px-2 py-1 bg-slate-100 rounded uppercase text-slate-400"><?= $e['category'] ?></span>
                    <h4 class="font-bold ml-3 text-slate-800"><?= htmlspecialchars($e['role']) ?> @ <?= htmlspecialchars($e['company']) ?></h4>
                </div>
                <div class="flex gap-2">
                    <button @click="prepModal('exp', <?= htmlspecialchars(json_encode($e)) ?>)" class="text-blue-500 hover:text-blue-700 p-2"><i class="fa-solid fa-pen-to-square"></i></button>
                    <form method="POST" onsubmit="return confirm('Supprimer ?')">
                        <input type="hidden" name="action" value="delete_exp"><input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="prepModal('exp', {id:'', company:'', role:'', location:'', period:'', content:'', category:'ops'})" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:text-blue-600 transition">+ Ajouter Experience</button>
    </div>

    <div x-show="section === 'skills'" class="space-y-6">
        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach($cv_skills as $s): ?>
            <div class="bg-white p-3 rounded-lg border flex justify-between items-center text-sm">
                <span><strong><?= htmlspecialchars($s['label']) ?></strong> (<?= htmlspecialchars($s['level_text']) ?>)</span>
                <div class="flex gap-2">
                    <button @click="prepModal('skill', <?= htmlspecialchars(json_encode($s)) ?>)" class="text-blue-400 hover:text-blue-600 px-1"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST"><input type="hidden" name="action" value="delete_skill"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="text-red-200 hover:text-red-500 px-1">✕</button></form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="prepModal('skill', {id:'', label:'', level_text:'', category:'management'})" class="w-full border-2 border-dashed border-slate-200 py-3 rounded-xl text-slate-400 font-bold text-xs">+ Ajouter Skill</button>
    </div>

    <div x-show="section === 'education'" class="space-y-6">
        <div class="grid gap-3">
            <?php foreach($cv_edus as $edu): ?>
            <div class="bg-white p-4 rounded-xl border flex justify-between items-center text-sm">
                <span><strong><?= htmlspecialchars($edu['degree']) ?></strong> - <?= htmlspecialchars($edu['institution']) ?> (<?= $edu['year'] ?>)</span>
                <div class="flex gap-2">
                    <button @click="prepModal('edu', <?= htmlspecialchars(json_encode($edu)) ?>)" class="text-blue-400 hover:text-blue-600 px-1"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST"><input type="hidden" name="action" value="delete_edu"><input type="hidden" name="id" value="<?= $edu['id'] ?>"><button type="submit" class="text-red-200 hover:text-red-500 px-1">✕</button></form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="prepModal('edu', {id:'', degree:'', institution:'', year:'', icon:'fa-graduation-cap'})" class="w-full border-2 border-dashed border-slate-200 py-3 rounded-xl text-slate-400 font-bold text-xs">+ Ajouter Diplôme</button>
    </div>

    <template x-if="editItem">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <div @click.away="editItem = null" class="bg-white w-full max-w-2xl rounded-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-black uppercase tracking-tighter" x-text="editItem.id ? 'Modifier ' + editItem.type : 'Ajouter ' + editItem.type"></h3>
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
                            <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full bg-slate-50">
                                <option value="ops">Opérations</option><option value="management">Management</option><option value="tech">Technique</option>
                            </select>
                            <textarea name="content" x-model="editItem.content" placeholder="Description..." rows="6" class="border p-3 rounded-xl w-full text-sm"></textarea>
                        </div>
                    </template>

                    <template x-if="editItem.type === 'skill'">
                        <div class="space-y-4">
                            <input type="text" name="label" x-model="editItem.label" placeholder="Compétence" class="border p-3 rounded-xl w-full">
                            <input type="text" name="level_text" x-model="editItem.level_text" placeholder="Niveau" class="border p-3 rounded-xl w-full">
                            <select name="category" x-model="editItem.category" class="border p-3 rounded-xl w-full bg-slate-50">
                                <option value="management">Management</option><option value="ops">Ops</option><option value="tech">Tech</option>
                            </select>
                        </div>
                    </template>

                    <template x-if="editItem.type === 'edu'">
                        <div class="space-y-4">
                            <input type="text" name="degree" x-model="editItem.degree" placeholder="Diplôme" class="border p-3 rounded-xl w-full">
                            <input type="text" name="institution" x-model="editItem.institution" placeholder="École" class="border p-3 rounded-xl w-full">
                            <input type="text" name="year" x-model="editItem.year" placeholder="Année" class="border p-3 rounded-xl w-full">
                            <input type="hidden" name="icon" x-model="editItem.icon">
                        </div>
                    </template>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg" x-text="editItem.id ? 'Mettre à jour' : 'Enregistrer'"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>