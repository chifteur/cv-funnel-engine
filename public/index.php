<?php
/**
 * Front Controller - Manganese OS
 */
session_start(); // DOIT ÊTRE ICI, avant tout le reste.

$configFile = __DIR__ . '/../core/config.php';

// 🛑 HOOK DE PROVISIONING
// Si la configuration n'existe pas, on lance le script d'installation et on coupe l'exécution.
if (!file_exists($configFile)) {
    require_once __DIR__ . '/../core/provisioning.php';
    exit;
}

require_once $configFile; // Paramètres (DB, constantes)
require_once __DIR__ . '/../core/logger.php'; // Logger pour les erreurs et le debug
require_once __DIR__ . '/../core/tools.php';  // Utilitaires (UUID, helpers)
require_once __DIR__ . '/../core/router.php'; // Logique de routage (utilise 1 et 2)

// Gestion centralisée des erreurs et exceptions
set_error_handler(function ($severity, $message, $file, $line) {
    Logger::error('PHP Error', compact('severity', 'message', 'file', 'line'));
});

set_exception_handler(function ($exception) {
    Logger::error('Uncaught Exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        Logger::error('Fatal Error', $error);
    }
});

$request = $_SERVER['REQUEST_URI'];
dispatch($request);