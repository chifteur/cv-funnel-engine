-- Insertion d'une expérience exemple
INSERT INTO `experiences` (`company`, `title`, `description`, `category`, `is_highlight`) 
VALUES ('SwissTiming', 'Software Architect', 'Architecture logicielle pour les JO de Rio 2016...', 'tech', TRUE);

-- Insertion d'une application test
INSERT INTO `applications` (`slug`, `company_name`, `job_title`, `custom_pitch`, `created_at`) 
VALUES ('test-recruteur', 'Entreprise Démo', 'Responsable Opérations', 'Voici mon profil adapté à vos besoins.', NOW());