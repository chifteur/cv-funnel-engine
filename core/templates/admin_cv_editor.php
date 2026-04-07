<?php
$db = get_db_connection();
$message = "";

// --- LOGIQUE DE SAUVEGARDE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $stmt = $db->prepare("UPDATE profile_settings SET full_name=?, job_title=?, bio=?, email=?, phone=?, linkedin_url=?, photo_path=? WHERE id=1");
        $stmt->execute([$_POST['full_name'], $_POST['job_title'], $_POST['bio'], $_POST['email'], $_POST['phone'], $_POST['linkedin_url'], $_POST['photo_path']]);
        $message = "Profil mis à jour !";
    }
    
    if (isset($_POST['add_exp'])) {
        $stmt = $db->prepare("INSERT INTO cv_experiences (company, role, location, period, content, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['company'], $_POST['role'], $_POST['location'], $_POST['period'], $_POST['content'], $_POST['category']]);
        $message = "Expérience ajoutée !";
    }

    if (isset($_POST['delete_exp'])) {
        $stmt = $db->prepare("DELETE FROM cv_experiences WHERE id = ?");
        $stmt->execute([$_POST['exp_id']]);
        $message = "Expérience supprimée.";
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$profile = $db->query("SELECT * FROM profile_settings WHERE id=1")->fetch();
$exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$skills = $db->query("SELECT * FROM cv_skills")->fetchAll();
$edus = $db->query("SELECT * FROM cv_education")->fetchAll();
?>

<div x-data="{ section: 'profile' }" class="space-y-8">
    
    <?php if ($message): ?>
        <div class="bg-green-500 text-white p-4 rounded-lg shadow-lg mb-6">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="flex gap-4 border-b border-slate-200 mb-8">
        <button @click="section = 'profile'" :class="section === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Identité</button>
        <button @click="section = 'experiences'" :class="section === 'experiences' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Parcours</button>
        <button @click="section = 'skills'" :class="section === 'skills' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400'" class="pb-2 px-4 border-b-2 font-bold transition">Skills & Education</button>
    </div>

    <div x-show="section === 'profile'" class="bg-white p-8 rounded-2xl shadow-sm border">
        <h3 class="text-xl font-bold mb-6">Informations Générales</h3>
        <form method="POST" class="grid grid-cols-2 gap-6">
            <input type="hidden" name="update_profile" value="1">
            <div class="col-span-1">
                <label class="block text-xs font-bold uppercase text-slate-400">Nom Complet</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name']) ?>" class="w-full mt-1 p-2 border rounded">
            </div>
            <div class="col-span-1">
                <label class="block text-xs font-bold uppercase text-slate-400">Titre Actuel</label>
                <input type="text" name="job_title" value="<?= htmlspecialchars($profile['job_title']) ?>" class="w-full mt-1 p-2 border rounded">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-400">Bio Executive Summary</label>
                <textarea name="bio" rows="4" class="w-full mt-1 p-2 border rounded text-sm"><?= htmlspecialchars($profile['bio']) ?></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-400">Chemin Photo</label>
                <input type="text" name="photo_path" value="<?= htmlspecialchars($profile['photo_path']) ?>" class="w-full mt-1 p-2 border rounded text-xs">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-400">LinkedIn URL</label>
                <input type="text" name="linkedin_url" value="<?= htmlspecialchars($profile['linkedin_url']) ?>" class="w-full mt-1 p-2 border rounded text-xs">
            </div>
            <div class="col-span-2 text-right">
                <button type="submit" class="bg-slate-900 text-white px-8 py-2 rounded-full font-bold">Mettre à jour le profil</button>
            </div>
        </form>
    </div>

    <div x-show="section === 'experiences'" class="space-y-6">
        <?php foreach ($exps as $exp): ?>
        <div class="bg-white p-6 rounded-xl border flex justify-between items-center group">
            <div>
                <span class="text-xs font-bold text-blue-600 uppercase"><?= $exp['category'] ?></span>
                <h4 class="font-bold text-slate-800"><?= $exp['role'] ?> @ <?= $exp['company'] ?></h4>
                <p class="text-xs text-slate-400"><?= $exp['period'] ?></p>
            </div>
            <form method="POST" onsubmit="return confirm('Supprimer cette expérience ?');">
                <input type="hidden" name="delete_exp" value="1">
                <input type="hidden" name="exp_id" value="<?= $exp['id'] ?>">
                <button type="submit" class="text-slate-300 hover:text-red-500 transition"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
        <?php endforeach; ?>

        <div class="bg-slate-900 text-white p-8 rounded-2xl shadow-xl mt-12">
            <h3 class="text-lg font-bold mb-6">Ajouter une expérience</h3>
            <form method="POST" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="add_exp" value="1">
                <input type="text" name="company" placeholder="Entreprise" class="bg-slate-800 border-none p-2 rounded text-sm" required>
                <input type="text" name="role" placeholder="Poste" class="bg-slate-800 border-none p-2 rounded text-sm" required>
                <input type="text" name="location" placeholder="Lieu (ex: Courtelary)" class="bg-slate-800 border-none p-2 rounded text-sm">
                <input type="text" name="period" placeholder="Période (ex: 2012 - 2018)" class="bg-slate-800 border-none p-2 rounded text-sm" required>
                <select name="category" class="bg-slate-800 border-none p-2 rounded text-sm">
                    <option value="ops">Opérations</option>
                    <option value="management">Management</option>
                    <option value="tech">Technique</option>
                </select>
                <textarea name="content" placeholder="Description (utilisez des retours à la ligne pour les puces)" class="col-span-2 bg-slate-800 border-none p-2 rounded text-sm" rows="5"></textarea>
                <button type="submit" class="col-span-2 bg-blue-600 p-2 rounded font-bold hover:bg-blue-500">Enregistrer dans le CV Master</button>
            </form>
        </div>
    </div>

</div>