<?php

function get_admin_setting(PDO $db, string $settingKey): ?string {
    $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$settingKey]);
    $value = $stmt->fetchColumn();
    return is_string($value) ? $value : null;
}

function set_admin_setting(PDO $db, string $settingKey, string $value): void {
    $stmt = $db->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$settingKey, $value]);
}

function create_admin_recovery_token(PDO $db, string $purpose, int $lifetimeMinutes, ?string $subject = null): string {
    if (!in_array($purpose, ['activation', 'email_verification', 'password_reset'], true)) {
        throw new InvalidArgumentException('Invalid admin recovery token purpose.');
    }

    $db->exec('DELETE FROM admin_recovery_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $subjectHash = $subject === null
        ? null
        : hash_hmac('sha256', strtolower(trim($subject)), DB_PASS);
    $stmt = $db->prepare(
        "INSERT INTO admin_recovery_tokens (token_hash, purpose, subject_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL {$lifetimeMinutes} MINUTE))"
    );
    $stmt->execute([$tokenHash, $purpose, $subjectHash]);
    return $token;
}

function is_admin_recovery_token_valid(PDO $db, string $token, string $purpose, ?string $subject = null): bool {
    if (!in_array($purpose, ['activation', 'email_verification', 'password_reset'], true)
        || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
        return false;
    }

    $sql = 'SELECT 1 FROM admin_recovery_tokens
        WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW()';
    $params = [hash('sha256', $token), $purpose];
    if ($subject !== null) {
        $sql .= ' AND subject_hash = ?';
        $params[] = hash_hmac('sha256', strtolower(trim($subject)), DB_PASS);
    }
    $stmt = $db->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
}

function consume_admin_recovery_token(PDO $db, string $token, string $purpose, ?string $subject = null): bool {
    if (!in_array($purpose, ['activation', 'email_verification', 'password_reset'], true)
        || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
        return false;
    }

    $sql = 'UPDATE admin_recovery_tokens SET used_at = NOW()
        WHERE token_hash = ? AND purpose = ? AND used_at IS NULL AND expires_at > NOW()';
    $params = [hash('sha256', $token), $purpose];
    if ($subject !== null) {
        $sql .= ' AND subject_hash = ?';
        $params[] = hash_hmac('sha256', strtolower(trim($subject)), DB_PASS);
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() === 1;
}

function admin_recovery_request_allowed(PDO $db, string $type, string $email, string $ipAddress): bool {
    if (!in_array($type, ['email_verification', 'password_reset'], true)) {
        throw new InvalidArgumentException('Invalid admin recovery request type.');
    }

    $emailHash = hash_hmac('sha256', strtolower(trim($email)), DB_PASS);
    $ipHash = hash_hmac('sha256', $ipAddress, DB_PASS);
    $db->exec('DELETE FROM admin_recovery_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');

    $insert = $db->prepare(
        'INSERT INTO admin_recovery_attempts (request_type, email_hash, ip_hash) VALUES (?, ?, ?)'
    );
    $insert->execute([$type, $emailHash, $ipHash]);

    $byEmail = $db->prepare(
        'SELECT COUNT(*) FROM admin_recovery_attempts
         WHERE request_type = ? AND email_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $byEmail->execute([$type, $emailHash]);
    $emailRequests = (int)$byEmail->fetchColumn();

    $byIp = $db->prepare(
        'SELECT COUNT(*) FROM admin_recovery_attempts
         WHERE request_type = ? AND ip_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $byIp->execute([$type, $ipHash]);
    $ipRequests = (int)$byIp->fetchColumn();

    return $emailRequests <= 3 && $ipRequests <= 10;
}

function get_admin_smtp_config(): array {
    $environmentConfig = [
        'mailer_path' => getenv('CVFE_PHPMAILER_PATH'),
        'host' => getenv('CVFE_SMTP_HOST'),
        'username' => getenv('CVFE_SMTP_USERNAME'),
        'password' => getenv('CVFE_SMTP_PASSWORD'),
        'from_email' => getenv('CVFE_SMTP_FROM_EMAIL'),
        'from_name' => getenv('CVFE_SMTP_FROM_NAME') ?: 'CV Funnel Engine',
        'port' => getenv('CVFE_SMTP_PORT') ?: '587',
    ];
    $requiredEnvironmentValues = ['mailer_path', 'host', 'username', 'password', 'from_email'];
    $hasEnvironmentConfig = true;
    foreach ($requiredEnvironmentValues as $requiredValue) {
        if (!is_string($environmentConfig[$requiredValue]) || $environmentConfig[$requiredValue] === '') {
            $hasEnvironmentConfig = false;
            break;
        }
    }

    if ($hasEnvironmentConfig) {
        return $environmentConfig;
    }

    $configPath = getenv('CVFE_SMTP_CONFIG_FILE');
    if (!is_string($configPath) || $configPath === '') {
        $home = $_SERVER['HOME'] ?? getenv('HOME');
        $configPath = is_string($home) && $home !== ''
            ? rtrim($home, '/') . '/.cvfe-smtp.php'
            : '';
    }

    $resolvedPath = $configPath !== '' ? realpath($configPath) : false;
    if (!is_string($resolvedPath) || !is_file($resolvedPath) || !is_readable($resolvedPath)) {
        throw new RuntimeException('Le service email de récupération n’est pas configuré.');
    }

    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $resolvedDocumentRoot = is_string($documentRoot) && $documentRoot !== ''
        ? realpath($documentRoot)
        : false;
    if (is_string($resolvedDocumentRoot)
        && str_starts_with($resolvedPath, rtrim($resolvedDocumentRoot, '/') . '/')) {
        throw new RuntimeException('Le fichier de configuration email doit être hors du répertoire public.');
    }

    $permissions = fileperms($resolvedPath);
    if (!is_int($permissions) || ($permissions & 0007) !== 0 || ($permissions & 0020) !== 0) {
        throw new RuntimeException('Les permissions du fichier email doivent interdire l’accès aux autres utilisateurs.');
    }

    $fileConfig = require $resolvedPath;
    if (!is_array($fileConfig)) {
        throw new RuntimeException('Le fichier de configuration email doit retourner un tableau.');
    }

    return $fileConfig;
}

function send_admin_recovery_email(string $recipient, string $subject, string $token, string $purpose): void {
    $config = get_admin_smtp_config();
    $mailerPath = $config['mailer_path'] ?? null;
    $host = $config['host'] ?? null;
    $username = $config['username'] ?? null;
    $password = $config['password'] ?? null;
    $fromEmail = $config['from_email'] ?? null;
    $fromName = $config['from_name'] ?? 'CV Funnel Engine';
    $portValue = $config['port'] ?? 587;

    if (!is_string($mailerPath) || $mailerPath === ''
        || !is_string($host) || $host === ''
        || !is_string($username) || $username === ''
        || !is_string($password) || $password === ''
        || !is_string($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)
        || !(is_int($portValue) || (is_string($portValue) && ctype_digit($portValue)))
        || !in_array((int)$portValue, [465, 587], true)
        || !is_string($fromName)) {
        throw new RuntimeException('Le service email de récupération n’est pas configuré.');
    }

    $mailerPath = rtrim($mailerPath, '/');
    if (!is_file($mailerPath . '/Exception.php') && is_file($mailerPath . '/src/Exception.php')) {
        $mailerPath .= '/src';
    }

    $sourceFiles = [
        $mailerPath . '/Exception.php',
        $mailerPath . '/PHPMailer.php',
        $mailerPath . '/SMTP.php',
    ];
    foreach ($sourceFiles as $sourceFile) {
        if (!is_file($sourceFile) || !is_readable($sourceFile)) {
            throw new RuntimeException('PHPMailer est introuvable ou illisible sur le serveur.');
        }
        require_once $sourceFile;
    }

    $route = match ($purpose) {
        'email_verification' => 'verify_email',
        'password_reset' => 'reset_key',
        default => throw new InvalidArgumentException('Invalid recovery email purpose.')
    };
    $url = rtrim(SITE_URL, '/') . '/manage?' . $route . '=' . rawurlencode($token);
    $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $phpMailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
    if (!class_exists($phpMailerClass)) {
        throw new RuntimeException('PHPMailer n’est pas disponible sur le serveur.');
    }

    try {
        $mail = new $phpMailerClass(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = (int)$portValue === 465
            ? constant($phpMailerClass . '::ENCRYPTION_SMTPS')
            : constant($phpMailerClass . '::ENCRYPTION_STARTTLS');
        $mail->Port = (int)$portValue;
        $mail->Timeout = 12;
        $mail->SMTPDebug = 0;
        $mail->CharSet = constant($phpMailerClass . '::CHARSET_UTF8');
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($recipient);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '<!doctype html><html lang="fr"><body>'
            . '<p>Une action concernant le compte administrateur de CV Funnel Engine a été demandée.</p>'
            . '<p><a href="' . $safeUrl . '">Continuer</a></p>'
            . '<p>Ce lien est à usage unique et expire après un délai limité. Si tu n’es pas à l’origine de cette demande, ignore cet email.</p>'
            . '</body></html>';
        $mail->AltBody = "Une action concernant le compte administrateur de CV Funnel Engine a été demandée.\n\n"
            . $url . "\n\nCe lien est à usage unique et expire après un délai limité.";
        $mail->send();
    } catch (\Exception $e) {
        Logger::error('Failed to send admin recovery email', ['error' => $e->getMessage()]);
        throw new RuntimeException('Impossible d’envoyer l’email de récupération.');
    }
}
