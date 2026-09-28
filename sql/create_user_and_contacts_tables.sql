-- ===========================================================
-- Project: COP4331 LAMP Stack (Contacts Manager)
-- ============================================================

-- 1. Create and select the database
CREATE DATABASE IF NOT EXISTS `ContactsAppDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ContactsAppDB`;

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

-- 4. Create Application Database User & Grant Permissions
-- Note: Replace password if desired for custom deployments.
CREATE USER IF NOT EXISTS 'ContactsAppUser'@'localhost' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactsAppDB`.* TO 'ContactsAppUser'@'localhost';

-- Also allow connection from any host (useful for Docker containerization)
CREATE USER IF NOT EXISTS 'ContactsAppUser'@'%' IDENTIFIED BY 'WeLoveCOP4331!';
GRANT ALL PRIVILEGES ON `ContactsAppDB`.* TO 'ContactsAppUser'@'%';

FLUSH PRIVILEGES;
