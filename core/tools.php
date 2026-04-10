<?php
/**
 * Fonctions utilitaires - Project Manganese
 */

// Convertit une chaîne UUID (hex) en binaire pour MySQL
function uuid_to_bin($uuid) {
    if (!$uuid || !is_string($uuid)) return $uuid;

    // Retire les tirets pour n'avoir que les caractères hex
    $clean = str_replace('-', '', $uuid);

    // Si la chaîne fait déjà 16 octets et contient des caractères non-hex, 
    // c'est qu'elle est déjà en binaire. On la retourne telle quelle.
    if (strlen($clean) === 16 && !ctype_xdigit($clean)) {
        return $clean;
    }

    // On vérifie que la chaîne est bien de l'hexadécimal pur
    if (!ctype_xdigit($clean)) {
        // On retourne la valeur telle quelle pour éviter le crash de pack()
        // SQL renverra simplement "0 résultats" au lieu de faire une erreur PHP
        return $clean;
    }

    return pack("H*", $clean);
}

// Convertit le binaire MySQL en chaîne UUID lisible
function bin_to_uuid($bin) {
    if (!$bin) return null;
    
    // Si ce n'est pas du binaire (pas 16 octets), on ne peut pas le convertir
    if (strlen($bin) !== 16) return $bin;

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