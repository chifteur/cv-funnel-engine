<?php
/**
 * CV INTERACTIF DYNAMIQUE - MANGANESE OS
 * Basé sur la Maquette V3
 */

$db = get_db_connection();

// 1. Récupération du profil master
$profile = $db->query("SELECT * FROM profile_settings WHERE id = 1")->fetch();

// 2. Récupération des compétences (classées par catégorie)
$stmtSkills = $db->query("SELECT * FROM cv_skills ORDER BY category, id");
$all_skills = $stmtSkills->fetchAll();

// 3. Récupération des expériences
$stmtExp = $db->query("SELECT * FROM cv_experiences ORDER BY id DESC");
$experiences = $stmtExp->fetchAll();

// 4. Récupération des formations
$stmtEdu = $db->query("SELECT * FROM cv_education ORDER BY year DESC");
$educations = $stmtEdu->fetchAll();

// 5. Récupération des documents liés à cette postulation
$stmtDocs = $db->prepare("
    SELECT d.* FROM documents d
    JOIN rel_app_doc rel ON d.id = rel.doc_id
    WHERE rel.app_id = ?
");
$stmtDocs->execute([$app['id']]);
$attached_docs = $stmtDocs->fetchAll();

// 6. Logique de la "Lens" (Couleur dominante)
$primaryColor = match($app['default_lens']) {
    'management' => 'blue-700',
    'tech'       => 'emerald-600',
    default      => 'blue-600', // ops
};
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['full_name']) ?> | <?= htmlspecialchars($app['company_name']) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root { --main-color: <?= $app['default_lens'] === 'tech' ? '#059669' : '#2563eb' ?>; }
        .why-me-card { transform: rotate(-1.5deg); }
        .highlight-ops { border-left: 5px solid var(--main-color); background: white; }
        .hero-gradient { background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); }
        .text-primary { color: var(--main-color); }
        .bg-primary { background-color: var(--main-color); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased font-sans leading-normal" data-app-id="<?= $app['id'] ?>">

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tighter uppercase">
                    <?= htmlspecialchars($profile['full_name']) ?>
                </h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                    <?= htmlspecialchars($app['job_title']) ?> • Dossier <?= htmlspecialchars($app['company_name']) ?>
                </p>
            </div>
            <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="#experience" class="hover:text-primary transition">Expérience</a>
                <a href="#skills" class="hover:text-primary transition">Compétences</a>
                <a href="#education" class="hover:text-primary transition">Éducation</a>
                <a href="mailto:<?= $profile['email'] ?>" class="bg-slate-900 text-white px-4 py-2 rounded-full hover:bg-primary transition">Contact direct</a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-12">

        <section class="mb-24 hero-gradient p-10 rounded-3xl border shadow-sm">
            <div class="grid md:grid-cols-3 gap-12 items-start">
                <div class="md:col-span-2">
                    <div class="flex flex-col md:flex-row items-center gap-8 mb-8 text-center md:text-left">
                        <img src="<?= htmlspecialchars($profile['photo_path']) ?>" alt="Photo" class="w-32 h-32 rounded-full shadow-lg border-4 border-white object-cover">
                        <h2 class="text-4xl md:text-5xl font-black mb-2 leading-tight">
                            Bonjour <span class="text-primary"><?= htmlspecialchars($app['company_name']) ?></span>.
                        </h2>
                    </div>
                    <div class="text-xl text-gray-700 leading-relaxed mb-8">
                        <?= nl2br(htmlspecialchars($app['custom_pitch'])) ?>
                    </div>
                    <div class="flex gap-4 items-center p-4 bg-white rounded-xl border border-blue-100">
                        <i class="fa-solid fa-quote-left text-blue-200 text-2xl"></i>
                        <p class="text-sm italic text-slate-600"><?= htmlspecialchars($profile['bio']) ?></p>
                    </div>
                </div>

                <div class="why-me-card bg-primary p-8 rounded-3xl shadow-2xl text-white mt-10">
                    <h3 class="text-xl font-bold mb-6 underline decoration-2 underline-offset-8">Pourquoi moi ?</h3>
                    <ul class="space-y-4 text-sm font-medium">
                        <li class="flex gap-3"><i class="fa-solid fa-check-to-slot opacity-70"></i> Leadership certifié ASFC</li>
                        <li class="flex gap-3"><i class="fa-solid fa-microchip opacity-70"></i> 20+ ans d'expertise IT</li>
                        <li class="flex gap-3"><i class="fa-solid fa-chart-line opacity-70"></i> Pilotage KPIs & Roadmaps</li>
                        <li class="flex gap-3"><i class="fa-solid fa-users-viewfinder opacity-70"></i> Culture Agile & SAFe</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="skills" class="mb-24">
            <h3 class="text-2xl font-black mb-10 flex items-center gap-3">
                <i class="fa-solid fa-gears text-gray-300"></i> Compétences & Expertises
            </h3>
            <div class="grid md:grid-cols-2 gap-8">
                <?php 
                $categories = ['management' => 'Gouvernance & Management', 'ops' => 'Excellence Opérationnelle', 'tech' => 'Expertise Technique'];
                foreach ($categories as $key => $title): 
                    $cat_skills = array_filter($all_skills, fn($s) => $s['category'] === $key);
                    if (empty($cat_skills)) continue;
                ?>
                <div class="bg-white p-8 rounded-2xl shadow-sm border">
                    <h4 class="font-bold text-primary uppercase text-xs tracking-widest mb-6"><?= $title ?></h4>
                    <ul class="space-y-3">
                        <?php foreach ($cat_skills as $skill): ?>
                        <li class="flex justify-between border-b border-gray-50 pb-2 text-sm">
                            <span class="text-gray-700"><?= htmlspecialchars($skill['label']) ?></span>
                            <span class="font-bold <?= ($key === $app['default_lens']) ? 'text-primary' : 'text-gray-400' ?>">
                                <?= htmlspecialchars($skill['level_text']) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="experience" class="mb-24">
            <h3 class="text-2xl font-black mb-10 flex items-center gap-3">
                <i class="fa-solid fa-briefcase text-gray-300"></i> Parcours Professionnel
            </h3>
            <div class="space-y-12">
                <?php foreach ($experiences as $exp): ?>
                <div class="p-8 rounded-xl transition hover:shadow-md border <?= ($exp['category'] === $app['default_lens']) ? 'highlight-ops' : 'bg-white border-gray-100' ?>">
                    <div class="flex flex-col md:flex-row justify-between mb-4">
                        <div>
                            <h4 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($exp['role']) ?></h4>
                            <p class="<?= ($exp['category'] === $app['default_lens']) ? 'text-primary' : 'text-gray-500' ?> font-bold italic">
                                <?= htmlspecialchars($exp['company']) ?> | <?= htmlspecialchars($exp['location']) ?>
                            </p>
                        </div>
                        <span class="text-sm font-bold text-gray-400"><?= htmlspecialchars($exp['period']) ?></span>
                    </div>
                    <div class="text-sm text-gray-600 leading-relaxed">
                        <ul class="list-disc ml-5 space-y-2">
                            <?php 
                            $lines = explode("\n", $exp['content']);
                            foreach ($lines as $line) {
                                if (trim($line)) echo "<li>" . htmlspecialchars(trim($line, "• ")) . "</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="grid md:grid-cols-3 gap-12 mb-24">
            <div class="md:col-span-2">
                <h3 class="text-2xl font-black mb-8">Formation & Certifications</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($educations as $edu): ?>
                    <div class="flex gap-4 p-5 bg-white border rounded-xl items-center shadow-sm">
                        <i class="fa-solid <?= htmlspecialchars($edu['icon']) ?> text-primary text-2xl"></i>
                        <div>
                            <h5 class="font-bold text-sm text-gray-800"><?= htmlspecialchars($edu['degree']) ?></h5>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($edu['institution']) ?> (<?= $edu['year'] ?>)</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="md:col-span-1">
                <h3 class="text-2xl font-black mb-8 text-gray-300">Pièces Jointes</h3>
                <div class="space-y-3">
                    <?php foreach ($attached_docs as $doc): ?>
                    <a href="/download.php?id=<?= $doc['id'] ?>&app=<?= $app['slug'] ?>" 
                       class="download-link flex items-center p-4 bg-white border rounded-xl hover:border-primary transition group"
                       data-filename="<?= htmlspecialchars($doc['label']) ?>">
                        <i class="fa-solid fa-file-pdf text-red-500 mr-3 text-xl"></i>
                        <span class="text-xs font-bold text-gray-700 uppercase group-hover:text-primary"><?= htmlspecialchars($doc['label']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <section class="bg-gray-900 rounded-3xl p-10 md:p-16 text-white shadow-2xl relative overflow-hidden">
            <div class="grid md:grid-cols-2 gap-12 items-center relative z-10">
                <div>
                    <h2 class="text-3xl font-black mb-6 italic">Nathanaël + <?= htmlspecialchars($app['company_name']) ?></h2>
                    <p class="text-gray-400 leading-relaxed mb-8">
                        Mon objectif est d'apporter la rigueur d'un ingénieur et la vision d'un COO pour faire évoluer vos opérations tout en préservant l'énergie de vos équipes.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="mailto:<?= $profile['email'] ?>?subject=Contact via CV Manganese (<?= $app['company_name'] ?>)" 
                           class="bg-primary px-8 py-4 rounded-full font-bold hover:opacity-90 transition flex items-center gap-2">
                           <i class="fa-solid fa-calendar-check"></i> Fixer un entretien
                        </a>
                    </div>
                </div>
                
                <div class="space-y-6 bg-gray-800/50 p-8 rounded-2xl border border-gray-700">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-4">Adéquation au poste</h4>
                    <?php 
                        $kpis = [
                            ['label' => 'Leadership & Bienveillance', 'val' => '95%'],
                            ['label' => 'Pilotage KPI / Opérations', 'val' => '100%'],
                            ['label' => 'Culture Logicielle & Agile', 'val' => '90%']
                        ];
                        foreach ($kpis as $kpi):
                    ?>
                    <div>
                        <div class="flex justify-between mb-2 text-[10px] font-bold tracking-widest uppercase">
                            <span><?= $kpi['label'] ?></span><span><?= $kpi['val'] ?></span>
                        </div>
                        <div class="w-full bg-gray-700 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-primary h-full rounded-full" style="width: <?= $kpi['val'] ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    </main>

    <footer class="text-center py-12 text-gray-400 text-xs border-t mt-12">
        &copy; <?= date('Y') ?> Manganese OS • Nathanaël Schmied • <a href="/manage/<?= ADMIN_ACCESS_KEY ?>" class="opacity-0">Admin</a>
    </footer>

    <script src="/assets/js/telemetry.js"></script>
</body>
</html>