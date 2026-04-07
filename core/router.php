<?php
/**
 * Routeur Logic - Project Manganese
 * PHP 8.4
 */

function dispatch(string $request_uri): void {
    $db = get_db_connection();
    
    // Nettoyage de l'URL pour ne garder que le chemin
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');

    // 1. ROUTE : Administration Secrète
    if ($path === 'manage/' . ADMIN_ACCESS_KEY || $path === 'manage/' . ADMIN_ACCESS_KEY . '/') {
        render_view('admin_dashboard');
        return;
    }

    // 2. ROUTE : Tracking URL (/go/entreprise)
    if (str_starts_with($path, 'go/')) {
        $slug = str_replace('go/', '', $path);
        
        // Recherche de la candidature en DB
        $stmt = $db->prepare("SELECT * FROM applications WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $app = $stmt->fetch();

        if ($app) {
            // TRACKING SILENCIEUX
            track_visit($app['id']);
            
            // CHARGEMENT DU CV PERSONNALISÉ
            // On passe les données de l'application à la vue
            render_view('cv_interactive', ['app' => $app]);
            return;
        }
    }

    // 3. ROUTE PAR DÉFAUT : Landing Page (Type LinkedIn)
    render_view('public_home');
}

/**
 * Enregistre la visite sans perturber l'affichage
 */
function track_visit(int $app_id): void {
    $db = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $db->prepare("INSERT INTO tracking_logs (app_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$app_id, $ip, $ua]);
    
    // Note : On pourrait ajouter ici l'envoi d'un email d'alerte
}

/**
 * Charge un fichier de template et lui passe des données
 */
function render_view(string $view_name, array $data = []): void {
    // Extraction des variables pour les rendre disponibles dans le template
    extract($data);
    
    $file = PATH_CORE . '/templates/' . $view_name . '.php';
    
    if (file_exists($file)) {
        include $file;
    } else {
        echo "Erreur : La vue '{$view_name}' est introuvable.";
    }
}