<?php
/**
 * MANGANESE OS - ÉDITEUR DE CV MASTER
 * Gère les formulaires pour toutes les sections
 */
$db = get_db_connection();
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$cv_langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
?>

<div x-data="{ section: 'profile' }" class="space-y-8">
    <h2 class="text-3xl font-black uppercase tracking-tight">Éditeur de CV</h2>

    <div class="flex gap-4 border-b border-slate-200 mb-8">
        <button @click="section = 'profile'" :class="section === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Identité</button>
        <button @click="section = 'experiences'" :class="section === 'experiences' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Parcours</button>
        <button @click="section = 'skills'" :class="section === 'skills' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Skills & Langues</button>
        <button @click="section = 'education'" :class="section === 'education' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Éducation</button>
    </div>

    <div x-show="section === 'profile'" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
        <form method="POST" class="grid grid-cols-2 gap-6">
            <input type="hidden" name="action" value="update_profile">
            <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" placeholder="Nom" class="w-full border-b py-2 outline-none focus:border-blue-600 font-bold">
            <input type="text" name="job_title" value="<?= htmlspecialchars($profile['job_title'] ?? '') ?>" placeholder="Titre" class="w-full border-b py-2 outline-none focus:border-blue-600 font-bold">
            <textarea name="bio" rows="4" placeholder="Executive Summary" class="col-span-2 border rounded-xl p-4 mt-2 text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" placeholder="Email" class="w-full border-b py-2 outline-none">
            <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="Téléphone" class="w-full border-b py-2 outline-none">
            <input type="text" name="photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" placeholder="Chemin Photo" class="col-span-2 border-b py-2 font-mono text-xs">
            <button type="submit" class="col-span-2 bg-slate-900 text-white py-3 rounded-full font-bold">Sauvegarder Profil Master</button>
        </form>
    </div>

    <div x-show="section === 'experiences'" class="space-y-6">
        <div class="grid gap-3">
            <?php foreach($cv_exps as $e): ?>
            <div class="bg-white p-4 rounded-xl border flex justify-between items-center group">
                <div>
                    <span class="text-[9px] font-black px-2 py-1 bg-slate-100 rounded uppercase text-slate-500"><?= $e['category'] ?></span>
                    <h4 class="font-bold ml-2 inline"><?= htmlspecialchars($e['role']) ?> @ <?= htmlspecialchars($e['company']) ?></h4>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_exp"><input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <button type="submit" class="text-slate-200 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="bg-slate-900 text-white p-6 rounded-2xl">
            <h3 class="font-bold mb-4 italic text-sm">Ajouter une expérience</h3>
            <form method="POST" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="action" value="add_exp">
                <input type="text" name="company" placeholder="Entreprise" class="bg-slate-800 p-2 rounded text-sm outline-none" required>
                <input type="text" name="role" placeholder="Poste" class="bg-slate-800 p-2 rounded text-sm outline-none" required>
                <input type="text" name="period" placeholder="Période" class="bg-slate-800 p-2 rounded text-sm outline-none">
                <select name="category" class="bg-slate-800 p-2 rounded text-sm outline-none">
                    <option value="ops">Opérations</option>
                    <option value="management">Management</option>
                    <option value="tech">Technique</option>
                </select>
                <textarea name="content" placeholder="Contenu..." class="col-span-2 bg-slate-800 p-2 rounded text-sm" rows="3"></textarea>
                <button type="submit" class="col-span-2 bg-blue-600 py-2 rounded font-bold">Enregistrer</button>
            </form>
        </div>
    </div>

    <div x-show="section === 'skills'" class="grid grid-cols-2 gap-8">
        <div>
            <h3 class="font-bold text-slate-400 uppercase text-xs mb-4">Compétences</h3>
            <div class="space-y-2 mb-6">
                <?php foreach ($cv_skills as $s): ?>
                    <div class="bg-white p-2 border rounded text-sm flex justify-between items-center">
                        <span><strong><?= $s['label'] ?></strong></span>
                        <form method="POST"><input type="hidden" name="action" value="delete_skill"><input type="hidden" name="skill_id" value="<?= $s['id'] ?>"><button type="submit" class="text-red-300">✕</button></form>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="bg-slate-100 p-4 rounded-xl space-y-3">
                <input type="hidden" name="action" value="add_skill">
                <input type="text" name="skill_label" placeholder="Skill" class="w-full p-2 border rounded text-sm" required>
                <input type="text" name="skill_level" placeholder="Niveau" class="w-full p-2 border rounded text-sm">
                <select name="skill_category" class="w-full p-2 border rounded text-sm">
                    <option value="management">Management</option><option value="ops">Ops</option><option value="tech">Tech</option>
                </select>
                <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded font-bold">Ajouter</button>
            </form>
        </div>
        <div>
            <h3 class="font-bold text-slate-400 uppercase text-xs mb-4">Langues</h3>
            <div class="space-y-2 mb-6">
                <?php foreach ($cv_langs as $l): ?>
                    <div class="bg-white p-2 border rounded text-sm flex justify-between items-center">
                        <span><?= $l['label'] ?> : <?= $l['level'] ?></span>
                        <form method="POST"><input type="hidden" name="action" value="delete_lang"><input type="hidden" name="lang_id" value="<?= $l['id'] ?>"><button type="submit" class="text-red-300">✕</button></form>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="bg-slate-100 p-4 rounded-xl space-y-3">
                <input type="hidden" name="action" value="add_lang">
                <input type="text" name="lang_label" placeholder="Langue" class="w-full p-2 border rounded text-sm" required>
                <input type="text" name="lang_level" placeholder="Niveau" class="w-full p-2 border rounded text-sm">
                <button type="submit" class="w-full bg-slate-900 text-white py-2 rounded font-bold">Ajouter</button>
            </form>
        </div>
    </div>
</div>