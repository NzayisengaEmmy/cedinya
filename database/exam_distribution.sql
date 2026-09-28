-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2026 at 12:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exam_distribution`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('download','view','upload') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`id`, `file_id`, `user_id`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 2, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36', '2026-09-26 11:16:45'),
(2, 2, 1, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:35:15'),
(3, 2, 1, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:35:16'),
(4, 2, 1, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:42:51'),
(5, 2, 1, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:42:51'),
(6, 1, 1, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:44:22'),
(7, 1, 1, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:44:23'),
(8, 1, 1, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:47:27'),
(9, 1, 1, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:47:27'),
(10, 1, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:50:11'),
(11, 1, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:50:12'),
(12, 2, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:50:38'),
(13, 2, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:50:39'),
(14, 2, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:52:05'),
(15, 2, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:52:06'),
(16, 2, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:58:43'),
(17, 2, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 09:58:44'),
(18, 1, 2, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 10:17:28'),
(19, 1, 2, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 10:17:29'),
(20, 1, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 10:18:24'),
(21, 1, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36 Edg/153.0.0.0', '2026-09-27 10:18:24'),
(22, 2, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-27 14:04:45'),
(23, 2, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-27 14:04:56'),
(24, 2, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-27 14:04:56'),
(25, 2, 3, 'download', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-27 14:08:13'),
(26, 2, 3, 'download', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0', '2026-09-27 14:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `exam_files`
--

CREATE TABLE `exam_files` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `exam_type` varchar(50) NOT NULL,
  `category` enum('primary','ordinary','advanced','tvet') DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('draft','published','revoked') DEFAULT 'draft',
  `available_from` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_files`
--

INSERT INTO `exam_files` (`id`, `title`, `description`, `academic_year`, `exam_type`, `category`, `level`, `subject`, `version`, `file_name`, `original_name`, `file_size`, `mime_type`, `status`, `available_from`, `available_until`, `uploaded_by`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'QP Math P1', 'EXAM', '2026/2027', 'PLE', 'primary', 'P1', 'Mathematics', 1, 'exam_6ab7a95ba7786_1790421339.pdf', 'B260925090503QOBT.pdf', 55519, 'application/pdf', 'published', '2026-09-26 10:00:00', '2026-10-01 23:59:00', 1, NULL, '2026-09-26 11:15:39', '2026-09-26 11:47:05'),
(2, 'QP English P2', 'EXAM', '2026/2027', 'End-of-Term', 'primary', 'P2', 'English', 1, 'exam_6ab8e2b67cc68_1790501558.pdf', 'USACCO IBARUWA VESTINE.pdf', 350490, 'application/pdf', 'published', '2026-09-20 13:00:00', '2026-09-28 00:00:00', 2, NULL, '2026-09-27 09:32:38', '2026-09-27 09:32:38');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `category` enum('primary','ordinary','advanced','tvet') DEFAULT NULL,
  `combination_trade` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `district_id`, `code`, `category`, `combination_trade`) VALUES
(1, 'GS GIHINGA', 3714, '371405', 'ordinary', 'Ordinary'),
(2, 'GS KAMONYI', 3714, '371408', 'advanced', 'MSS1'),
(3, 'EP BUMAZI', 3714, '371402', 'primary', 'Primary'),
(4, 'GS BIGUTU', 3714, '371407', 'advanced', 'Arts and Humanities');

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `district_id` int(11) NOT NULL,
  `sectorname` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`district_id`, `sectorname`) VALUES
(3701, 'Bushekeri'),
(3702, 'Bushenge'),
(3703, 'Cyato'),
(3704, 'Gihombo'),
(3705, 'Kagano'),
(3706, 'Kanjongo'),
(3707, 'Karambi'),
(3708, 'Karengera'),
(3709, 'Kirimbi'),
(3710, 'Macuba'),
(3711, 'Mahembe'),
(3712, 'Nyabitekeri'),
(3713, 'Rangiro'),
(3714, 'Ruharambuga'),
(3715, 'Shangi');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','primaryadmin','OLadmin','ALadmin','TVETadmin','admin','district','headteacher','teacher','SEI') NOT NULL DEFAULT 'headteacher',
  `school_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `totp_secret`, `totp_enabled`, `full_name`, `role`, `school_id`, `district_id`, `email`, `phone`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$yA6EXvTsqq2TVJbqz8Ow7unVqlbMjFsdUkEk7AyMaPa5sjHgpx7G6', 'N5MZU2IFP3QWFRLAJMNJW76AR32JPXZN', 0, 'System Admin', 'superadmin', NULL, NULL, NULL, NULL, 1, '2026-09-26 09:06:13'),
(2, '123', '$2y$10$yA6EXvTsqq2TVJbqz8Ow7unVqlbMjFsdUkEk7AyMaPa5sjHgpx7G6', NULL, 0, 'Emmanuel NIYIGENA', 'OLadmin', 1, 3714, 'niyemanu@gmail.com', '0783346195', 0, '2026-09-26 10:29:26'),
(3, '101', '$2y$10$yA6EXvTsqq2TVJbqz8Ow7unVqlbMjFsdUkEk7AyMaPa5sjHgpx7G6', NULL, 0, 'Emmanuel NIYIGENA', 'headteacher', 1, NULL, 'niyemanu@gmail.com', '0783346195', 1, '2026-09-26 11:09:15'),
(4, '202', '$2y$10$.MBMMPBGCdD1xkcVSGTcVes28.op5I.8YLG9T0at/puv9UYwdK8Cm', NULL, 0, 'Samuel', 'headteacher', 1, NULL, 'shakanawe1@gmail.com', '0786285670', 0, '2026-09-27 18:50:28'),
(5, 'Ruharas', '$2y$10$gdI3oeRUI6Qif3z0HB.xuODE/JetEcVL59BBRqUXswp2IgIJ0YI82', 'PS23KLXNYYMFW5MZEQBMG7ONQL4QECIB', 0, 'Muneza Degrace', 'SEI', NULL, 3714, 'ruharas@gmail.com', '078333333', 1, '2026-09-27 20:38:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_access_logs_action_created` (`action`,`created_at`);

--
-- Indexes for table `exam_files`
--
ALTER TABLE `exam_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_exam_files_listing` (`status`,`created_at`),
  ADD KEY `idx_exam_files_availability` (`status`,`available_from`,`available_until`),
  ADD KEY `idx_exam_files_year` (`status`,`academic_year`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`district_id`),
  ADD UNIQUE KEY `uq_sectors_sectorname` (`sectorname`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `exam_files`
--
ALTER TABLE `exam_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `exam_files` (`id`),
  ADD CONSTRAINT `access_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `exam_files`
--
ALTER TABLE `exam_files`
  ADD CONSTRAINT `exam_files_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
