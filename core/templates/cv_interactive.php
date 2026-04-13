<?php
/**
 * CV INTERACTIF DYNAMIQUE - MANGANESE OS
 * Basé sur la Maquette V3
 */

$db = get_db_connection();

// On charge tout depuis la DB
$profile = $db->query("SELECT * FROM profile_settings WHERE id = 1")->fetch();
$cv_exps = $db->query("SELECT * FROM cv_experiences ORDER BY display_order ASC, id DESC")->fetchAll();
$cv_skills = $db->query("SELECT * FROM cv_skills ORDER BY category")->fetchAll();
$cv_edus = $db->query("SELECT * FROM cv_education ORDER BY year DESC")->fetchAll();
$cv_langs = $db->query("SELECT * FROM cv_languages")->fetchAll();

// Docs liés
$stmtDocs = $db->prepare("SELECT d.* FROM documents d JOIN rel_app_doc rel ON d.id = rel.doc_id WHERE rel.app_id = ? ORDER BY d.category");
$stmtDocs->execute([$app['id']]);
$attached_docs = $stmtDocs->fetchAll();

$lens = $app['default_lens'];

// Grouper les skills par catégorie
$skills_by_category = [];
foreach ($cv_skills as $skill) {
    $cat = $skill['category'];
    if (!isset($skills_by_category[$cat])) {
        $skills_by_category[$cat] = [];
    }
    $skills_by_category[$cat][] = $skill;
}
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
        .why-me-card { transform: rotate(-1.5deg); }
        .highlight-lens { border-left: 5px solid #3b82f6; background: white; }
        .hero-gradient { background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); }

        @media print {
            /* Cache les éléments inutiles sur le PDF */
            .print\:hidden { display: none !important; }

            header, 
            .fixed, 
            .sticky {
                position: static !important;
                width: 100% !important;
                box-shadow: none !important;
                border: none !important;
            }
            
            /* Force le fond blanc et les couleurs */
            body { 
                    background: white !important; 
                    print-color-adjust: exact; 
                    -webkit-print-color-adjust: exact; 
                    font-size: 11pt; /* Taille standard pour la lecture papier */
                }
            
            /* Supprime les headers/footers ajoutés par le navigateur (date, url) */
            @page { margin: 1.5cm; size: auto; }
            body { 
                margin: 1cm; 
                padding-top: 0 !important;
            }
            header {
                margin-bottom: 2rem;
            }

            .why-me-card, .skill-category, .experience-card, .document-card, .education-card, .match-card {
                break-inside: avoid;
            }
            
            h2 {
                break-after: avoid; /* Évite qu'un titre soit seul en bas de page */
            }            
        }        
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased font-sans leading-normal" data-telemetry-id="<?= $telemetry_id ?>">

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tighter"><?= htmlspecialchars(strtoupper($profile['full_name'] ?? '')) ?></h1>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest"><?= htmlspecialchars($profile['job_title'] ?? '') ?> • Candidature <?= htmlspecialchars($app['company_name']) ?></p>
            </div>
            <div class="hidden md:flex items-center gap-6 text-sm font-medium print:hidden">
                <a href="#experience" class="hover:text-blue-600 transition">Expérience</a>
                <a href="#skills" class="hover:text-blue-600 transition">Compétences</a>
                <a href="#education" class="hover:text-blue-600 transition">Éducation</a>
                <a href="mailto:<?= $profile['email'] ?? '' ?>" class="bg-slate-900 text-white px-4 py-2 rounded-full hover:bg-blue-600 transition whitespace-nowrap">Contact direct</a>                
            </div>
            <div class="fixed top-4 right-4 print:hidden">
                <button onclick="window.print()" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black shadow-xl hover:bg-blue-600 transition flex items-center gap-2">
                    <i class="fa-solid fa-file-pdf"></i> EXPORTER EN PDF
                </button>
            </div>            
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-12">

        <!-- HERO SECTION -->
        <section id="hero" class="mb-24 hero-gradient p-10 rounded-3xl border shadow-sm">
            <div class="grid md:grid-cols-3 gap-12 items-start">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-8 mb-4">
                        <div class="flex flex-col items-center shrink-0">
                            <img src="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" 
                                alt="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" 
                                class="h-40 w-auto max-w-48 rounded-3xl shadow-xl border-4 border-white object-cover transform hover:scale-105 transition duration-300">
                            
                            <div class="flex gap-3 mt-4">
                                <?php if (!empty($profile['phone'])): ?>
                                    <a href="tel:<?= $profile['phone'] ?>" title="M'appeler" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-blue-600 hover:bg-blue-600 hover:text-white transition-all duration-300 border border-slate-100">
                                        <i class="fa-solid fa-phone text-sm"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($profile['linkedin_url'])): ?>
                                    <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank" title="Mon profil LinkedIn" class="w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-sm text-[#0077b5] hover:bg-[#0077b5] hover:text-white transition-all duration-300 border border-slate-100">
                                        <i class="fa-brands fa-linkedin-in text-sm"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h2 class="text-4xl md:text-5xl font-black leading-tight">
                            Bonjour <span class="text-blue-600"><?= htmlspecialchars($app['company_name']) ?></span>
                        </h2>
                    </div>
                    <p class="text-xl text-gray-700 leading-relaxed mb-8">
                        <?= nl2br(htmlspecialchars($app['custom_pitch'] ?? '')) ?>
                    </p>
                    <div class="flex gap-4 items-center p-6 bg-white rounded-xl border">
                        <i class="fa-solid fa-quote-left text-blue-300 text-2xl"></i>
                        <p class="text-base italic text-blue-900"><?= htmlspecialchars($app['strengths'] ?? '') ?></p>
                    </div>
                </div>

                <div class="why-me-card bg-blue-600 p-8 rounded-3xl shadow-2xl text-white mt-10">
                    <h3 class="text-xl font-bold mb-6 underline decoration-2 underline-offset-8">Pourquoi moi ?</h3>
                    <ul class="space-y-4 text-sm font-medium leading-relaxed">
                        <?php 
                        // 1. On récupère le texte et on le découpe à chaque saut de ligne
                        // PHP_EOL gère intelligemment les retours à la ligne selon le système (Unix/Windows)
                        $why_me_lines = explode("\n", $app['why_me'] ?? ''); 
                        
                        // 2. On nettoie les lignes vides pour éviter des listes à puces inutiles
                        $why_me_lines = array_filter(array_map('trim', $why_me_lines));

                        foreach ($why_me_lines as $line): 
                            // Par défaut, l'icône est un "check"
                            $iconName = 'check';
                            $displayText = $line;
                            // On vérifie si la ligne contient ":" pour changer l'icône
                            if (strpos($line, ':') !== false) {
                                $parts = explode(':', $line, 2); // On sépare en 2 parties max
                                $iconName = trim($parts[0]);
                                $displayText = trim($parts[1]);
                            }
                        ?>
                            <li class="flex gap-3">
                                <i class="fa-solid fa-<?= htmlspecialchars($iconName) ?> text-blue-200"></i>
                                <span><?= htmlspecialchars($displayText) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
        <!-- SKILLS SECTION -->
        <?php if (!empty($cv_skills)): ?>
        <section id="skills" class="mb-24">
            <h3 class="text-2xl font-black mb-10 flex items-center gap-3">
                <i class="fa-solid fa-gears text-gray-300"></i> Compétences & Méthodologies
            </h3>
            <div class="grid md:grid-cols-<?= count($skills_by_category) >= 2 ? '2' : '1' ?> gap-8">
                <?php foreach ($skills_by_category as $category => $skills): ?>
                <div class="skill-category bg-white p-8 rounded-2xl shadow-sm border">
                    <h4 class="font-bold text-blue-600 uppercase text-xs tracking-widest mb-6">
                        <?= $category === 'management' ? 'Management & Gouvernance' : ($category === 'ops' ? 'Excellence Opérationnelle' : 'Techniques & Technologie') ?>
                    </h4>
                    <ul class="space-y-3 text-gray-700">
                        <?php foreach ($skills as $skill): ?>
                        <li class="flex justify-between border-b pb-2 text-sm">
                            <span><?= htmlspecialchars($skill['label']) ?></span>
                            <span class="font-bold <?= $skill['category'] === $lens ? 'text-blue-600' : '' ?>"><?= htmlspecialchars($skill['level_text']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- EXPERIENCE SECTION -->
        <?php if (!empty($cv_exps)): ?>
        <section id="experience" class="mb-24">
            <h3 class="text-2xl font-black mb-10 flex items-center gap-3">
                <i class="fa-solid fa-briefcase text-gray-300"></i> Parcours Professionnel
            </h3>
            <div class="space-y-8">
                <?php foreach($cv_exps as $exp): ?>
                <div class="experience-card p-8 rounded-xl shadow-sm border <?= $exp['category'] === $lens ? 'highlight-lens' : 'bg-white' ?> hover:shadow-md transition">
                    <div class="flex flex-col md:flex-row justify-between mb-4">
                        <div>
                            <h4 class="text-xl font-bold"><?= htmlspecialchars($exp['role']) ?></h4>
                            <p class="text-blue-600 font-bold"><?= htmlspecialchars($exp['company']) ?></p>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($exp['location']) ?></p>
                        </div>
                        <span class="text-sm font-bold text-gray-400"><?= htmlspecialchars($exp['period']) ?></span>
                    </div>
                    <ul class="list-disc ml-5 space-y-2 text-gray-600 text-sm">
                        <?php foreach (explode("\n", $exp['content']) as $line): ?>
                            <?php if (trim($line)): ?>
                            <li><?= htmlspecialchars(trim($line)) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- DOCUMENTS SECTION -->
        <?php if (!empty($attached_docs)): ?>
        <section id="documents" class="document-card mb-24 print:mb-8">
            <h3 class="text-2xl font-black mb-10 flex items-center gap-3 print:mb-4 print:text-xl">
                <i class="fa-solid fa-folder-open text-blue-600"></i> Documents & Certifications
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:gap-3">
                <?php foreach ($attached_docs as $doc): 
                    // Logique d'icône et de couleur par catégorie
                    $isDiploma = ($doc['category'] === 'diploma');
                    $icon = $isDiploma ? 'fa-graduation-cap' : 'fa-file-signature';
                    $catLabel = $isDiploma ? 'Diplôme / Certification' : 'Référence Professionnelle';
                    $accentClass = $isDiploma ? 'text-blue-600 bg-blue-50' : 'text-slate-400 bg-slate-50';
                ?>
                <a href="/storage/docs/<?= htmlspecialchars($doc['filename']) ?>" 
                target="_blank" 
                class="group flex gap-5 p-6 bg-white border border-slate-100 rounded-3xl items-center shadow-sm hover:shadow-xl hover:border-blue-200 transition-all duration-300 transform hover:-translate-y-1 print:p-3 print:rounded-2xl print:gap-3">
                    
                    <div class="w-16 h-16 <?= $accentClass ?> rounded-2xl flex items-center justify-center shrink-0 group-hover:bg-blue-600 transition-colors duration-300 print:w-10 print:h-10 print:rounded-xl">
                        <i class="fa-solid <?= $icon ?> text-2xl group-hover:text-white print:text-base"></i>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <span class="text-[10px] font-black uppercase tracking-wider <?= $isDiploma ? 'text-blue-500' : 'text-slate-400' ?> print:text-[8px]">
                            <?= $catLabel ?>
                        </span>
                        <h5 class="font-bold text-slate-800 leading-tight truncate group-hover:text-blue-600 transition-colors print:text-xs">
                            <?= htmlspecialchars($doc['label']) ?>
                        </h5>
                        <p class="text-xs text-slate-400 mt-1 print:hidden">Format PDF • Cliquez pour ouvrir</p>
                    </div>

                    <div class="text-slate-200 group-hover:text-blue-500 transition-colors print:hidden">
                        <i class="fa-solid fa-arrow-up-right-from-square text-xl"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- EDUCATION SECTION -->
        <?php if (!empty($cv_edus) || !empty($cv_langs)): ?>
        <section id="education" class="education-card mb-24 print:mb-8">
            <h3 class="text-2xl font-black mb-10 flex items-center gap-3 print:mb-4 print:text-xl">
                <i class="fa-solid fa-graduation-cap text-gray-300"></i> Formation & Certifications
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print:gap-3">
                <?php foreach ($cv_edus as $edu): ?>
                <div class="flex gap-4 p-6 bg-white border rounded-xl items-center shadow-sm print:gap-2 print:p-3 print:rounded-xsl">
                    <i class="fa-solid fa-<?= htmlspecialchars($edu['icon'] ?? 'award') ?> text-blue-600 text-3xl print:text-base print:text-black"></i>
                    <div>
                        <h5 class="font-bold text-gray-800"><?= htmlspecialchars($edu['degree']) ?></h5>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($edu['institution']) ?><?= !empty($edu['year']) ? ', ' . htmlspecialchars($edu['year']) : '' ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($cv_langs)): ?>
                <div class="flex gap-4 p-6 bg-white border rounded-xl items-center shadow-sm print:gap-2 print:p-3 print:rounded-xsl print:text-black">
                    <i class="fa-solid fa-language text-blue-600 text-3xl"></i>
                    <div>
                        <h5 class="font-bold text-gray-800">Langues</h5>
                        <p class="text-sm text-gray-500">
                            <?= implode(' | ', array_map(fn($l) => htmlspecialchars($l['label']) . ' (' . htmlspecialchars($l['level']) . ')', $cv_langs)) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- MATCH SECTION -->
        <section id="match" class="match-card bg-gray-900 rounded-3xl p-10 md:p-16 text-white shadow-2xl">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl font-black mb-6 italic">Pourquoi <?= htmlspecialchars($profile['full_name'] ?? '') ?> + <?= htmlspecialchars($app['company_name']) ?> ?</h2>
                    <p class="text-gray-400 leading-relaxed mb-8">
                        <?= nl2br(htmlspecialchars($app['perfect_match'] ?? '')) ?>
                    </p>
                </div>
                <div class="space-y-6 bg-gray-800 p-8 rounded-2xl border border-gray-700">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4 text-center">Indicateurs d'Adéquation</h4>
                    <?php 
                    $lens_scores = [
                        'management' => 85,
                        'ops' => 95,
                        'tech' => 80
                    ];
                    $scores = [
                        'Expertise ' . $lens => $lens_scores[$lens] ?? 85,
                        'Culture D\'équipe' => 90,
                        'Expérience Opérationnelle' => 88
                    ];
                    ?>
                    <?php foreach ($scores as $label => $score): ?>
                    <div>
                        <div class="flex justify-between mb-2 text-xs font-bold tracking-widest uppercase">
                            <span><?= htmlspecialchars($label) ?></span><span><?= $score ?>%</span>
                        </div>
                        <div class="w-full bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $score ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    </main>

    <div class="fixed bottom-10 left-1/2 -translate-x-1/2 flex gap-4 z-50 w-full max-w-fit px-4 print:hidden">
        <a href="mailto:<?= $profile['email'] ?? '' ?>?subject=Contact suite à votre CV personnalisé (<?= urlencode($profile['full_name'] ?? '') ?>)" 
        class="bg-blue-600 text-white px-8 py-4 rounded-full font-bold shadow-2xl hover:bg-blue-700 hover:scale-105 transition-all duration-300 flex items-center gap-3 border-4 border-white">
            <i class="fa-solid fa-calendar-check"></i> 
            <span>Discuter de ma contribution</span>
        </a>
    </div>

    <footer class="text-center pt-12 pb-32 text-gray-400 text-xs border-t mt-12 bg-slate-50/50 print:pt-4 print:pb-4 print:mt-6 print:bg-transparent">
        <div class="max-w-4xl mx-auto px-6">
            
            <div class="flex flex-wrap justify-center gap-x-8 gap-y-4 mb-8 text-gray-500 font-medium print:gap-x-4 print:mb-2 print:text-[10px]">
                <?php if (!empty($profile['phone'])): ?>
                    <a href="tel:<?= $profile['phone'] ?>" class="hover:text-blue-600 flex items-center gap-2 transition">
                        <i class="fa-solid fa-phone text-[10px]"></i> <?= htmlspecialchars($profile['phone']) ?>
                    </a>
                <?php endif; ?>

                <?php if (!empty($profile['linkedin_url'])): ?>
                    <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" target="_blank" class="hover:text-blue-600 flex items-center gap-2 transition">
                        <i class="fa-brands fa-linkedin-in text-[10px]"></i> LinkedIn
                    </a>
                <?php endif; ?>

                <a href="mailto:<?= $profile['email'] ?? '' ?>" class="hover:text-blue-600 flex items-center gap-2 transition">
                    <i class="fa-solid fa-envelope text-[10px]"></i> <?= htmlspecialchars($profile['email'] ?? '') ?>
                </a>
            </div>

            <p class="mb-2 print:mb-0 print:text-[9px]">
                &copy; 2026 Manganese — <strong><?= htmlspecialchars($profile['full_name'] ?? '') ?></strong>
            </p>
            <p class="opacity-75 print:text-[9px]">
                Dossier créé spécifiquement pour <span class="text-blue-600 font-semibold print:text-black"><?= htmlspecialchars($app['company_name']) ?></span>.
            </p>
        </div>
    </footer>

    <script src="/assets/js/telemetry.js"></script>
</body>
</html>