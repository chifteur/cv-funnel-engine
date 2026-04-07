-- Ajouter le champ display_order à la table cv_experiences
ALTER TABLE `cv_experiences` 
ADD COLUMN `display_order` INT NOT NULL DEFAULT 0 AFTER `category`;

-- Initialiser les valeurs d'ordre basées sur l'ID (plus récent = plus haut)
-- Les IDs plus élevés recevront un display_order plus bas (pour l'affichage inversé)
UPDATE `cv_experiences` 
SET `display_order` = (SELECT MAX(id) FROM cv_experiences) - id;

-- Créer un index sur display_order pour optimiser les requêtes
ALTER TABLE `cv_experiences` 
ADD INDEX `idx_display_order` (`display_order`);
