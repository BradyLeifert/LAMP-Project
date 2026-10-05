-- ============================================================
-- SQL Full Reset Script: resetdb.sql
-- Project: COP4331 LAMP Stack Demo (Colors Manager)
-- Description: Drops existing tables if present, recreates schema,
--              seeds users and colors, and sets up user permissions.
-- ============================================================

-- Create and select database
CREATE DATABASE IF NOT EXISTS `ContactsAppDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ContactsAppDB`;

-- Drop existing tables to ensure a clean state
DROP TABLE IF EXISTS `Contacts`;
DROP TABLE IF EXISTS `Users`;

-- 2. Create Users Table
CREATE TABLE IF NOT EXISTS `Users` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL DEFAULT '',
    `LastName` VARCHAR(50) NOT NULL DEFAULT '',
    `Username` VARCHAR(50) NOT NULL DEFAULT '',
    `Password` VARCHAR(50) NOT NULL DEFAULT '',
    `PasswordHash` VARCHAR(255) NOT NULL DEFAULT '',
    `Role` enum('User','Admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'User',
    `IsDisabled` tinyint(1) NOT NULL DEFAULT '0',
   `Date Created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   `Date Updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    INDEX `idx_users_login` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `Contacts` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ProfilePicture` LONGBLOB NULL DEFAULT NULL,
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

INSERT INTO `Users` VALUES (1,'Rick','Leinecker','RickL','COP4331','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),
(2,'Sam','Hill','SamH','Test','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),
(3,'Alex','Smith','AlexS','OOP','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),
(4,'Rick','Leinecker','RickL_MD5','5832a71366768098cceb7095efb774f2','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),
(5,'Sam','Hill','SamH_MD5','0cbc6611f5540bd0809a388dc95a615b','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),
(6,'Alex','Smith','AlexS_MD5','e628e41ca122e4f795675f1cefa91ddd','2026-09-10 01:55:33','2026-09-10 01:55:33','User',0),
(7,'John','Doe','johndoe','34819d7beeabb9260a5c854bc85b3e44','2026-09-18 14:53:12','2026-09-18 14:53:12','User',0),
(8,'Johntwo','Doe','johndoetwo','34819d7beeabb9260a5c854bc85b3e44','2026-09-18 17:30:33','2026-09-18 17:30:33','User',0),
(9,'root','Application','Administrator','admin','2026-09-24 17:30:33','2026-09-24 17:30:33','Admin',0);

INSERT INTO `Contacts` VALUES (1,1,'John','Smith','555-1234','john.doe@gmail.com','2026-09-10 03:05:46','2026-09-10 03:05:46'),
(2,1,'Bob','Baker','555-5678','bob.baker@yahoo.com','2026-09-10 03:05:46','2026-09-10 03:05:46'),
(3,2,'Alex','Knight','555-9876','alex.k@ucf.edu','2026-09-10 03:05:46','2026-09-10 03:05:46'),
(5,1,'addTest','lastname','407-1234','test@ucf.edu','2026-09-28 16:26:43','2026-09-28 16:26:43');

-- 4. Create Application Database User & Grant Permissions
-- Note: Replace password if desired for custom deployments.
CREATE USER IF NOT EXISTS 'ContactsAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactsAppDB`.* TO 'ContactsAppUser'@'localhost';

-- Also allow connection from any host (useful for Docker containerization)
CREATE USER IF NOT EXISTS 'ContactsAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactsAppDB`.* TO 'ContactsAppUser'@'%';

FLUSH PRIVILEGES;
