<?php
/**
 * MANGANESE OS - ÉDITEUR DE CV MASTER
 * Interface visuelle des onglets CV
 */
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$cv_langs = $db->query("SELECT * FROM cv_languages")->fetchAll();
?>

<div x-data="{ section: 'profile' }" class="space-y-8">
    <h2 class="text-3xl font-black uppercase tracking-tight">Éditeur de CV</h2>

    <div class="flex gap-4 border-b border-slate-200 mb-8 font-bold">
        <button @click="section = 'profile'" :class="section === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Identité</button>
        <button @click="section = 'experiences'" :class="section === 'experiences' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Parcours</button>
        <button @click="section = 'skills'" :class="section === 'skills' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Skills & Langues</button>
        <button @click="section = 'education'" :class="section === 'education' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 transition">Éducation</button>
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
                <label class="block text-[10px] font-black uppercase text-slate-400">Bio / Summary</label>
                <textarea name="bio" rows="4" class="w-full mt-1 border rounded-xl p-3 text-sm"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="col-span-2 bg-slate-900 text-white py-3 rounded-full font-bold hover:bg-blue-600 transition">Sauvegarder Profil</button>
        </form>
    </div>

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
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_exp"><input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button type="submit" class="text-slate-200 hover:text-red-500 p-2"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button @click="prepEdit('exp', {id:'', company:'', role:'', location:'', period:'', content:'', category:'ops'})" class="w-full border-2 border-dashed border-slate-200 py-4 rounded-xl text-slate-400 font-bold hover:text-blue-600 transition">+ Ajouter Experience</button>
    </div>

    <div x-show="section === 'skills'" class="grid grid-cols-2 gap-8">
        <div class="space-y-4">
            <h3 class="font-bold text-slate-400 uppercase text-xs">Compétences</h3>
            <?php foreach ($cv_skills as $s): ?>
                <div class="bg-white p-2 border rounded flex justify-between items-center">
                    <span class="text-sm font-bold"><?= htmlspecialchars($s['label']) ?></span>
                    <div class="flex gap-2">
                        <button @click="prepEdit('skill', <?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-400"><i class="fa-solid fa-pen"></i></button>
                        <form method="POST"><input type="hidden" name="action" value="delete_skill"><input type="hidden" name="id" value="<?= $s['id'] ?>"><button type="submit" class="text-red-200">✕</button></form>
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
                    <form method="POST"><input type="hidden" name="action" value="delete_lang"><input type="hidden" name="id" value="<?= $l['id'] ?>"><button type="submit" class="text-red-200">✕</button></form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

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
                                <option value="ops">Opérations</option><option value="management">Management</option><option value="tech">Technique</option>
                            </select>
                            <textarea name="content" x-model="editItem.content" placeholder="Description (une puce par ligne)..." rows="6" class="border p-3 rounded-xl w-full text-sm focus:border-blue-500 outline-none"></textarea>
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
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-full font-bold shadow-lg hover:bg-blue-700 transition" x-text="editItem.id ? 'Appliquer les modifications' : 'Créer'"></button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>