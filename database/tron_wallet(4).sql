-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2025 at 11:25 AM
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
-- Database: `tron_wallet`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 86, 'BALANCE_CHANGE', 'Balance changed from 0.00 to 8.00', NULL, NULL, '2025-06-06 19:09:56'),
(2, 86, 'BALANCE_CHANGE', 'Balance changed from 8.00 to 0.50', NULL, NULL, '2025-06-07 05:45:03'),
(3, 86, 'BALANCE_CHANGE', 'Balance changed from 0.50 to 8.00', NULL, NULL, '2025-06-07 05:45:53'),
(7, 86, 'BALANCE_CHANGE', 'Balance changed from 8.00 to 15.00', NULL, NULL, '2025-06-07 12:22:15'),
(10, 86, 'BALANCE_CHANGE', 'Balance changed from 15.00 to 2285.00', NULL, NULL, '2025-06-07 12:27:04'),
(11, 86, 'BALANCE_CHANGE', 'Balance changed from 2285.00 to 4517.00', NULL, NULL, '2025-06-07 12:27:25'),
(12, 86, 'BALANCE_CHANGE', 'Balance changed from 4517.00 to 3507.00', NULL, NULL, '2025-06-07 12:28:05'),
(13, 86, 'BALANCE_CHANGE', 'Balance changed from 3507.00 to 2297.00', NULL, NULL, '2025-06-07 12:36:14'),
(14, 86, 'BALANCE_CHANGE', 'Balance changed from 2297.00 to 26426.50', NULL, NULL, '2025-06-07 12:37:17'),
(15, 86, 'BALANCE_CHANGE', 'Balance changed from 26426.50 to 32329.78', NULL, NULL, '2025-06-07 13:18:53'),
(16, 86, 'BALANCE_CHANGE', 'Balance changed from 32329.78 to 22319.78', NULL, NULL, '2025-06-07 13:19:29'),
(23, 86, 'BALANCE_CHANGE', 'Balance changed from 22319.78 to 32309.78', NULL, NULL, '2025-06-07 13:31:26'),
(24, 86, 'BALANCE_CHANGE', 'Balance changed from 32309.78 to 22299.78', NULL, NULL, '2025-06-07 13:39:50'),
(25, 86, 'BALANCE_CHANGE', 'Balance changed from 22299.78 to 11144.89', NULL, NULL, '2025-06-07 13:41:13'),
(26, 86, 'BALANCE_CHANGE', 'Balance changed from 11144.89 to 5567.45', NULL, NULL, '2025-06-07 13:43:18'),
(27, 86, 'BALANCE_CHANGE', 'Balance changed from 5567.45 to 26913.62', NULL, NULL, '2025-06-07 14:10:15'),
(28, 86, 'BALANCE_CHANGE', 'Balance changed from 26913.62 to 37581.70', NULL, NULL, '2025-06-07 14:10:35'),
(29, 86, 'BALANCE_CHANGE', 'Balance changed from 37581.70 to 42910.74', NULL, NULL, '2025-06-07 14:10:50'),
(30, 86, 'BALANCE_CHANGE', 'Balance changed from 42910.74 to 48239.78', NULL, NULL, '2025-06-07 14:10:58'),
(31, 86, 'BALANCE_CHANGE', 'Balance changed from 48239.78 to 48233.28', NULL, NULL, '2025-06-08 11:52:14'),
(32, 86, 'BALANCE_CHANGE', 'Balance changed from 48233.28 to 48226.78', NULL, NULL, '2025-06-08 12:11:12'),
(33, 86, 'BALANCE_CHANGE', 'Balance changed from 48226.78 to 48233.28', NULL, NULL, '2025-06-08 12:11:12'),
(34, 86, 'BALANCE_CHANGE', 'Balance changed from 48233.28 to 48226.78', NULL, NULL, '2025-06-08 16:08:47'),
(35, 86, 'BALANCE_CHANGE', 'Balance changed from 48226.78 to 48233.28', NULL, NULL, '2025-06-08 16:08:48'),
(36, 86, 'BALANCE_CHANGE', 'Balance changed from 48233.28 to 48226.78', NULL, NULL, '2025-06-08 16:09:22'),
(37, 86, 'BALANCE_CHANGE', 'Balance changed from 48226.78 to 48233.28', NULL, NULL, '2025-06-08 16:09:25'),
(38, 86, 'BALANCE_CHANGE', 'Balance changed from 48233.28 to 48226.78', NULL, NULL, '2025-06-08 16:22:32'),
(39, 86, 'BALANCE_CHANGE', 'Balance changed from 48226.78 to 48233.28', NULL, NULL, '2025-06-08 16:22:33'),
(40, 86, 'BALANCE_CHANGE', 'Balance changed from 48233.28 to 48226.78', NULL, NULL, '2025-06-08 16:22:59'),
(41, 86, 'BALANCE_CHANGE', 'Balance changed from 48226.78 to 48233.28', NULL, NULL, '2025-06-08 16:23:00'),
(42, 86, 'BALANCE_CHANGE', 'Balance changed from 48233.28 to 48226.78', NULL, NULL, '2025-06-08 16:23:26'),
(43, 37, 'BALANCE_CHANGE', 'Balance changed from 0.00 to 9.26', NULL, NULL, '2025-06-08 16:52:53'),
(44, 37, 'BALANCE_CHANGE', 'Balance changed from 9.26 to 0.00', NULL, NULL, '2025-06-08 17:00:10'),
(45, 86, 'LOGIN', 'User logged in at 2025-06-09', NULL, NULL, '2025-06-09 08:34:55'),
(46, 86, 'BALANCE_CHANGE', 'Balance changed from 48226.78 to 38216.78', NULL, NULL, '2025-06-09 08:35:46'),
(47, 86, 'BALANCE_CHANGE', 'Balance changed from 38216.78 to 19103.39', NULL, NULL, '2025-06-09 08:41:51'),
(48, 86, 'BALANCE_CHANGE', 'Balance changed from 19103.39 to 55653.14', NULL, NULL, '2025-06-09 08:42:22'),
(49, 86, 'BALANCE_CHANGE', 'Balance changed from 55653.14 to 27821.57', NULL, NULL, '2025-06-09 08:48:04'),
(50, 86, 'BALANCE_CHANGE', 'Balance changed from 27821.57 to 55633.14', NULL, NULL, '2025-06-09 08:48:16'),
(51, 86, 'BALANCE_CHANGE', 'Balance changed from 55633.14 to 27811.57', NULL, NULL, '2025-06-09 08:58:03'),
(52, 86, 'BALANCE_CHANGE', 'Balance changed from 27811.57 to 55613.14', NULL, NULL, '2025-06-09 08:58:14'),
(53, 86, 'BALANCE_CHANGE', 'Balance changed from 55613.14 to 27801.57', NULL, NULL, '2025-06-09 08:58:20'),
(54, 86, 'BALANCE_CHANGE', 'Balance changed from 27801.57 to 55593.14', NULL, NULL, '2025-06-09 08:58:27'),
(55, 86, 'BALANCE_CHANGE', 'Balance changed from 55593.14 to 27791.57', NULL, NULL, '2025-06-09 09:00:47'),
(56, 86, 'BALANCE_CHANGE', 'Balance changed from 27791.57 to 55573.14', NULL, NULL, '2025-06-09 09:00:52'),
(57, 86, 'BALANCE_CHANGE', 'Balance changed from 55573.14 to 27781.57', NULL, NULL, '2025-06-09 09:01:04'),
(58, 86, 'BALANCE_CHANGE', 'Balance changed from 27781.57 to 13885.78', NULL, NULL, '2025-06-09 09:17:44'),
(59, 86, 'BALANCE_CHANGE', 'Balance changed from 13885.78 to 0.00', NULL, NULL, '2025-06-09 09:17:50');

-- --------------------------------------------------------

--
-- Table structure for table `balance_supply`
--

