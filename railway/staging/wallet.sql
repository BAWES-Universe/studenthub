-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Mar 04, 2025 at 01:44 PM
-- Server version: 5.7.39
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wallet`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '10',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `limit_email` datetime DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `enable_auth_2fa` tinyint(1) DEFAULT '1',
  `auth_2fa_method` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_2fa_secret` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_uuid`, `username`, `auth_key`, `password_hash`, `password_reset_token`, `email`, `status`, `created_at`, `updated_at`, `ip_address`, `limit_email`, `deleted`, `enable_auth_2fa`, `auth_2fa_method`, `auth_2fa_secret`) VALUES
('1', 'Khalid', 'Lu4vPW4Npfgce6WkXdt9OErpxXdB7GW4', '$2y$13$ysNVt1e9iJbbE5emXUiDvO9z4LIWbZ4gnft8eR.AuhhdtlkuSmC42', NULL, 'khalid@bawes.net', 10, '2018-08-21 19:20:58', '2025-02-23 17:27:25', NULL, NULL, 0, 1, NULL, 'FSH37PC2QQIFAL33'),
('2', 'Krushn', NULL, '$2y$13$Wr6SGdFdPINir67oVU7FkuENki/ZsDgCsrHCr0yoFnBIz35Z9C8Qy', NULL, 'kk@bawes.net', 10, '2025-02-23 14:40:28', '2025-02-23 17:29:08', NULL, NULL, 0, 1, NULL, 'EBXHX6KQCNFVN7YU');

-- --------------------------------------------------------

--
-- Table structure for table `admin_token`
--

CREATE TABLE `admin_token` (
  `token_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `admin_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `token_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token_device` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token_device_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token_status` smallint(6) NOT NULL,
  `token_last_used_datetime` datetime DEFAULT NULL,
  `token_expiry_datetime` datetime DEFAULT NULL,
  `token_created_datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `admin_token`
--

INSERT INTO `admin_token` (`token_uuid`, `admin_uuid`, `token_value`, `token_device`, `token_device_id`, `token_status`, `token_last_used_datetime`, `token_expiry_datetime`, `token_created_datetime`) VALUES
('1', '1', 'test', NULL, NULL, 1, NULL, NULL, '2025-02-02 18:12:54'),
('admin_token_6108a494-f1cf-11ef-ad06-944ab4ab3a22', '2', 'RS1TmjVmWG0ABDq5hbgow2p3Vu75MVOO', NULL, NULL, 1, NULL, NULL, '2025-02-23 15:47:26');

-- --------------------------------------------------------

--
-- Table structure for table `balance_account`
--

CREATE TABLE `balance_account` (
  `balance_account_uuid` char(60) NOT NULL,
  `account_uuid` char(60) NOT NULL,
  `type` char(60) NOT NULL COMMENT 'invoice, payment, provider, user',
  `balance` decimal(10,3) NOT NULL DEFAULT '0.000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `balance_account`
--

INSERT INTO `balance_account` (`balance_account_uuid`, `account_uuid`, `type`, `balance`) VALUES
('balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', 'PayableToUsers', '11.000'),
('balance_account_32734820-d1ca-11ef-b93b-5e3e1bceba38', 'user_32724060-d1ca-11ef-b93b-5e3e1bceba38', 'Payable_for_this_user_uuid', '0.000'),
('balance_account_4f66929c-e6b2-11ee-ba14-0cf9bfd0d2ad', 'user_e3e3daa4-e6af-11ee-ba14-0cf9bfd0d2ad', 'Payable_for_this_user_uuid', '0.000'),
('balance_account_b9386c42-e191-11ef-aa62-0eeb727a0ccc', 'user_31c621c2-aa43-11ee-9f54-5c208b5c2abf', 'Payable_for_this_user_uuid', '1.000'),
('balance_account_b93a14b6-e191-11ef-aa62-0eeb727a0ccc', 'user_31c621c2-aa43-11ee-9f54-5c208b5c2abf', 'PayableToUsers', '1.000'),
('balance_account_d1feca2c-ce29-11ee-b3f2-f724cc937ebc', 'user_d1fde972-ce29-11ee-b3f2-f724cc937ebc', 'Payable_for_this_user_uuid', '0.000'),
('balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', 'Payable_for_this_user_uuid', '11.000');

-- --------------------------------------------------------

--
-- Table structure for table `balance_transaction`
--

CREATE TABLE `balance_transaction` (
  `balance_transaction_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `account_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `amount` decimal(10,3) NOT NULL DEFAULT '0.000',
  `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `balance` decimal(10,3) NOT NULL DEFAULT '0.000',
  `data` text COLLATE utf8_unicode_ci,
  `file` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transaction_datetime` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `balance_transaction`
--

INSERT INTO `balance_transaction` (`balance_transaction_uuid`, `account_uuid`, `amount`, `user_uuid`, `balance`, `data`, `file`, `transaction_datetime`, `created_at`, `ip_address`) VALUES
('balance_transaction_01e1fd62-0686-11ed-b3b0-e86f74f57156', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '6.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '6.000', 'test entry', NULL, '2022-07-18 16:10:14', '2022-07-18 16:10:14', NULL),
('balance_transaction_01e59d96-0686-11ed-b3b0-e86f74f57156', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '6.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '6.000', 'test entry', NULL, '2022-07-18 16:10:14', '2022-07-18 16:10:14', NULL),
('balance_transaction_035df8d0-0686-11ed-b3b0-e86f74f57156', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '6.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '12.000', 'test entry', NULL, '2022-07-18 16:10:17', '2022-07-18 16:10:17', NULL),
('balance_transaction_035f4e92-0686-11ed-b3b0-e86f74f57156', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '6.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '12.000', 'test entry', NULL, '2022-07-18 16:10:17', '2022-07-18 16:10:17', NULL),
('balance_transaction_0a429642-0695-11ed-b3b0-e86f74f57156', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '-2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '13.000', 'test segment', NULL, '2022-07-18 17:57:51', '2022-07-18 17:57:51', NULL),
('balance_transaction_0a4440a0-0695-11ed-b3b0-e86f74f57156', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '-2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '13.000', 'test segment', NULL, '2022-07-18 17:57:51', '2022-07-18 17:57:51', NULL),
('balance_transaction_44b72856-0686-11ed-b3b0-e86f74f57156', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '3.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '15.000', 'test 2', NULL, '2022-07-18 16:12:06', '2022-07-18 16:12:06', NULL),
('balance_transaction_44b94db6-0686-11ed-b3b0-e86f74f57156', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '3.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '15.000', 'test 2', NULL, '2022-07-18 16:12:06', '2022-07-18 16:12:06', NULL),
('balance_transaction_48f9541e-5ae3-11ed-a6c6-5184a19c2afd', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '-1.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '10.000', '{\"to_uuid\":\"user_c68d3740-0680-11ed-b3b0-e86f74f57156\"}', NULL, '2022-11-02 22:19:34', '2022-11-03 00:49:34', NULL),
('balance_transaction_48fd49f2-5ae3-11ed-a6c6-5184a19c2afd', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '1.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '11.000', '{\"to_uuid\":\"user_c68d3740-0680-11ed-b3b0-e86f74f57156\"}', NULL, '2022-11-02 22:19:34', '2022-11-03 00:49:34', NULL),
('balance_transaction_49a5adbc-5ae4-11ed-a6c6-5184a19c2afd', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '-1.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '10.000', 'Paid to krishna', NULL, '2022-11-02 22:26:45', '2022-11-03 00:56:45', NULL),
('balance_transaction_49a7c336-5ae4-11ed-a6c6-5184a19c2afd', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '1.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '11.000', 'Received from krishna', NULL, '2022-11-02 22:26:45', '2022-11-03 00:56:45', NULL),
('balance_transaction_6cad0faa-5ad8-11ed-a6c6-5184a19c2afd', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '-2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '11.000', 'Transfer Initiated', NULL, '2022-11-02 21:01:50', '2022-11-02 23:31:50', NULL),
('balance_transaction_6caec28c-5ad8-11ed-a6c6-5184a19c2afd', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '-2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '11.000', 'Transfer Initiated', NULL, '2022-11-02 21:01:50', '2022-11-02 23:31:50', NULL),
('balance_transaction_a5ed8b2a-0694-11ed-b3b0-e86f74f57156', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '17.000', 'test segment', NULL, '2022-07-18 17:55:02', '2022-07-18 17:55:02', NULL),
('balance_transaction_a5f175f0-0694-11ed-b3b0-e86f74f57156', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '17.000', 'test segment', NULL, '2022-07-18 17:55:02', '2022-07-18 17:55:02', NULL),
('balance_transaction_b938938e-e191-11ef-aa62-0eeb727a0ccc', 'balance_account_b9386c42-e191-11ef-aa62-0eeb727a0ccc', '1.000', 'user_31c621c2-aa43-11ee-9f54-5c208b5c2abf', '1.000', NULL, NULL, '2025-02-02 21:15:46', '2025-02-02 23:45:46', '::1'),
('balance_transaction_b93a26c2-e191-11ef-aa62-0eeb727a0ccc', 'balance_account_b93a14b6-e191-11ef-aa62-0eeb727a0ccc', '1.000', 'user_31c621c2-aa43-11ee-9f54-5c208b5c2abf', '1.000', NULL, NULL, '2025-02-02 21:15:46', '2025-02-02 23:45:46', '::1'),
('balance_transaction_bc919dee-0694-11ed-b3b0-e86f74f57156', 'balance_account_f07436d8-0682-11ed-b3b0-e86f74f57156', '-2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '15.000', 'test segment', NULL, '2022-07-18 17:55:40', '2022-07-18 17:55:40', NULL),
('balance_transaction_bc93ddb6-0694-11ed-b3b0-e86f74f57156', 'balance_account_01e511a0-0686-11ed-b3b0-e86f74f57156', '-2.000', 'user_c68d3740-0680-11ed-b3b0-e86f74f57156', '15.000', 'test segment', NULL, '2022-07-18 17:55:40', '2022-07-18 17:55:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `balance_transaction_tag`
--

CREATE TABLE `balance_transaction_tag` (
  `tag_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `balance_account_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `balance_transaction_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `balance_transaction_tag`
