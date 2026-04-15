<?php
/**
 * Manganese OS - Gestionnaire d'Upload CRM
 */

function handleCrmFileUpload($file_array, $index, $slug) {
    if (!isset($file_array['name'][$index]) || $file_array['error'][$index] !== UPLOAD_ERR_OK) {
        return null;
    }

    $base_dir = __DIR__ . "/../storage/crms/" . $slug;
    
    // Création du dossier du slug s'il n'existe pas
    if (!is_dir($base_dir)) {
        mkdir($base_dir, 0755, true);
    }

    $filename = time() . "_" . basename($file_array['name'][$index]);
    $target_path = $base_dir . "/" . $filename;

    if (move_uploaded_file($file_array['tmp_name'][$index], $target_path)) {
        // On retourne le chemin relatif pour la DB
        return "storage/crms/" . $slug . "/" . $filename;
    }

    return null;
}