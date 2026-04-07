-- Suppression des tables si elles existent (attention : efface les données)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `tracking_logs`, `rel_app_doc`, `documents`, `applications`, `experiences`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Table des expériences (Tronc commun)
CREATE TABLE `experiences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company` VARCHAR(100) NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `location` VARCHAR(100),
    `date_start` DATE,
    `date_end` DATE NULL, -- NULL si poste actuel
    `description` TEXT, -- Format Markdown recommandé
    `category` ENUM('management', 'ops', 'tech') DEFAULT 'ops',
    `is_highlight` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des documents (Stock générique)
CREATE TABLE `documents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `label` VARCHAR(150) NOT NULL, -- Ex: "Brevet Fédéral ASFC"
    `filename` VARCHAR(255) NOT NULL, -- Nom du fichier physique sur le serveur
    `category` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des candidatures (URLs uniques)
CREATE TABLE `applications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) UNIQUE NOT NULL, -- Ex: "jenov"
    `company_name` VARCHAR(100) NOT NULL,
    `job_title` VARCHAR(100) NOT NULL,
    `job_url` TEXT NULL,
    `custom_pitch` TEXT NULL, -- Texte spécifique pour l'entreprise
    `default_lens` ENUM('management', 'ops', 'tech') DEFAULT 'ops',
    `created_at` DATETIME NOT NULL,
    `status` ENUM('sent', 'interview', 'rejected', 'accepted') DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table de liaison Candidatures <-> Documents
CREATE TABLE `rel_app_doc` (
    `app_id` INT UNSIGNED NOT NULL,
    `doc_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`app_id`, `doc_id`),
    CONSTRAINT `fk_app` FOREIGN KEY (`app_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_doc` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table de tracking (Log des visites)
CREATE TABLE `tracking_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `app_id` INT UNSIGNED NOT NULL,
    `visited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    CONSTRAINT `fk_track_app` FOREIGN KEY (`app_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;