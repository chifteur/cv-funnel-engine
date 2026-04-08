<?php
/**
 * Fonctions utilitaires - Project Manganese
 */

// Convertit une chaîne UUID (hex) en binaire pour MySQL
function uuid_to_bin($uuid) {
    return pack("H*", str_replace('-', '', $uuid));
}

// Convertit le binaire MySQL en chaîne UUID lisible
function bin_to_uuid($bin) {
    $hex = unpack("H*", $bin)[1];
    return sprintf('%08s-%04s-%04s-%04s-%12s',
        substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12)
    );
}

// Génère un UUID v4
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}