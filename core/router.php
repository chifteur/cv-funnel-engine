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

        // On définit si on est sur un chemin qui nécessite un tracking
        $should_track = (str_starts_with($path, 'go/') || str_starts_with($path, 'storage/'));
        if ($should_track) {

            // 1. On cherche si ce visiteur a déjà une session ouverte récemment dans la BDD
            $stmt = $db->prepare("SELECT bin_to_uuid(id) as s_id FROM telemetry_sessions 
                                WHERE visitor_uuid = ? 
                                AND started_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
                                ORDER BY started_at DESC LIMIT 1");
            $stmt->execute([$visitor_uuid]);
            $last_s = $stmt->fetch();

            if ($last_s) {
                Logger::debug("Restoring existing telemetry session from DB", ['visitor_uuid' => $visitor_uuid, 'session_id' => $last_s['s_id']]);
                $_SESSION['current_telemetry_id'] = $last_s['s_id'];
            } else {
                Logger::debug("No recent session found in DB, will create new session on demand", ['visitor_uuid' => $visitor_uuid]);
                $_SESSION['current_telemetry_id'] = generate_uuid();
            }
        } else {
            // Si on n'est pas sur un chemin critique, on s'assure que l'ID de session est vide
            $_SESSION['current_telemetry_id'] = null;
        }

    }
    //$session_id = $_SESSION['current_telemetry_id'];


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
            try {
                // 1. Détection du changement de contexte (Slug A -> Slug B)
                if (isset($_SESSION['current_app_id']) && $_SESSION['current_app_id'] !== $app['id']) {
                    unset($_SESSION['current_telemetry_id']);
                    Logger::debug("Slug:{$slug}-Context switch detected: Clearing telemetry session", [
                        'previous_app_id' => $_SESSION['current_app_id'],
                        'new_app_id' => $app['id']
                    ]);
                }
                
                $_SESSION['current_app_id'] = $app['id'];

                // 2. VÉRIFICATION DE SURVIE (Le correctif du bug)
                // Si PHP a un ID en mémoire, on vérifie s'il existe toujours physiquement en BDD
                if (isset($_SESSION['current_telemetry_id'])) {
                    $check = $db->prepare("SELECT 1 FROM telemetry_sessions WHERE id = UUID_TO_BIN(?)");
                    $check->execute([$_SESSION['current_telemetry_id']]);
                    if (!$check->fetch()) {
                        // La session a été supprimée de la BDD : on l'efface de PHP pour forcer la recréation
                        Logger::debug("Slug:{$slug}-Telemetry session ID in PHP not found in DB, unsetting to force recreation", [
                            'telemetry_id' => $_SESSION['current_telemetry_id']
                        ]);
                        unset($_SESSION['current_telemetry_id']);
                    }
                }

                // 3. Initialisation / Recréation de la session
                if (!isset($_SESSION['current_telemetry_id'])) {
                    $new_id = bin_to_uuid(random_bytes(16));
                    $_SESSION['current_telemetry_id'] = $new_id;
                    logger::debug("Slug:{$slug}-Creating new telemetry session", [
                        'telemetry_id' => $new_id,
                        'app_id' => $app['id'],
                        'visitor_uuid' => $visitor_uuid
                    ]);
                    log_session($db, $_SESSION['current_telemetry_id'], $app['id'], $visitor_uuid);

                    // Calcul de récurrence (Pour notification)
                    // On compte les sessions DIFFÉRENTES pour ce visiteur sur CETTE app
                    $stmt = $db->prepare("SELECT COUNT(*) FROM telemetry_sessions WHERE visitor_uuid = ? AND app_id = ?");
                    $stmt->execute([$visitor_uuid, $app['id']]);
                    $total_sessions = (int)$stmt->fetchColumn();
                    logger::debug("Slug:{$slug}-Visitor session count for app", [
                        'visitor_uuid' => $visitor_uuid,
                        'app_id' => $app['id'],
                        'total_sessions' => $total_sessions
                    ]);

                    // Si le compte est > 1, c'est qu'il y a d'anciennes sessions + celle qu'on vient de créer
                    if ($total_sessions > 1) {
                        logger::debug("Slug:{$slug}-Returning visitor detected", [
                            'visitor_uuid' => $visitor_uuid,
                            'app_id' => $app['id'],
                            'total_sessions' => $total_sessions
                        ]);
                        $msg = "🔥 ALERTE RETOUR : {$app['company_name']} est de retour sur ton CV ! (Visite n°{$total_sessions})";
                    } else {
                        logger::debug("Slug:{$slug}-First time visitor detected", [
                            'visitor_uuid' => $visitor_uuid,
                            'app_id' => $app['id']
                        ]);
                        $msg = "🚀 PREMIÈRE VISITE : {$app['company_name']} découvre ton CV.";
                    }

                    // Notification Telegram (On envoie le message de récurrence)
                    sendTelegramNotification($msg);                    
                }

                // --- POINT DE RÉFÉRENCE : On utilise toujours la session de la superglobale ---
                $active_sid = $_SESSION['current_telemetry_id'];
                logger::debug("Slug:{$slug}-Active telemetry session ready", [
                    'active_sid' => $active_sid,
                    'app_id' => $app['id'],
                    'visitor_uuid' => $visitor_uuid
                ]);


                // Log de l'événement vue (On utilise $active_sid !)
                log_event($db, $active_sid, 'view_section', 'landing', 'Ouverture initiale');
                

            } catch (Exception $e) {
                // error_log("Manganese Telemetry Error: " . $e->getMessage());
                Logger::error("Slug:{$slug}-Error during telemetry handling", [
                    'error_message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]);
            }

            render_view('cv_interactive', ['app' => $app, 'telemetry_id' => $_SESSION['current_telemetry_id']]);
            return;
        }
    }
    
    // 3. Service des documents & images (Media Proxy)
    if (str_starts_with($path, 'storage/')) {
        $decodedPath = urldecode($path);
        $fullPath = __DIR__ . '/../' . $decodedPath;

        if (file_exists($fullPath) && is_file($fullPath)) {
            // 1. DÉTERMINATION DU SID (Priorité à l'URL pour l'isolation des onglets)
            $sid_to_log = $_GET['sid'] ?? $_SESSION['current_telemetry_id'] ?? null;

            // 2. LOG DE L'ÉVÉNEMENT (Avec bouclier de protection)
            if ($sid_to_log && str_starts_with($path, 'storage/docs/')) {
                try {
                    // On logue l'action
                    log_event($db, $sid_to_log, 'download', basename($fullPath), "Téléchargement direct");
                    
                    // On met à jour la durée de session
                    $stmt = $db->prepare("UPDATE telemetry_sessions SET duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()) WHERE id = ?");
                    $stmt->execute([uuid_to_bin($sid_to_log)]);
                } catch (Exception $e) {
                    // Si la BDD échoue, on logue l'erreur en secret mais on laisse le fichier sortir
                    error_log("Manganese Storage Log Error: " . $e->getMessage());
                }
            }

            // Utilisation de la classe finfo (plus besoin de finfo_close)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($fullPath);

            // Nettoyage pour éviter la corruption du fichier binaire
            if (ob_get_level()) ob_end_clean();

            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($fullPath));
            header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
            
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
    static $tg_config = null;
    $db = get_db_connection();

    // On ne récupère les réglages qu'une seule fois par exécution
    if ($tg_config === null) {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('tg_bot_token', 'tg_chat_id')");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $tg_config = $settings;
    }

    $token = $tg_config['tg_bot_token'] ?? null;
    $chatId = $tg_config['tg_chat_id'] ?? null;

    // Si les clés sont absentes ou par défaut, on avorte silencieusement
    if (!$token || !$chatId || $token === 'VOTRE_TOKEN_ICI') {
        return false;
    }

    $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&parse_mode=HTML&text=" . urlencode($message);
    
    // Utilisation de curl (plus robuste) ou file_get_contents
    // On ajoute un timeout court pour ne pas ralentir le chargement du CV
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    return @file_get_contents($url, false, $ctx);
}