<?php
/**
 * Routeur Logic - Project Manganese
 */

function dispatch(string $request_uri): void {
    $db = get_db_connection();
    
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');

    // 1. Administration Secrète
    if ($path === 'manage/' . ADMIN_ACCESS_KEY || $path === 'manage/' . ADMIN_ACCESS_KEY . '/') {
        render_view('admin_dashboard');
        return;
    }

    // 2. Traitement des URLs /go/entreprise
    if (str_starts_with($path, 'go/')) {
        $slug = str_replace('go/', '', $path);
        
        $stmt = $db->prepare("SELECT * FROM applications WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $app = $stmt->fetch();

        if ($app) {
            // On laisse telemetry.js gérer le tracking
            render_view('cv_interactive', ['app' => $app]);
            return;
        }
    }

    // 3. Service des documents (Media Proxy)
    if (str_starts_with($path, 'storage/')) {
        $decodedPath = urldecode($path);
        
        // On construit le chemin absolu à partir de la racine du projet
        $fullPath = __DIR__ . '/../' . $decodedPath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            // Nettoyage des tampons de sortie pour éviter de corrompre le PDF
            if (ob_get_level()) ob_end_clean();

            header('Content-Type: application/pdf');
            header('Content-Length: ' . filesize($fullPath));
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            readfile($fullPath);
            exit; // On arrête tout ici
        } else {
            // Debug pour toi : si ça échoue, on veut savoir quel chemin PHP a essayé de lire
            die("Fichier introuvable sur le disque. Chemin tenté : " . htmlspecialchars($fullPath));
        }
    }

    // 4. Landing Page par défaut
    render_view('public_home');
}

function render_view(string $view_name, array $data = []): void {
    extract($data);
    $file = PATH_CORE . '/templates/' . $view_name . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        echo "Erreur : La vue '{$view_name}' est introuvable.";
    }
}