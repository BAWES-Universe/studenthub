
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- MySQL dump 10.13  Distrib 5.7.33, for Linux (x86_64)
--
-- Host: localhost    Database: wallet
-- ------------------------------------------------------
-- Server version	5.7.33-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+03:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

SET time_zone = "+03:00";

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` smallint(6) NOT NULL DEFAULT '10',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`admin_uuid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `balance_account`
--

DROP TABLE IF EXISTS `balance_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `balance_account` (
  `balance_account_uuid` char(60) NOT NULL,
  `account_uuid` char(60) NOT NULL,
  `type` char(60) NOT NULL COMMENT 'invoice, payment, provider, user',
  `balance` decimal(10,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`balance_account_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `balance_account`
--

LOCK TABLES `balance_account` WRITE;
/*!40000 ALTER TABLE `balance_account` DISABLE KEYS */;
INSERT INTO `balance_account` VALUES ('balance_account_7ecea5ac-a7cb-11ef-8332-0a706777eec4','user_26794516-1dc4-11ef-83b3-0a706777eec4','Payable_for_this_user_uuid',0.000);
/*!40000 ALTER TABLE `balance_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `balance_transaction`
--

DROP TABLE IF EXISTS `balance_transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  PRIMARY KEY (`balance_transaction_uuid`),
  KEY `idx-balance_transaction-user_uuid` (`user_uuid`),
  CONSTRAINT `fk-balance_transaction-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `balance_transaction`
--

LOCK TABLES `balance_transaction` WRITE;
/*!40000 ALTER TABLE `balance_transaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `balance_transaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank`
--

DROP TABLE IF EXISTS `bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank` (
  `bank_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `bank_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_iban_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_swift_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bank_transfer_type` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`bank_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank`
--

LOCK TABLES `bank` WRITE;
/*!40000 ALTER TABLE `bank` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migration`
--

DROP TABLE IF EXISTS `migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migration` (
  `version` varchar(180) NOT NULL,
  `apply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migration`
--

LOCK TABLES `migration` WRITE;
/*!40000 ALTER TABLE `migration` DISABLE KEYS */;
/*!40000 ALTER TABLE `migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `setting_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `code` varchar(128) COLLATE utf8_unicode_ci NOT NULL COMMENT 'module identifier',
  `key` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `serialized` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`setting_uuid`),
  KEY `fk-setting-user_uuid` (`user_uuid`),
  CONSTRAINT `fk-setting-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `setting`
--

LOCK TABLES `setting` WRITE;
/*!40000 ALTER TABLE `setting` DISABLE KEYS */;
/*!40000 ALTER TABLE `setting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer`
--

DROP TABLE IF EXISTS `transfer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfer` (
  `transfer_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
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
  `transfer_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`transfer_uuid`),
  KEY `fk-transfer-user_uuid` (`user_uuid`),
  KEY `fk-transfer-bank_uuid` (`bank_uuid`),
  KEY `idx-transfer-transfer_file_uuid` (`transfer_file_uuid`),
  CONSTRAINT `fk-transfer-bank_uuid` FOREIGN KEY (`bank_uuid`) REFERENCES `bank` (`bank_uuid`),
  CONSTRAINT `fk-transfer-transfer_file_uuid` FOREIGN KEY (`transfer_file_uuid`) REFERENCES `transfer_file` (`transfer_file_uuid`),
  CONSTRAINT `fk-transfer-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer`
--

LOCK TABLES `transfer` WRITE;
/*!40000 ALTER TABLE `transfer` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_file`
--

DROP TABLE IF EXISTS `transfer_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transfer_file` (
  `transfer_file_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `transfer_file_s3_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `transfer_amount` decimal(10,3) DEFAULT NULL,
  `transfer_file_created_at` datetime NOT NULL,
  `transfer_file_updated_at` datetime NOT NULL,
  PRIMARY KEY (`transfer_file_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_file`
--

LOCK TABLES `transfer_file` WRITE;
/*!40000 ALTER TABLE `transfer_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_file_entry`
--

DROP TABLE IF EXISTS `transfer_file_entry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`tfe_uuid`),
  KEY `idx-transfer_file_entry-created_by` (`created_by`),
  KEY `idx-transfer_file_entry-updated_by` (`updated_by`),
  KEY `idx-transfer_file_entry-transfer_file_uuid` (`transfer_file_uuid`),
  KEY `idx-transfer_file_entry-debit_narrative` (`debit_narrative`),
  KEY `idx-transfer_file_entry-credit_narrative` (`credit_narrative`),
  CONSTRAINT `fk-transfer_file_entry-created_by` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_uuid`),
  CONSTRAINT `fk-transfer_file_entry-credit_narrative` FOREIGN KEY (`credit_narrative`) REFERENCES `user` (`user_uuid`),
  CONSTRAINT `fk-transfer_file_entry-debit_narrative` FOREIGN KEY (`debit_narrative`) REFERENCES `transfer` (`transfer_uuid`),
  CONSTRAINT `fk-transfer_file_entry-transfer_file_uuid` FOREIGN KEY (`transfer_file_uuid`) REFERENCES `transfer_file` (`transfer_file_uuid`),
  CONSTRAINT `fk-transfer_file_entry-updated_by` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`admin_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_file_entry`
--

LOCK TABLES `transfer_file_entry` WRITE;
/*!40000 ALTER TABLE `transfer_file_entry` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_file_entry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
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
  PRIMARY KEY (`user_uuid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`),
  KEY `idx-setting-user_uuid` (`user_uuid`),
  KEY `idx-transfer-user_uuid` (`user_uuid`),
  KEY `idx-transfer-bank_uuid` (`bank_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES ('user_092e3a40-1d9e-11ef-83b3-0a706777eec4','Reem Test','O-f8mdS_OqDdSWUOEAug1Fuyf4og8a2c','$2y$13$gZR2tYU1miQgd6blp84lZeDD4ITuOSDYpAeqiqNNVk7QvWe.Fgk9O',NULL,'admin@admin.com',NULL,NULL,NULL,10,'2024-05-29 12:30:06','2024-05-29 12:30:06',NULL),('user_11db8a06-c2cd-11ef-8332-0a706777eec4','Abdulrahman  Alagha','Qoy10L1p-haMloTPQ0p7LfZfHDDuWxZ0','$2y$13$9ZLjgOZmtbEtVucxS4POFuGFN/5HPCRRNnBlgj09OzIX6mUwOWAfK',NULL,'abdulrahman.alagha@bawes.net',NULL,NULL,NULL,10,'2024-12-25 17:32:29','2024-12-25 17:32:29',NULL),('user_1dc84907-1757-11ef-83b3-0a706777eec4','Khalid almutawa','OtmgD4n8BI4DaCmXJAZJjYSWxSytt6ae','$2y$13$2jLJWbONp/TrYiIjsAPPpeF6I7ge6KtJIVoU7vPFn5gv.Jf7PYIf6',NULL,'khalid+test123@bawes.net',NULL,NULL,NULL,10,'2024-05-21 12:47:20','2024-05-21 12:47:20',NULL),('user_246432ad-1da5-11ef-83b3-0a706777eec4','Reem Test 2','XB2y-sxgThuTM0BPObM1LwyeB-yLyKeU','$2y$13$oG//mufr8GTeWGEFGHtxcOqLv3T561K6bHo607Pz8Pek/2T1Jw4RW',NULL,'reem20030505@gmail.com',NULL,NULL,NULL,10,'2024-05-29 13:20:58','2024-05-29 13:20:58',NULL),('user_26794516-1dc4-11ef-83b3-0a706777eec4','Reem Hasib','FRUOu6cYY3ZuDOIBjpEVnZbsnnfv1scF','$2y$13$tes3PAXau3gq0TBpVVrplOVWW5cnNbxYdHa2.veM1w67HFkKNHs/C',NULL,'reem.hasib@bawes.net',NULL,NULL,NULL,10,'2024-05-29 17:02:56','2024-05-29 17:02:56',NULL),('user_4becf7fe-6839-11ef-85a0-0a706777eec4','Reem Hasib Test 2','IE-GhDJIBgCh5S4C_59vupvfSsQMeps9','$2y$13$RYdbNiAyhbJ.uUBmKNjMQuyMmVsF.AhqgjFmo/Rz4/IyNAjp8CN3G',NULL,'componentsdoc@gmail.com',NULL,NULL,NULL,10,'2024-09-01 11:07:56','2024-09-01 11:07:56',NULL),('user_51fa19b5-abf6-11ef-8332-0a706777eec4','Abdulrahman Alagha','zWoiwhxQqOg-5prQ89D5GaysWwTYv9Cp','$2y$13$8GDDLgQ3wB1xB42Q5sbXOuUPmLB1pbKEV0U.YcAVghu4MQaapcfQy',NULL,'abode_alagha@hotmail.com',NULL,NULL,NULL,10,'2024-11-26 15:59:49','2024-11-26 15:59:49',NULL),('user_6e5b31d7-0d29-11ef-919a-0a706777eec4','Mohammed Sohan','z2OPLJz4jEFnf2XtnTpsvr1_ym0RZPqj','$2y$13$IviC6hlKZ06npmBDOPbbCeV5qY7szzenMnaqXDkYC0ro1DzemWHtS',NULL,'mdshn212@gmail.com',NULL,NULL,NULL,10,'2024-05-08 13:55:06','2024-05-08 13:55:06',NULL),('user_de28d4ab-0d28-11ef-919a-0a706777eec4','Mohammad Sohan','NHb2iOH4-BxX5xQhvb2k1jbL45SYH4yP','$2y$13$AAosXEvUeZRDdCcjq5GBHOa9v3M3K0wFWBawGsaMG5h7G4Br9dY5O',NULL,'mohammad.sohan@bawes.net',NULL,NULL,NULL,10,'2024-05-08 13:51:04','2024-05-08 13:51:04',NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_token`
--

DROP TABLE IF EXISTS `user_token`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_token` (
  `token_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `user_uuid` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `token_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token_device` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token_device_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `token_status` smallint(6) NOT NULL,
  `token_last_used_datetime` datetime DEFAULT NULL,
  `token_expiry_datetime` datetime DEFAULT NULL,
  `token_created_datetime` datetime NOT NULL,
  PRIMARY KEY (`token_uuid`),
  KEY `idx-user_token-user_uuid` (`user_uuid`),
  CONSTRAINT `fk-user_token-user_uuid` FOREIGN KEY (`user_uuid`) REFERENCES `user` (`user_uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_token`
--

LOCK TABLES `user_token` WRITE;
/*!40000 ALTER TABLE `user_token` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_token` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-01-17 17:30:48
