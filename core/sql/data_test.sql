-- Nettoyage préalable pour le test
TRUNCATE TABLE `experiences`;

-- 1. Profil "Ops / Project Management" (Ex: SwissTiming)
INSERT INTO `experiences` (`company`, `title`, `location`, `date_start`, `date_end`, `description`, `category`, `is_highlight`) 
VALUES (
    'SwissTiming (Swatch Group)', 
    'Software Architect & Deputy Project Manager', 
    'Corgémont', 
    '2012-01-01', 
    '2018-12-31', 
    '• Architecture logicielle du système de scoring vidéo multisport pour les JO de Rio 2016.\n• Redesign complet du framework de développement SDK.\n• Gestion de projet et coordination d\'équipes pluridisciplinaires.', 
    'ops', 
    TRUE
);

-- 2. Profil "Tech / Architecture" (Ex: SolvAxis)
INSERT INTO `experiences` (`company`, `title`, `location`, `date_start`, `date_end`, `description`, `category`, `is_highlight`) 
VALUES (
    'SolvAxis (ProConcept)', 
    'Lead Software Architect', 
    'Sonceboz', 
    '2009-01-01', 
    '2011-12-31', 
    '• Change Management : Modernisation de l\'ERP vers une version Web (AJAX/JS).\n• Industrialisation : Mise en place des processus Agile/SCRUM et de Git.\n• Gestion des impacts technologiques et humains pour la base installée.', 
    'tech', 
    FALSE
);

-- 3. Profil "Management / Leadership" (Basé sur votre rôle de COO actuel)
INSERT INTO `experiences` (`company`, `title`, `location`, `date_start`, `date_end`, `description`, `category`, `is_highlight`) 
VALUES (
    'Membre de la Direction (Co-management)', 
    'COO / Software Manager', 
    'Courtelary', 
    '2019-01-01', 
    NULL, -- NULL signifie "Aujourd'hui"
    '• Management d\'équipes multi-sites internationales (22+ ingénieurs).\n• Pilotage de la stratégie Cloud et de la Roadmap R&D.\n• Alignement des départements Sales, Services et R&D.\n• Définition et suivi des KPIs et OKRs au niveau groupe.', 
    'management', 
    TRUE
);