-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: ContactsAppDB
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.4

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
-- Table structure for table `Contacts`
--

DROP TABLE IF EXISTS `Contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Contacts` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL DEFAULT '0',
  `FirstName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `DateCreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DateUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_contacts_userid` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Contacts`
--

LOCK TABLES `Contacts` WRITE;
/*!40000 ALTER TABLE `Contacts` DISABLE KEYS */;
INSERT INTO `Contacts` VALUES (1,1,'John','Smith','555-1234','john.doe@gmail.com','2026-09-10 03:05:46','2026-09-10 03:05:46'),(2,1,'Bob','Baker','555-5678','bob.baker@yahoo.com','2026-09-10 03:05:46','2026-09-10 03:05:46'),(3,2,'Alex','Knight','555-9876','alex.k@ucf.edu','2026-09-10 03:05:46','2026-09-10 03:05:46'),(5,1,'addTest','lastname','407-1234','test@ucf.edu','2026-09-28 16:26:43','2026-09-28 16:26:43');
/*!40000 ALTER TABLE `Contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Users`
--

DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Users` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `LastName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Password` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `Date Created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Date Updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Role` enum('User','Admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'User',
  `IsDisabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `idx_users_login` (`Username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Users`
--

LOCK TABLES `Users` WRITE;
/*!40000 ALTER TABLE `Users` DISABLE KEYS */;
INSERT INTO `Users` VALUES (1,'Rick','Leinecker','RickL','COP4331','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),(2,'Sam','Hill','SamH','Test','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),(3,'Alex','Smith','AlexS','OOP','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),(4,'Rick','Leinecker','RickL_MD5','5832a71366768098cceb7095efb774f2','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),(5,'Sam','Hill','SamH_MD5','0cbc6611f5540bd0809a388dc95a615b','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),(6,'Alex','Smith','AlexS_MD5','e628e41ca122e4f795675f1cefa91ddd','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),(7,'John','Doe','johndoe','34819d7beeabb9260a5c854bc85b3e44','2026-09-18 14:53:12','2026-09-18 14:53:12','User',0),(8,'Johntwo','Doe','johndoetwo','34819d7beeabb9260a5c854bc85b3e44','2026-09-18 17:30:33','2026-09-18 17:30:33','User',0);
/*!40000 ALTER TABLE `Users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-28 22:10:14