CREATE TABLE `balance_supply` (
  `id` int(11) NOT NULL,
  `max_balance` decimal(10,2) NOT NULL,
  `remaining_balance` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bonding_curves`
--

CREATE TABLE `bonding_curves` (
  `id` bigint(20) NOT NULL,
  `token_id` bigint(20) NOT NULL,
  `curve_type` varchar(20) DEFAULT 'linear',
  `initial_price` decimal(20,8) NOT NULL,
  `k_parameter` decimal(20,8) DEFAULT 1.00000000,
  `virtual_trx_reserves` decimal(20,8) DEFAULT 30000.00000000,
  `virtual_token_reserves` decimal(20,8) DEFAULT 1073000000.00000000,
  `real_trx_reserves` decimal(20,8) DEFAULT 0.00000000,
  `real_token_reserves` decimal(20,8) DEFAULT 1000000000.00000000,
  `tokens_available` decimal(20,8) DEFAULT 800000000.00000000,
  `graduation_threshold` decimal(20,8) DEFAULT 69000.00000000,
  `current_progress` decimal(5,2) DEFAULT 0.00,
  `fee_percentage` decimal(5,4) DEFAULT 1.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tokens_sold` decimal(20,8) DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bonding_curves`
--

INSERT INTO `bonding_curves` (`id`, `token_id`, `curve_type`, `initial_price`, `k_parameter`, `virtual_trx_reserves`, `virtual_token_reserves`, `real_trx_reserves`, `real_token_reserves`, `tokens_available`, `graduation_threshold`, `current_progress`, `fee_percentage`, `created_at`, `updated_at`, `tokens_sold`) VALUES
(1, 2, 'linear', 0.00010000, 1.00000000, 30000.00000000, 1073000000.00000000, 0.00000000, 1000000000.00000000, 1000000000.00000000, 69000.00000000, 0.00, 1.0000, '2025-06-07 11:57:43', '2025-06-07 14:10:58', 0.00000000),
(3, 4, 'linear', 0.00010000, 1.00000000, 30000.00000000, 1073000000.00000000, -23139.50000000, 800000000.00000000, 1000000000.00000000, 69000.00000000, 0.00, 1.0000, '2025-06-07 12:28:05', '2025-06-07 12:37:17', -200000000.00000000),
(4, 5, 'linear', 0.00002908, 1.00000000, 25286.72428865, 1272999999.23080000, -4713.27571135, 758730769.23077000, 999999999.23077000, 69000.00000000, -6.83, 1.0000, '2025-06-07 12:36:14', '2025-06-07 13:18:53', -199999999.23077000),
(5, 6, 'linear', 0.00003728, 1.00000000, 40000.00000000, 804750000.00000000, 0.00000000, 531750000.00000000, 1000000000.00000000, 69000.00000000, -14.49, 1.0000, '2025-06-07 13:19:29', '2025-06-07 13:31:26', -200000000.00000000),
(6, 7, 'linear', 0.00003728, 1.00000000, 95543.13499998, -685140960.30000000, 65543.13499998, 531750000.00000000, 531750000.00000000, 69000.00000000, 0.00, 1.0000, '2025-06-09 08:35:46', '2025-06-09 09:17:50', 1758140960.29999970);

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cards`
--

INSERT INTO `cards` (`id`, `name`, `image`, `price`) VALUES
(1, 'Vortex', 'uploads/card/Untitled design.jpg', 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'company_wallet_address', 'TCompanyWalletAddress123456789', '2025-06-07 12:06:31', '2025-06-07 12:06:31'),
(2, 'trading_fee_trx', '10', '2025-06-07 12:06:31', '2025-06-07 12:06:31'),
(3, 'launch_fee_trx', '10', '2025-06-07 12:06:31', '2025-06-07 12:06:31'),
(4, 'platform_fee_percentage', '1.0', '2025-06-07 12:06:31', '2025-06-07 12:06:31');

-- --------------------------------------------------------

--
-- Table structure for table `deposit_logs`
--

CREATE TABLE `deposit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_address` varchar(255) DEFAULT NULL,
  `to_address` varchar(255) DEFAULT NULL,
  `amount` decimal(20,6) DEFAULT NULL,
  `tx_hash` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposit_logs`
--

INSERT INTO `deposit_logs` (`id`, `user_id`, `from_address`, `to_address`, `amount`, `tx_hash`, `status`, `processed_at`, `created_at`) VALUES
(1, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 9.259005, '700b18963b856c1dde1a85a276f2af76f137d32a0ce351ce9c8fa1187d8d5dbd', 'completed', '2025-06-08 16:52:53', '2025-06-08 16:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `purpose` enum('withdrawal','registration','password_reset') DEFAULT 'withdrawal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 5 minute),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `internal_transfers`
--

CREATE TABLE `internal_transfers` (
  `id` int(11) NOT NULL,
  `from_user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `from_username` varchar(255) NOT NULL,
  `to_username` varchar(255) NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `status` enum('completed','failed') DEFAULT 'completed',
  `transfer_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `announcement_id`, `created_at`) VALUES
(1, 14, 1, '2024-10-17 14:08:22'),
(2, 14, 2, '2024-10-17 14:30:55'),
(3, 14, 4, '2024-10-17 15:08:02'),
(4, 14, 8, '2024-10-17 15:23:52'),
(5, 14, 9, '2024-10-17 16:06:10'),
(6, 5, 9, '2024-10-17 16:07:38'),
(7, 5, 4, '2024-10-17 16:07:46'),
(8, 15, 9, '2024-10-17 16:23:59'),
(9, 5, 10, '2024-10-17 16:40:38'),
(10, 15, 10, '2024-10-17 16:50:07'),
(11, 14, 10, '2024-10-17 18:47:07'),
(12, 14, 11, '2024-10-17 18:53:55'),
(13, 14, 13, '2024-10-17 19:02:27'),
(14, 14, 12, '2024-10-17 19:02:29'),
(15, 14, 15, '2024-10-18 06:13:30'),
(16, 14, 16, '2024-10-18 06:16:29'),
(17, 14, 17, '2024-10-18 07:04:54'),
(18, 15, 17, '2024-10-18 07:27:05'),
(19, 15, 16, '2024-10-18 07:27:08'),
(20, 14, 18, '2024-10-18 07:35:49'),
(21, 14, 19, '2024-10-18 07:38:51'),
(22, 53, 21, '2024-10-18 09:01:44'),
(23, 53, 19, '2024-10-18 09:01:48'),
(24, 14, 20, '2024-10-18 09:38:09'),
(25, 14, 21, '2024-10-18 09:38:10'),
(26, 15, 18, '2024-10-18 12:53:40'),
(27, 15, 21, '2024-10-18 12:53:42'),
(28, 14, 24, '2024-10-18 13:32:37'),
(29, 14, 23, '2024-10-18 13:32:38'),
(30, 5, 24, '2024-10-18 13:33:05'),
(31, 5, 23, '2024-10-18 13:33:16'),
(32, 5, 22, '2024-10-18 13:33:17'),
(33, 5, 25, '2024-10-18 13:43:49'),
(34, 5, 27, '2024-10-18 14:02:14'),
(35, 5, 26, '2024-10-18 14:03:02'),
(36, 55, 28, '2024-10-18 15:11:00'),
(37, 15, 28, '2024-10-18 15:11:01'),
(38, 55, 27, '2024-10-18 15:11:18'),
(39, 55, 26, '2024-10-18 15:11:20'),
(40, 55, 25, '2024-10-18 15:11:21'),
(41, 15, 27, '2024-10-18 15:11:22'),
(42, 55, 24, '2024-10-18 15:11:23'),
(43, 55, 22, '2024-10-18 15:11:25'),
(44, 15, 26, '2024-10-18 15:11:26'),
(45, 55, 21, '2024-10-18 15:11:27'),
(46, 15, 24, '2024-10-18 15:11:30'),
(47, 15, 29, '2024-10-18 16:05:52'),
(48, 14, 29, '2024-10-18 16:45:24'),
(49, 14, 31, '2024-10-18 16:46:29'),
(50, 15, 31, '2024-10-18 16:47:53'),
(51, 14, 28, '2024-10-18 17:14:50'),
(52, 14, 32, '2024-10-18 17:19:35'),
(53, 14, 33, '2024-10-18 17:21:20'),
(54, 14, 34, '2024-10-18 17:23:50'),
(55, 14, 35, '2024-10-18 17:25:47'),
(56, 14, 26, '2024-10-18 17:26:02'),
(57, 14, 25, '2024-10-18 17:26:05'),
(58, 14, 36, '2024-10-18 17:29:12'),
(59, 5, 37, '2024-10-18 17:42:24'),
(60, 5, 36, '2024-10-18 17:46:27'),
(61, 5, 38, '2024-10-18 17:46:31'),
(62, 5, 39, '2024-10-18 17:54:02'),
(63, 14, 39, '2024-10-18 18:49:42'),
(64, 14, 38, '2024-10-18 18:49:44'),
(65, 14, 37, '2024-10-18 18:49:45'),
(66, 5, 34, '2024-10-19 06:07:34'),
(67, 15, 39, '2024-10-19 07:31:16'),
(68, 5, 40, '2024-10-19 12:01:47'),
(69, 14, 41, '2024-10-19 12:06:25'),
(70, 14, 42, '2024-10-19 12:10:54'),
(71, 14, 40, '2024-10-19 12:11:18'),
(72, 5, 42, '2024-10-19 12:34:23'),
(73, 5, 41, '2024-10-19 12:38:02'),
(74, 5, 35, '2024-10-19 12:42:19'),
(75, 5, 33, '2024-10-19 12:42:34'),
(76, 5, 32, '2024-10-19 12:42:39'),
(77, 5, 44, '2024-10-19 12:49:24'),
(78, 37, 44, '2024-10-19 12:51:59'),
(79, 37, 42, '2024-10-19 12:52:07'),
(80, 37, 41, '2024-10-19 12:53:29'),
(81, 37, 39, '2024-10-19 12:53:33'),
(82, 37, 38, '2024-10-19 12:53:42'),
(83, 37, 37, '2024-10-19 12:55:02'),
(84, 37, 36, '2024-10-19 12:55:25'),
(85, 37, 35, '2024-10-19 12:55:59'),
(86, 37, 34, '2024-10-19 13:00:12'),
(87, 37, 45, '2024-10-19 13:02:01'),
(88, 14, 45, '2024-10-19 13:08:01'),
(89, 14, 44, '2024-10-19 13:08:17'),
(90, 14, 46, '2024-10-19 13:11:18'),
(91, 14, 47, '2024-10-19 13:20:04'),
(92, 14, 48, '2024-10-19 14:09:59'),
(93, 14, 50, '2024-10-19 14:19:22'),
(94, 14, 51, '2024-10-19 14:34:36'),
(95, 15, 51, '2024-10-19 14:40:27'),
(96, 15, 50, '2024-10-19 14:40:44'),
(97, 5, 52, '2024-10-19 15:25:28'),
(98, 5, 53, '2024-10-19 15:26:15'),
(99, 5, 55, '2024-10-19 15:30:34'),
(100, 5, 54, '2024-10-19 15:40:07'),
(101, 14, 55, '2024-10-19 15:50:18'),
(102, 14, 56, '2024-10-19 15:52:34'),
(103, 5, 56, '2024-10-19 15:54:19'),
(104, 14, 57, '2024-10-19 15:59:51'),
(105, 5, 57, '2024-10-19 15:59:57'),
(106, 5, 58, '2024-10-19 16:06:01'),
(107, 14, 58, '2024-10-19 16:28:24'),
(108, 14, 54, '2024-10-19 16:34:32'),
(109, 15, 58, '2024-10-19 16:43:05'),
(110, 15, 57, '2024-10-19 16:44:38'),
(111, 15, 56, '2024-10-19 16:44:46'),
(112, 14, 53, '2024-10-19 16:47:08'),
(113, 15, 55, '2024-10-19 16:48:39'),
(114, 15, 54, '2024-10-19 16:49:09'),
(115, 15, 53, '2024-10-19 16:49:12'),
(116, 15, 52, '2024-10-19 16:49:14'),
(117, 15, 59, '2024-10-19 18:18:49'),
(118, 15, 60, '2024-10-19 18:21:17'),
(119, 5, 60, '2024-10-19 18:34:32'),
(120, 14, 60, '2024-10-19 18:41:47'),
(121, 14, 59, '2024-10-19 18:43:16'),
(122, 5, 61, '2024-10-19 18:49:21'),
(123, 5, 59, '2024-10-19 18:57:54'),
(124, 14, 61, '2024-10-20 03:42:41'),
(125, 5, 62, '2024-10-20 04:15:33'),
(126, 15, 63, '2024-10-20 04:30:27'),
(127, 15, 62, '2024-10-20 04:30:30'),
(128, 15, 61, '2024-10-20 04:30:32'),
(129, 5, 63, '2024-10-20 04:38:39'),
(130, 5, 64, '2024-10-20 06:31:36'),
(131, 58, 64, '2024-10-20 09:56:09'),
(132, 58, 55, '2024-10-20 10:03:15'),
(133, 58, 56, '2024-10-20 10:03:17'),
(134, 58, 57, '2024-10-20 10:03:19'),
(135, 58, 58, '2024-10-20 10:03:21'),
(136, 58, 59, '2024-10-20 10:03:23'),
(137, 58, 60, '2024-10-20 10:03:24'),
(138, 58, 61, '2024-10-20 10:03:27'),
(139, 58, 62, '2024-10-20 10:03:29'),
(140, 58, 63, '2024-10-20 10:03:31'),
(141, 14, 64, '2024-10-20 10:22:11'),
(142, 14, 63, '2024-10-20 10:22:15'),
(143, 14, 62, '2024-10-20 10:22:18'),
(144, 5, 65, '2024-10-20 14:25:23'),
(145, 14, 65, '2024-10-20 15:05:57'),
(146, 15, 65, '2024-10-21 01:18:31'),
(147, 15, 64, '2024-10-21 01:18:36'),
(148, 37, 65, '2024-10-21 05:37:09'),
(149, 37, 64, '2024-10-21 05:37:15'),
(150, 37, 63, '2024-10-21 05:37:18'),
(151, 37, 62, '2024-10-21 05:37:21'),
(152, 5, 66, '2024-10-21 07:51:06'),
(153, 5, 67, '2024-10-21 07:53:23'),
(154, 5, 68, '2024-10-21 09:36:44'),
(155, 5, 69, '2024-10-21 12:29:46'),
(156, 5, 71, '2024-10-21 12:35:07'),
(157, 5, 70, '2024-10-21 12:35:11'),
(158, 5, 72, '2024-10-21 12:40:05'),
(159, 5, 73, '2024-10-21 12:58:40'),
(160, 5, 74, '2024-10-21 14:33:43'),
(161, 5, 75, '2024-10-21 15:09:42'),
(162, 15, 75, '2024-10-21 15:38:30'),
(163, 15, 74, '2024-10-21 16:13:39'),
(164, 14, 75, '2024-10-21 17:23:54'),
(165, 14, 72, '2024-10-21 17:24:02'),
(166, 14, 71, '2024-10-21 17:24:04'),
(167, 14, 69, '2024-10-21 17:24:39'),
(168, 15, 73, '2024-10-21 17:38:18'),
(169, 15, 72, '2024-10-21 17:38:20'),
(170, 15, 71, '2024-10-21 17:38:22'),
(171, 15, 70, '2024-10-21 17:38:26'),
(172, 15, 69, '2024-10-21 17:38:27'),
(173, 15, 68, '2024-10-21 17:38:29'),
(174, 15, 67, '2024-10-21 17:38:31'),
(175, 15, 66, '2024-10-21 17:38:34'),
(176, 15, 76, '2024-10-21 17:52:56'),
(177, 5, 76, '2024-10-21 18:14:48'),
(178, 5, 77, '2024-10-22 06:03:29'),
(179, 5, 78, '2024-10-22 06:32:26'),
(180, 5, 79, '2024-10-22 06:38:04'),
(181, 15, 79, '2024-10-22 13:23:47'),
(182, 14, 79, '2024-10-22 13:39:12'),
(183, 14, 76, '2024-10-22 15:25:30'),
(184, 14, 78, '2024-10-22 15:36:22'),
(185, 14, 77, '2024-10-22 15:36:26'),
(186, 14, 73, '2024-10-22 15:36:31'),
(187, 14, 74, '2024-10-22 15:37:34'),
(188, 14, 70, '2024-10-22 15:37:39'),
(189, 5, 80, '2024-10-22 16:08:28'),
(190, 14, 80, '2024-10-22 16:12:40'),
(191, 5, 81, '2024-10-22 16:13:45'),
(192, 14, 81, '2024-10-22 16:18:41'),
(193, 14, 83, '2024-10-22 16:23:31'),
(194, 5, 83, '2024-10-22 16:23:50'),
(195, 14, 84, '2024-10-22 16:42:06'),
(196, 5, 84, '2024-10-22 16:54:28'),
(197, 5, 86, '2024-10-22 16:56:57'),
(198, 14, 86, '2024-10-22 17:04:06'),
(199, 5, 87, '2024-10-22 17:05:33'),
(200, 14, 85, '2024-10-22 17:06:37'),
(201, 14, 87, '2024-10-22 17:17:36'),
(202, 5, 85, '2024-10-22 17:28:11'),
(203, 5, 88, '2024-10-23 07:43:04'),
(204, 14, 88, '2024-10-23 09:40:49'),
(205, 57, 88, '2024-10-23 15:15:35'),
(206, 14, 90, '2024-10-23 15:35:23'),
(207, 14, 89, '2024-10-23 15:54:21'),
(208, 14, 91, '2024-10-23 16:41:29'),
(209, 5, 91, '2024-10-23 17:51:35'),
(210, 5, 89, '2024-10-23 17:51:37'),
(211, 5, 92, '2024-10-23 18:53:22'),
(212, 14, 92, '2024-10-23 18:56:18'),
(213, 15, 92, '2024-10-23 19:48:17'),
(214, 15, 91, '2024-10-23 19:48:23'),
(215, 15, 89, '2024-10-23 20:04:59'),
(216, 68, 92, '2024-10-24 15:03:58'),
(217, 68, 91, '2024-10-24 15:04:03'),
(218, 68, 89, '2024-10-24 15:04:04'),
(219, 68, 88, '2024-10-24 15:04:06'),
(220, 37, 92, '2024-10-24 15:56:51'),
(221, 37, 91, '2024-10-24 15:56:54'),
(222, 37, 89, '2024-10-24 15:56:55'),
(223, 37, 88, '2024-10-24 15:56:57'),
(224, 5, 93, '2024-10-24 17:17:57'),
(225, 5, 94, '2024-10-24 17:20:17'),
(226, 37, 94, '2024-10-24 17:21:19'),
(227, 37, 93, '2024-10-24 17:23:06'),
(228, 15, 94, '2024-10-25 11:54:58'),
(229, 15, 93, '2024-10-25 11:55:01'),
(230, 15, 88, '2024-10-25 11:55:08'),
(231, 15, 86, '2024-10-25 11:55:10'),
(232, 15, 78, '2024-10-25 11:55:13'),
(233, 15, 77, '2024-10-25 11:55:17'),
(234, 5, 95, '2024-10-25 12:55:50'),
(235, 14, 95, '2024-10-25 16:48:02'),
(236, 5, 96, '2024-10-26 07:04:01'),
(237, 14, 96, '2024-10-26 09:50:46'),
(238, 15, 96, '2024-10-26 11:52:35'),
(239, 15, 95, '2024-10-26 13:58:04'),
(240, 37, 96, '2024-10-26 16:09:16'),
(241, 37, 95, '2024-10-26 16:09:25'),
(242, 37, 97, '2024-10-26 18:34:31'),
(243, 5, 97, '2024-10-26 18:46:51'),
(244, 15, 98, '2024-10-26 18:55:32'),
(245, 14, 98, '2024-10-26 19:12:41'),
(246, 14, 97, '2024-10-26 19:12:46'),
(247, 15, 97, '2024-10-27 11:41:33'),
(248, 37, 98, '2024-10-27 11:48:35'),
(249, 68, 98, '2024-10-27 11:53:16'),
(250, 74, 98, '2024-10-28 14:33:23'),
(251, 74, 97, '2024-10-28 14:33:35'),
(252, 74, 96, '2024-10-28 14:33:44'),
(253, 74, 95, '2024-10-28 14:33:52'),
(254, 74, 94, '2024-10-28 14:33:57'),
(255, 37, 99, '2024-10-28 16:09:22'),
(256, 37, 100, '2024-10-28 16:11:16'),
(257, 14, 100, '2024-10-29 07:15:01'),
(258, 37, 101, '2024-10-29 16:43:52'),
(259, 76, 101, '2024-10-29 17:15:19'),
(260, 5, 101, '2024-10-30 04:45:04'),
(261, 5, 102, '2024-10-30 15:06:11'),
(262, 14, 102, '2024-10-30 15:09:06'),
(263, 14, 103, '2024-10-30 15:14:39'),
(264, 14, 101, '2024-10-30 15:16:45'),
(265, 5, 103, '2024-10-30 15:16:58'),
(266, 15, 103, '2024-10-30 15:21:42'),
(267, 15, 102, '2024-10-30 15:21:45'),
(268, 5, 100, '2024-10-30 15:26:45'),
(269, 68, 103, '2024-10-30 15:29:27'),
(270, 14, 94, '2024-10-30 15:36:28'),
(271, 5, 98, '2024-10-30 15:47:01'),
(272, 14, 104, '2024-10-30 16:18:33'),
(273, 5, 104, '2024-10-30 16:18:48'),
(274, 68, 104, '2024-10-30 16:21:44'),
(275, 68, 102, '2024-10-30 16:47:57'),
(276, 68, 101, '2024-10-30 16:48:03'),
(277, 14, 105, '2024-10-30 17:28:00'),
(278, 14, 106, '2024-10-30 17:33:26'),
(279, 15, 106, '2024-10-30 17:58:57'),
(280, 15, 105, '2024-10-30 17:59:04'),
(281, 68, 106, '2024-10-30 18:07:54'),
(282, 68, 105, '2024-10-30 18:20:25'),
(283, 14, 107, '2024-10-30 18:30:20'),
(284, 68, 107, '2024-10-30 18:31:05'),
(285, 68, 108, '2024-10-30 18:35:31'),
(286, 14, 108, '2024-10-30 18:35:51'),
(287, 15, 108, '2024-10-30 18:52:53'),
(288, 5, 105, '2024-10-30 19:10:09'),
(289, 5, 108, '2024-10-30 19:10:30'),
(290, 15, 107, '2024-10-31 03:14:52'),
(291, 15, 104, '2024-10-31 03:14:56'),
(292, 15, 101, '2024-10-31 03:26:25'),
(293, 15, 100, '2024-10-31 03:46:23'),
(294, 77, 108, '2024-10-31 07:21:28'),
(295, 77, 106, '2024-10-31 09:12:08'),
(296, 77, 105, '2024-10-31 09:12:17'),
(297, 77, 104, '2024-10-31 09:12:19'),
(298, 77, 103, '2024-10-31 09:12:20'),
(299, 77, 102, '2024-10-31 09:12:21'),
(300, 77, 101, '2024-10-31 09:12:23'),
(301, 77, 100, '2024-10-31 09:12:24'),
(302, 77, 98, '2024-10-31 09:12:25'),
(303, 37, 108, '2024-10-31 11:08:30'),
(304, 77, 109, '2024-10-31 12:49:38'),
(305, 5, 109, '2024-10-31 12:52:36'),
(306, 5, 107, '2024-10-31 12:52:53'),
(307, 5, 110, '2024-10-31 15:10:39'),
(308, 77, 110, '2024-10-31 15:12:41'),
(309, 15, 110, '2024-10-31 15:43:29'),
(310, 15, 111, '2024-10-31 15:47:45'),
(311, 5, 111, '2024-10-31 17:43:49'),
(312, 77, 111, '2024-11-01 04:48:20'),
(313, 79, 111, '2024-11-01 06:40:22'),
(314, 79, 110, '2024-11-01 06:40:36'),
(315, 79, 109, '2024-11-01 06:40:41'),
(316, 79, 108, '2024-11-01 06:40:50'),
(317, 79, 107, '2024-11-01 06:40:52'),
(318, 15, 109, '2024-11-01 06:40:52'),
(319, 79, 106, '2024-11-01 06:40:54'),
(320, 79, 105, '2024-11-01 06:40:56'),
(321, 79, 104, '2024-11-01 06:40:58'),
(322, 79, 103, '2024-11-01 06:40:59'),
(323, 79, 102, '2024-11-01 06:41:01'),
(324, 14, 109, '2024-11-01 14:04:33'),
(325, 14, 110, '2024-11-01 14:04:36'),
(326, 14, 111, '2024-11-01 14:04:38'),
(327, 15, 112, '2024-11-01 17:10:51'),
(328, 14, 112, '2024-11-02 09:45:54'),
(329, 5, 112, '2024-11-02 15:13:01'),
(330, 79, 112, '2024-11-03 09:29:10'),
(331, 77, 112, '2024-11-03 17:02:07'),
(332, 77, 113, '2024-11-04 15:28:47'),
(333, 14, 113, '2024-11-04 17:38:09'),
(334, 79, 113, '2024-11-05 07:33:51'),
(335, 14, 114, '2024-11-05 11:22:41'),
(336, 77, 114, '2024-11-05 11:34:59'),
(337, 5, 114, '2024-11-05 12:40:32'),
(338, 79, 114, '2024-11-05 14:00:26'),
(339, 37, 114, '2024-11-05 16:07:51'),
(340, 37, 115, '2024-11-06 03:46:21'),
(341, 14, 115, '2024-11-06 05:28:30'),
(342, 79, 115, '2024-11-06 06:49:13'),
(343, 5, 115, '2024-11-06 12:46:58'),
(344, 15, 115, '2024-11-08 09:14:16');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `title`, `description`, `image`, `pdf_file`, `price`, `created_at`) VALUES
(16, 'Ethics of Islam', 'books', 'uploads/images/1729251663_ethics of islam (1).jpg', 'uploads/pdfs/1729251663_Ethics_of_Islam.pdf', 4.00, '2024-10-18 11:41:03'),
(18, 'Atomic Habit', 'Audio Book', 'uploads/images/1729266844_atomic habit.svg', 'uploads/pdfs/1729266844_Atomic Habits by James Clear Audiobook  Book Summary in Hindi.mp3', 5.00, '2024-10-18 15:54:05'),
(19, 'Power of subconscious mind', 'Audio Book', 'uploads/images/1729267167_Phonesium.svg', 'uploads/pdfs/1729267167_The Power of Your Subconscious Mind by Dr. Joseph Murphy Audiobook  Books Summary in Hindi.mp3', 10.00, '2024-10-18 15:59:28'),
(20, 'Steve Jobs Stanford Speech', 'Visual Book', 'uploads/images/1729268019_Phonesium (1).svg', 'uploads/pdfs/1729268019_STEVE JOBS_ Stanford Speech In Hindi  By Deepak Daiya.mp4', 20.00, '2024-10-18 16:13:40'),
(22, 'Rich Dad Poor Dad', 'Audio Book', 'uploads/images/1729268750_Phonesium (2).svg', 'uploads/pdfs/1729268750_Rich Dad Poor Dad Book Summary in Hindi By Robert Kiyosaki  6 Rules of Money.mp3', 20.00, '2024-10-18 16:25:51'),
(24, 'Python | Numpy', 'Books', 'uploads/images/1730027749_Numpy.png', 'uploads/pdfs/1730027749_Coffee Break NumPy (Christian Mayer  (Author).pdf', 20.00, '2024-10-27 11:15:49'),
(25, 'Fullstack Reactjs', 'A complete guide for react JS.', 'uploads/images/1730129595_HMST (3).svg', 'uploads/pdfs/1730129595_Fullstack React The Complete Guide to ReactJS and Friends.pdf', 5.00, '2024-10-28 15:33:15'),
(26, 'Black Hat Python Guide', 'A complete Black Hat guide in python.', 'uploads/images/1730130065_HMST (4).svg', 'uploads/pdfs/Phonesium.pdf', 10.00, '2024-10-28 15:41:05'),
(27, 'Data Analytics Practical Guide to Leveraging the Power of Algorithms', 'A practical Guide to Leveraging the Power of Algorithms, Data Science', 'uploads/images/1730130391_HMST (5).svg', 'uploads/pdfs/Phonesium.pdf', 5.00, '2024-10-28 15:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `quick_login_tokens`
--

CREATE TABLE `quick_login_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quick_login_tokens`
--

INSERT INTO `quick_login_tokens` (`id`, `user_id`, `token`, `expiry`, `created_at`) VALUES
(2, 14, 'e0e13db41ad615fee70a843cc1f57854f899b3bdee5d6ceeafe9994563c7178e', '2024-10-22 19:14:52', '2024-10-22 18:14:52');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `feedback` enum('good','bad') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `username`, `product_id`, `feedback`, `created_at`) VALUES
(7, 5, 'prasanga', 16, 'good', '2024-11-02 19:42:58');

-- --------------------------------------------------------

--
-- Table structure for table `supply`
--

CREATE TABLE `supply` (
  `id` int(11) NOT NULL,
  `current_supply` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_supply` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_supply` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply`
--

INSERT INTO `supply` (`id`, `current_supply`, `total_supply`, `remaining_supply`) VALUES
(1, 100243140.92, 1000000000.00, 899756859.08);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'withdrawal_fee', '1.5', 'Fixed withdrawal fee in TRX', '2025-06-06 17:52:14'),
(2, 'min_withdrawal', '5.0', 'Minimum withdrawal amount in TRX', '2025-06-06 17:52:14'),
(3, 'max_withdrawal', '10000.0', 'Maximum withdrawal amount in TRX', '2025-06-06 17:52:14'),
(4, 'company_wallet', 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'Company wallet address for fees', '2025-06-06 17:52:14'),
(5, 'maintenance_mode', 'false', 'Enable/disable maintenance mode', '2025-06-06 17:52:14'),
(6, 'otp_expiry_minutes', '5', 'OTP expiry time in minutes', '2025-06-06 17:52:14');

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `id` bigint(20) NOT NULL,
  `contract_address` varchar(42) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` text DEFAULT NULL,
  `website_url` text DEFAULT NULL,
  `twitter_url` text DEFAULT NULL,
  `telegram_url` text DEFAULT NULL,
  `total_supply` decimal(20,8) NOT NULL DEFAULT 1000000000.00000000,
  `initial_liquidity` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `current_price` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `market_cap` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `liquidity_pool_address` varchar(42) DEFAULT NULL,
  `bonding_curve_progress` decimal(5,2) DEFAULT 0.00,
  `graduation_threshold` decimal(20,8) DEFAULT 69000.00000000,
  `is_graduated` tinyint(1) DEFAULT 0,
  `graduated_at` timestamp NULL DEFAULT NULL,
  `launch_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'active',
  `total_transactions` int(11) DEFAULT 0,
  `unique_holders` int(11) DEFAULT 0,
  `volume_24h` decimal(20,8) DEFAULT 0.00000000,
  `volume_total` decimal(20,8) DEFAULT 0.00000000,
  `price_change_24h` decimal(10,4) DEFAULT 0.0000,
  `ath_price` decimal(20,8) DEFAULT 0.00000000,
  `ath_timestamp` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `initial_buy_amount` decimal(20,8) DEFAULT 0.00000000,
  `creator_initial_tokens` decimal(20,8) DEFAULT 0.00000000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tokens`
--

INSERT INTO `tokens` (`id`, `contract_address`, `creator_id`, `name`, `symbol`, `description`, `image_url`, `website_url`, `twitter_url`, `telegram_url`, `total_supply`, `initial_liquidity`, `current_price`, `market_cap`, `liquidity_pool_address`, `bonding_curve_progress`, `graduation_threshold`, `is_graduated`, `graduated_at`, `launch_time`, `status`, `total_transactions`, `unique_holders`, `volume_24h`, `volume_total`, `price_change_24h`, `ath_price`, `ath_timestamp`, `created_at`, `updated_at`, `initial_buy_amount`, `creator_initial_tokens`) VALUES
(2, 'T31707bac788ebef925640120eda96039d36cfc9c', 86, 'ESEWA', 'ESEWA', 'A official esewa coin', 'uploads/tokens/6844293714bc5.png', '', '', '', 1000000000.00000000, 0.00000000, 0.00010000, 100000.00000000, NULL, 0.00, 69000.00000000, 0, NULL, '2025-06-07 11:57:43', 'active', 7, 0, 42712.33500000, 69424.67000000, 0.0000, 0.00000000, NULL, '2025-06-07 11:57:43', '2025-06-07 14:10:58', 0.00000000, 0.00000000),
(4, 'Tcbbb03c69d49a210eab971ea8d4a55ad4b878c6f', 86, 'Khalti', 'KHT', 'Akhgt', 'uploads/tokens/68443055991fe.png', '', '', '', 1000000000.00000000, 1000.00000000, 0.00010000, 100000.00000000, NULL, 0.00, 69000.00000000, 0, NULL, '2025-06-07 12:28:05', 'active', 0, 0, 0.00000000, 0.00000000, 0.0000, 0.00000000, NULL, '2025-06-07 12:28:05', '2025-06-07 12:28:05', 1000.00000000, 200000000.00000000),
(5, 'Tf2f735b12643f6fb7c857023b066baadb312d714', 86, 'USDR', 'USDR', 'Test', 'uploads/tokens/6844323e7bb4e.png', '', '', '', 1000000000.00000000, 1200.00000000, 0.00002908, 29077.35321528, NULL, 0.00, 69000.00000000, 0, NULL, '2025-06-07 12:36:14', 'active', 0, 0, 0.00000000, 0.00000000, 0.0000, 0.00000000, NULL, '2025-06-07 12:36:14', '2025-06-07 12:36:14', 1200.00000000, 200000000.00000000),
(6, 'T361697295af86ad552ce38dec68baff4a909df6b', 86, 'NEONE', 'NEON', '', 'uploads/tokens/68443c61d7aed.jpg', '', '', '', 1000000000.00000000, 10000.00000000, 0.00003728, 37278.65796831, NULL, 0.00, 69000.00000000, 0, NULL, '2025-06-07 13:19:29', 'active', 0, 0, 0.00000000, 0.00000000, 0.0000, 0.00000000, NULL, '2025-06-07 13:19:29', '2025-06-07 13:19:29', 10000.00000000, 200000000.00000000),
(7, 'T3799d585bc93c38a75f7441be944087230795b2f', 86, 'TITTOK', 'TIKTOK', 'A official tiktok', 'uploads/tokens/68469ce24a498.svg', '', '', '', 1000000000.00000000, 10000.00000000, 0.00003728, 37280.00000000, NULL, 0.00, 69000.00000000, 0, NULL, '2025-06-09 08:35:46', 'active', 0, 0, 0.00000000, 0.00000000, 0.0000, 0.00000000, NULL, '2025-06-09 08:35:46', '2025-06-09 08:48:04', 10000.00000000, 200000000.00000000);

-- --------------------------------------------------------

--
-- Table structure for table `token_balances`
--

CREATE TABLE `token_balances` (
  `id` bigint(20) NOT NULL,
  `token_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `percentage` decimal(8,4) DEFAULT 0.0000,
  `is_creator` tinyint(1) DEFAULT 0,
  `first_purchase_at` timestamp NULL DEFAULT NULL,
  `last_transaction_at` timestamp NULL DEFAULT NULL,
  `total_bought` decimal(20,8) DEFAULT 0.00000000,
  `total_sold` decimal(20,8) DEFAULT 0.00000000,
  `avg_buy_price` decimal(20,8) DEFAULT 0.00000000,
  `unrealized_pnl` decimal(20,8) DEFAULT 0.00000000,
  `realized_pnl` decimal(20,8) DEFAULT 0.00000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token_balances`
--

INSERT INTO `token_balances` (`id`, `token_id`, `user_id`, `balance`, `percentage`, `is_creator`, `first_purchase_at`, `last_transaction_at`, `total_bought`, `total_sold`, `avg_buy_price`, `unrealized_pnl`, `realized_pnl`, `created_at`, `updated_at`) VALUES
(1, 2, 86, 0.00000000, 0.0000, 1, '2025-06-07 11:57:43', '2025-06-07 14:10:58', 267123350.00000000, 467123350.00000000, 0.00000000, 0.00000000, 0.00000000, '2025-06-07 11:57:43', '2025-06-07 14:10:58'),
(3, 4, 86, 0.00000000, 0.0000, 1, '2025-06-07 12:28:05', '2025-06-07 12:37:17', 0.00000000, 210000000.00000000, 0.00000000, 0.00000000, 0.00000000, '2025-06-07 12:28:05', '2025-06-07 12:37:17'),
(4, 5, 86, 0.76923001, 0.0000, 1, '2025-06-07 12:36:14', '2025-06-07 13:18:53', 0.00000000, 241269230.00000000, 0.00000000, 0.00000000, 0.00000000, '2025-06-07 12:36:14', '2025-06-07 13:18:53'),
(5, 6, 86, 0.00000000, 0.0000, 1, '2025-06-07 13:19:29', '2025-06-07 13:31:26', 0.00000000, 468250000.00000000, 0.00000000, 0.00000000, 0.00000000, '2025-06-07 13:19:29', '2025-06-07 13:31:26'),
(9, 7, 86, 1489890960.30000000, 0.0000, 1, '2025-06-09 08:35:46', '2025-06-09 09:17:50', 4985858503.22000000, 3964217542.91999960, 0.00000000, 0.00000000, 0.00000000, '2025-06-09 08:35:46', '2025-06-09 09:17:50');

-- --------------------------------------------------------

--
-- Table structure for table `token_transactions`
--

CREATE TABLE `token_transactions` (
  `id` bigint(20) NOT NULL,
  `token_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_hash` varchar(66) NOT NULL,
  `transaction_type` varchar(10) NOT NULL,
  `trx_amount` decimal(20,8) NOT NULL,
  `token_amount` decimal(20,8) NOT NULL,
  `price_per_token` decimal(20,8) NOT NULL,
  `fee_amount` decimal(20,8) DEFAULT 0.00000000,
  `slippage` decimal(8,4) DEFAULT 0.0000,
  `market_cap_before` decimal(20,8) DEFAULT NULL,
  `market_cap_after` decimal(20,8) DEFAULT NULL,
  `bonding_curve_progress_before` decimal(5,2) DEFAULT NULL,
  `bonding_curve_progress_after` decimal(5,2) DEFAULT NULL,
  `block_number` bigint(20) DEFAULT NULL,
  `gas_used` int(11) DEFAULT NULL,
  `gas_price` decimal(20,8) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token_transactions`
