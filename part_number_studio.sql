-- MariaDB dump 10.19  Distrib 10.4.25-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: part_number_studio
-- ------------------------------------------------------
-- Server version	11.8.5-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `part_number_studio`
--

/*!40000 DROP DATABASE IF EXISTS `part_number_studio`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `part_number_studio` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `part_number_studio`;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_company_id_code_unique` (`company_id`,`code`),
  KEY `categories_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` (`id`, `company_id`, `code`, `name`, `note`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1,1,'MTR','Motor','Drive motors and sub-assemblies',1,1,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(2,1,'BRG','Bearing','Ball and roller bearings',1,2,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(3,1,'SHF','Shaft','Turned shafts and spindles',1,3,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(4,1,'HSG','Housing','Cast and machined housings',1,4,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(5,1,'GBX','Gearbox','Retired in 2024 — kept for old numbers',0,5,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(6,2,'CNC','CNC mill','3- and 5-axis milling',1,1,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(7,2,'LTH','Lathe','Turning centres',1,2,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(8,2,'PRS','Press','Stamping presses',1,3,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(9,2,'EDM','Wire EDM',NULL,1,4,'2026-09-10 08:20:16','2026-09-10 08:20:16');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(10) NOT NULL,
  `note` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` (`id`, `name`, `code`, `note`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Thai Part Industries','TPI','Machine part manufacturing',1,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(2,'Siam Precision Co.','SP','Precision turning and milling',1,'2026-09-10 08:20:16','2026-09-10 08:20:16');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_10_100000_create_part_number_studio_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_segments`
--

DROP TABLE IF EXISTS `template_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `template_segments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` enum('fixed','free') NOT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `separator` varchar(4) DEFAULT NULL,
  `fixed_value` varchar(40) DEFAULT NULL,
  `min_length` tinyint(3) unsigned DEFAULT NULL,
  `max_length` tinyint(3) unsigned DEFAULT NULL,
  `charset` enum('A-Z0-9','0-9','A-Z') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_segments_template_id_position_index` (`template_id`,`position`),
  CONSTRAINT `template_segments_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `template_segments`
--

LOCK TABLES `template_segments` WRITE;
/*!40000 ALTER TABLE `template_segments` DISABLE KEYS */;
INSERT INTO `template_segments` (`id`, `template_id`, `label`, `type`, `position`, `separator`, `fixed_value`, `min_length`, `max_length`, `charset`, `created_at`, `updated_at`) VALUES (1,1,'Company code','fixed',1,NULL,'TPI',NULL,NULL,NULL,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(2,1,'Category','free',2,NULL,NULL,3,3,'A-Z','2026-09-10 08:20:16','2026-09-10 08:20:16'),(3,1,'Sequence','free',3,NULL,NULL,4,4,'0-9','2026-09-10 08:20:16','2026-09-10 08:20:16'),(4,2,'Prefix','fixed',1,NULL,'RM',NULL,NULL,NULL,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(5,2,'Material','free',2,NULL,NULL,3,3,'A-Z','2026-09-10 08:20:16','2026-09-10 08:20:16'),(6,2,'Grade','free',3,'-',NULL,2,4,'A-Z0-9','2026-09-10 08:20:16','2026-09-10 08:20:16'),(7,3,'Company code','fixed',1,NULL,'SP',NULL,NULL,NULL,'2026-09-10 08:20:16','2026-09-13 04:23:12'),(8,3,'Drawing no.','free',2,NULL,NULL,6,6,'0-9','2026-09-10 08:20:16','2026-09-13 04:23:12'),(9,3,'Revision','free',3,NULL,NULL,1,3,'A-Z','2026-09-10 08:20:16','2026-09-13 04:23:12'),(18,5,'Fixed text','free',1,NULL,NULL,4,4,'0-9','2026-09-13 04:10:28','2026-09-14 05:15:37'),(19,5,'Free text','free',2,NULL,NULL,3,3,'0-9','2026-09-13 04:10:37','2026-09-14 05:15:37'),(20,5,'number','free',3,NULL,NULL,4,4,'0-9','2026-09-13 04:22:14','2026-09-14 05:15:37'),(21,6,'Prefix','fixed',1,NULL,'AWLFO',NULL,NULL,NULL,'2026-09-14 05:04:42','2026-09-14 05:25:26'),(22,6,'Free text','free',2,NULL,NULL,4,4,'0-9','2026-09-14 05:04:42','2026-09-14 05:25:26'),(23,6,'Free text','free',3,NULL,NULL,3,3,'0-9','2026-09-14 05:10:59','2026-09-14 05:25:26'),(45,14,'Prefix','fixed',1,NULL,'AWLRA',NULL,NULL,NULL,'2026-09-14 05:57:43','2026-09-14 06:01:10'),(46,14,'Free text','free',2,NULL,NULL,1,4,'A-Z0-9','2026-09-14 05:57:43','2026-09-14 06:01:10'),(47,14,'Free text','free',3,NULL,NULL,1,4,'A-Z0-9','2026-09-14 05:58:02','2026-09-14 06:01:10'),(53,15,'Prefix','fixed',1,NULL,'BFHF',NULL,NULL,NULL,'2026-09-14 08:11:38','2026-09-14 08:13:17'),(54,15,'Free text','free',2,NULL,NULL,5,5,'A-Z0-9','2026-09-14 08:11:38','2026-09-14 08:13:17'),(55,15,'Free text','free',3,NULL,NULL,1,1,'A-Z','2026-09-14 08:11:46','2026-09-14 08:13:17');
/*!40000 ALTER TABLE `template_segments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `templates`
--

DROP TABLE IF EXISTS `templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `separator` varchar(4) NOT NULL DEFAULT '-',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `templates_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `templates`
--

LOCK TABLES `templates` WRITE;
/*!40000 ALTER TABLE `templates` DISABLE KEYS */;
INSERT INTO `templates` (`id`, `company_id`, `name`, `code`, `note`, `separator`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1,1,'Finished goods','FG','Company prefix, category, then a sequence typed in by the user','-',1,1,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(2,1,'Raw materials','RM','Underscores by default, so this never reads like a finished part','_',1,2,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(3,2,'Drawing parts','DWG','Drawing number and revision, so drawings stay traceable','-',1,1,'2026-09-10 08:20:16','2026-09-10 08:20:16'),(5,2,'4-3-4','DWG','Drawing number and revision, so drawings stay traceable','-',1,2,'2026-09-13 03:59:55','2026-09-14 05:15:18'),(6,2,'AWLFO','AWLFO',NULL,'-',1,3,'2026-09-14 05:04:42','2026-09-14 05:10:32'),(14,2,'AWLRA','AWLRA',NULL,'-',1,4,'2026-09-14 05:57:43','2026-09-14 05:57:43'),(15,2,'BFHF','BFHF',NULL,'-',1,5,'2026-09-14 08:11:38','2026-09-14 08:11:38');
/*!40000 ALTER TABLE `templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-14 22:22:15