--

INSERT INTO `balance_transaction_tag` (`tag_uuid`, `balance_account_uuid`, `balance_transaction_uuid`) VALUES
('tag_b937d50c-e191-11ef-aa62-0eeb727a0ccc', 'balance_account_b9386c42-e191-11ef-aa62-0eeb727a0ccc', 'balance_transaction_b938938e-e191-11ef-aa62-0eeb727a0ccc'),
('tag_b937d50c-e191-11ef-aa62-0eeb727a0ccc', 'balance_account_b93a14b6-e191-11ef-aa62-0eeb727a0ccc', 'balance_transaction_b93a26c2-e191-11ef-aa62-0eeb727a0ccc');

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `bank_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `bank_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_iban_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_swift_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_transfer_type` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `bank`
--

INSERT INTO `bank` (`bank_uuid`, `bank_name`, `bank_iban_code`, `bank_swift_code`, `bank_address`, `bank_transfer_type`, `deleted`) VALUES
('bank_d9bdb244-d8ac-11ef-bc58-0761d507a113', 'Ahli United Bank', 'BKME', 'BKMEKWKW', 'Kuwait', 'TRF', 0);

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ip`
--

CREATE TABLE `blocked_ip` (
  `ip_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currency`
--

CREATE TABLE `currency` (
  `currency_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `symbol_left` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `symbol_right` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value` double DEFAULT NULL,
  `decimal_place` tinyint(1) DEFAULT NULL,
  `sort_order` int(3) DEFAULT NULL,
  `status` tinyint(2) DEFAULT '10',
  `datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `expense_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `detail` text COLLATE utf8_unicode_ci,
  `amount` decimal(10,3) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_tag`
--

CREATE TABLE `expense_tag` (
  `tag_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `expense_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extension`
--

CREATE TABLE `extension` (
  `extension_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` tinyint(2) DEFAULT '10',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `loan_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` decimal(10,3) DEFAULT NULL,
  `user_comment` text COLLATE utf8_unicode_ci,
  `note` text COLLATE utf8_unicode_ci COMMENT 'note from admin',
  `status` tinyint(1) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migration`
--

CREATE TABLE `migration` (
  `version` varchar(180) NOT NULL,
  `apply_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `migration`
--

INSERT INTO `migration` (`version`, `apply_time`) VALUES
('m000000_000000_base', 1658079759),
('m130524_201442_init', 1658080019),
('m190124_110200_add_verification_token_column_to_user_table', 1658080019),
('m220808_123922_transaction_datetime', 1667410032),
('m220810_094207_attachment', 1667410032),
('m221018_084010_user_token', 1667410032),
('m221023_084537_user_transfer', 1667410032),
('m221102_172128_bank', 1667410123),
('m221107_134724_extension', 1738085757),
('m221115_045709_loan', 1738085757),
('m221115_065931_uuid_sort', 1738085757),
('m221122_065152_kyc_table', 1738085757),
('m221122_074705_country_tbl', 1738085757),
('m221130_094508_tap', 1738085758),
('m221211_055616_tag', 1738085758),
('m221211_151704_subscription_type', 1738085758),
('m221212_085743_webhook', 1738085758),
('m221213_081327_balance_tag', 1738085758),
('m250202_173235_limit_email', 1738519729),
('m250223_090410_add_2fa_to_user', 1740306437);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` decimal(10,3) DEFAULT NULL,
  `currency_code` char(3) COLLATE utf8_unicode_ci DEFAULT 'KWD',
  `currency_value` double DEFAULT '1' COMMENT 'compared to KWD',
  `status` tinyint(2) DEFAULT '10',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `payment_current_status` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `response_message` text COLLATE utf8_unicode_ci,
  `payment_gateway_transaction_id` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_sandbox` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting`
--

CREATE TABLE `setting` (
  `setting_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(128) COLLATE utf8_unicode_ci NOT NULL COMMENT 'module identifier',
  `key` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `serialized` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription`
--

CREATE TABLE `subscription` (
  `subscription_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `subscription_type` enum('Monthly','Yearly') COLLATE utf8_unicode_ci DEFAULT 'Monthly',
  `no_of_users` int(11) DEFAULT NULL,
  `credit_card` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` decimal(10,3) DEFAULT NULL,
  `currency` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `renewal_date` datetime DEFAULT NULL,
  `detail` text COLLATE utf8_unicode_ci,
  `status` tinyint(1) DEFAULT '10',
  `created_by` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_by` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_tag`
--

CREATE TABLE `subscription_tag` (
  `tag_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `subscription_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE `tag` (
  `tag_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `tag_name` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tag_type` enum('Credit','Debit') COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `tag`
--

INSERT INTO `tag` (`tag_uuid`, `tag_name`, `tag_type`, `created_at`, `updated_at`) VALUES
('tag_b937d50c-e191-11ef-aa62-0eeb727a0ccc', '', NULL, '2025-02-02 23:45:46', '2025-02-02 23:45:46');

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

CREATE TABLE `transfer` (
  `transfer_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `transfer_uuid_short` varchar(35) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transfer_confirmation_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transfer_file_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transfer_benef_name` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transfer_benef_iban` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transfer_cost` decimal(10,3) DEFAULT NULL,
  `transfer_total` decimal(10,3) DEFAULT NULL,
  `transfer_status` tinyint(1) DEFAULT NULL,
  `transfer_created_at` datetime DEFAULT NULL,
  `transfer_updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_file`
--

CREATE TABLE `transfer_file` (
  `transfer_file_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `transfer_file_s3_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `transfer_amount` decimal(10,3) DEFAULT NULL,
  `transfer_file_created_at` datetime NOT NULL,
  `transfer_file_updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transfer_file_entry`
--

CREATE TABLE `transfer_file_entry` (
  `tfe_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `transfer_file_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status_description` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `section_index` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `transfer_method` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `credit_amount` decimal(10,3) DEFAULT NULL,
  `credit_currency` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(10,3) DEFAULT NULL,
  `dealRefNo` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value_date` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `debit_account_no` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `credit_account_no` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `debit_narrative` char(60) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'transfer_uuid',
  `credit_narrative` char(60) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user_uuid',
  `payment_details_1` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment_details_2` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment_details_3` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment_details_4` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_address_line_1` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_address_line_2` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_bank_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_bank_address_1` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_bank_address_2` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `beneficiary_bank_address_3` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `swift` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intermediary_account` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intermediary_swift` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intrmediary_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intermediary_address_1` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intermediary_address_2` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intermediary_address_3` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `charges_type` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sort_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BIC_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IBAN` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ABA_routing_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_by` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_by` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bank_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_account_name` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `iban` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '10',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `verification_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `limit_email` datetime DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  `enable_auth_2fa` tinyint(1) DEFAULT '0',
  `auth_2fa_method` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_2fa_secret` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_uuid`, `username`, `auth_key`, `password_hash`, `password_reset_token`, `email`, `bank_uuid`, `bank_account_name`, `iban`, `status`, `created_at`, `updated_at`, `verification_token`, `ip_address`, `limit_email`, `deleted`, `enable_auth_2fa`, `auth_2fa_method`, `auth_2fa_secret`) VALUES
('user_31c621c2-aa43-11ee-9f54-5c208b5c2abf', 'Mohamed Kanso', '_DJPPH6ySAPLgm2fvLIn2X7xIBk_lT7U', '$2y$13$/56PVum6pj6r8nAl68jyjO.EMlKRuBRujWI0gKzl/HLeGxik01Y9y', NULL, 'mohamedkanso9@hotmail.com', NULL, NULL, NULL, 10, '2024-01-03 19:50:06', '2024-01-03 19:50:06', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_32724060-d1ca-11ef-b93b-5e3e1bceba38', 'Krishna bhagvan', '6c4kuqQQSDMHwghpRg3QGW1buDD6KRWp', '$2y$13$kJXHvkDtcjD7Wp7Wzr248eYWLlvmxpgnZGYA4jUvBXKuKB/h26hxS', NULL, 'krishna@bhagvan.com', NULL, NULL, NULL, 10, '2025-01-13 21:49:43', '2025-01-13 21:49:43', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_902b0c26-ac1f-11ef-ba6d-d9afdd49d552', 'A b', 'ZtPDq5wQPIe3Zt-t_pUouLOKZCqvIGIN', '$2y$13$AFVHRDzsN/dKNnVDFc4cL.c7f5M82scRoh9iS1WQWo6I0qqRhPN2C', NULL, 'a@b.com', NULL, NULL, NULL, 10, '2024-11-26 23:25:03', '2024-11-26 23:25:03', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_93503252-e773-11ef-a6e7-0fad3914ba69', 'Abdulrahman Qussai Alagha', 'EpSh31Ab6rOv6gFYLd7qJLAO3Y1KYmu8', '$2y$13$1PpTbmnruBXxH68bdWckReTB74dzrHP8jryYtln/0UCmWaIX9CAYS', NULL, 'abode_alagha@hotmail.com', NULL, '', '', 10, '2025-02-10 11:25:05', '2025-02-10 11:25:05', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_abdab7de-23b1-11ee-ad34-5b2ccd7e12e0', 'Asd jj', 'Taw8hcDzWQy7VtVaFv3h7ltR9SIF9WGk', '$2y$13$FJPVRuGyS0SCxHG3O1KssePjI8WZUCApN0IB.QiuX/wQ5arSGSlfy', NULL, 't1@t.com', NULL, NULL, NULL, 10, '2023-07-16 13:50:49', '2023-07-16 13:50:49', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_ac7a3436-8db9-11ee-8e86-e53761442f22', 'Demo demo', 'lHUdGsXgE5soKRm5YFHSOl8AJOzAGzVU', '$2y$13$E4qKdMhSF18yDuY09QkGyeIjooNFt.qH1tE2ac6pHHry1gW3.Ay6q', NULL, 'demo@localhost.com', NULL, NULL, NULL, 10, '2023-11-28 12:15:09', '2023-11-28 12:15:09', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_c68d3740-0680-11ed-b3b0-e86f74f57156', 'krishna', '', '$2y$13$2YqWVtfOyG4.xhBiG.YQKuNLwO3EYyinzNc/3tqh4a6OQmVEOCe1W', NULL, 'kk@bawes.net', 'bank_d9bdb244-d8ac-11ef-bc58-0761d507a113', 'sdfsf afarar as dad', 'BKME12345678901234567890123456', 10, '2022-07-18 15:32:47', '2022-11-02 23:27:22', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_d1fde972-ce29-11ee-b3f2-f724cc937ebc', 'Rohan saxena', 'X-FsJK5xDZApNAwWN2tRZWzO8G9z6qCd', '$2y$13$QmaEt.rmEjmIO8NLp1Imy.T78jKXr40rzXyiaBFZte9QMpBipQVWK', NULL, 'kathrechakrushn@gmail.com', NULL, NULL, NULL, 10, '2024-02-18 12:19:10', '2024-02-18 12:19:10', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_d99b203c-f502-11ee-8302-a9371bdf0faa', 'Sdf df', 'y-Cip6THEkQTn3xt7k2rwzK9P2t1_av1', '$2y$13$g4kM.A.7RAd8mOLV73S5AuTSXP3rMar1PZeNIqXoG6jfBvIYrS2lW', NULL, 'me@iamkrushn.com', NULL, NULL, NULL, 10, '2024-04-07 22:48:28', '2024-04-07 22:48:28', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_e30503ce-e159-11ef-820f-1f8a550c3e11', 'Er. Krushnkumar', 'APEuS99klHstVvHikyVknYyDPxRnK8D0', '$2y$13$UnUm0J73Zuv73izcxJ7gkuPqeHOyWs2QJxNJjeRPpu09APeL0QzHa', NULL, 'kathrechakrushn2@gmail.com', NULL, '', '', 10, '2025-02-02 17:06:04', '2025-02-02 17:06:04', NULL, NULL, NULL, 0, 0, NULL, NULL),
('user_e3e3daa4-e6af-11ee-ba14-0cf9bfd0d2ad', 'Student 20 mar', 'WCxhOE7WwOJixJfJLrC6f3HmXWXf0d1M', '$2y$13$EDJMZtT7qcxNreNJp.QRmesgA3h0z6ykTqMFZRxCDZbD1e8YozOZ.', NULL, 'student20mar@local.com', NULL, NULL, NULL, 10, '2024-03-20 17:19:21', '2024-03-20 17:19:21', NULL, NULL, NULL, 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_token`
--

CREATE TABLE `user_token` (
  `token_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `token_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token_device` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token_device_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token_status` smallint(6) NOT NULL,
  `token_last_used_datetime` datetime DEFAULT NULL,
  `token_expiry_datetime` datetime DEFAULT NULL,
  `token_created_datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_uuid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`);

--
-- Indexes for table `admin_token`
--
ALTER TABLE `admin_token`
  ADD PRIMARY KEY (`token_uuid`),
  ADD KEY `idx-admin_token-admin_uuid` (`admin_uuid`);

--
-- Indexes for table `balance_account`
--
ALTER TABLE `balance_account`
  ADD PRIMARY KEY (`balance_account_uuid`);

--
-- Indexes for table `balance_transaction`
--
ALTER TABLE `balance_transaction`
  ADD PRIMARY KEY (`balance_transaction_uuid`),
  ADD KEY `idx-balance_transaction-user_uuid` (`user_uuid`);

--
-- Indexes for table `balance_transaction_tag`
--
ALTER TABLE `balance_transaction_tag`
  ADD PRIMARY KEY (`tag_uuid`,`balance_transaction_uuid`),
  ADD KEY `fk-balance_transaction_tag-balance_transaction_uuid` (`balance_transaction_uuid`),
  ADD KEY `idx-balance_transaction_tag-balance_account_uuid` (`balance_account_uuid`);

--
-- Indexes for table `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`bank_uuid`);

--
-- Indexes for table `blocked_ip`
--
ALTER TABLE `blocked_ip`
  ADD PRIMARY KEY (`ip_uuid`);

--
-- Indexes for table `currency`
--
ALTER TABLE `currency`
  ADD PRIMARY KEY (`currency_id`);

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`expense_uuid`);

--
-- Indexes for table `expense_tag`
--
ALTER TABLE `expense_tag`
  ADD PRIMARY KEY (`tag_uuid`,`expense_uuid`),
  ADD KEY `fk-expense_tag-expense_uuid` (`expense_uuid`);

--
-- Indexes for table `extension`
--
ALTER TABLE `extension`
  ADD PRIMARY KEY (`extension_uuid`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`loan_uuid`),
  ADD KEY `idx-loan-user_uuid` (`user_uuid`);

--
-- Indexes for table `migration`
--
ALTER TABLE `migration`
  ADD PRIMARY KEY (`version`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_uuid`),
  ADD KEY `idx-payment-user_uuid` (`user_uuid`),
  ADD KEY `ind-payment-payment_gateway_transaction_id` (`payment_gateway_transaction_id`);

--
-- Indexes for table `setting`
--
ALTER TABLE `setting`
  ADD PRIMARY KEY (`setting_uuid`),
  ADD KEY `fk-setting-user_uuid` (`user_uuid`);

--
-- Indexes for table `subscription`
--
ALTER TABLE `subscription`
  ADD PRIMARY KEY (`subscription_uuid`);

--
-- Indexes for table `subscription_tag`
--
ALTER TABLE `subscription_tag`
  ADD PRIMARY KEY (`tag_uuid`,`subscription_uuid`),
  ADD KEY `idx-subscription_tag-tag_uuid` (`tag_uuid`),
  ADD KEY `idx-subscription_tag-subscription_uuid` (`subscription_uuid`);

--
-- Indexes for table `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`tag_uuid`);

--
-- Indexes for table `transfer`
--
ALTER TABLE `transfer`
  ADD PRIMARY KEY (`transfer_uuid`),
  ADD KEY `fk-transfer-user_uuid` (`user_uuid`),
  ADD KEY `fk-transfer-bank_uuid` (`bank_uuid`),
  ADD KEY `idx-transfer-transfer_file_uuid` (`transfer_file_uuid`),
  ADD KEY `idx-transfer-transfer_uuid_short` (`transfer_uuid_short`);

--
-- Indexes for table `transfer_file`
--
ALTER TABLE `transfer_file`
  ADD PRIMARY KEY (`transfer_file_uuid`);

--
-- Indexes for table `transfer_file_entry`
--
ALTER TABLE `transfer_file_entry`
  ADD PRIMARY KEY (`tfe_uuid`),
  ADD KEY `idx-transfer_file_entry-created_by` (`created_by`),
  ADD KEY `idx-transfer_file_entry-updated_by` (`updated_by`),
  ADD KEY `idx-transfer_file_entry-transfer_file_uuid` (`transfer_file_uuid`),
  ADD KEY `idx-transfer_file_entry-debit_narrative` (`debit_narrative`),
  ADD KEY `idx-transfer_file_entry-credit_narrative` (`credit_narrative`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_uuid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`),
  ADD KEY `idx-setting-user_uuid` (`user_uuid`),
  ADD KEY `idx-transfer-user_uuid` (`user_uuid`),
  ADD KEY `idx-transfer-bank_uuid` (`bank_uuid`);

--
-- Indexes for table `user_token`
--
ALTER TABLE `user_token`
  ADD PRIMARY KEY (`token_uuid`),
  ADD KEY `idx-user_token-user_uuid` (`user_uuid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `currency`
--
ALTER TABLE `currency`
  MODIFY `currency_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_token`
--
ALTER TABLE `admin_token`
  ADD CONSTRAINT `fk-admin_token-admin_uuid` FOREIGN KEY (`admin_uuid`) REFERENCES `admin` (`admin_uuid`) ON DELETE CASCADE;

--
-- Constraints for table `balance_transaction`
--
ALTER TABLE `balance_transaction`
  ADD CONSTRAINT `fk-balance_transaction-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);

--
-- Constraints for table `balance_transaction_tag`
--
ALTER TABLE `balance_transaction_tag`
  ADD CONSTRAINT `fk-balance_transaction_tag-balance_transaction_uuid` FOREIGN KEY (`balance_transaction_uuid`) REFERENCES `balance_transaction` (`balance_transaction_uuid`),
  ADD CONSTRAINT `fk-balance_transaction_tag-tag_uuid` FOREIGN KEY (`tag_uuid`) REFERENCES `tag` (`tag_uuid`);

--
-- Constraints for table `expense_tag`
--
ALTER TABLE `expense_tag`
  ADD CONSTRAINT `fk-expense_tag-expense_uuid` FOREIGN KEY (`expense_uuid`) REFERENCES `expense` (`expense_uuid`),
  ADD CONSTRAINT `fk-expense_tag-tag_uuid` FOREIGN KEY (`tag_uuid`) REFERENCES `tag` (`tag_uuid`);

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `fk-loan-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk-payment-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);

--
-- Constraints for table `setting`
--
ALTER TABLE `setting`
  ADD CONSTRAINT `fk-setting-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);

--
-- Constraints for table `subscription_tag`
--
ALTER TABLE `subscription_tag`
  ADD CONSTRAINT `fk-subscription_tag-subscription_uuid` FOREIGN KEY (`subscription_uuid`) REFERENCES `subscription` (`subscription_uuid`),
  ADD CONSTRAINT `fk-subscription_tag-tag_uuid` FOREIGN KEY (`tag_uuid`) REFERENCES `tag` (`tag_uuid`);

--
-- Constraints for table `transfer`
--
ALTER TABLE `transfer`
  ADD CONSTRAINT `fk-transfer-bank_uuid` FOREIGN KEY (`bank_uuid`) REFERENCES `bank` (`bank_uuid`),
  ADD CONSTRAINT `fk-transfer-transfer_file_uuid` FOREIGN KEY (`transfer_file_uuid`) REFERENCES `transfer_file` (`transfer_file_uuid`),
  ADD CONSTRAINT `fk-transfer-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`);

--
-- Constraints for table `transfer_file_entry`
--
ALTER TABLE `transfer_file_entry`
  ADD CONSTRAINT `fk-transfer_file_entry-created_by` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_uuid`),
  ADD CONSTRAINT `fk-transfer_file_entry-credit_narrative` FOREIGN KEY (`credit_narrative`) REFERENCES `user` (`user_uuid`),
  ADD CONSTRAINT `fk-transfer_file_entry-debit_narrative` FOREIGN KEY (`debit_narrative`) REFERENCES `transfer` (`transfer_uuid`),
  ADD CONSTRAINT `fk-transfer_file_entry-transfer_file_uuid` FOREIGN KEY (`transfer_file_uuid`) REFERENCES `transfer_file` (`transfer_file_uuid`),
  ADD CONSTRAINT `fk-transfer_file_entry-updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`admin_uuid`);

--
-- Constraints for table `user_token`
--
ALTER TABLE `user_token`
  ADD CONSTRAINT `fk-user_token-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
