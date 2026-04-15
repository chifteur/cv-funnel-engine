<?php
/**
 * Automator de Migration - CV Funnel Engine
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tools.php'; // Pour ta fonction get_db_version()

// Sécurité : On s'assure que ce script ne peut être lancé que par une requête autorisée
$key = $_GET['key'] ?? '';
if ($key !== ADMIN_ACCESS_KEY) {
    http_response_code(403);
    die("Accès refusé.");
}

try {
    $db = get_db_connection();
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

} catch (Exception $e) {
    http_response_code(500);
    die("❌ Erreur fatale lors de la migration : " . $e->getMessage());
}