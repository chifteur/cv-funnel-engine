<?php
/**
 * Provisioning & Auto-Configuration - Manganese OS
 * Ce script est déclenché automatiquement depuis public/index.php s'il détecte l'absence du fichier de configuration (config.php). 
 * Il présente un formulaire d'installation pour saisir les paramètres de la base de données, puis initialise la structure SQL et génère le fichier de configuration final.
 */

// Double sécurité : on bloque l'accès si la config existe déjà
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    die("Le système est déjà configuré.");
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost'; // Par défaut localhost ou 'mysql.votredomaine.ch'
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $admin_key = $_POST['admin_key'] ?? '';

    // Déduction intelligente de SITE_URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $path = dirname($_SERVER['PHP_SELF']);
    
    // On nettoie "/public" à la fin du chemin si le serveur l'a inclus
    $path = preg_replace('#/public$#', '', $path);
    
    // On assemble le tout proprement
    $site_url = rtrim($protocol . $_SERVER['HTTP_HOST'] . $path, '/\\');

    try {
        // 1. Test de la connexion à la base de données
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // 2. Exécution du fichier SQL de structure
        $sqlFile = __DIR__ . '/sql/structure.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if (!empty(trim($sql))) {
                $pdo->exec($sql);
            }
        } else {
            throw new Exception("Le fichier de structure SQL (/core/sql/structure.sql) est introuvable.");
        }

        // 3. Préparation du nouveau fichier de configuration
        $templateFile = __DIR__ . '/config.exemple.php';
        if (!file_exists($templateFile)) {
            throw new Exception("Le fichier template config.exemple.php est introuvable.");
        }
        $configContent = file_get_contents($templateFile);

        // Remplacement des valeurs via des expressions régulières pour cibler les constantes
        $configContent = preg_replace("/define\('DB_HOST',\s*'.*?'\);/", "define('DB_HOST', '" . addslashes($db_host) . "');", $configContent);
        $configContent = preg_replace("/define\('DB_NAME',\s*'.*?'\);/", "define('DB_NAME', '" . addslashes($db_name) . "');", $configContent);
        $configContent = preg_replace("/define\('DB_USER',\s*'.*?'\);/", "define('DB_USER', '" . addslashes($db_user) . "');", $configContent);
        $configContent = preg_replace("/define\('DB_PASS',\s*'.*?'\);/", "define('DB_PASS', '" . addslashes($db_pass) . "');", $configContent);
        $configContent = preg_replace("/define\('ADMIN_ACCESS_KEY',\s*'.*?'\);/", "define('ADMIN_ACCESS_KEY', '" . addslashes($admin_key) . "');", $configContent);
        $configContent = preg_replace("/define\('SITE_URL',\s*'.*?'\);/", "define('SITE_URL', '" . addslashes($site_url) . "');", $configContent);

        // 4. Écriture finale du fichier config.php
        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception("Impossible de créer config.php. Vérifiez les droits d'écriture sur le dossier 'core/'.");
        }


        // 5. Création de l'arborescence des dossiers requis
        // En partant de /core/, on remonte d'un cran puis on va dans storage/docs
        $docsDir = __DIR__ . '/../storage/docs'; 
        
        if (!is_dir($docsDir)) {
            // Le paramètre 'true' crée tous les dossiers intermédiaires manquants
            if (!mkdir($docsDir, 0755, true)) {
                throw new Exception("Impossible de créer le dossier $docsDir. Vérifiez les droits.");
            }
        }        
        $success = true;

    } catch (PDOException $e) {
        $error = "Erreur de connexion SQL : Vérifiez vos identifiants (" . $e->getMessage() . ")";
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - MANGANESE OS</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .install-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { margin-top: 0; font-size: 24px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0066cc; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #005bb5; }
        .error { background: #fee; color: #c00; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .success { text-align: center; }
        .success a { display: inline-block; margin-top: 15px; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

<div class="install-box">
    <?php if ($success): ?>
        <div class="success">
            <h1>🎉 Configuration réussie</h1>
            <p>Le fichier de configuration a été créé, la base de données est prête et l'arborescence de stockage a été générée.</p>
            
            <p style="margin-top: 20px; font-weight: bold;">Prochaine étape :</p>
            <a href="<?= htmlspecialchars($site_url) ?>/manage?key=<?= htmlspecialchars($admin_key) ?>" 
               style="display: inline-block; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
               Accéder à l'administration
            </a>
            
            <p style="font-size: 12px; color: #888; margin-top: 15px;">
                Note : Pensez à noter votre clé d'accès admin si vous ne l'avez pas déjà fait.
            </p>
        </div>
    <?php else: ?>
        <h1>⚙️ Provisioning</h1>
        <p style="font-size: 14px; color: #666; margin-bottom: 20px;">Configuration initiale de l'environnement.</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="db_host">Hôte de la base de données</label>
                <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'dbone.manganese.ch') ?>" required>
            </div>
            <div class="form-group">
                <label for="db_name">Nom de la base (DB_NAME)</label>
                <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="db_user">Utilisateur MySQL (DB_USER)</label>
                <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="db_pass">Mot de passe (DB_PASS)</label>
                <input type="password" id="db_pass" name="db_pass" required>
            </div>
            <div class="form-group">
                <label for="admin_key">Clé d'accès Admin (ADMIN_ACCESS_KEY)</label>
                <input type="text" id="admin_key" name="admin_key" value="<?= htmlspecialchars($_POST['admin_key'] ?? bin2hex(random_bytes(16))) ?>" required>
            </div>
            <button type="submit">Installer & Initialiser SQL</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>