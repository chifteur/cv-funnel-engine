-- Nettoyage des tables pour une installation propre
TRUNCATE TABLE `profile_settings`;
TRUNCATE TABLE `cv_experiences`;
TRUNCATE TABLE `cv_skills`;
TRUNCATE TABLE `cv_education`;
TRUNCATE TABLE `cv_languages`;

-- 1. IDENTITÉ MASTER (profile_settings)
INSERT INTO `profile_settings` (`id`, `full_name`, `job_title`, `bio`, `email`, `phone`, `linkedin_url`, `photo_path`) 
VALUES (1, 
'Nathanaël Schmied', 
'COO | Software Manager | Transformation Leader', 
'Manager opérationnel avec plus de 20 ans d’expérience dans l’industrie du logiciel. Expert dans la gestion d’organisations internationales complexes, je transforme les visions stratégiques en actions concrètes. Ma force réside dans l’alignement des départements, l’optimisation des processus et la conduite du changement pour garantir une rentabilité durable.', 
'nschmied@gmail.com', 
'+41 76 822 25 77', 
'https://www.linkedin.com/in/nathanaël-schmied/', 
'/public/assets/images/12089_Nathanael_Schmied.jpg');

-- 2. EXPÉRIENCES (cv_experiences)
INSERT INTO `cv_experiences` (`company`, `role`, `location`, `period`, `content`, `category`) VALUES 
('Membre de la Direction (Co-management)', 'COO | Software Manager', 'Courtelary', '2019 — Présent', 
'• Management d’organisations internationales (Suisse, France, Maroc, Inde).\n• Alignement stratégique entre Sales, Services, R&D et C-level.\n• Surveillance budgétaire pour la R&D et les opérations Cloud.\n• Optimisation des processus et change management pour la scalabilité.', 
'management'),

('SwissTiming (Swatch Group)', 'Software Architect & Deputy Project Manager', 'Corgémont', '2012 — 2018', 
'• Architecture logicielle du système de scoring vidéo multisport pour les JO de Rio 2016.\n• Redesign complet d’un framework de développement SDK.\n• Gestion de projet et coordination technique.', 
'ops'),

('SolvAxis (ProConcept)', 'Lead Software Architect', 'Sonceboz', '2009 — 2011', 
'• Change Management : Modernisation de l’ERP vers une version Web (AJAX/JS).\n• Industrialisation : Mise en place des processus Agile/SCRUM et de Git.\n• Gestion des impacts technologiques et humains.', 
'tech'),

('MN Manganese Sàrl', 'Independent Infrastructure Consultant', 'Courtelary', '2008 — 2018', 
'• Conseil stratégique : Accompagnement des PME dans la structuration et la sécurisation de leurs systèmes d’information.', 
'ops'),

('ProConcept SA', 'Software Engineer', 'Sonceboz', '2001 — 2009', 
'• Développement et maintenance du framework ERP ProConcept (Delphi, Java, C#).\n• Migration de l’ERP vers MS.Net 2.0 pour Audemars Piguet (marché Japon).', 
'tech');

-- 3. COMPÉTENCES (cv_skills)
INSERT INTO `cv_skills` (`category`, `label`, `level_text`) VALUES 
('management', 'Gouvernance & Stratégie R&D', 'Expert'),
('management', 'Performance & KPIs (OKRs)', 'Expert'),
('management', 'International Management', 'Maitrisé'),
('ops', 'Agile (SAFe 6 Agilist, ScrumMaster)', 'Expert'),
('ops', 'Optimisation SDLC / Lean', 'Confirmé'),
('tech', 'Architecture logicielle Cloud', 'Confirmé'),
('tech', 'Analyse Business / AWS', 'Confirmé');

-- 4. ÉDUCATION & CERTIFICATIONS (cv_education)
INSERT INTO `cv_education` (`degree`, `institution`, `year`, `icon`) VALUES 
('Brevet Fédéral - Spécialiste conduite d’équipe (ASFC)', 'ASFC', '2024-2025', 'fa-award'),
('Ingénieur HES en informatique', 'HE-ARC', '2001', 'fa-graduation-cap'),
('SAFe 6 Agilist Certification', 'Scaled Agile', '2023', 'fa-certificate'),
('ScrumMaster Certification', 'Scrum Alliance', '2022', 'fa-certificate');

-- 5. LANGUES (cv_languages)
INSERT INTO `cv_languages` (`label`, `level`) VALUES 
('Français', 'Langue maternelle'),
('Anglais', 'Niveau B1/B2');