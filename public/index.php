<?php
/**
 * Front Controller - Manganese OS
 */
session_start(); // DOIT ÊTRE ICI, avant tout le reste.

require_once __DIR__ . '/../core/config.php'; // 1. Paramètres (DB, constantes)
require_once __DIR__ . '/../core/tools.php';  // 2. Utilitaires (UUID, helpers)
require_once __DIR__ . '/../core/router.php'; // 3. Logique de routage (utilise 1 et 2)

// Optionnel : Debugging PHP 8.4 pour tes premiers tests
// ini_set('display_errors', 1); 
// error_reporting(E_ALL);

$request = $_SERVER['REQUEST_URI'];
dispatch($request);