<?php
/**
 * Front Controller
 */
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/router.php';

// On récupère l'URI complète
$request = $_SERVER['REQUEST_URI'];

// On lance la machine
dispatch($request);