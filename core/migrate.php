<?php
/**
 * Automator de Migration - CV Funnel Engine
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tools.php'; // Pour ta fonction get_db_version()

// Détection du mode d'exécution (CLI = Command Line Interface)
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    session_start();
}

try {
    $db = get_db_connection();
    if (!$is_cli) {
        $adminKeyHash = get_admin_access_key_hash($db);
        $sessionFingerprint = $_SESSION['admin_key_fingerprint'] ?? null;
        if (
            $adminKeyHash === null
            || !is_string($sessionFingerprint)
            || !hash_equals($adminKeyHash, $sessionFingerprint)
        ) {
            http_response_code(403);
            die("Accès refusé.");
        }
    }

    $current_version = get_db_version($db);
    $migrations_dir = __DIR__ . '/sql/migrations/';
    
    echo "🔍 Version actuelle de la DB : $current_version\n";

    if (!is_dir($migrations_dir)) {
        die("✅ Aucun dossier de migration trouvé. Rien à faire.");
    }

    // On liste tous les fichiers .sql
    $files = glob($migrations_dir . '*.sql');
    $migrations_applied = 0;

    // On trie les fichiers grâce à une fonction naturelle pour que 1.0.10 passe bien après 1.0.2
    usort($files, function($a, $b) {
        $versionA = basename($a, '.sql');
        $versionB = basename($b, '.sql');
        return version_compare($versionA, $versionB);
    });

    foreach ($files as $file) {
        $file_version = basename($file, '.sql');

        // La magie de PHP : version_compare gère la logique de versionnage sémantique
        if (version_compare($file_version, $current_version, '>')) {
            echo "⏳ Application de la migration v$file_version...\n";
            
            // On lit et exécute le SQL
            $sql = file_get_contents($file);
            if (!empty(trim($sql))) {
                // On utilise une transaction si possible (bien que certains ALTER TABLE coupent les transactions dans MySQL)
                $db->exec($sql);
            }

            if ($current_version === '1.0.1' && $file_version === '1.0.2') {
                migrateLegacyAdminAccessKey($db);
            }

            // On met à jour la version dans la base de données
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'db_version'");
            $stmt->execute([$file_version]);
            
            // On met à jour la version courante en mémoire pour la boucle
            $current_version = $file_version;
            $migrations_applied++;
            
            echo "✅ Migration v$file_version réussie.\n";
        }
    }

    if ($migrations_applied === 0) {
        echo "✨ La base de données est déjà à jour.\n";
    }

} catch (Throwable $e) {
    $message = "❌ Erreur fatale lors de la migration : " . $e->getMessage();
    if ($is_cli) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
    http_response_code(500);
    echo $message;
    exit;
}

function migrateLegacyAdminAccessKey(PDO $db): void {
    $storedAdminKeyHash = get_admin_access_key_hash($db);
    $configFile = __DIR__ . '/config.php';

    if (!defined('ADMIN_ACCESS_KEY')) {
        if ($storedAdminKeyHash === null) {
            throw new RuntimeException('Aucune clé admin existante dans config.php ou settings.');
        }
        return;
    }

    if (!is_string(ADMIN_ACCESS_KEY) || ADMIN_ACCESS_KEY === '') {
        throw new RuntimeException('La clé ADMIN_ACCESS_KEY existante est vide ou invalide.');
    }

    $configContent = file_get_contents($configFile);
    if (!is_string($configContent)) {
        throw new RuntimeException('Impossible de lire core/config.php.');
    }

    $updatedConfig = preg_replace(
        '/^[ \t]*define\(\s*([\'"])ADMIN_ACCESS_KEY\1\s*,[^\r\n]*\);[ \t]*(?:\/\/[^\r\n]*)?\R?/m',
        '',
        $configContent,
        -1,
        $removedDefinitions
    );
    if (!is_string($updatedConfig) || $removedDefinitions !== 1) {
        throw new RuntimeException('La définition ADMIN_ACCESS_KEY ne peut pas être supprimée de façon sûre.');
    }

    if ($storedAdminKeyHash === null) {
        $adminKeyHash = password_hash(ADMIN_ACCESS_KEY, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "UPDATE settings SET setting_value = ? WHERE setting_key = 'admin_access_key_hash'"
        );
        $stmt->execute([$adminKeyHash]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Impossible d’enregistrer le hash de la clé admin dans settings.');
        }
    }

    if (!is_writable($configFile)) {
        throw new RuntimeException('core/config.php n’est pas accessible en écriture.');
    }

    if (file_put_contents($configFile, $updatedConfig, LOCK_EX) === false) {
        throw new RuntimeException('Impossible de retirer ADMIN_ACCESS_KEY de core/config.php.');
    }
}
