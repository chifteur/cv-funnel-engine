-- =====================================================================
-- MIGRATION PRODUCTION-READY
-- Titre: Rendre les catégories dynamiques
-- Date: 29 avril 2026 (Updated 30 avril 2026)
-- Version: 1.1
-- Risque: MINIMAL (création de table + modification de colonnes ENUM)
-- Rollback: 
--   DROP TABLE category_dictionary;
--   ALTER TABLE cv_experiences MODIFY category ENUM('management','tech','ops');
--   ALTER TABLE cv_skills MODIFY category ENUM('management','tech','ops');
-- =====================================================================

-- ✅ ÉTAPE 0: Transformer les ENUM en VARCHAR (compatibilité future)
-- Cela permet d'ajouter des catégories sans modifier la structure des tables
ALTER TABLE `cv_experiences` 
  MODIFY `category` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL 
  COMMENT 'Référence à category_dictionary.code (ex: ops, management, tech, sales...)';

ALTER TABLE `cv_skills` 
  MODIFY `category` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL 
  COMMENT 'Référence à category_dictionary.code (ex: ops, management, tech, sales...)';

ALTER TABLE `applications` 
  MODIFY `default_lens` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'ops' 
  COMMENT 'Référence à category_dictionary.code (ex: ops, management, tech, sales...)';

-- Note: Les données existantes ('management', 'tech', 'ops') sont conservées
-- L'ENUM limite à ces 3 valeurs était empêchait l'ajout de nouvelles catégories
-- VARCHAR permet une flexibilité totale tout en restant backward compatible

-- ✅ ÉTAPE 1: Créer la table de dictionnaire des catégories
-- Bonne pratique: Toujours vérifier que la table n'existe pas
CREATE TABLE IF NOT EXISTS category_dictionary (
    id INT PRIMARY KEY AUTO_INCREMENT COMMENT 'ID unique',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Clé technique (ops, management, tech)',
    label_short VARCHAR(100) NOT NULL COMMENT 'Label court pour les selects/UI',
    label_long VARCHAR(150) NOT NULL COMMENT 'Label long pour l\'affichage détaillé',
    display_order INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage dans les listes',
    color_hex VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'Couleur HEX associée (optionnel)',
    icon_name VARCHAR(50) DEFAULT 'briefcase' COMMENT 'Icône FontAwesome (optionnel)',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'TRUE = actif, FALSE = archivé',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date de modification',
    
    -- Indexation pour les requêtes fréquentes
    INDEX idx_code (code),
    INDEX idx_display_order (display_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dictionnaire centralisé des catégories de profil (Opérations, Management, Technique)';

-- ✅ ÉTAPE 2: Insérer les données initiales
-- Bonne pratique: Utiliser ON DUPLICATE KEY UPDATE pour l'idempotence
INSERT INTO category_dictionary (code, label_short, label_long, display_order, icon_name) 
VALUES 
    ('ops', 'Opérations', 'Excellence Opérationnelle', 1, 'cog'),
    ('management', 'Management', 'Management & Gouvernance', 0, 'chess-king'),
    ('tech', 'Technique', 'Techniques & Technologie', 2, 'microchip')
ON DUPLICATE KEY UPDATE 
    label_short = VALUES(label_short),
    label_long = VALUES(label_long),
    display_order = VALUES(display_order),
    icon_name = VALUES(icon_name),
    is_active = TRUE;

-- ✅ ÉTAPE 3: Vérification post-migration
-- Décommenter pour vérifier manuellement
SELECT 
    'Migration Status' AS Status,
    COUNT(*) AS Categories_Created,
    COUNT(CASE WHEN is_active = TRUE THEN 1 END) AS Active_Categories,
    GROUP_CONCAT(code ORDER BY display_order SEPARATOR ', ') AS Category_Codes,
    NOW() AS Executed_At
FROM category_dictionary;

-- =====================================================================
-- OPTIONNEL: Fournir des catégories avec plus de détails
-- =====================================================================

-- Si vous avez besoin d'ajouter des détails supplémentaires aux catégories existantes:
/*
UPDATE category_dictionary SET 
    color_hex = '#10b981' WHERE code = 'ops';    -- Vert
UPDATE category_dictionary SET 
    color_hex = '#3b82f6' WHERE code = 'management';  -- Bleu
UPDATE category_dictionary SET 
    color_hex = '#8b5cf6' WHERE code = 'tech';    -- Violet
*/

-- =====================================================================
-- OPTIONNEL: Ajouter des colonnes supplémentaires ultérieurement
-- =====================================================================

-- Si vous avez besoin de tracer qui a créé/modifié:
/*
ALTER TABLE category_dictionary ADD COLUMN created_by INT DEFAULT NULL AFTER created_at;
ALTER TABLE category_dictionary ADD COLUMN updated_by INT DEFAULT NULL AFTER updated_at;
ALTER TABLE category_dictionary ADD FOREIGN KEY (created_by) REFERENCES profile_settings(id) ON DELETE SET NULL;
ALTER TABLE category_dictionary ADD FOREIGN KEY (updated_by) REFERENCES profile_settings(id) ON DELETE SET NULL;
*/

-- =====================================================================
-- ROLLBACK / ANNULATION (si nécessaire)
-- =====================================================================

-- DROP TABLE category_dictionary;

-- =====================================================================
-- FIN MIGRATION
-- =====================================================================
