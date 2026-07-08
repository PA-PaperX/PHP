-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: iya_db
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `borrows`
--

DROP TABLE IF EXISTS `borrows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `borrows` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `equipment_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `borrow_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `reason` text,
  `status` enum('pending','approved','pending_return','returned','rejected') DEFAULT 'pending',
  `admin_note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0',
  `is_read_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `borrows_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `borrows_ibfk_2` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrows`
--

LOCK TABLES `borrows` WRITE;
/*!40000 ALTER TABLE `borrows` DISABLE KEYS */;
INSERT INTO `borrows` VALUES (10,19,14,1,'2026-05-28','2026-05-31',NULL,'Need laptop for temporary remote work.','pending',NULL,'2026-05-28 11:08:16','2026-05-28 19:52:20',0,1),(11,21,10,3,'2026-05-28','2026-06-04',NULL,'ขอหน่อยไม่มีเงินซื้อ','approved',NULL,'2026-05-28 11:31:10','2026-05-29 09:15:34',1,0),(12,21,15,1,'2026-05-28','2026-05-29',NULL,'1','rejected',NULL,'2026-05-28 11:42:36','2026-05-29 09:15:05',1,0),(13,21,14,3,'2026-05-28','2026-05-31',NULL,'9','approved',NULL,'2026-05-28 11:43:08','2026-05-29 09:15:04',1,0),(14,21,16,1,'2026-05-28','2026-06-01','2026-05-28','6777','returned',NULL,'2026-05-28 11:49:38','2026-05-28 11:51:01',0,0),(15,24,16,1,'2026-05-29','2026-05-31',NULL,'dont khow','pending',NULL,'2026-05-29 09:32:07','2026-05-29 09:32:58',0,1),(16,22,12,1,'2026-05-29','2026-05-29',NULL,'อยากยืมมาใช้เรียน','approved','','2026-05-29 09:34:00','2026-05-29 10:07:46',0,1),(17,21,15,4,'2026-05-29','2026-05-31','2026-05-29','เอาไปจำนำ','returned','','2026-05-29 10:18:12','2026-05-29 10:21:25',0,1);
/*!40000 ALTER TABLE `borrows` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = latin1 */ ;
/*!50003 SET character_set_results = latin1 */ ;
/*!50003 SET collation_connection  = latin1_swedish_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_borrow_delete` AFTER DELETE ON `borrows` FOR EACH ROW BEGIN
    IF OLD.status IN ('pending', 'approved', 'pending_return') THEN
        UPDATE equipment SET available = available + OLD.quantity WHERE id = OLD.equipment_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text,
  `image_path` varchar(500) DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `available` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (10,'เมาส์','ฮาร์ดแวร์ (Hardware)','','/uploads/equip_6a15d393d420e.jpg',5,2,'2026-05-26 17:08:35','2026-05-28 19:43:37'),(12,'คีย์บอร์ด','ฮาร์ดแวร์ (Hardware)','พอใช้','/uploads/equip_6a16939175ff3.png',1,0,'2026-05-27 06:47:45','2026-05-29 09:34:00'),(14,'Test Laptop','ฮาร์ดแวร์ (Hardware)','A testing laptop',NULL,8,4,'2026-05-27 20:14:07','2026-05-28 19:43:37'),(15,'Test Laptop','อื่นๆ (Other)','A testing laptop',NULL,7,7,'2026-05-27 20:23:09','2026-05-29 10:21:25'),(16,'67','อื่นๆ (Other)','6767','/uploads/equip_6a182b8b7950f.jpg',1,0,'2026-05-28 11:48:27','2026-05-29 09:32:07'),(17,'12','เครือข่าย (Network)','',NULL,10,10,'2026-05-28 11:52:24','2026-05-28 11:52:24');
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issues`
--

DROP TABLE IF EXISTS `issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issues` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `description` text,
  `location` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','in_progress','resolved','closed') DEFAULT 'pending',
  `admin_note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `admin_image_path` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `admin_id` int DEFAULT NULL,
  `is_read_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `issues_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issues`
--

LOCK TABLES `issues` WRITE;
/*!40000 ALTER TABLE `issues` DISABLE KEYS */;
INSERT INTO `issues` VALUES (14,18,'1','hardware','adad','2',13.75598737,100.49960059,NULL,'resolved','','2026-05-28 10:10:39','2026-05-28 10:13:16',NULL,0,2,0),(15,19,'My laptop is broken','hardware','The screen is flickering.','Room 101',13.75630000,100.50171417,NULL,'closed','G','2026-05-28 11:07:45','2026-05-29 09:56:10',NULL,0,2,1),(16,21,'ไม่มีเงิน','network','ไม่มีเงินเติมเกม','ห้องมาร์ค',44.49775780,106.81261009,'/uploads/issue_6a18271ebadb4.jpg','closed','ไม่มีเหมือนกัน','2026-05-28 11:29:34','2026-05-28 11:33:06',NULL,1,20,0),(17,21,'ไม่ไปเรียน','other','นอนอยู่','บ้าน',13.75496610,100.50291580,NULL,'resolved','ตื่นให้ได้','2026-05-28 11:35:56','2026-05-29 09:15:21','/uploads/admin_issue_6a1828fea5730.jpg',1,20,0),(18,21,'1','hardware','1','1',13.75146456,100.50566238,NULL,'closed','...','2026-05-28 11:40:58','2026-05-29 09:15:06',NULL,1,20,0),(19,22,'คอม','software','กมดยไดนยาไม','11/2 บ้านบุ้ง',13.74687081,100.53712362,'/uploads/issue_6a195d60455b5.png','closed','ไม่รับครับขี้เกียจ','2026-05-29 09:33:20','2026-05-29 09:33:39',NULL,0,2,0),(20,24,'net dee ken','network','cant in IP','nawamin',49.28055624,30.92914244,NULL,'closed','ให้พ่อมึงมาซ่อมนะ','2026-05-29 09:33:26','2026-05-29 09:36:09',NULL,1,2,1),(21,26,'คอม57 มากเกินไป','hardware','ไม่ใช่ 67 รับไม่ได้','67',13.75317087,100.50948852,'/uploads/issue_6a195fe89238f.jpg','resolved','','2026-05-29 09:44:08','2026-05-29 09:47:10',NULL,0,2,1),(22,27,'คอมพิวเตอร์ ดิสรัน 100','hardware','ต้องการคอมใหม่','วิลัยมุก',13.75379893,100.48806709,NULL,'resolved','ไม่บอก','2026-05-29 10:04:58','2026-05-29 10:07:06',NULL,0,2,0),(23,21,'เน็ตไม่ดี','network','ทำงานไม่ได้','it02',16.54423160,104.72591638,NULL,'closed','กำลังหาพนักงานใหม่อยู่เพราะหน้าเก่าลาออกหมดแล้ว','2026-05-29 10:15:39','2026-05-29 10:18:43',NULL,1,27,1),(24,28,'จอดับ','other','ก้หด้ะะะะะะะะะะะะะะะะำพ','โต๊ะคอม',13.75025569,100.48783394,NULL,'closed','ว่ายน้ำไม่เป็น','2026-05-29 12:12:14','2026-05-29 12:13:49','/uploads/admin_issue_6a1982fd4be79.jpg',0,2,0);
/*!40000 ALTER TABLE `issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tickets`
--

DROP TABLE IF EXISTS `password_reset_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `status` enum('pending','resolved','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_read_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `access_token` (`access_token`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tickets`
--

LOCK TABLES `password_reset_tickets` WRITE;
/*!40000 ALTER TABLE `password_reset_tickets` DISABLE KEYS */;
INSERT INTO `password_reset_tickets` VALUES (1,'paperx@gmail.com','6b73e018c1b3889105bf362112827443908bd9e5466d7713d773bada9249cc38',NULL,NULL,'cancelled','2026-05-27 19:49:46','2026-05-27 20:01:42',0),(3,'testuser@gmail.com','72a1f8ef86cb87b5653112ff93db939cb12d6a5cf5ba77579613706f952671ac',NULL,NULL,'cancelled','2026-05-27 20:14:34','2026-05-28 10:13:31',0),(5,'aaaaaa@gmail.com','f3fe3d8afba44c48cbfe6e1446e6eb36cb0de3f8f849d75c2c16dac7e700a3c9',NULL,NULL,'cancelled','2026-05-29 09:17:41','2026-05-29 09:21:05',0),(7,'paperx@gmail.com','685bc1249308e833b71a30c57ebe2478e80871e37e15a0262bea24aaf2378f56',NULL,NULL,'cancelled','2026-05-29 10:14:15','2026-05-29 10:15:13',1),(8,'rave12345@gmail.com','094c95ea72cebc21eca934b58f05163fb1d6444106184c901cff09eb89e1a32e',NULL,NULL,'cancelled','2026-05-29 12:14:59','2026-05-29 12:15:58',0),(9,'denay@gmail.com','6032c8ad7ec8688bcbc17976ed6a9c31fdfdcc08dcb62870a87101e6e77e3b53',NULL,NULL,'pending','2026-06-01 15:03:23','2026-06-01 15:03:23',0);
/*!40000 ALTER TABLE `password_reset_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_type` enum('user','admin','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `password_reset_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
INSERT INTO `ticket_messages` VALUES (1,1,'system','Ticket created. Please wait for an admin to assist you.','2026-05-27 19:49:46'),(2,1,'user','I forgot my password','2026-05-27 19:49:59'),(7,1,'system','Admin ได้ทำการปฏิเสธคำขอของคุณแล้ว (เหตุผล: ไม่บอก)','2026-05-27 20:01:42'),(8,3,'system','Ticket created. Please wait for an admin to assist you.','2026-05-27 20:14:34'),(9,3,'system','Admin ได้ทำการปฏิเสธคำขอของคุณแล้ว (เหตุผล: ไม่บอก)','2026-05-28 10:13:31'),(15,5,'system','Ticket created. Please wait for an admin to assist you.','2026-05-29 09:17:41'),(16,5,'admin','ไม่ให้','2026-05-29 09:21:01'),(17,5,'system','Admin ได้ทำการปฏิเสธคำขอของคุณแล้ว (เหตุผล: ไม่บอก)','2026-05-29 09:21:05'),(20,7,'system','Ticket created. Please wait for an admin to assist you.','2026-05-29 10:14:15'),(21,7,'user','ผมลืมรหัสครับพี่','2026-05-29 10:14:34'),(22,7,'system','Admin ได้ทำการปฏิเสธคำขอของคุณแล้ว (เหตุผล: รหัสเก่าดีอยู่เเล้ว)','2026-05-29 10:15:13'),(23,8,'system','Ticket created. Please wait for an admin to assist you.','2026-05-29 12:14:59'),(24,8,'admin','ดีครับมีปัญหาอะไรครับ','2026-05-29 12:15:11'),(25,8,'user','อิ้ววว คุณเป็นใคร','2026-05-29 12:15:20'),(26,8,'admin','admin 6767','2026-05-29 12:15:32'),(27,8,'user','แม่ไม่ให้คุยกับคนแปลกหน้า','2026-05-29 12:15:37'),(28,8,'admin','เสียใจแบบนี้','2026-05-29 12:15:50'),(29,8,'user','สม','2026-05-29 12:15:55'),(30,8,'system','Admin ได้ทำการปฏิเสธคำขอของคุณแล้ว (เหตุผล: ไม่บอก)','2026-05-29 12:15:58'),(31,9,'system','Ticket created. Please wait for an admin to assist you.','2026-06-01 15:03:23');
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `username` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'paperx@gmail.com','$2y$10$eqAEziGErbKpibpDCHmXUe3b8g9.vjqK.wwgMXfM3cZ.5qXo2522G','admin','2026-05-26 15:28:59','paperx','/uploads/profiles/user_2_1779895948.jpg'),(18,'test@gmail.com','$2y$10$TOggOOo4y/ukHxHF7qqRaeRcaTRvIgb8bx8pbQ40m2Q6Me9gqnj5e','user','2026-05-28 10:10:04','test',NULL),(19,'testuser@email.com','$2y$10$fmUB/Bn3AXyQ3ISK2.7gb.Ek05.aahOpRaABBEWZiOMNIBW1XKJv2','user','2026-05-28 11:04:53','testuser',NULL),(20,'school.ocm@gmail.com','$2y$10$nEX61om35X7uesCBaENzC.0y3jfojU2BMjF8RmUpihX2k3ZB6g/Ou','admin','2026-05-28 11:14:39','arm','/uploads/profiles/profile_6a1964d63ea9e.png'),(21,'aaaaaa@gmail.com','$2y$10$2kvl6XNtyTzGrSMfsg9OGegCu0BpZf4Fy9LSGooK7VxwlyzQ3LbHK','user','2026-05-28 11:26:26','Kuy',NULL),(22,'puyyaporn2549@gmail.com','$2y$10$cEsGEx7FjjNJ.ZBscmVymO493rvOoHSsAf0ajCfwbjxGlQclLQcPK','user','2026-05-29 09:27:37','mimi',NULL),(23,'dsasad@gmail.com','$2y$10$Zyuzspr0d8ynUWtX4Uk37Oh7gmfgDATzwwFWJZnqtafLgG.2Tv6Re','user','2026-05-29 09:31:06','sadsad',NULL),(24,'asd123456@gmail.com','$2y$10$RRI.nr5nnFHX9GtIPNp.mOd4sNLcQS6eDy2snAO4npUuX6n/6gr/u','admin','2026-05-29 09:31:26','asd000a1',NULL),(25,'sixseven@gmail.com','$2y$10$1SztdFri5WK15vQE3qJJueQEZpnI/vzoT.47HwNF0z07Hf.EEBIoW','user','2026-05-29 09:36:53','sixseven',NULL),(26,'armarm555@gmail.67com','$2y$10$aJaX8n3WDmH/1SqiRDF4aO7Rl8s8m1/YCJmnk8hfZsyHRRiJk.b0S','user','2026-05-29 09:41:34','Eonaria ',NULL),(27,'Satayu2026@gmail.com','$2y$10$sCzsoQmktCmHkXojgrCXX.7tRoTGWoXCclkPp/RuEFKjWyhiT5Nkm','admin','2026-05-29 10:02:25','Satayu',NULL),(28,'rave12345@gmail.com','$2y$10$SKcrudAajPbaYdL6.3Mp6eitpovKX/W3IrNvurJ1XF2OgezAYQIKy','user','2026-05-29 11:07:40','ravee','/uploads/profiles/profile_6a1974438661a.gif'),(29,'denay@gmail.com','$2y$10$VmIpzxApknsFRjXaYkFB7eUkc9lk3J4NdrDLg3hZcNA9E/7kCin9W','user','2026-06-01 15:03:17','denay',NULL);
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

-- Dump completed on 2026-06-07 19:29:16
