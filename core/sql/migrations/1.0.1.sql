-- =============================================
-- MIGRATION: Créer table de dictionnaire pour les catégories
-- Date: 30 avril 2026
-- Objet: Rendre dynamiques les catégories Opérations, Management, Technique
-- =============================================

-- 0. Transformer les ENUM en VARCHAR pour permettre l'extensibilité
ALTER TABLE `cv_experiences` 
  MODIFY `category` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL 
  COMMENT 'Référence à category_dictionary.code';

ALTER TABLE `cv_skills` 
  MODIFY `category` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL 
  COMMENT 'Référence à category_dictionary.code';

ALTER TABLE `applications` 
  MODIFY `default_lens` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'ops' 
  COMMENT 'Référence à category_dictionary.code';

-- 1. Créer la table de dictionnaire
CREATE TABLE IF NOT EXISTS category_dictionary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique: ops, management, tech',
    label_short VARCHAR(100) NOT NULL COMMENT 'Label court pour les options: Opérations',
    label_long VARCHAR(150) NOT NULL COMMENT 'Label long pour l\'affichage: Excellence Opérationnelle',
    display_order INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage',
    color_hex VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'Couleur associée (hex)',
    icon_name VARCHAR(50) DEFAULT 'briefcase' COMMENT 'Icône FontAwesome',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Catégorie active ou archivée',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_display_order (display_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dictionnaire centralisé des catégories de profil';

-- 2. Insérer les données de migration existantes
INSERT INTO category_dictionary (code, label_short, label_long, display_order, icon_name) VALUES
('ops', 'Opérations', 'Excellence Opérationnelle', 1, 'cog'),
('management', 'Management', 'Management & Gouvernance', 0, 'chess-king'),
('tech', 'Technique', 'Techniques & Technologie', 2, 'microchip');
