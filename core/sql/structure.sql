-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: grahan.pdx1-mysql-a7-5a.dreamhost.com
-- Generation Time: Apr 15, 2026 at 03:47 AM
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
  `status` enum('sent','interview','rejected','accepted') COLLATE utf8mb4_unicode_ci DEFAULT 'sent',
  `why_me` text COLLATE utf8mb4_unicode_ci COMMENT 'Présentation personnelle',
  `strengths` text COLLATE utf8mb4_unicode_ci COMMENT 'Forces et compétences clés',
  `perfect_match` text COLLATE utf8mb4_unicode_ci COMMENT 'Pourquoi ce match est le bon'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
-- Table structure for table `crm_events_attached`
--

CREATE TABLE `crm_events_attached` (
  `id` int NOT NULL,
  `event_id` int NOT NULL,
  `link` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attached_type` enum('url','file') COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `category` enum('management','tech','ops') COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0'
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
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telemetry_events`
--

CREATE TABLE `telemetry_events` (
  `id` int NOT NULL,
  `session_id` binary(16) DEFAULT NULL,
  `event_type` enum('view_section','download','copy_text','scroll_depth','heartbeat','reading_focus') COLLATE utf8mb4_unicode_ci NOT NULL,
  `element_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_data` mediumtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `telemetry_sessions`
--

CREATE TABLE `telemetry_sessions` (
  `id` binary(16) NOT NULL,
  `app_id` int NOT NULL,
  `visitor_uuid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` mediumtext COLLATE utf8mb4_unicode_ci,
  `browser_lang` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `duration_seconds` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `crm_events_attached`
--
ALTER TABLE `crm_events_attached`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `cv_education`
--
ALTER TABLE `cv_education`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cv_experiences`
--
ALTER TABLE `cv_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_display_order` (`display_order`);

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
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `crm_events`
--
ALTER TABLE `crm_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `crm_events_attached`
--
ALTER TABLE `crm_events_attached`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cv_education`
--
ALTER TABLE `cv_education`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cv_experiences`
--
ALTER TABLE `cv_experiences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cv_languages`
--
ALTER TABLE `cv_languages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cv_skills`
--
ALTER TABLE `cv_skills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `telemetry_events`
--
ALTER TABLE `telemetry_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1504;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crm_events_attached`
--
ALTER TABLE `crm_events_attached`
  ADD CONSTRAINT `crm_events_attached_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `crm_events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rel_app_doc`
--
ALTER TABLE `rel_app_doc`
  ADD CONSTRAINT `fk_app` FOREIGN KEY (`app_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;