-- Stockage des jetons temporaires et des demandes de limitation de débit.
CREATE TABLE IF NOT EXISTS `admin_recovery_tokens` (
  `token_hash` char(64) COLLATE ascii_bin NOT NULL,
  `purpose` enum('activation','email_verification','password_reset') NOT NULL,
  `subject_hash` char(64) COLLATE ascii_bin DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token_hash`),
  KEY `idx_recovery_purpose_expiry` (`purpose`, `expires_at`),
  KEY `idx_recovery_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_recovery_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_type` enum('email_verification','password_reset') NOT NULL,
  `email_hash` char(64) COLLATE ascii_bin NOT NULL,
  `ip_hash` char(64) COLLATE ascii_bin NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recovery_email_rate` (`request_type`, `email_hash`, `created_at`),
  KEY `idx_recovery_ip_rate` (`request_type`, `ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('admin_access_key_hash', ''),
  ('admin_recovery_email', ''),
  ('admin_recovery_email_verified', '0');
