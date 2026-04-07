<?php
/**
 * Front Controller - Manganese OS
 */
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/router.php';

// Optionnel : Debugging PHP 8.4 pour tes premiers tests
// ini_set('display_errors', 1); 
// error_reporting(E_ALL);

$request = $_SERVER['REQUEST_URI'];
dispatch($request);