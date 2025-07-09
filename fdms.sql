-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 13, 2025 at 01:48 PM
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
-- Database: `fdms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `entity_type` text NOT NULL,
  `entity_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `ip_addr` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `c_id`, `action`, `entity_type`, `entity_id`, `description`, `ip_addr`, `created_at`) VALUES
(NULL, 1, 1, 'create', 'user', 1, 'Vivek Jadhav created the company and registered as admin.', '::1', '2025-05-12 11:41:41'),
(NULL, 2, 2, 'create', 'user', 2, 'Vivek Jadhav created the company and registered as admin.', '::1', '2025-05-12 11:43:23'),
(NULL, 3, 3, 'create', 'user', 3, 'Vivek Jadhav created the company and registered as admin.', '::1', '2025-05-12 11:46:16'),
(NULL, 2, 2, 'create', 'role', 2, 'Created role: admin', '::1', '2025-05-13 05:18:54'),
(NULL, 2, 2, 'create', 'role', 3, 'Created role: user', '::1', '2025-05-13 05:30:36'),
(NULL, 2, 2, 'create', 'role', 4, 'Created role: viewer', '::1', '2025-05-13 05:35:24'),
(NULL, 2, 2, 'create', 'role', 1, 'Created role: superadmin', '::1', '2025-05-13 05:58:53'),
(NULL, 1, 1, 'create', 'role', 1, 'Created role: superadmin', '::1', '2025-05-13 06:00:20'),
(NULL, 1, 1, 'create', 'role', 2, 'Created role: admin', '::1', '2025-05-13 06:01:30'),
(NULL, 2, 2, 'create', 'role', 2, 'Created role: admin', '::1', '2025-05-13 06:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `c_id` int(11) NOT NULL,
  `c_name` varchar(11) NOT NULL,
  `c_logo` blob NOT NULL,
  `logo_path` text NOT NULL,
  `c_domain` text NOT NULL,
  `c_storage` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`c_id`, `c_name`, `c_logo`, `logo_path`, `c_domain`, `c_storage`, `created_at`) VALUES
(1, 'Amposys', 0x75706c6f6164732f363832316465373535346366622e706e67, 'uploads/6821de7554cfb.png', 'amposys', 0, '2025-05-12 11:41:41'),
(2, 'Google', 0x75706c6f6164732f363832316465646239373031612e706e67, 'uploads/6821dedb9701a.png', 'google', 0, '2025-05-12 11:43:23'),
(3, 'doodle', 0x75706c6f6164732f363832316466383831663130642e706e67, 'uploads/6821df881f10d.png', 'doodle', 0, '2025-05-12 11:46:16');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `c_id` int(11) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `c_id`, `name`, `description`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 1, 'superadmin', 'Apmosys Super Administrator with full system access', '[\"uploadDocuments\",\"downloadDocuments\",\"commentDocuments\",\"shareDocuments\",\"manageDocuments\",\"manageFolders\",\"manageTags\",\"deleteDocuments\"]', 0, '2025-05-13 06:00:20', '2025-05-13 06:00:20'),
(2, 1, 'admin', 'Administrator Have Limited system access', '[\"uploadDocuments\",\"downloadDocuments\",\"commentDocuments\",\"shareDocuments\",\"manageDocuments\",\"manageFolders\",\"deleteDocuments\"]', 0, '2025-05-13 06:01:30', '2025-05-13 06:01:30'),
(1, 2, 'superadmin', 'Super Administrator with full system access', '[\"uploadDocuments\",\"downloadDocuments\",\"commentDocuments\",\"shareDocuments\",\"manageDocuments\",\"manageFolders\",\"manageTags\",\"deleteDocuments\"]', 0, '2025-05-13 05:58:53', '2025-05-13 05:58:53'),
(2, 2, 'admin', 'Admin Have Limited Access', '[\"uploadDocuments\",\"downloadDocuments\",\"commentDocuments\",\"shareDocuments\",\"manageDocuments\",\"deleteDocuments\"]', 0, '2025-05-13 06:47:55', '2025-05-13 06:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `profile` blob NOT NULL,
  `c_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` int(11) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_ip` varchar(254) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `profile`, `c_id`, `role_id`, `is_active`, `last_login`, `last_login_ip`, `created_at`, `updated_at`) VALUES
(1, 'Vivek Jadhav', 'vivek.jadhav@apmosys.com', '$2y$10$TJ77E2Ypqc4Euw3eQFLDpuGhHG73HHrMEvUCFFbwat1/uLmolwNfi', '', 1, 1, 1, '2025-05-13 03:50:03', '::1', '2025-05-12 11:41:41', '0000-00-00 00:00:00'),
(2, 'Vivek Jadhav', 'vivek@gmail.com', '$2y$10$TJ77E2Ypqc4Euw3eQFLDpuGhHG73HHrMEvUCFFbwat1/uLmolwNfi', '', 2, 1, 1, '2025-05-13 09:59:24', '::1', '2025-05-12 11:43:23', '0000-00-00 00:00:00'),
(3, 'Vivek Jadhav', 'vivek2@gmail.com', '$2y$10$Mnjq6BCnq3gKtScTJoDKLO5qPhTapCWilUled.Np6qIRPfq6aYSt2', '', 3, 1, 1, '2025-05-12 11:46:16', '::1', '2025-05-12 11:46:16', '0000-00-00 00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`c_id`,`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `c_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
