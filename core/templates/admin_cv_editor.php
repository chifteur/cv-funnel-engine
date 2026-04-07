<?php
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
            $message = "✅ Profil mis à jour.";
        }

        // --- EXPÉRIENCES ---
        if ($action === 'add_exp') {
            $stmt = $db->prepare("INSERT INTO cv_experiences (company, role, location, period, content, category) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category']]);
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
        if ($action === 'delete_skill') {
            $stmt = $db->prepare("DELETE FROM cv_skills WHERE id = ?");
            $stmt->execute([$_POST['id']]);
        }

        // --- EDUCATION ---
        if ($action === 'add_edu') {
            $stmt = $db->prepare("INSERT INTO cv_education (degree, institution, year, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['degree'], $_POST['institution'], $_POST['year'], $_POST['icon']]);
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

    } catch (Exception $e) { $message = "❌ Erreur : " . $e->getMessage(); }
}

// --- 2. RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
$exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
?>

<div x-data="{ section: 'profile' }" class="max-w-5xl mx-auto space-y-10">
    
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
                <label class="text-[10px] font-black uppercase text-slate-400">Executive Summary (Why me?)</label>
                <textarea name="bio" rows="4" class="w-full border rounded-xl p-4 mt-2 text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="col-span-2 bg-slate-900 text-white py-3 rounded-full font-bold">Enregistrer le profil master</button>
        </form>
    </div>

    <div x-show="section === 'parcours'" class="space-y-6">
        <div class="grid gap-4">
            <?php foreach($exps as $e): ?>
            <div class="bg-white p-4 rounded-xl border flex justify-between items-center group">
                <div>
                    <span class="text-[9px] font-black px-2 py-1 bg-slate-100 rounded uppercase text-slate-500"><?= $e['category'] ?></span>
                    <h4 class="font-bold ml-2 inline"><?= htmlspecialchars($e['role']) ?> @ <?= htmlspecialchars($e['company']) ?></h4>
                </div>
                <form method="POST" onsubmit="return confirm('Supprimer ?')">
                    <input type="hidden" name="action" value="delete_exp"><input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <button type="submit" class="text-slate-200 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="openModal = 'new_exp'" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:bg-white hover:text-blue-600 transition">+ Ajouter une expérience</button>
    </div>

    <div x-show="section === 'skills'" class="space-y-8">
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl border">
                <h3 class="font-black uppercase text-xs mb-4">Ajouter un Skill</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add_skill">
                    <input type="text" name="label" placeholder="Ex: Management Agile" class="w-full border-b p-2 outline-none">
                    <input type="text" name="level_text" placeholder="Ex: Expert" class="w-full border-b p-2 outline-none">
                    <select name="category" class="w-full border p-2 rounded text-sm bg-slate-50">
                        <option value="management">Management</option>
                        <option value="ops">Opérations</option>
                        <option value="tech">Technique</option>
                    </select>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-full font-bold">Ajouter au CV</button>
                </form>
            </div>
            <div class="space-y-2">
                <?php foreach($skills as $s): ?>
                <div class="bg-white p-3 rounded-lg border flex justify-between items-center text-sm">
                    <span><strong><?= $s['label'] ?></strong> (<?= $s['level_text'] ?>)</span>
                    <form method="POST"><input type="hidden" name="action" value="delete_skill"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="text-slate-300 hover:text-red-500"><i class="fa-solid fa-times"></i></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div x-show="section === 'edu'" class="grid md:grid-cols-2 gap-10">
        <div class="space-y-4">
            <h3 class="font-black uppercase text-xs text-slate-400">Diplômes & Formations</h3>
            <form method="POST" class="bg-white p-6 rounded-2xl border space-y-3 mb-6">
                <input type="hidden" name="action" value="add_edu">
                <input type="text" name="degree" placeholder="Diplôme" class="w-full border-b p-2 text-sm outline-none">
                <input type="text" name="institution" placeholder="École" class="w-full border-b p-2 text-sm outline-none">
                <input type="text" name="year" placeholder="Année" class="w-full border-b p-2 text-sm outline-none">
                <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded-full text-xs font-bold">Ajouter Diplôme</button>
            </form>
            <?php foreach($edus as $edu): ?>
                <div class="text-xs p-3 bg-white border rounded-lg flex justify-between">
                    <span><?= $edu['degree'] ?> (<?= $edu['year'] ?>)</span>
                    <form method="POST"><input type="hidden" name="action" value="delete_edu"><input type="hidden" name="id" value="<?= $edu['id'] ?>"><button type="submit" class="text-red-300">✕</button></form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="space-y-4">
            <h3 class="font-black uppercase text-xs text-slate-400">Langues</h3>
            <form method="POST" class="bg-white p-6 rounded-2xl border space-y-3 mb-6">
                <input type="hidden" name="action" value="add_lang">
                <input type="text" name="label" placeholder="Langue" class="w-full border-b p-2 text-sm outline-none">
                <input type="text" name="level" placeholder="Niveau" class="w-full border-b p-2 text-sm outline-none">
                <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded-full text-xs font-bold">Ajouter Langue</button>
            </form>
            <?php foreach($langs as $l): ?>
                <div class="text-xs p-3 bg-white border rounded-lg flex justify-between">
                    <span><?= $l['label'] ?> : <strong><?= $l['level'] ?></strong></span>
                    <form method="POST"><input type="hidden" name="action" value="delete_lang"><input type="hidden" name="id" value="<?= $l['id'] ?>"><button type="submit" class="text-red-300">✕</button></form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>