<?php
/**
 * PAGE PUBLIC - MANGANESE OS
 * Basé sur la Maquette V5
 */

$db = get_db_connection();

// On charge tout depuis la DB
$profile = $db->query("SELECT * FROM profile_settings WHERE id = 1")->fetch();

$page_title = "Dossier Professionnel";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 overflow-hidden" x-data="{ open: false }">

    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-100/50 rounded-full blur-[120px]"></div>
    </div>

    <main class="relative z-10 min-h-screen flex flex-col items-center justify-center p-6">
        
        <div class="mb-12 text-center" x-init="setTimeout(() => open = true, 500)">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-slate-200 shadow-sm mb-6">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 italic">Dossier Professionnel</span>
            </div>
            <h1 class="text-5xl md:text-7xl font-black italic tracking-tighter text-slate-900 mb-2 uppercase">
                <?= htmlspecialchars($profile['full_name'] ?? 'Profil Professionnel') ?><span class="text-blue-600">.</span>
            </h1>
            <p class="text-slate-400 font-medium tracking-tight">Espace de consultation sécurisé</p>
        </div>

        <div x-show="open" 
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="w-full max-w-md bg-white border border-slate-200 p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50">
            
            <div class="text-center mb-8">
                <h2 class="text-sm font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Accès Privé</h2>
                <p class="text-xs text-slate-500">Veuillez entrer votre clé d'accès pour consulter le dossier complet.</p>
            </div>

            <form x-data="{ slug: '' }" 
                @submit.prevent="if(slug) window.location.href = '/go/' + slug.trim()" 
                class="space-y-4">
                
                <div class="relative group">
                    <input type="text" 
                        x-model="slug"
                        placeholder="Ex: entreprise-2026" 
                        class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm font-mono outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50 transition-all">
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-blue-500 transition-colors">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <button type="submit" 
                        :disabled="!slug"
                        :class="slug ? 'bg-slate-900 shadow-slate-200' : 'bg-slate-300 cursor-not-allowed shadow-none'"
                        class="w-full text-white rounded-2xl py-4 font-black text-xs uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg">
                    Déverrouiller le dossier
                </button>
            </form>

            <?php if (!empty($profile['linkedin_url'])): ?>
            <div class="mt-8 pt-8 border-t border-slate-50 text-center">
                <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest mb-4">Vous n'avez pas de clé ?</p>
                <a href="<?= htmlspecialchars($profile['linkedin_url']) ?>" 
                   target="_blank"
                   class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="fa-brands fa-linkedin"></i> Me contacter sur LinkedIn
                </a>
            </div>
            <?php endif; ?>
        </div>

        <footer class="mt-12 text-[9px] font-black text-slate-300 uppercase tracking-[0.3em]">
            Protégé par Manganese Engine • 2026
        </footer>
    </main>

</body>
</html>