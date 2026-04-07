<?php
require_once __DIR__ . '/../core/config.php';

$doc_id = $_GET['id'] ?? null;
$app_slug = $_GET['app'] ?? null;

if (!$doc_id || !$app_slug) die("Accès refusé.");

$db = get_db_connection();

// On vérifie que le document est bien lié à cette candidature
$stmt = $db->prepare("
    SELECT d.filename, d.label 
    FROM documents d
    JOIN rel_app_doc rel ON d.id = rel.doc_id
    JOIN applications a ON a.id = rel.app_id
    WHERE d.id = ? AND a.slug = ?
");
$stmt->execute([$doc_id, $app_slug]);
$file = $stmt->fetch();

if ($file) {
    $path = PATH_STORAGE . $file['filename'];
    if (file_exists($path)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $file['label'] . '.pdf"');
        readfile($path);
        exit;
    }
}

die("Fichier introuvable ou accès non autorisé.");