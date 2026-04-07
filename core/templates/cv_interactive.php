<?php
/**
 * CV INTERACTIF DYNAMIQUE - MANGANESE OS
 * Basé sur la Maquette V3
 */

$db = get_db_connection();

// On charge tout depuis la DB
$profile = $db->query("SELECT * FROM profile_settings WHERE id = 1")->fetch();
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();

// Docs liés
$stmtDocs = $db->prepare("SELECT d.* FROM documents d JOIN rel_app_doc rel ON d.id = rel.doc_id WHERE rel.app_id = ?");
$stmtDocs->execute([$app['id']]);
$attached_docs = $stmtDocs->fetchAll();

$lens = $app['default_lens'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['full_name'] ?? 'Profil') ?> | <?= htmlspecialchars($app['company_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .highlight-lens { border-left: 5px solid #3b82f6; background: white; }
        .why-me-card { transform: rotate(-1.5deg); }
    </style>
</head>
<body class="bg-gray-50 text-slate-900" data-app-id="<?= $app['id'] ?>">

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-black uppercase tracking-tighter"><?= htmlspecialchars($profile['full_name'] ?? '') ?></h1>
            <a href="mailto:<?= $profile['email'] ?? '' ?>" class="bg-slate-900 text-white px-4 py-2 rounded-full text-sm font-bold">Contact direct</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-12">
        <section class="mb-20 grid md:grid-cols-3 gap-12 items-start">
            <div class="md:col-span-2">
                <div class="flex items-center gap-6 mb-8">
                    <img src="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" class="w-24 h-24 rounded-full shadow-lg object-cover border-4 border-white">
                    <h2 class="text-4xl font-black">Bonjour <span class="text-blue-600"><?= htmlspecialchars($app['company_name']) ?></span></h2>
                </div>
                <div class="text-xl text-slate-600 leading-relaxed italic">
                    <?= nl2br(htmlspecialchars($app['custom_pitch'] ?? '')) ?>
                </div>
            </div>
            <div class="why-me-card bg-blue-600 p-8 rounded-3xl shadow-xl text-white">
                <h3 class="font-bold text-lg mb-4 underline">Pourquoi moi ?</h3>
                <p class="text-sm opacity-90"><?= nl2br(htmlspecialchars($profile['bio'] ?? '')) ?></p>
            </div>
        </section>

        <section class="mb-20">
            <h3 class="text-2xl font-black mb-10 uppercase tracking-widest text-slate-300">Expériences</h3>
            <div class="space-y-8">
                <?php foreach($cv_exps as $exp): ?>
                <div class="p-8 rounded-xl border <?= $exp['category'] === $lens ? 'highlight-lens shadow-md' : 'bg-white' ?>">
                    <div class="flex justify-between mb-4">
                        <h4 class="text-xl font-bold"><?= htmlspecialchars($exp['role']) ?> <span class="text-blue-600 text-sm">@ <?= htmlspecialchars($exp['company']) ?></span></h4>
                        <span class="text-sm font-bold text-slate-400"><?= htmlspecialchars($exp['period']) ?></span>
                    </div>
                    <div class="text-slate-600 text-sm leading-relaxed">
                        <?= nl2br(htmlspecialchars($exp['content'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script src="/assets/js/telemetry.js"></script>
</body>
</html>