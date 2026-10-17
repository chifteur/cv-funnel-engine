<?php
/** @var string $csrfToken */
/** @var string $mode */
$csrf = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');
$safeToken = htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8');
$safeError = htmlspecialchars($recoveryError ?? '', ENT_QUOTES, 'UTF-8');
$safeMessage = htmlspecialchars($recoveryMessage ?? '', ENT_QUOTES, 'UTF-8');
$safeStatus = htmlspecialchars($statusMessage ?? '', ENT_QUOTES, 'UTF-8');
$safeCurrentEmail = htmlspecialchars($currentEmail ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Récupération du management | CV Funnel Engine</title>
    <style>
        body { font: 16px system-ui, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 20px; box-sizing: border-box; }
        main { width: 100%; max-width: 440px; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-sizing: border-box; box-shadow: 0 12px 35px #0f172a12; }
        h1 { font-size: 24px; margin: 8px 0; } p { color: #475569; line-height: 1.5; }
        label { display: block; font-weight: 700; font-size: 14px; margin: 16px 0 6px; }
        input { display: block; width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 8px; padding: 11px; font: inherit; }
        button, .button { display: block; width: 100%; margin-top: 20px; border: 0; border-radius: 8px; padding: 12px; background: #1d4ed8; color: white; font-weight: 700; font-size: 15px; text-align: center; text-decoration: none; cursor: pointer; box-sizing: border-box; }
        .error, .message { border-radius: 8px; padding: 12px; font-size: 14px; }
        .error { background: #fef2f2; color: #991b1b; } .message { background: #eff6ff; color: #1e3a8a; }
        .brand { color: #1d4ed8; font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
    </style>
</head>
<body>
<main>
    <div class="brand">CV Funnel Engine</div>
    <?php if ($mode === 'activation'): ?>
        <h1>Activer cette instance</h1>
        <p>Choisis ta clé administrateur et l’adresse qui servira à la récupération. L’adresse devra être vérifiée avant toute connexion.</p>
        <?php if ($safeError !== ''): ?><p class="error" role="alert"><?= $safeError ?></p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="activate_admin">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="activation_token" value="<?= $safeToken ?>">
            <label for="new_admin_key">Clé administrateur (12 à 256 caractères)</label>
            <input id="new_admin_key" name="new_admin_key" type="password" minlength="12" maxlength="256" autocomplete="new-password" required>
            <label for="confirm_admin_key">Confirmer la clé</label>
            <input id="confirm_admin_key" name="confirm_admin_key" type="password" minlength="12" maxlength="256" autocomplete="new-password" required>
            <label for="recovery_email">Adresse email de récupération</label>
            <input id="recovery_email" name="recovery_email" type="email" maxlength="254" autocomplete="email" required>
            <button type="submit">Activer et vérifier l’adresse</button>
        </form>
    <?php elseif ($mode === 'verify_email'): ?>
        <h1>Vérifier l’adresse email</h1>
        <p>Confirme cette adresse pour activer la connexion et la récupération du compte.</p>
        <?php if ($safeError !== ''): ?><p class="error" role="alert"><?= $safeError ?></p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="verify_admin_email">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="verification_token" value="<?= $safeToken ?>">
            <button type="submit">Confirmer mon adresse email</button>
        </form>
    <?php elseif ($mode === 'setup_email'): ?>
        <h1>Configurer la récupération</h1>
        <p>Configure ou corrige l’adresse de récupération, puis confirme-la par email avant d’accéder au dashboard.</p>
        <?php if ($safeError !== ''): ?><p class="error" role="alert"><?= $safeError ?></p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="setup_admin_recovery_email">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <label for="recovery_email">Adresse email de récupération</label>
            <input id="recovery_email" name="recovery_email" type="email" maxlength="254" autocomplete="email" value="<?= $safeCurrentEmail ?>" required>
            <label for="current_admin_key">Clé administrateur actuelle</label>
            <input id="current_admin_key" name="current_admin_key" type="password" autocomplete="current-password" required>
            <button type="submit">Enregistrer et vérifier l’adresse</button>
        </form>
        <?php if (!empty($showResendVerification)): ?>
            <form method="POST">
                <input type="hidden" name="action" value="resend_admin_verification">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="button">Renvoyer l’email de vérification</button>
            </form>
        <?php endif; ?>
    <?php elseif ($mode === 'reset_key'): ?>
        <h1>Choisir une nouvelle clé</h1>
        <p>Le lien est à usage unique. Le changement déconnectera toutes les sessions administrateur existantes.</p>
        <?php if ($safeError !== ''): ?><p class="error" role="alert"><?= $safeError ?></p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="reset_admin_key">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="reset_token" value="<?= $safeToken ?>">
            <label for="new_admin_key">Nouvelle clé (12 à 256 caractères)</label>
            <input id="new_admin_key" name="new_admin_key" type="password" minlength="12" maxlength="256" autocomplete="new-password" required>
            <label for="confirm_admin_key">Confirmer la nouvelle clé</label>
            <input id="confirm_admin_key" name="confirm_admin_key" type="password" minlength="12" maxlength="256" autocomplete="new-password" required>
            <button type="submit">Remplacer la clé</button>
        </form>
    <?php elseif ($mode === 'forgot_key'): ?>
        <h1>Clé oubliée ?</h1>
        <p>Indique l’adresse de récupération vérifiée. Si elle correspond à cette instance, un lien temporaire sera envoyé.</p>
        <?php if ($safeMessage !== ''): ?><p class="message" role="status"><?= $safeMessage ?></p><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="request_admin_reset">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <label for="recovery_email">Adresse de récupération</label>
            <input id="recovery_email" name="recovery_email" type="email" maxlength="254" autocomplete="email" required>
            <button type="submit">Envoyer le lien</button>
        </form>
    <?php else: ?>
        <h1>Compte administrateur</h1>
        <?php if ($safeStatus !== ''): ?><p class="message" role="status"><?= $safeStatus ?></p><?php endif; ?>
        <?php if (!empty($showResendVerification)): ?>
            <form method="POST">
                <input type="hidden" name="action" value="resend_admin_verification">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit">Renvoyer l’email de vérification</button>
            </form>
        <?php endif; ?>
        <a class="button" href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/manage', ENT_QUOTES, 'UTF-8') ?>">Aller à la connexion</a>
    <?php endif; ?>
</main>
</body>
</html>
