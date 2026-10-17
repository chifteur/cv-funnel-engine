<?php
/** @var string $csrfToken */
/** @var string $loginError */
$escapedCsrfToken = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');
$escapedError = htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8');
$showLoginForm = $showLoginForm ?? true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès au management | CV Funnel Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 p-4 text-slate-900">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-xl">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-700">CV Funnel Engine</p>
        <h1 class="mt-3 text-2xl font-black">Accès au management</h1>
        <p class="mt-2 text-sm text-slate-600">Saisis ta clé d’administration pour ouvrir le tableau de bord.</p>

        <?php if ($escapedError !== ''): ?>
            <p role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800"><?= $escapedError ?></p>
        <?php endif; ?>

        <?php if (!empty($resetComplete)): ?>
            <p role="status" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">Clé modifiée. Connecte-toi avec la nouvelle clé.</p>
        <?php endif; ?>

        <?php if ($showLoginForm): ?>
        <form method="POST" class="mt-6 space-y-4">
            <input type="hidden" name="action" value="admin_login">
            <input type="hidden" name="csrf_token" value="<?= $escapedCsrfToken ?>">
            <div>
                <label for="admin_key" class="mb-2 block text-sm font-bold">Clé d’administration</label>
                <input id="admin_key" name="admin_key" type="password" autocomplete="current-password" required autofocus
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <button type="submit" class="w-full rounded-lg bg-blue-700 px-4 py-3 text-sm font-bold text-white hover:bg-blue-800">
                Ouvrir le tableau de bord
            </button>
        </form>
        <p class="mt-5 text-center text-sm"><a class="font-semibold text-blue-700 hover:underline" href="?forgot_key=1">Clé oubliée ?</a></p>
        <?php endif; ?>

        <?php if (!empty($showResendVerification)): ?>
            <form method="POST" class="mt-5">
                <input type="hidden" name="action" value="resend_admin_verification">
                <input type="hidden" name="csrf_token" value="<?= $escapedCsrfToken ?>">
                <button type="submit" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Renvoyer l’email de vérification</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
