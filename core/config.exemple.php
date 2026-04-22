<?php
/**
 * Configuration centrale - Project Manganese
 * PHP 8.4
 */

// Paramètres de la base de données (À remplir avec vos accès DreamHost)
define('DB_HOST', 'mon url'); // Souvent mysql.votredomaine.ch chez DreamHost
define('DB_NAME', 'nom de la DB MySQL');
define('DB_USER', 'username');
define('DB_PASS', 'passwork');

// Paramètre pour activer le mode debug (affiche les logs DEBUG dans Logger)
define('DEBUG', false);

// Sécurité
define('ADMIN_ACCESS_KEY', 'ma clef admin');
define('VERSION_ACCESS_KEY', 'ma clef de version'); // Clé d'accès pour l'API de version (à utiliser dans public/api/version.php)

// Chemins et URLs
define('SITE_URL', 'https://www.mon site web.ch');
define('PATH_CORE', __DIR__);
define('PATH_STORAGE', dirname(__DIR__) . '/storage/docs/');

// Paramètres d'affichage des erreurs (à passer à 0 en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Helper pour la connexion PDO
 */
function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    return $pdo;
}
