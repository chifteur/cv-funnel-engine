<?php
/**
 * Routeur Logic - Project Manganese
 */

function dispatch(string $request_uri): void {
    $db = get_db_connection();
    
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');

    // --- GESTION DU VISITEUR ---
    $visitor_uuid = $_COOKIE['mg_v_uuid'] ?? null;
    if (!$visitor_uuid) {
        $visitor_uuid = generate_uuid();
        setcookie('mg_v_uuid', $visitor_uuid, time() + (86400 * 365), "/");
    }

    // --- LOGIQUE DE SESSION ROBUSTE ---
    if (!isset($_SESSION['current_telemetry_id'])) {
        // 1. On cherche si ce visiteur a déjà une session ouverte récemment dans la BDD
        $stmt = $db->prepare("SELECT bin_to_uuid(id) as s_id FROM telemetry_sessions 
                            WHERE visitor_uuid = ? 
                            AND started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                            ORDER BY started_at DESC LIMIT 1");
        $stmt->execute([$visitor_uuid]);
        $last_s = $stmt->fetch();

        if ($last_s) {
            $_SESSION['current_telemetry_id'] = $last_s['s_id'];
        } else {
            $_SESSION['current_telemetry_id'] = generate_uuid();
        }
    }
    $session_id = $_SESSION['current_telemetry_id'];


    // 1. Administration Modulaire (manage?key=...&module=...&id=...)
    if ($path === 'manage') {
        $providedKey = $_GET['key'] ?? '';
        
        // Sécurité : Vérification de la clé
        if ($providedKey !== ADMIN_ACCESS_KEY) {
            render_view('public_home');
            return;
        }

        // Paramètres
        $module = $_GET['module'] ?? 'dashboard'; // 'dashboard' par défaut
        $id = $_GET['id'] ?? null;
        
        $view_name = "admin_module_" . $module;

        // Rendu de la vue
        render_view($view_name, [
            'module' => $module,
            'id' => $id,
            'key' => $providedKey
        ]);
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
            // Log de la session si c'est le premier hit
            log_session($db, $session_id, $app['id'], $visitor_uuid);
            // Log de l'événement vue
            log_event($db, $session_id, 'view_section', 'landing', 'Ouverture du CV');
            
            // Notification Telegram (Optionnel)
            sendTelegramNotification("🚀 Visite sur le CV : {$app['company_name']}\nSlug: $slug");

            render_view('cv_interactive', ['app' => $app]);
            return;
        }
    }

    // 3. Service des documents & images (Media Proxy)
    if (str_starts_with($path, 'storage/')) {
        $decodedPath = urldecode($path);
        $fullPath = __DIR__ . '/../' . $decodedPath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            // LOG DE TÉLÉMÉTRIE AVANT LE SERVING
            log_event($db, $session_id, 'download', basename($fullPath));

            // Utilisation de la classe finfo (plus besoin de finfo_close)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fullPath);

            // Nettoyage pour éviter la corruption du fichier binaire
            if (ob_get_level()) ob_end_clean();

            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($fullPath));
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            
            // Emplacement futur pour ta télémétrie
            // track_view($decodedPath); 

            readfile($fullPath);
            exit;
        } else {
            header("HTTP/1.0 404 Not Found");
            die("Ressource introuvable.");
        }
    }

    // 4. API Telemetry (Appels depuis le JS)
    if ($path === 'api/telemetry' || $path === 'api/telemetry.php') {
        require_once __DIR__ . '/../public/api/telemetry.php';
        return; // On arrête le script ici
    }

    // 5. Landing Page par défaut
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


function log_session($db, $s_id, $app_id, $v_uuid) {
    $stmt = $db->prepare("INSERT IGNORE INTO telemetry_sessions (id, app_id, visitor_uuid, ip_address, user_agent, browser_lang) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        uuid_to_bin($s_id), 
        $app_id, 
        $v_uuid, 
        $_SERVER['REMOTE_ADDR'], 
        $_SERVER['HTTP_USER_AGENT'],
        substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2)
    ]);
}

function log_event($db, $s_id, $type, $el_id, $data = '') {
    $stmt = $db->prepare("INSERT INTO telemetry_events (session_id, event_type, element_id, event_data) VALUES (?, ?, ?, ?)");
    $stmt->execute([uuid_to_bin($s_id), $type, $el_id, $data]);
}

function sendTelegramNotification($message) {
    $token = "TON_BOT_TOKEN"; // À récupérer sur @BotFather
    $chatId = "TON_CHAT_ID";   // À récupérer via @userinfobot
    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($message);
    @file_get_contents($url);
}