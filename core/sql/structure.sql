-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: grahan.pdx1-mysql-a7-5a.dreamhost.com
-- Generation Time: Apr 07, 2026 at 02:31 AM
-- Server version: 8.0.41-0ubuntu0.24.04.1
-- PHP Version: 8.1.2-1ubuntu2.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `manganese_ch`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int UNSIGNED NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_url` text COLLATE utf8mb4_unicode_ci,
  `custom_pitch` text COLLATE utf8mb4_unicode_ci,
  `default_lens` enum('management','ops','tech') COLLATE utf8mb4_unicode_ci DEFAULT 'ops',
  `created_at` datetime NOT NULL,
  `status` enum('sent','interview','rejected','accepted') COLLATE utf8mb4_unicode_ci DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `slug`, `company_name`, `job_title`, `job_url`, `custom_pitch`, `default_lens`, `created_at`, `status`) VALUES
(1, 'jenov-test', 'J-eNOV SA', 'Directeur Ops', 'https://www.jobup.ch/fr/emplois/detail/c9e00447-7dbe-410b-87f7-f12729462ba1/', 'Bonjour David, voici un test...', 'ops', '2026-04-04 10:08:35', 'sent'),
(2, 'vnv-test', 'VNV SA', 'Software Manager', 'https://www.linkedin.com/jobs/view/4325498872/', 'Bonjour Yannick, ceci est un test', 'management', '2026-04-04 10:09:25', 'sent');

-- --------------------------------------------------------

--
-- Table structure for table `crm_events`
--

CREATE TABLE `crm_events` (
  `id` int NOT NULL,
  `app_id` int NOT NULL,
  `event_date` datetime DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb3_unicode_ci,
  `next_action` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cv_education`
--

CREATE TABLE `cv_education` (
  `id` int NOT NULL,
  `degree` varchar(150) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `institution` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `year` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `icon` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT 'fa-graduation-cap'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cv_experiences`
--

CREATE TABLE `cv_experiences` (
  `id` int NOT NULL,
  `company` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `period` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb3_unicode_ci,
  `category` enum('management','tech','ops') COLLATE utf8mb3_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cv_languages`
--

CREATE TABLE `cv_languages` (
  `id` int NOT NULL,
  `label` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `level` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cv_skills`
--

CREATE TABLE `cv_skills` (
  `id` int NOT NULL,
  `category` enum('management','tech','ops') COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `label` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `level_text` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int UNSIGNED NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `experiences`
--

CREATE TABLE `experiences` (
  `id` int UNSIGNED NOT NULL,
  `company` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('management','ops','tech') COLLATE utf8mb4_unicode_ci DEFAULT 'ops',
  `is_highlight` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `experiences`
--

INSERT INTO `experiences` (`id`, `company`, `title`, `location`, `date_start`, `date_end`, `description`, `category`, `is_highlight`, `created_at`) VALUES
(1, 'SwissTiming (Swatch Group)', 'Software Architect & Deputy Project Manager', 'Corgémont', '2012-01-01', '2018-12-31', '• Architecture logicielle du système de scoring vidéo multisport pour les JO de Rio 2016.\n• Redesign complet du framework de développement SDK.\n• Gestion de projet et coordination d\'équipes pluridisciplinaires.', 'ops', 1, '2026-04-04 16:53:59'),
(2, 'SolvAxis (ProConcept)', 'Lead Software Architect', 'Sonceboz', '2009-01-01', '2011-12-31', '• Change Management : Modernisation de l\'ERP vers une version Web (AJAX/JS).\n• Industrialisation : Mise en place des processus Agile/SCRUM et de Git.\n• Gestion des impacts technologiques et humains pour la base installée.', 'tech', 0, '2026-04-04 16:53:59'),
(3, 'Membre de la Direction (Co-management)', 'COO / Software Manager', 'Courtelary', '2019-01-01', NULL, '• Management d\'équipes multi-sites internationales (22+ ingénieurs).\n• Pilotage de la stratégie Cloud et de la Roadmap R&D.\n• Alignement des départements Sales, Services et R&D.\n• Définition et suivi des KPIs et OKRs au niveau groupe.', 'management', 1, '2026-04-04 16:53:59');

-- --------------------------------------------------------

--
-- Table structure for table `profile_settings`
--

CREATE TABLE `profile_settings` (
  `id` int NOT NULL DEFAULT '1',
  `full_name` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `job_title` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb3_unicode_ci,
  `email` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rel_app_doc`
--

CREATE TABLE `rel_app_doc` (
  `app_id` int UNSIGNED NOT NULL,
  `doc_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telemetry_events`
--

CREATE TABLE `telemetry_events` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `type` enum('copy_paste','pdf_download','section_view','revisit') COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `data` text COLLATE utf8mb3_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telemetry_sessions`
--

CREATE TABLE `telemetry_sessions` (
  `id` int NOT NULL,
  `app_id` int DEFAULT NULL,
  `visitor_uid` varchar(64) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `duration_seconds` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracking_logs`
--

CREATE TABLE `tracking_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `app_id` int UNSIGNED NOT NULL,
  `visited_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tracking_logs`
--

INSERT INTO `tracking_logs` (`id`, `app_id`, `visited_at`, `ip_address`, `user_agent`) VALUES
(1, 1, '2026-04-04 17:10:13', '46.126.168.40', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(2, 1, '2026-04-04 17:10:56', '46.126.168.40', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(3, 2, '2026-04-04 17:11:10', '46.126.168.40', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
(4, 1, '2026-04-04 18:27:50', '46.126.168.40', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36'),
(5, 1, '2026-04-04 19:52:21', '46.126.168.40', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36'),
(6, 1, '2026-04-04 19:54:01', '46.126.168.40', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Mobile Safari/537.36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `crm_events`
--
ALTER TABLE `crm_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cv_education`
--
ALTER TABLE `cv_education`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cv_experiences`
--
ALTER TABLE `cv_experiences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cv_languages`
--
ALTER TABLE `cv_languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cv_skills`
--
ALTER TABLE `cv_skills`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `experiences`
--
ALTER TABLE `experiences`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profile_settings`
--
ALTER TABLE `profile_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rel_app_doc`
--
ALTER TABLE `rel_app_doc`
  ADD PRIMARY KEY (`app_id`,`doc_id`),
  ADD KEY `fk_doc` (`doc_id`);

--
-- Indexes for table `telemetry_events`
--
ALTER TABLE `telemetry_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `telemetry_sessions`
--
ALTER TABLE `telemetry_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tracking_logs`
--
ALTER TABLE `tracking_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_track_app` (`app_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crm_events`
--
ALTER TABLE `crm_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cv_education`
--
ALTER TABLE `cv_education`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cv_experiences`
--
ALTER TABLE `cv_experiences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cv_languages`
--
ALTER TABLE `cv_languages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cv_skills`
--
ALTER TABLE `cv_skills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `experiences`
--
ALTER TABLE `experiences`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `telemetry_events`
--
ALTER TABLE `telemetry_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `telemetry_sessions`
--
ALTER TABLE `telemetry_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tracking_logs`
--
ALTER TABLE `tracking_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rel_app_doc`
--
ALTER TABLE `rel_app_doc`
  ADD CONSTRAINT `fk_app` FOREIGN KEY (`app_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tracking_logs`
--
ALTER TABLE `tracking_logs`
  ADD CONSTRAINT `fk_track_app` FOREIGN KEY (`app_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;