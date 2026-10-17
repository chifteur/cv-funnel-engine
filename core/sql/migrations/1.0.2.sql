-- Réserve la clé admin dans settings. migrate.php y enregistre le hash legacy
-- pendant la transition 1.0.1 -> 1.0.2, puis retire la clé en clair de config.php.
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`)
VALUES ('admin_access_key_hash', '');