--

INSERT INTO `token_transactions` (`id`, `token_id`, `user_id`, `transaction_hash`, `transaction_type`, `trx_amount`, `token_amount`, `price_per_token`, `fee_amount`, `slippage`, `market_cap_before`, `market_cap_after`, `bonding_curve_progress_before`, `bonding_curve_progress_after`, `block_number`, `gas_used`, `gas_price`, `status`, `created_at`, `confirmed_at`) VALUES
(3, 2, 86, 'tx_6844301811066', 'sell', 2280.00000000, 20000000.00000000, 0.00011400, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 12:27:04', NULL),
(4, 2, 86, 'tx_6844302df350d', 'sell', 2242.00000000, 20000000.00000000, 0.00011210, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 12:27:25', NULL),
(5, 4, 86, 'launch_684430559c86b', 'initial_bu', 1000.00000000, 10000000.00000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 12:28:05', NULL),
(6, 5, 86, 'launch_6844323e7f789', 'initial_bu', 1200.00000000, 41269230.76923100, 0.00002908, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 12:36:14', NULL),
(7, 4, 86, 'tx_6844327d7ce88', 'sell', 24139.50000000, 210000000.00000000, 0.00011495, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 12:37:17', NULL),
(8, 5, 86, 'tx_68443c3d51a05', 'sell', 5913.27571135, 241269230.00000000, 0.00002451, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 13:18:53', NULL),
(9, 6, 86, 'launch_68443c61d98e5', 'initial_bu', 10000.00000000, 268250000.00000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 13:19:29', NULL),
(10, 6, 86, 'tx_68443f2e0d98d', 'sell', 10000.00000000, 468250000.00000000, 0.00002136, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 13:31:26', NULL),
(11, 2, 86, 'tx_68444126e1314', 'buy', 10000.00000000, 100000000.00000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 13:39:50', NULL),
(12, 2, 86, 'tx_6844417934de3', 'buy', 11144.89000000, 111448900.00000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 13:41:13', NULL),
(13, 2, 86, 'tx_684441f6c7b66', 'buy', 5567.44500000, 55674450.00000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 13:43:18', NULL),
(14, 2, 86, 'tx_68444847ab151', 'sell', 21356.16750000, 213561675.00000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 14:10:15', NULL),
(15, 2, 86, 'tx_6844485b4c560', 'sell', 10678.08375000, 106780837.50000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 14:10:35', NULL),
(16, 2, 86, 'tx_6844486a59945', 'sell', 5339.04187500, 53390418.75000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 14:10:50', NULL),
(17, 2, 86, 'tx_6844487208a68', 'sell', 5339.04187500, 53390418.75000000, 0.00010000, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-07 14:10:58', NULL),
(18, 7, 86, 'launch_68469ce250297', 'initial_bu', 10000.00000000, 268250000.00000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:35:46', NULL),
(19, 7, 86, 'tx_68469e4f25e97', 'buy', 19103.38999999, 512429989.27000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:41:51', NULL),
(20, 7, 86, 'tx_68469e6eeca90', 'sell', 36559.74999999, 980679989.27000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:42:22', NULL),
(21, 7, 86, 'tx_68469fc441b1f', 'buy', 27821.57000011, 746286748.93000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:48:04', NULL),
(22, 7, 86, 'tx_68469fd0e45f0', 'sell', 27821.57000011, 746286748.93000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:48:16', NULL),
(23, 7, 86, 'tx_6846a21b98058', 'buy', 27811.56999986, 746018508.58000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:58:03', NULL),
(24, 7, 86, 'tx_6846a2264e2ce', 'sell', 27811.56999986, 746018508.58000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:58:14', NULL),
(25, 7, 86, 'tx_6846a22cd77ea', 'buy', 27801.56999999, 745750268.24000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:58:20', NULL),
(26, 7, 86, 'tx_6846a2339071f', 'sell', 27801.56999999, 745750268.24000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 08:58:27', NULL),
(27, 7, 86, 'tx_6846a2bf02aa4', 'buy', 27791.57000011, 745482027.90000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 09:00:47', NULL),
(28, 7, 86, 'tx_6846a2c418ff9', 'sell', 27791.57000011, 745482027.90000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 09:00:52', NULL),
(29, 7, 86, 'tx_6846a2d043a45', 'buy', 27781.56999986, 745213787.55000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 09:01:04', NULL),
(30, 7, 86, 'tx_6846a6b83ac12', 'buy', 13885.78500018, 372472773.61000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 09:17:44', NULL),
(31, 7, 86, 'tx_6846a6be6ffd5', 'buy', 13875.77999994, 372204399.14000000, 0.00003728, 10.00000000, 0.0000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'confirmed', '2025-06-09 09:17:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trxbalance`
--

CREATE TABLE `trxbalance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `private_key` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mnemonic` text DEFAULT NULL,
  `public_key` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trxbalance`
--

INSERT INTO `trxbalance` (`id`, `user_id`, `private_key`, `address`, `username`, `balance`, `status`, `created_at`, `updated_at`, `mnemonic`, `public_key`) VALUES
(1, 14, 'd705a8c442c037475673596909a646de12a50b05f2e0cbb64fc5659514d401fa', 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 'park', 0.00, 'Unpaid', '2024-11-07 16:07:54', '2024-11-07 16:07:54', NULL, NULL),
(2, 37, '03e4a18af64f4c1c3553e85fd9c3213d406e0ca69415b826d08983a1ff2c8ab0', 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'phonesium', 0.00, 'Unpaid', '2024-11-12 14:18:01', '2025-06-08 17:00:10', NULL, NULL),
(6, 77, '08a3cc7d29a0155413c8aa7d65d1338e7d1ed3dea6146d6db6cad5f67e1a98a4', 'TCXQAMnoNHcrDkzYWUpCyEEpMbCcSrb5tL', 'jaya', 0.00, 'Unpaid', '2024-11-12 15:28:35', '2024-11-12 15:28:35', NULL, NULL),
(7, 5, '7da24ccd99b45e2bb79929932f982ca35fc629787986ed5489f7fed43faa12dd', 'TQw5i9cZtncVo14q3s2z6PNnHVqB8xUR6T', 'prasanga', 0.00, 'Unpaid', '2024-11-13 12:57:12', '2024-11-13 12:57:12', NULL, NULL),
(8, 75, '3a92f3ecf34bc77eeaa22a20122d052b9750bc345060a59abf9e8f7b8f82d363', 'TVgD2mv1N6yCnJ5MKcTdxZ6pD4Fvj1ZmEh', 'khadka', 0.00, 'Unpaid', '2024-11-14 15:23:57', '2024-11-14 15:23:57', NULL, NULL),
(9, 68, '265f559b3805661d2adb465e846c2bf63159c9e3837addbac1e4db04a9338892', 'TF8oTCvtmU4ysXSpB5jyFDfgw3rxv3RCrg', 'umesh', 0.00, 'Unpaid', '2024-11-14 18:12:05', '2024-11-14 18:12:05', NULL, NULL),
(10, 39, '9154b3dd9a2b4fb65ebaac830750eab35918c9f77a2aa708f95f9efb70681cc4', 'TWQHdmbq4XvU3y1DspNNzqmyL12emFo5mp', 'Bhattarai', 0.00, 'Unpaid', '2024-11-15 04:33:43', '2024-11-15 04:33:43', NULL, NULL),
(11, 15, '2bd61c35dba8f0d062a3faf68e99d33bc7c6d240ae42d6dae18fb6ce33046945', 'TBprgZydQYwSBEoSjjYd5rGTFfqKmr9vKJ', 'prasanna', 0.00, 'Unpaid', '2024-11-18 17:31:55', '2024-11-18 17:31:55', NULL, NULL),
(12, 55, '3cbd733af649959a5a6aac81b6b92f99001b05c14f9217c1e94455597e5c48a1', 'THids2xyykiRuMYKtmrW2wz4KhRA7kQ1rr', 'rakesh', 0.00, 'Unpaid', '2024-11-19 01:43:53', '2024-11-19 01:43:53', NULL, NULL),
(13, 85, '22f71fb9bc7b47f6a84907b9a1ca89be1fb9ec7a30985afbf6982b5811054917', 'TBxB52A1UJ2BrUWoRjPokbssxMGaN7i6wB', 'manan', 0.00, 'Unpaid', '2024-11-21 16:47:06', '2024-11-21 16:47:06', NULL, NULL),
(14, 58, '2e8672543b58981225bdc4ba94907252903f34421d620992f731a91fdf28666d', 'TLE56MeTzqZuL8V37euQuUwFNfEzdLerT9', 'Rhythm', 0.00, 'Unpaid', '2024-11-21 17:57:36', '2024-11-21 17:57:36', NULL, NULL),
(15, 86, '0eabbf3c81152bd1eaf1fea8b5a3a60bcd43396e84ac789ad0e23e7294e0b9b6', 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'jayas', 0.00, '', '2025-06-06 18:37:17', '2025-06-09 09:17:50', NULL, NULL);

--
-- Triggers `trxbalance`
--
DELIMITER $$
CREATE TRIGGER `audit_balance_change` AFTER UPDATE ON `trxbalance` FOR EACH ROW BEGIN
    IF NEW.balance != OLD.balance THEN
        INSERT INTO audit_logs (user_id, action, details) 
        VALUES (NEW.user_id, 'BALANCE_CHANGE', 
                CONCAT('Balance changed from ', OLD.balance, ' to ', NEW.balance));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `trxhistory`
--

CREATE TABLE `trxhistory` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_address` varchar(50) NOT NULL,
  `to_address` varchar(50) NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `tx_hash` varchar(100) DEFAULT NULL,
  `status` enum('send','receive','failed') NOT NULL,
  `transaction_type` varchar(50) DEFAULT 'transfer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trxhistory`
--

INSERT INTO `trxhistory` (`id`, `user_id`, `from_address`, `to_address`, `amount`, `timestamp`, `tx_hash`, `status`, `transaction_type`) VALUES
(1, 14, 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 0.100000, '2024-11-16 16:01:07', NULL, 'failed', 'transfer'),
(2, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 2.000000, '2024-11-16 16:01:34', 'f7f2d7af2526ebbf59406659db5a5cf9a35fa48a1f41c44cd7799ce72b07ac50', 'send', 'transfer'),
(3, 14, '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 0.800000, '2024-11-17 03:18:16', 'dc4e1dfcbfc22b25263c299140910826efe22ff11ed2ee1ea6aad9bec5aa9f2f', 'receive', 'transfer'),
(4, 14, '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 0.800000, '2024-11-17 03:18:16', '63b9b873e7722b15386ef8dcf0777c027b70d3b6bbcde774c16e5dc21884b57d', 'receive', 'transfer'),
(5, 14, '4188d7402633eb676e9b4f844533ac7c219a24e774', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 1.599999, '2024-11-17 03:18:16', 'b084373522081b46ea195d6b8502afbc45f0d4fbc34f1271d220b98618612f21', 'receive', 'transfer'),
(6, 14, '41f9f75b956fb40dbc2220b2531c5d1ad0cd4a0319', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 0.000001, '2024-11-17 03:18:16', 'e7f2cff86086b785e578ab423e38ecef49f8aad44d5b1e79e52dd410cb3771f3', 'receive', 'transfer'),
(7, 14, '411d995c08a6d5292a3d71e821aa82efd930d95415', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 3.000000, '2024-11-17 03:18:16', 'd654acdd2aea0772eba962a8f3011909f943432d62be7695281280acec0f70bb', 'receive', 'transfer'),
(8, 14, '41e4d6e099b9e4ba1d7a6c30f4b1a58afca1951810', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 1.400002, '2024-11-17 03:18:16', '38749b5547cb61ae0198cf86fbbffcaf78df2cfd3d97a673cdca8757741791cb', 'receive', 'transfer'),
(9, 14, '41e4d6e099b9e4ba1d7a6c30f4b1a58afca1951810', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 2.140001, '2024-11-17 03:18:16', 'f872eb3e855a1afa5422e2de52331ece96348703319b2c7c081b5fc7c46f6e85', 'receive', 'transfer'),
(10, 14, '41e4d6e099b9e4ba1d7a6c30f4b1a58afca1951810', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 5.000000, '2024-11-17 03:18:16', 'e13f871cb23acbd00b36f7695ee64ae410a63ab9ed1f4b61ea2f94489f7c139d', 'receive', 'transfer'),
(11, 14, '4137ea60504457f3f5a7cc6bc8e2c7fd7fbe6e9811', '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', 4.000000, '2024-11-17 03:18:16', 'beee438a1350748d889947bbabc476b83906a9cf7b305284a44d6a5f1671299b', 'receive', 'transfer'),
(12, 37, '4192b7a368c71400a7335976b33b45b9d428aefc48', '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', 0.800000, '2024-11-17 03:18:17', '58bf89f094e96866644f6a0ebc03f39a77a41e103466d1246569e7a0cc8cce9a', 'receive', 'transfer'),
(13, 37, '4123b77f181841ac8065844a4feddc869d657e27a1', '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', 0.000001, '2024-11-17 03:18:17', 'c035ad22c4a65aeb64e9e458211d7c940107a25e231197c93d340260ec968db2', 'receive', 'transfer'),
(14, 37, '41b100bf58541c0b01444d2cb81d595cd419dfbd2d', '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', 1.400000, '2024-11-17 03:18:17', '52e7fb1bbb98f37dc82c78b962bb6f1cdd145dcf443714a507f867b578b3f01e', 'receive', 'transfer'),
(15, 37, '41788146599b53599f52a976819b2c8bb49f17637d', '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', 0.000001, '2024-11-17 03:18:17', '58bee7c3e9d98ed1f92a2dbd760ca7abcf07d60e7113129bf87fd51f124207fe', 'receive', 'transfer'),
(16, 37, '415a67fa7cc56bd6d043a98e17d329c1dc9e14753f', '417b1aaa1c72ca6d410d1d0c9b15fd7b4b6753a126', 175.000000, '2024-11-17 03:18:17', 'a179a70aaefb108850776630459e0c039aaf9ba0d7a3f358cf87d8c92f4c98f8', 'receive', 'transfer'),
(17, 77, '4192b7a368c71400a7335976b33b45b9d428aefc48', '411c06e0e7af9d8c85a038a00016090d3b8f695a15', 2.000000, '2024-11-17 03:18:17', '3bd58511cb74e64e090971a6ed024df1113ea5a44213c4479680a9b250f00105', 'receive', 'transfer'),
(18, 77, '4192b7a368c71400a7335976b33b45b9d428aefc48', '411c06e0e7af9d8c85a038a00016090d3b8f695a15', 3.500001, '2024-11-17 03:18:17', '24215ba745e7eb54c9756d4878be3a8c6ab8c2fe6d7a673f2553ca2f84d166a0', 'receive', 'transfer'),
(19, 77, '412acc5010e90a88bbd6ab2a7274bfaaefca50025c', '411c06e0e7af9d8c85a038a00016090d3b8f695a15', 0.000001, '2024-11-17 03:18:17', 'aa3ef05f58ab09da28a69ce626dd8077ff0ade0fa14220b8d20bf4dc93757f41', 'receive', 'transfer'),
(20, 77, '4192b7a368c71400a7335976b33b45b9d428aefc48', '411c06e0e7af9d8c85a038a00016090d3b8f695a15', 4.697782, '2024-11-17 03:18:17', '2f27785bbe967bdbd4e742e69e297f861afed18121e329401b2ff18ac84fc217', 'receive', 'transfer'),
(21, 14, 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 2.000000, '2024-11-17 13:06:12', NULL, 'failed', 'transfer'),
(22, 14, 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 2.000000, '2024-11-17 13:07:56', '1df18947c22f24bfdb18d0076484ed92e28d6ee907664cd8bca3120fbde5fe8f', 'send', 'transfer'),
(23, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TS77Vwq3PMzMyfq1CrKfp6HLstPfCjRMfW', 2.000000, '2024-11-17 13:13:56', '86b98852cc6ccbcbdc262dbbed595c0cea6e94eafa183719bfedad819080ce6a', 'send', 'transfer'),
(24, 55, 'THids2xyykiRuMYKtmrW2wz4KhRA7kQ1rr', 'TQw5i9cZtncVo14q3s2z6PNnHVqB8xUR6T', 2.700000, '2024-11-19 01:51:57', NULL, 'failed', 'transfer'),
(25, 55, 'THids2xyykiRuMYKtmrW2wz4KhRA7kQ1rr', 'TQw5i9cZtncVo14q3s2z6PNnHVqB8xUR6T', 2.700000, '2024-11-19 01:52:04', NULL, 'failed', 'transfer'),
(26, 55, 'THids2xyykiRuMYKtmrW2wz4KhRA7kQ1rr', 'TQw5i9cZtncVo14q3s2z6PNnHVqB8xUR6T', 2.000000, '2024-11-19 01:52:12', NULL, 'failed', 'transfer'),
(27, 55, 'THids2xyykiRuMYKtmrW2wz4KhRA7kQ1rr', 'TQw5i9cZtncVo14q3s2z6PNnHVqB8xUR6T', 2.000000, '2024-11-19 01:52:18', NULL, 'failed', 'transfer'),
(28, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TF4rwMZMHvqXSXVGFb7jLqGU9uUwf7q4XU', 2.000000, '2024-12-31 13:50:01', '3dd64b56cd2844ca24c9dbb27ef85391de80c9eb43f1cdd26ccd7bb9c185bbe0', 'send', 'transfer'),
(29, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 2.000000, '2024-12-31 13:54:15', NULL, 'failed', 'transfer'),
(30, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TBprgZydQYwSBEoSjjYd5rGTFfqKmr9vKJ', 2.000001, '2025-01-10 16:01:00', NULL, 'failed', 'transfer'),
(31, 15, 'TBprgZydQYwSBEoSjjYd5rGTFfqKmr9vKJ', 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 3.000000, '2025-01-10 16:08:17', 'bd4de6952cb0bb2d6111c9599241b0323d1c655752e25308c6c462f8aef4a283', 'send', 'transfer'),
(32, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TF4rwMZMHvqXSXVGFb7jLqGU9uUwf7q4XU', 120.000000, '2025-01-24 14:28:17', '708451a4251ed07ce8f79edeb366db694b97dce01b87f1746f54c9a5aaa23d3e', 'send', 'transfer'),
(33, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TQw5i9cZtncVo14q3s2z6PNnHVqB8xUR6T', 122.000000, '2025-03-04 02:53:10', NULL, 'failed', 'transfer'),
(34, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 6.000000, '2025-06-07 11:30:03', 'withdraw_6843d1dfe71af', 'send', 'transfer'),
(35, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 1.500000, '2025-06-07 11:30:03', 'fee_6843d1dfe800a', 'send', 'transfer'),
(36, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 18:12:04', 'fee_68443018114ba', '', 'trading_fee'),
(37, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 18:12:25', 'fee_6844302df3720', '', 'trading_fee'),
(38, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 18:13:05', 'fee_684430559d887', '', 'launch_fee'),
(39, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 18:21:14', 'fee_6844323e7f8c9', '', 'launch_fee'),
(40, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 18:22:17', 'fee_6844327d7d0d1', '', 'trading_fee'),
(41, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:03:53', 'fee_68443c3d52f55', '', 'trading_fee'),
(42, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:04:29', 'fee_68443c61daa5b', '', 'launch_fee'),
(43, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:16:26', 'fee_68443f2e0e658', '', 'trading_fee'),
(44, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:55:15', 'fee_68444847ab726', '', 'trading_fee'),
(45, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:55:35', 'fee_6844485b4c772', '', 'trading_fee'),
(46, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:55:50', 'fee_6844486a59b9d', '', 'trading_fee'),
(47, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-07 19:55:58', 'fee_6844487208f64', '', 'trading_fee'),
(48, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, '2025-06-08 17:37:14', 'TRX17493835346959', '', 'withdrawal'),
(49, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, '2025-06-08 17:56:12', 'REFUND_2', 'failed', 'withdrawal_refund'),
(50, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, '2025-06-08 21:53:48', 'REFUND_3', 'failed', 'withdrawal_refund'),
(51, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, '2025-06-08 21:54:25', 'REFUND_4', 'failed', 'withdrawal_refund'),
(52, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, '2025-06-08 22:07:33', 'REFUND_5', 'failed', 'withdrawal_refund'),
(53, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, '2025-06-08 22:08:00', 'REFUND_6', 'failed', 'withdrawal_refund'),
(54, 37, 'TMC84LS2nbjLez1f4hHHxWPUjAmBXXGKxF', 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 9.259005, '2025-06-08 22:37:53', '700b18963b856c1dde1a85a276f2af76f137d32a0ce351ce9c8fa1187d8d5dbd', '', 'deposit'),
(55, 86, 'TFtK91iNFigJs8xZftwZuWri6BhUuyMRBH', 'TCompanyWalletAddress123456789', -10.000000, '2025-06-09 14:20:46', 'fee_68469ce250a74', '', 'launch_fee');

-- --------------------------------------------------------

--
-- Table structure for table `trxtransactions`
--

CREATE TABLE `trxtransactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `txid` varchar(255) NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `status` enum('Pending','Confirmed','Failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trxtransactions`
--

INSERT INTO `trxtransactions` (`id`, `user_id`, `txid`, `amount`, `status`, `created_at`) VALUES
(1, 14, 'Failed', 22.000000, 'Failed', '2024-11-12 09:18:56'),
(2, 77, 'Failed', 2.000000, 'Failed', '2024-11-12 16:36:53'),
(3, 86, 'withdraw_6843d1dfe71af', 6.000000, 'Pending', '2025-06-07 05:45:03');

-- --------------------------------------------------------

--
-- Table structure for table `users2`
--

CREATE TABLE `users2` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `youtube_link` varchar(255) DEFAULT NULL,
  `website_link` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default.jpg',
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `invited_by` varchar(255) DEFAULT NULL,
  `refer_count` int(11) DEFAULT 0,
  `mining_start_time` datetime DEFAULT NULL,
  `mining_status` varchar(20) DEFAULT 'Not Running',
  `last_update_time` datetime DEFAULT NULL,
  `current_level` varchar(50) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `total_donated_amount` decimal(10,2) DEFAULT 0.00,
  `last_login_date` date DEFAULT NULL,
  `PH_id` varchar(10) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users2`
--

INSERT INTO `users2` (`id`, `full_name`, `username`, `email`, `password`, `address`, `github_link`, `facebook_link`, `youtube_link`, `website_link`, `profile_image`, `balance`, `invited_by`, `refer_count`, `mining_start_time`, `mining_status`, `last_update_time`, `current_level`, `otp`, `total_donated_amount`, `last_login_date`, `PH_id`, `email_verified`, `created_at`) VALUES
(5, 'Prasanga Raman Pokharel', 'prasanga', 'prasangaramanpokharel@gmail.com', '$2y$10$2w59I3UDRUrd.W5Bp/hz.eORkDhAidnQrBl8LN8b05MyxqL7e71tO', 'Inaruwa-1, Sunsari', 'https://github.com/prasangapokharel', 'https://www.facebook.com/prasangapokharel?mibextid=LQQJ4d', 'https://youtube.com/@prasangaramanpokharel?si=oF7vhB-Io2bpX6S7', 'https://www.prasangapokharel.com.np/', 'solution/connect/uploads/1CF1B9CB-CD51-4974-A76D-9EBBF36907CA.jpeg', 15.42, NULL, 14, NULL, 'Not Running', NULL, NULL, '877410', 0.00, NULL, 'PhdJExHg', 0, '2025-06-06 17:52:13'),
(14, 'Jack Park', 'park', 'limpark@gmail.com', '$2y$10$CZ2uhRRazR8p5BFEBdU1Suacirhxr39frfxOIVC0tZniFpZGPM2NW', 'Korean, shgai', 'https://www.phonesium.space/park', 'https://www.phonesium.space/park', 'https://www.phonesium.space/park', 'https://www.phonesium.space/', 'solution/connect/uploads/DF158977-17B6-4DE5-8DFB-C19AD11503E6.jpeg', 3.40, 'jaya', 25, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhCLDLoE', 0, '2025-06-06 17:52:13'),
(15, 'Prasanna Pokharel', 'prasanna', 'prasanna.pokhrel@gmail.com', '$2y$10$4S1z4d2DcXwcoAWs5SkZ3eOw2gWoxtcJAuTl2agZIeWQXQQyZHdKW', 'Biratnagar, Janaki Chowk', 'https://www.facebook.com/PavanPokhrel?mibextid=LQQJ4d', 'https://www.facebook.com/PavanPokhrel?mibextid=LQQJ4d', 'https://www.facebook.com/PavanPokhrel?mibextid=LQQJ4d', 'https://www.facebook.com/PavanPokhrel?mibextid=LQQJ4d', 'solution/connect/uploads/74B37935-A80F-40E5-A342-40DCDED362D7.jpeg', 0.19, 'jaya', 3, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhfpzwVC', 0, '2025-06-06 17:52:13'),
(17, 'Dorian Lott', 'supak', 'pakerazak@gmail.com', '$2y$10$b4EazjlYPBdFed5xpsW3gOfDmIQF444Ktj3m4x3lXIVGa.PjxctxG', 'Aliqua Alias id id', '', '', '', '', 'default.jpg', 0.00, '', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhfN8NBi', 0, '2025-06-06 17:52:13'),
(19, 'Lysandra Holder', 'marazipon', 'tivej@gmail.com', '$2y$10$OMJ1YOaIIzFUWgBJGKWg7O2A7TKPuKBYdO8/CxHLrqWxgqWieBWni', 'Ipsam vel sequi inci', 'https://www.piv.cc', 'https://www.nicyfuwunu.tv', 'https://www.qugubup.mobi', 'https://www.famapikotip.cc', 'default.jpg', 0.00, 'jaya', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhZyvafN', 0, '2025-06-06 17:52:13'),
(21, 'Palmer Brooks', 'vacirume', 'pinecisav@gmail.com', '$2y$10$gQGFlYqA2O2BraXJU1RAnumx/7qHz6Q06wv7SPkZXU6TwJKZS405C', 'Officia aut exercita', 'https://www.vuc.org', 'https://www.xobetaco.org.au', 'https://www.hokaxecusu.com', 'https://www.gococy.ca', 'default.jpg', 0.00, 'jaya', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph4v3Fba', 0, '2025-06-06 17:52:13'),
(23, 'Brianna Hicks', 'nojucoreca', 'netoqosoro@gmail.com', '$2y$10$7Xnizl3CH/8D8OPixAq1qeZgo5L7DJUKoE9cF8RlM8tuAbPU1Ubni', 'Quibusdam voluptatem', 'https://www.bylocyde.me', 'https://www.bafazuwav.co', 'https://www.judowakyky.org.au', 'https://www.hobexajehumu.tv', 'default.jpg', 0.00, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhIF6NDj', 0, '2025-06-06 17:52:13'),
(26, 'Marny Beck', 'gevufof', 'rukyji@gmail.com', '$2y$10$vdFvAFwfAvU5Nr1l3cjyNO//PIDUMg2AxDxrIY37PGs9km0uScGva', 'Laborum In quis dol', 'https://www.juj.com', 'https://www.jodowaw.ca', 'https://www.mizyvexe.me.uk', 'https://www.rysuqipegy.tv', 'default.jpg', 0.00, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhrNS0b4', 0, '2025-06-06 17:52:13'),
(27, 'Ksksks', 'jkraut', 'kjajaj@gmail.com', '$2y$10$cMmPqV84y4R4/B7lYtqMtesGtCCJYq2QpGl3DyF7eLTupMZMINtji', 'Biratnagar', 'https://trustner.xpamster.com/dashboard.php', 'https://trustner.xpamster.com/dashboard.php', 'https://trustner.xpamster.com/dashboard.php', 'https://trustner.xpamster.com/dashboard.php', 'default.jpg', 0.00, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhHK1xl3', 0, '2025-06-06 17:52:13'),
(28, 'Henry Mayer', 'lutemiwunu', 'sobebucoqi@gmail.com', '$2y$10$zjCKCmhx2GEyxPnp6/a6Demls/ukljdHnbL.DzFBX2oG2ntTwKex.', 'Voluptate magni cons', 'https://www.mydegumorawe.org.uk', 'https://www.jusytyrasypi.info', 'https://www.suzumonumog.co', 'https://www.jecygaqevilu.org.uk', 'default.jpg', 0.00, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhRZxl63', 0, '2025-06-06 17:52:13'),
(29, 'Daphne Santiago', 'dasotezep', 'sidicahero@gmail.com', '$2y$10$ovX9bm82M1CfM7mp9hkG.e94CvWD7yl6gkUoQ3fLNgWPmCn4yqm4q', 'Et qui voluptatem ha', 'https://www.xeco.me', 'https://www.hacawysufuzip.com.au', 'https://www.woxevuv.cm', 'https://www.qab.net', 'default.jpg', 0.00, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhC6jnY8', 0, '2025-06-06 17:52:13'),
(30, 'Christopher Reid', 'sacetyd', 'hekylada@gmail.com', '$2y$10$DSzpb1cRskjpAvs7Wq/uG.oA4ullcQe2TJwp7gmhVJUjuRGgW.LAe', 'Dolore corporis labo', 'https://www.kilumazonogy.org.uk', 'https://www.velydysegypoh.me.uk', 'https://www.tuwosiquho.in', 'https://www.zoqon.cc', 'default.jpg', 0.00, 'jaya', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph056Pru', 0, '2025-06-06 17:52:13'),
(37, 'Phonesium', 'phonesium', 'phonesiumsupport@gmail.com', '$2y$10$vIpZfF1Y/n8ARpRIYrWFc.SyZKxEQaWxf2zMR3PwTMlLnWuzlHcLq', 'R@man741', 'https://phonesium.space', 'https://phonesium.space', 'https://phonesium.space', 'https://phonesium.space', 'solution/connect/uploads/6E51E1F4-E1DE-4A5D-AE04-80C4744AE481.jpeg', 100242765.06, 'park', 3, NULL, 'Not Running', NULL, NULL, NULL, 0.00, '2025-06-08', 'PhGCq5wO', 0, '2025-06-06 17:52:13'),
(38, 'Prasanna Pokhrel', 'Yespro', 'prasanna.pokhrel@gmail.com', '$2y$10$ZfDz1r.9fxPi52J9XXT5Q.z8BXr0JNLzf0TuWlOSivFZYkYime4WO', 'Nepal', NULL, 'https://m.facebook.com/PavanPokhrel/', NULL, NULL, 'default.jpg', 3.16, 'Prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Phadyj0u', 0, '2025-06-06 17:52:13'),
(39, 'Prarambha', 'Bhattarai', 'prarambhabhattarai1@gmail.com', '$2y$10$.gpFilZj1N/uu7gAb2NT1eQ0.B846bdhIAB2d8MgyJp.eoO3PLHnO', 'Nepal', '', '', '', '', 'solution/connect/uploads/IMG-20241014-WA0043.jpg', 34.19, 'Prasanna', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhMr5Rbk', 0, '2025-06-06 17:52:13'),
(40, 'Oshan Chaudhary', 'Oshan', 'oshanchaudhary31@gmail.com', '$2y$10$yOqUl/WkDhCftSJ9IZ7.6.jgu6ByuUHSG.wsB.i1ANvBnPZaZDnIq', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 5.52, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhT6tW5D', 0, '2025-06-06 17:52:13'),
(41, 'egfwg', 'wgwrgwg', 'gogosab737@gmail.com', '$2y$10$8O35KSaTYZsb5iFFa25XSuSeZi85926wQK/iSD1cE21g0Z3FtXtwm', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.34, 'test', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhJ7U9y2', 0, '2025-06-06 17:52:13'),
(42, 'FAITH OLAYO', 'faitholayo1', 'faitholayo1@gmail.com', '$2y$10$0XrWTIt4U4PwIcpuL/NXiOxDyxNGi4YUSn0VRDY9R2480XtmZwvwa', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 14.20, 'Park', 1, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph0s3YFW', 0, '2025-06-06 17:52:13'),
(43, 'Aashish pokhrel', 'aashish20256', 'dellizulter@gmail.com', '$2y$10$iyiftZGQD6bFY4Yjd98nOuNxwaHEBPQo0xKjeKuPpSSamihqlme3y', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 5.06, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhNz4xeM', 0, '2025-06-06 17:52:13'),
(44, 'A.GOLD', 'A.GOLD✌️', 'jajaabdulhakeem@gmail.com', '$2y$10$S1uoPVDu8QXKgWdCX78NU.uM5YKnJChlpKjIWVqHlVz8PRRzveOqS', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.02, 'Faitholayo1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhVQjfaM', 0, '2025-06-06 17:52:13'),
(45, 'Sameer Sinkemu', 'Lyam', 'blackevil391@gmail.com', '$2y$10$0CnCvLkMJBMms6H86Mu88O9c.5U0c3pv2cdaHm7IJvT9uOcyNrqRS', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 1.16, 'Park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhnAKrtD', 0, '2025-06-06 17:52:13'),
(46, 'Jonah Olsen', 'bimadonivi', 'xoboqyt@gmail.com', '$2y$10$PKMJLAI4fKU/z4IEkygqg.6bAev5F8.j.D6Q1pPOInztkSpuq0KGa', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.01, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhoNJu2Q', 0, '2025-06-06 17:52:13'),
(52, 'Sameer Sinkemuni', 'Baucha', 'shresthasameer426@gmail.com', '$2y$10$Hs1R7MczvtB8.rfogC35TukFIN01vfFTCr2ro0DhMBwoSc5S0/8jK', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 5.06, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhMgttkx', 0, '2025-06-06 17:52:13'),
(53, 'Abijeet Raut', 'Abijeet', 'abijeetraut2@gmail.com', '$2y$10$HPmp/pf7GF09Zo5s/T3b9uplca4PoVNHyAmntDIuZw4PJ7o562Joe', NULL, NULL, NULL, NULL, NULL, 'solution/connect/uploads/img_2_1717691986325.jpg', 1.70, 'phonesium', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhkaZUPd', 0, '2025-06-06 17:52:13'),
(54, 'Suleiman Ahmed Musa', 'Sakulani55', 'sakulani55@gmail.com', '$2y$10$erAefrS16BBIkiliqUkyjugKVCSNXmfG2AmPfFlYKHuPpbcupsz1y', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 1.73, 'phonesium', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph59lCba', 0, '2025-06-06 17:52:13'),
(55, 'Rakesh Niraula', 'rakesh', 'rakesh@gmail.com', '$2y$10$Wfgyx8plJoXBKBvVGEtpreRzkGtEdcir9y7TFjQ7jOl6mvI7phBPy', NULL, NULL, NULL, NULL, NULL, 'solution/connect/uploads/IMG_3290.jpeg', 5.09, 'park', 1, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhXsnkdW', 0, '2025-06-06 17:52:13'),
(56, 'Rupesh Niraula', 'rupesh', 'niraularupesh4@gmail.com', '$2y$10$9clNeXZLDkGt8dPe5nxWv.wT9NB.SYeD9DRxe8hGIGLqB8rnHh52S', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 12.27, 'rakesh', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhbVt2br', 0, '2025-06-06 17:52:13'),
(57, 'Kapil tamang', 'Mrcupss', 'kapiltamang123@gmail.com', '$2y$10$QeUTtRfqZ0b5x1ubqUMace7MP.6gEvM1lcAQs2wt.7GEZMufKySp2', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 11.65, 'phonesium', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph3xEWih', 0, '2025-06-06 17:52:13'),
(58, 'Rhythm Niraula', 'Rhythm', 'rhythmniraula4@gmail.com', '$2y$10$r1bPg.5WpwZNhZ8lEGee1.2JOGPoyxK1LdyMO2zqQn/jCz4e.TrYu', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 7.10, 'Prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhWSYQpp', 0, '2025-06-06 17:52:13'),
(59, 'Ouedraogo William', 'Willkiss1', 'wedwill71@gmail.com', '$2y$10$oURRD7fHxlWRi1Z43.xB3udmIjA4xUxIXz0OieZ3giI1WM3G7WYyi', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 80.38, 'park', 8, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph9ZGO3I', 0, '2025-06-06 17:52:13'),
(60, 'KOTCHIAN PACOME NIGIER KOUBADJE', 'Nigier', 'nigier.koubadje@gmail.com', '$2y$10$Yp1oFvzU2iL18CdSPRThVOxlGxPDomZbK/GNJZ4lHlyr8J9ACN6x2', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 5.08, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhUdZb6u', 0, '2025-06-06 17:52:13'),
(61, 'Kouassi kouassi Antoine', 'Tonyo2020', 'kouf4016@gmail.com', '$2y$10$zB17LzWX4m/Dq8AnwyyUU.3LJbwxRiHrc0uCxXqErOryyDGlpcN2W', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 10.01, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhL4qodX', 0, '2025-06-06 17:52:13'),
(62, 'Sawadogo Souleymane', 'Kayabf1', 'solo1984sawadogo@gmail.com', '$2y$10$W0ekKADe8YnC3vaBRRn1Z..KjrHkVmBHh46Wu1RghgMIFNcjV2JBm', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 19.15, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhngM7Ki', 0, '2025-06-06 17:52:13'),
(63, 'Dembele Hamidou', 'Hamilton225', 'dembelehamidou225@gmail.com', '$2y$10$KktPPSaAz/gf1Dlx/.Y79O1Rxliqo3xvYzPYQH61mUIum5sYemcd.', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 1.21, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhNQHmE0', 0, '2025-06-06 17:52:13'),
(64, 'Yeboue konan claude', 'yeboue69', 'yebouekonan44@gmail.com', '$2y$10$f7qL2vmjB4KACuyHPxOaE.FrhloyViqQn4TyxFE0QT9WOcVdTFfJ2', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 3.16, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhPc0m8N', 0, '2025-06-06 17:52:13'),
(65, 'santoutou', 'Diakiss2001', 'santoutouseydou83@gmail.com', '$2y$10$c6kG6vrP4iV.TeU9ht6Cde7dcEoWRmcPGDivUAMYhj.ZrEKXTvjtW', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 2.33, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph8T5D11', 0, '2025-06-06 17:52:13'),
(66, 'Padi-Padi Germi', 'Germi5', 'padipadigermi618@gmail.com', '$2y$10$HZsBwjPXFp5UaIMdQd56XeQpCiMUFFVhbYbld.H9UKXEKtwYfqoqG', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.12, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhsMokjo', 0, '2025-06-06 17:52:13'),
(68, 'Umesh Pokharel', 'umesh', 'nutrinexusnp@gmail.com', '$2y$10$9nA0fgPYSRTv3s8syPjxUuwx3sPgnOfmc.OFGTkzWRRj9kMibePjC', NULL, NULL, NULL, NULL, NULL, 'solution/connect/uploads/A81FA993-D938-4067-AE08-69BBFD0C0751.jpeg', 7.27, 'park', 0, NULL, 'Not Running', NULL, NULL, '783880', 0.00, NULL, 'PhmsiBjj', 0, '2025-06-06 17:52:13'),
(71, 'azttrx', 'azt_trx', 'sirus.mardi1367@gmail.com', '$2y$10$sA/2R3sI2SrudPOpoaNw1O4s/.CGT0JYA/sg1XuQEy0ojxkgJ7u/G', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 1.00, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhG65ALN', 0, '2025-06-06 17:52:13'),
(72, 'Niru Didi', 'niru', 'niru@gmail.com', '$2y$10$hzv6asLNIrLB0gkKA.aZaudJTRppvPzTSgDjzR7pkBFNBFJwJoZWS', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.00, '', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhayrVem', 0, '2025-06-06 17:52:13'),
(73, 'Nepearning', 'Nepearning', 'nearning2@gmail.com', '$2y$10$qGPfigfYJp46NqkqLmN1HegP2EkuJR3CWgPlbLNPtl/teQROcRQLO', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 2.00, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhudURAb', 0, '2025-06-06 17:52:13'),
(74, 'Bhawana khadka', 'bhawana', 'bcainebhawana2079@gmail.com', '$2y$10$j/rMw9y31KWP/1hyIPeswuk7hvA4ijoU/gsIWMOBWbI3jfoliLK4K', 'myanglung 2 ,terhathum', '', '', '', '', 'solution/connect/uploads/81eba28a-2195-4437-8433-28e1d793a625.jpg', 4.50, '', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhlKxfof', 0, '2025-06-06 17:52:13'),
(75, 'Ashish', 'khadka', 'ashishkhadka122@gmail.com', '$2y$10$opP29QRWptAJWyCXMdiTJuK/9jk1cIghYDXew.xeMcMu5Hivmz9Nu', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 93.38, 'prasanga', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhCPDOH8', 0, '2025-06-06 17:52:13'),
(77, 'jaaya Pokharel', 'jaya', 'prashanna787898@gmail.com', '$2y$10$77zhXdQNs9pWVXVfXrLMxeW8H2XXp9lx70ZEhB.ujmODeKGfBpaH6', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.50, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhRQsu0u', 0, '2025-06-06 17:52:13'),
(78, 'Barry', 'Newton', 'barryyamba468@gmail.com', '$2y$10$TG1J5dDyOeaHfCPphVOVHuI/nPQfuApyOxGFh/NFBy5mhR2SIb6iO', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.00, 'Willkiss1', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhLJaTo2', 0, '2025-06-06 17:52:13'),
(79, 'Shreewashramanpokharel', 'Shreewash', 'shreewashramanpokharel12@gmail.com', '$2y$10$mvKYKPaQOZM5U6bDadEiwuuShXFSp16aZiQpE6H4Y5FKYz2oLrO42', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 18.50, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhaIAQ8i', 0, '2025-06-06 17:52:13'),
(82, 'Kirby Perry', 'puxicihe', 'zywezam@gmail.com', '$2y$10$Zym/ABGZy8LyBNnJhaNNSusz0SNfirG9x1qEfpzmz9iY/HVxpPsBm', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.00, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'Ph4q8b2U', 0, '2025-06-06 17:52:13'),
(83, 'BerezaSasha', 'Bereza', 'samsungsons3@gmail.com', '$2y$10$4FU4fsXReqMOoAnDsC/4Y.ZROXmF1Bm3kCjYBgo0ND..HDjdV.GQ2', 'TLmeNexi6Dfg3NrjrDXrMeNbPv98DK8G7z', '', '', '', '', 'default.jpg', 0.00, 'park', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhHLLom6', 0, '2025-06-06 17:52:13'),
(84, 'Aop Calyp', 'apocalypto', 'rhythmniraulaoo7@gmail.com', '$2y$10$mF/66/UGlQjMpee5PJC0HORx79FZhLWx0ksz/NhN125biiSmTFOVe', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.00, '', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhZjkFCA', 0, '2025-06-06 17:52:13'),
(85, 'Manan Neupane', 'manan', 'neupanemanan.2003@gmail.com', '$2y$10$jFSc0euHdc6XTeuBwzVYq.hzyeRJaHTWvNxWOF2pLk1HK/Oj5E.3.', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.00, 'prasangha', 0, NULL, 'Not Running', NULL, NULL, NULL, 0.00, NULL, 'PhMfpWob', 0, '2025-06-06 17:52:13'),
(86, '', 'jayas', 'incpractical@gmail.com', '$2y$10$I0dg34tFEq8snJnlGnJ16ONTsU3PXzaRJZeDJr5qmCCX1y7.dRzDi', NULL, NULL, NULL, NULL, NULL, 'default.jpg', 0.00, NULL, 0, NULL, 'Not Running', NULL, NULL, '040473', 0.00, '2025-06-09', '21eb01fba8', 0, '2025-06-06 18:37:01');

--
-- Triggers `users2`
--
DELIMITER $$
CREATE TRIGGER `audit_user_login` AFTER UPDATE ON `users2` FOR EACH ROW BEGIN
    IF NEW.last_login_date != OLD.last_login_date THEN
        INSERT INTO audit_logs (user_id, action, details) 
        VALUES (NEW.id, 'LOGIN', CONCAT('User logged in at ', NEW.last_login_date));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_supply_after_delete` AFTER DELETE ON `users2` FOR EACH ROW BEGIN
    UPDATE supply 
    SET current_supply = (SELECT SUM(balance) FROM users2),
        remaining_supply = (1000000000.00 - (SELECT SUM(balance) FROM users2))
    WHERE id = 1;  -- Assuming you have only one row in the supply table
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_supply_after_insert` AFTER INSERT ON `users2` FOR EACH ROW BEGIN
    UPDATE supply 
    SET current_supply = (SELECT SUM(balance) FROM users2),
        remaining_supply = (1000000000.00 - (SELECT SUM(balance) FROM users2))
    WHERE id = 1;  -- Assuming you have only one row in the supply table
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_supply_after_update` AFTER UPDATE ON `users2` FOR EACH ROW BEGIN
    UPDATE supply 
    SET current_supply = (SELECT SUM(balance) FROM users2),
        remaining_supply = (1000000000.00 - (SELECT SUM(balance) FROM users2))
    WHERE id = 1;  -- Assuming you have only one row in the supply table
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `login_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_wallet_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_wallet_summary` (
`id` int(11)
,`username` varchar(255)
,`email` varchar(255)
,`address` text
,`balance` decimal(15,2)
,`status` enum('Paid','Unpaid')
,`wallet_created` timestamp
,`transaction_count` bigint(21)
,`total_sent` decimal(40,6)
,`total_received` decimal(40,6)
);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_backups`
--

CREATE TABLE `wallet_backups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `encrypted_private_key` text NOT NULL,
  `encrypted_mnemonic` text DEFAULT NULL,
  `backup_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_accessed` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `from_address` varchar(50) NOT NULL,
  `to_address` varchar(50) NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `fee` decimal(18,6) NOT NULL DEFAULT 1.500000,
  `total_amount` decimal(18,6) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `tx_hash` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`id`, `user_id`, `from_address`, `to_address`, `amount`, `fee`, `total_amount`, `status`, `tx_hash`, `created_at`, `processed_at`) VALUES
(1, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'pending', 'TRX17493835346959', '2025-06-08 11:52:14', '2025-06-08 11:52:14'),
(2, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'failed', NULL, '2025-06-08 12:11:12', '2025-06-08 12:11:12'),
(3, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'failed', NULL, '2025-06-08 16:08:47', '2025-06-08 16:08:48'),
(4, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'failed', NULL, '2025-06-08 16:09:22', '2025-06-08 16:09:25'),
(5, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'failed', NULL, '2025-06-08 16:22:32', '2025-06-08 16:22:33'),
(6, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'failed', NULL, '2025-06-08 16:22:59', '2025-06-08 16:23:00'),
(7, 86, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 5.000000, 1.500000, 6.500000, 'pending', NULL, '2025-06-08 16:23:26', NULL),
(8, 37, 'TCfiKE9LorXunPTHLwufBi3ga8JJtm5dRv', 'TXjbeaNsQ8sBaEsVGtfVV9DQYwnwL6q1dw', 7.760000, 1.500000, 9.260000, 'pending', NULL, '2025-06-08 17:00:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `withdraw_historys`
--

CREATE TABLE `withdraw_historys` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `bittorrent_address` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `withdraw_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdraw_historys`
--

INSERT INTO `withdraw_historys` (`id`, `username`, `bittorrent_address`, `amount`, `withdraw_date`) VALUES
(1, 'horoqafibo', '0x8595f9da7b868b1822194faed312235e43007b49', 100.00, '2024-10-12 20:23:12'),
(2, '19', '0x8595f9da7b868b1822194faed312235e43007b49', 100.00, '2024-10-12 20:23:12'),
(3, 'horoqafibo', '0x8595f9da7b868b1822194faed312235e43007b49', 700.00, '2024-10-12 20:24:47'),
(4, '19', '0x8595f9da7b868b1822194faed312235e43007b49', 700.00, '2024-10-12 20:24:47');

-- --------------------------------------------------------

--
-- Structure for view `user_wallet_summary`
--
DROP TABLE IF EXISTS `user_wallet_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_wallet_summary`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`email` AS `email`, `tb`.`address` AS `address`, `tb`.`balance` AS `balance`, `tb`.`status` AS `status`, `tb`.`created_at` AS `wallet_created`, (select count(0) from `trxhistory` `th` where `th`.`user_id` = `u`.`id`) AS `transaction_count`, (select sum(`th`.`amount`) from `trxhistory` `th` where `th`.`user_id` = `u`.`id` and `th`.`status` = 'send') AS `total_sent`, (select sum(`th`.`amount`) from `trxhistory` `th` where `th`.`user_id` = `u`.`id` and `th`.`status` = 'receive') AS `total_received` FROM (`users2` `u` left join `trxbalance` `tb` on(`u`.`id` = `tb`.`user_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_action` (`user_id`,`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `balance_supply`
--
ALTER TABLE `balance_supply`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bonding_curves`
--
ALTER TABLE `bonding_curves`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_id` (`token_id`),
  ADD KEY `idx_token_id` (`token_id`),
  ADD KEY `idx_bonding_curves_token` (`token_id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`),
  ADD KEY `idx_company_settings_name` (`setting_name`);

--
-- Indexes for table `deposit_logs`
--
ALTER TABLE `deposit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_tx_hash` (`tx_hash`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_otp` (`user_id`,`otp`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `internal_transfers`
--
ALTER TABLE `internal_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfer_id` (`transfer_id`),
  ADD KEY `idx_from_user` (`from_user_id`),
  ADD KEY `idx_to_user` (`to_user_id`),
  ADD KEY `idx_transfer_id` (`transfer_id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`announcement_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quick_login_tokens`
--
ALTER TABLE `quick_login_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `supply`
--
ALTER TABLE `supply`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_address` (`contract_address`),
  ADD KEY `idx_creator_id` (`creator_id`),
  ADD KEY `idx_tokens_status` (`status`),
  ADD KEY `idx_tokens_market_cap` (`market_cap`),
  ADD KEY `idx_tokens_volume` (`volume_24h`),
  ADD KEY `idx_tokens_launch_time` (`launch_time`),
  ADD KEY `idx_tokens_creator` (`creator_id`);

--
-- Indexes for table `token_balances`
--
ALTER TABLE `token_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_token_user` (`token_id`,`user_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token_id` (`token_id`),
  ADD KEY `idx_token_balances_user_token` (`user_id`,`token_id`),
  ADD KEY `idx_token_balances_token_balance` (`token_id`,`balance`);

--
-- Indexes for table `token_transactions`
--
ALTER TABLE `token_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_hash` (`transaction_hash`),
  ADD KEY `idx_token_id` (`token_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_token_transactions_token_status_time` (`token_id`,`status`,`created_at`),
  ADD KEY `idx_token_transactions_user_token` (`user_id`,`token_id`),
  ADD KEY `idx_token_transactions_type_time` (`transaction_type`,`created_at`),
  ADD KEY `idx_token_transactions_hash` (`transaction_hash`);

--
-- Indexes for table `trxbalance`
--
ALTER TABLE `trxbalance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_address` (`user_id`,`address`(20)),
  ADD KEY `idx_trxbalance_user` (`user_id`),
  ADD KEY `idx_trxbalance_balance` (`balance`);

--
-- Indexes for table `trxhistory`
--
ALTER TABLE `trxhistory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_status_timestamp` (`user_id`,`status`,`timestamp`),
  ADD KEY `idx_trxhistory_transaction_type` (`transaction_type`),
  ADD KEY `idx_trxhistory_user_id` (`user_id`),
  ADD KEY `idx_trxhistory_timestamp` (`timestamp`);

--
-- Indexes for table `trxtransactions`
--
ALTER TABLE `trxtransactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users2`
--
ALTER TABLE `users2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `PH_id` (`PH_id`),
  ADD KEY `idx_username_email` (`username`,`email`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`,`login_date`);

--
-- Indexes for table `wallet_backups`
--
ALTER TABLE `wallet_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_backup` (`user_id`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `withdraw_historys`
--
ALTER TABLE `withdraw_historys`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `balance_supply`
--
ALTER TABLE `balance_supply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bonding_curves`
--
ALTER TABLE `bonding_curves`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deposit_logs`
--
ALTER TABLE `deposit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_transfers`
--
ALTER TABLE `internal_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=345;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `quick_login_tokens`
--
ALTER TABLE `quick_login_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `supply`
--
ALTER TABLE `supply`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `token_balances`
--
ALTER TABLE `token_balances`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `token_transactions`
--
ALTER TABLE `token_transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `trxbalance`
--
ALTER TABLE `trxbalance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `trxhistory`
--
ALTER TABLE `trxhistory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `trxtransactions`
--
ALTER TABLE `trxtransactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users2`
--
ALTER TABLE `users2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_backups`
--
ALTER TABLE `wallet_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `withdraw_historys`
--
ALTER TABLE `withdraw_historys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bonding_curves`
--
ALTER TABLE `bonding_curves`
  ADD CONSTRAINT `bonding_curves_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internal_transfers`
--
ALTER TABLE `internal_transfers`
  ADD CONSTRAINT `internal_transfers_ibfk_1` FOREIGN KEY (`from_user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internal_transfers_ibfk_2` FOREIGN KEY (`to_user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quick_login_tokens`
--
ALTER TABLE `quick_login_tokens`
  ADD CONSTRAINT `quick_login_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `token_balances`
--
ALTER TABLE `token_balances`
  ADD CONSTRAINT `token_balances_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `token_balances_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `token_transactions`
--
ALTER TABLE `token_transactions`
  ADD CONSTRAINT `token_transactions_ibfk_1` FOREIGN KEY (`token_id`) REFERENCES `tokens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `token_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trxbalance`
--
ALTER TABLE `trxbalance`
  ADD CONSTRAINT `trxbalance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`);

--
-- Constraints for table `trxhistory`
--
ALTER TABLE `trxhistory`
  ADD CONSTRAINT `trxhistory_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`);

--
-- Constraints for table `trxtransactions`
--
ALTER TABLE `trxtransactions`
  ADD CONSTRAINT `trxtransactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`);

--
-- Constraints for table `wallet_backups`
--
ALTER TABLE `wallet_backups`
  ADD CONSTRAINT `wallet_backups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users2` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
