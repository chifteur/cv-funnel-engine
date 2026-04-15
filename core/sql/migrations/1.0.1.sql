-- Migration CV Funnel Engine v1.0.1
-- Ajout d'un champ pseudo au profil pour test de migration

ALTER TABLE `profile_settings` 
ADD COLUMN `nickname` VARCHAR(50) DEFAULT NULL AFTER `full_name`;