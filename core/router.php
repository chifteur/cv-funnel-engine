<?php
/**
 * Routeur Logic - Project Manganese
 */
require_once __DIR__ . '/admin_recovery.php';

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


    // 1. Administration modulaire
    if ($path === 'manage') {
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow, noarchive');

        $manageUrl = rtrim(SITE_URL, '/') . '/manage';
        $storedAdminKeyHash = get_admin_access_key_hash($db);
        $csrfToken = $_SESSION['admin_csrf_token'] ??= bin2hex(random_bytes(32));
        $postAction = $_POST['action'] ?? '';

        if (isset($_GET['activation']) && in_array($_GET['activation'], ['pending', 'mail_error'], true)) {
            render_view('admin_recovery', [
                'mode' => 'status',
                'csrfToken' => $csrfToken,
                'statusMessage' => $_GET['activation'] === 'mail_error'
                    ? 'Le compte est configuré, mais l’email de vérification n’a pas pu être envoyé. Vérifie la configuration email puis réessaie.'
                    : 'Compte configuré. Consulte ta boîte email et confirme l’adresse avant de te connecter.',
                'showResendVerification' => true,
            ]);
            return;
        }

        if (isset($_GET['email_verified']) && $_GET['email_verified'] === '1') {
            render_view('admin_recovery', [
                'mode' => 'status',
                'csrfToken' => $csrfToken,
                'statusMessage' => 'Adresse de récupération vérifiée. Tu peux maintenant te connecter.',
            ]);
            return;
        }

        if (isset($_GET['activate']) || $postAction === 'activate_admin') {
            $activationToken = $_GET['activate'] ?? ($_POST['activation_token'] ?? '');
            $activationToken = is_string($activationToken) ? $activationToken : '';

            if (!is_admin_recovery_token_valid($db, $activationToken, 'activation')) {
                render_view('admin_recovery', [
                    'mode' => 'status',
                    'csrfToken' => $csrfToken,
                    'statusMessage' => 'Ce lien d’activation est invalide, expiré ou déjà utilisé. Demande à l’opérateur de générer un nouveau lien.',
                ]);
                return;
            }

            if ($postAction === 'activate_admin') {
                $submittedCsrfToken = $_POST['csrf_token'] ?? '';
                $newKey = $_POST['new_admin_key'] ?? '';
                $confirmKey = $_POST['confirm_admin_key'] ?? '';
                $recoveryEmail = $_POST['recovery_email'] ?? '';
                $recoveryEmail = is_string($recoveryEmail) ? strtolower(trim($recoveryEmail)) : '';

                if (!is_string($submittedCsrfToken) || !hash_equals($csrfToken, $submittedCsrfToken)) {
                    $recoveryError = 'Le formulaire a expiré. Recharge le lien et réessaie.';
                } elseif (!is_string($newKey) || strlen($newKey) < 12 || strlen($newKey) > 256) {
                    $recoveryError = 'La clé doit contenir entre 12 et 256 caractères.';
                } elseif (!is_string($confirmKey) || !hash_equals($newKey, $confirmKey)) {
                    $recoveryError = 'La confirmation de la clé ne correspond pas.';
                } elseif (!filter_var($recoveryEmail, FILTER_VALIDATE_EMAIL) || strlen($recoveryEmail) > 254) {
                    $recoveryError = 'Saisis une adresse email valide.';
                } else {
                    $newKeyHash = password_hash($newKey, PASSWORD_DEFAULT);
                    if (!is_string($newKeyHash)) {
                        throw new RuntimeException('Impossible de sécuriser la clé admin.');
                    }

                    $db->beginTransaction();
                    if (!consume_admin_recovery_token($db, $activationToken, 'activation')) {
                        $db->rollBack();
                        $recoveryError = 'Ce lien d’activation a déjà été utilisé ou a expiré.';
                    } else {
                        set_admin_setting($db, 'admin_access_key_hash', $newKeyHash);
                        set_admin_setting($db, 'admin_recovery_email', $recoveryEmail);
                        set_admin_setting($db, 'admin_recovery_email_verified', '0');
                        $verificationToken = create_admin_recovery_token($db, 'email_verification', 1440, $recoveryEmail);
                        $db->commit();
                        session_regenerate_id(true);
                        $_SESSION['admin_key_fingerprint'] = $newKeyHash;

                        try {
                            send_admin_recovery_email(
                                $recoveryEmail,
                                'Vérifie ton adresse de récupération',
                                $verificationToken,
                                'email_verification'
                            );
                            header('Location: ' . $manageUrl . '?activation=pending', true, 303);
                            exit;
                        } catch (RuntimeException $e) {
                            Logger::error('Initial admin email verification could not be sent', ['error' => $e->getMessage()]);
                            header('Location: ' . $manageUrl . '?activation=mail_error', true, 303);
                            exit;
                        }
                    }
                }
            }

            render_view('admin_recovery', [
                'mode' => 'activation',
                'csrfToken' => $csrfToken,
                'token' => $activationToken,
                'recoveryError' => $recoveryError ?? '',
            ]);
            return;
        }

        if (isset($_GET['verify_email']) || $postAction === 'verify_admin_email') {
            $verificationToken = $_GET['verify_email'] ?? ($_POST['verification_token'] ?? '');
            $verificationToken = is_string($verificationToken) ? $verificationToken : '';
            if ($postAction === 'verify_admin_email') {
                $submittedCsrfToken = $_POST['csrf_token'] ?? '';
                if (!is_string($submittedCsrfToken) || !hash_equals($csrfToken, $submittedCsrfToken)) {
                    $recoveryError = 'Le formulaire a expiré. Recharge le lien et réessaie.';
                } elseif (($pendingEmail = get_admin_setting($db, 'admin_recovery_email')) !== null) {
                    $db->beginTransaction();
                    if (consume_admin_recovery_token($db, $verificationToken, 'email_verification', $pendingEmail)) {
                        set_admin_setting($db, 'admin_recovery_email_verified', '1');
                        $invalidateTokens = $db->prepare(
                            "UPDATE admin_recovery_tokens SET used_at = NOW()
                             WHERE purpose = 'email_verification' AND used_at IS NULL"
                        );
                        $invalidateTokens->execute();
                        $db->commit();
                        header('Location: ' . $manageUrl . '?email_verified=1', true, 303);
                        exit;
                    }
                    $db->rollBack();
                    $recoveryError = 'Ce lien de vérification est invalide, expiré ou déjà utilisé.';
                } else {
                    $recoveryError = 'Ce lien de vérification est invalide, expiré ou déjà utilisé.';
                }
            } elseif (
                ($pendingEmail = get_admin_setting($db, 'admin_recovery_email')) === null
                || !is_admin_recovery_token_valid($db, $verificationToken, 'email_verification', $pendingEmail)
            ) {
                $recoveryError = 'Ce lien de vérification est invalide, expiré ou déjà utilisé.';
            }

            render_view('admin_recovery', [
                'mode' => 'verify_email',
                'csrfToken' => $csrfToken,
                'token' => $verificationToken,
                'recoveryError' => $recoveryError ?? '',
            ]);
            return;
        }

        if (isset($_GET['reset_key']) || $postAction === 'reset_admin_key') {
            $resetToken = $_GET['reset_key'] ?? ($_POST['reset_token'] ?? '');
            $resetToken = is_string($resetToken) ? $resetToken : '';
            $verifiedEmail = get_admin_setting($db, 'admin_recovery_email');
            $hasVerifiedEmail = get_admin_setting($db, 'admin_recovery_email_verified') === '1'
                && is_string($verifiedEmail)
                && $verifiedEmail !== '';
            if ($postAction === 'reset_admin_key') {
                $submittedCsrfToken = $_POST['csrf_token'] ?? '';
                $newKey = $_POST['new_admin_key'] ?? '';
                $confirmKey = $_POST['confirm_admin_key'] ?? '';
                if (!is_string($submittedCsrfToken) || !hash_equals($csrfToken, $submittedCsrfToken)) {
                    $recoveryError = 'Le formulaire a expiré. Recharge le lien et réessaie.';
                } elseif (!is_string($newKey) || strlen($newKey) < 12 || strlen($newKey) > 256) {
                    $recoveryError = 'La clé doit contenir entre 12 et 256 caractères.';
                } elseif (!is_string($confirmKey) || !hash_equals($newKey, $confirmKey)) {
                    $recoveryError = 'La confirmation de la clé ne correspond pas.';
                } else {
                    $newKeyHash = password_hash($newKey, PASSWORD_DEFAULT);
                    if (!is_string($newKeyHash)) {
                        throw new RuntimeException('Impossible de sécuriser la clé admin.');
                    }

                    $db->beginTransaction();
                    if (!$hasVerifiedEmail
                        || !consume_admin_recovery_token($db, $resetToken, 'password_reset', $verifiedEmail)) {
                        $db->rollBack();
                        $recoveryError = 'Ce lien est invalide, expiré ou déjà utilisé.';
                    } else {
                        $invalidateTokens = $db->prepare(
                            "UPDATE admin_recovery_tokens SET used_at = NOW()
                             WHERE purpose = 'password_reset' AND used_at IS NULL"
                        );
                        $invalidateTokens->execute();
                        set_admin_setting($db, 'admin_access_key_hash', $newKeyHash);
                        $db->commit();
                        unset($_SESSION['admin_key_fingerprint']);
                        header('Location: ' . $manageUrl . '?reset=complete', true, 303);
                        exit;
                    }
                }
            } elseif (!$hasVerifiedEmail
                || !is_admin_recovery_token_valid($db, $resetToken, 'password_reset', $verifiedEmail)) {
                $recoveryError = 'Ce lien est invalide, expiré ou déjà utilisé.';
            }

            render_view('admin_recovery', [
                'mode' => 'reset_key',
                'csrfToken' => $csrfToken,
                'token' => $resetToken,
                'recoveryError' => $recoveryError ?? '',
            ]);
            return;
        }

        if (isset($_GET['forgot_key']) || $postAction === 'request_admin_reset') {
            $recoveryMessage = 'Si cette adresse est vérifiée pour cette instance, un lien de réinitialisation sera envoyé.';
            if ($postAction === 'request_admin_reset') {
                $submittedCsrfToken = $_POST['csrf_token'] ?? '';
                $email = $_POST['recovery_email'] ?? '';
                $email = is_string($email) ? strtolower(trim($email)) : '';
                if (!is_string($submittedCsrfToken) || !hash_equals($csrfToken, $submittedCsrfToken)) {
                    $recoveryMessage = 'Le formulaire a expiré. Recharge la page et réessaie.';
                } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 254) {
                    $allowed = admin_recovery_request_allowed($db, 'password_reset', $email, $_SERVER['REMOTE_ADDR'] ?? '');
                    $configuredEmail = get_admin_setting($db, 'admin_recovery_email');
                    if ($allowed
                        && get_admin_setting($db, 'admin_recovery_email_verified') === '1'
                        && is_string($configuredEmail)
                        && hash_equals($configuredEmail, $email)
                        && get_admin_access_key_hash($db) !== null) {
                        $db->beginTransaction();
                        $invalidateTokens = $db->prepare(
                            "UPDATE admin_recovery_tokens SET used_at = NOW()
                             WHERE purpose = 'password_reset' AND used_at IS NULL"
                        );
                        $invalidateTokens->execute();
                        $resetToken = create_admin_recovery_token($db, 'password_reset', 30, $email);
                        $db->commit();
                        try {
                            send_admin_recovery_email($email, 'Réinitialise ta clé admin', $resetToken, 'password_reset');
                        } catch (RuntimeException $e) {
                            Logger::error('Admin password reset email could not be sent', ['error' => $e->getMessage()]);
                            consume_admin_recovery_token($db, $resetToken, 'password_reset');
                        }
                    }
                }
            }

            render_view('admin_recovery', [
                'mode' => 'forgot_key',
                'csrfToken' => $csrfToken,
                'recoveryMessage' => $recoveryMessage,
            ]);
            return;
        }

        if ($postAction === 'setup_admin_recovery_email') {
            $submittedCsrfToken = $_POST['csrf_token'] ?? '';
            $currentKey = $_POST['current_admin_key'] ?? '';
            $email = $_POST['recovery_email'] ?? '';
            $email = is_string($email) ? strtolower(trim($email)) : '';
            $configuredKeyHash = get_admin_access_key_hash($db);
            $sessionFingerprint = $_SESSION['admin_key_fingerprint'] ?? null;
            $sessionAuthorized = $configuredKeyHash !== null
                && is_string($sessionFingerprint)
                && hash_equals($configuredKeyHash, $sessionFingerprint);
            if (!is_string($submittedCsrfToken) || !hash_equals($csrfToken, $submittedCsrfToken)) {
                $recoveryError = 'Le formulaire a expiré. Recharge la page et réessaie.';
            } elseif (!$sessionAuthorized) {
                http_response_code(403);
                $recoveryError = 'Reconnecte-toi avec ta clé admin avant de configurer l’adresse de récupération.';
            } elseif ($configuredKeyHash === null || !is_string($currentKey) || !password_verify($currentKey, $configuredKeyHash)) {
                $recoveryError = 'La clé admin est incorrecte.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
                $recoveryError = 'Saisis une adresse email valide.';
            } elseif (!admin_recovery_request_allowed(
                $db,
                'email_verification',
                $email,
                $_SERVER['REMOTE_ADDR'] ?? ''
            )) {
                $recoveryError = 'Trop de demandes récentes pour cette adresse ou cette connexion. Réessaie plus tard.';
            } else {
                $db->beginTransaction();
                set_admin_setting($db, 'admin_recovery_email', $email);
                set_admin_setting($db, 'admin_recovery_email_verified', '0');
                $stmt = $db->prepare(
                    "UPDATE admin_recovery_tokens SET used_at = NOW()
                     WHERE purpose IN ('email_verification', 'password_reset') AND used_at IS NULL"
                );
                $stmt->execute();
                $verificationToken = create_admin_recovery_token($db, 'email_verification', 1440, $email);
                $db->commit();
                try {
                    send_admin_recovery_email($email, 'Vérifie ton adresse de récupération', $verificationToken, 'email_verification');
                    render_view('admin_recovery', [
                        'mode' => 'status',
                        'csrfToken' => $csrfToken,
                        'statusMessage' => 'Un email de vérification a été envoyé. Confirme l’adresse avant de te connecter.',
                        'showResendVerification' => true,
                    ]);
                    return;
                } catch (RuntimeException $e) {
                    Logger::error('Admin recovery email setup could not be sent', ['error' => $e->getMessage()]);
                    $recoveryError = 'L’adresse est enregistrée en attente de vérification, mais l’email n’a pas pu être envoyé. Vérifie la configuration email ou réessaie plus tard.';
                }
            }

            render_view('admin_recovery', [
                'mode' => 'setup_email',
                'csrfToken' => $csrfToken,
                'recoveryError' => $recoveryError,
                'currentEmail' => $email,
                'showResendVerification' => get_admin_setting($db, 'admin_recovery_email_verified') !== '1'
                    && get_admin_setting($db, 'admin_recovery_email') !== null,
            ]);
            return;
        }

        if ($postAction === 'resend_admin_verification') {
            $submittedCsrfToken = $_POST['csrf_token'] ?? '';
            if (is_string($submittedCsrfToken) && hash_equals($csrfToken, $submittedCsrfToken)) {
                $email = get_admin_setting($db, 'admin_recovery_email');
                if (is_string($email) && $email !== ''
                    && get_admin_setting($db, 'admin_recovery_email_verified') !== '1'
                    && admin_recovery_request_allowed($db, 'email_verification', $email, $_SERVER['REMOTE_ADDR'] ?? '')) {
                    $db->beginTransaction();
                    $invalidateTokens = $db->prepare(
                        "UPDATE admin_recovery_tokens SET used_at = NOW()
                         WHERE purpose = 'email_verification' AND used_at IS NULL"
                    );
                    $invalidateTokens->execute();
                    $verificationToken = create_admin_recovery_token($db, 'email_verification', 1440, $email);
                    $db->commit();
                    try {
                        send_admin_recovery_email($email, 'Vérifie ton adresse de récupération', $verificationToken, 'email_verification');
                    } catch (RuntimeException $e) {
                        Logger::error('Admin email verification could not be resent', ['error' => $e->getMessage()]);
                        consume_admin_recovery_token($db, $verificationToken, 'email_verification');
                    }
                }
            }
            render_view('admin_recovery', [
                'mode' => 'status',
                'csrfToken' => $csrfToken,
                'statusMessage' => 'Si une adresse attend une vérification, un nouvel email sera envoyé.',
                'showResendVerification' => true,
            ]);
            return;
        }

        $isAuthenticated = $storedAdminKeyHash !== null
            && isset($_SESSION['admin_key_fingerprint'])
            && is_string($_SESSION['admin_key_fingerprint'])
            && hash_equals($storedAdminKeyHash, $_SESSION['admin_key_fingerprint']);

        if (!$isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'admin_login') {
            $submittedToken = $_POST['csrf_token'] ?? '';
            $providedKey = $_POST['admin_key'] ?? '';
            $validCsrf = is_string($submittedToken) && hash_equals($csrfToken, $submittedToken);
            $validKey = $storedAdminKeyHash !== null
                && is_string($providedKey)
                && password_verify($providedKey, $storedAdminKeyHash);

            if ($validCsrf && $validKey) {
                session_regenerate_id(true);
                $_SESSION['admin_key_fingerprint'] = $storedAdminKeyHash;
                $isAuthenticated = true;
            } else {
                render_view('admin_login', [
                    'csrfToken' => $csrfToken,
                    'loginError' => $storedAdminKeyHash === null
                        ? "Aucune clé d’administration n’est configurée dans settings. Termine le provisioning avant de te connecter."
                        : 'Clé invalide. Vérifie la saisie et réessaie.',
                ]);
                return;
            }
        }

        if (!$isAuthenticated) {
            render_view('admin_login', [
                'csrfToken' => $csrfToken,
                'loginError' => $storedAdminKeyHash === null
                    ? "L’instance n’a pas encore été activée."
                    : (get_admin_setting($db, 'admin_recovery_email_verified') !== '1'
                        ? "Confirme ton adresse de récupération avant de te connecter."
                        : ''),
                'showLoginForm' => $storedAdminKeyHash !== null,
                'showResendVerification' => $storedAdminKeyHash !== null
                    && get_admin_setting($db, 'admin_recovery_email_verified') !== '1',
                'resetComplete' => isset($_GET['reset']) && $_GET['reset'] === 'complete',
            ]);
            return;
        }

        if (get_admin_setting($db, 'admin_recovery_email_verified') !== '1') {
            $pendingEmail = get_admin_setting($db, 'admin_recovery_email');
            render_view('admin_recovery', [
                'mode' => 'setup_email',
                'csrfToken' => $csrfToken,
                'recoveryError' => '',
                'currentEmail' => $pendingEmail ?? '',
                'showResendVerification' => is_string($pendingEmail) && $pendingEmail !== '',
            ]);
            return;
        }

        $module = $_GET['module'] ?? 'dashboard'; // 'dashboard' par défaut
        $id = $_GET['id'] ?? null;
        $view_name = "admin_module_" . $module;

        render_view($view_name, [
            'module' => $module,
            'id' => $id,
            'csrfToken' => $csrfToken
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

    // 5. API version (Appels depuis le JS du Dashboard ADMIN)
    if ($path === 'api/version' || $path === 'api/version.php') {

        $versionKey = $_GET['key'] ?? '';
        
        // Sécurité : Vérification de la clé globale
        if ($versionKey !== VERSION_ACCESS_KEY) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Accès refusé']);
            return;
        }
        
        require_once __DIR__ . '/api/version.php';
        return; // On arrête le script ici
    }

    // 6. Landing Page par défaut
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