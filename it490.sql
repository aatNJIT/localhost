-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 19, 2025 at 04:11 AM
-- Server version: 8.0.44-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it490`
--

-- --------------------------------------------------------

--
-- Table structure for table `Catalogs`
--

CREATE TABLE `Catalogs` (
  `CatalogID` int NOT NULL,
  `UserID` int NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Catalog_Comments`
--

CREATE TABLE `Catalog_Comments` (
  `CommentID` int NOT NULL,
  `CatalogID` int NOT NULL,
  `UserID` int NOT NULL,
  `Text` text NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Catalog_Games`
--

CREATE TABLE `Catalog_Games` (
  `CatalogID` int NOT NULL,
  `AppID` int NOT NULL,
  `Rating` int NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Catalog_Votes`
--

CREATE TABLE `Catalog_Votes` (
  `UserID` int NOT NULL,
  `CatalogID` int NOT NULL,
  `VoteType` enum('up','down') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Followers`
--

CREATE TABLE `Followers` (
  `FollowerID` int NOT NULL,
  `FollowedID` int NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Games`
--

CREATE TABLE `Games` (
  `AppID` int NOT NULL,
  `Name` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Tags` json DEFAULT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Messages`
--

CREATE TABLE `Messages` (
  `ID` int NOT NULL,
  `SenderID` int NOT NULL,
  `ReceiverID` int NOT NULL,
  `Text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Sessions`
--

CREATE TABLE `Sessions` (
  `SessionID` bigint NOT NULL,
  `UserID` int NOT NULL,
  `Created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `Tags`
-- (See below for the actual view)
--
CREATE TABLE `Tags` (
`AppID` int
,`Description` text
,`Name` varchar(512)
,`Tags` json
);

-- --------------------------------------------------------

--
-- Table structure for table `Users`
--

CREATE TABLE `Users` (
  `ID` int NOT NULL,
  `SteamID` bigint UNSIGNED DEFAULT NULL,
  `Username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `OTP` varchar(6) DEFAULT NULL,
  `OTP_Expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure for view `Tags`
--
DROP TABLE IF EXISTS `Tags`;

CREATE ALGORITHM=UNDEFINED DEFINER=`it490`@`%` SQL SECURITY DEFINER VIEW `Tags`  AS SELECT `Games`.`AppID` AS `AppID`, `Games`.`Name` AS `Name`, `Games`.`Tags` AS `Tags`, `Games`.`Description` AS `Description` FROM `Games` WHERE (`Games`.`Tags` is not null) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Catalogs`
--
ALTER TABLE `Catalogs`
  ADD PRIMARY KEY (`CatalogID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `Catalog_Comments`
--
ALTER TABLE `Catalog_Comments`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `CommentCatalogID` (`CatalogID`),
  ADD KEY `CommentUserID` (`UserID`);

--
-- Indexes for table `Catalog_Games`
--
ALTER TABLE `Catalog_Games`
  ADD PRIMARY KEY (`CatalogID`,`AppID`),
  ADD KEY `CatalogAppID` (`AppID`);

--
-- Indexes for table `Catalog_Votes`
--
ALTER TABLE `Catalog_Votes`
  ADD PRIMARY KEY (`UserID`,`CatalogID`);

--
-- Indexes for table `Followers`
--
ALTER TABLE `Followers`
  ADD PRIMARY KEY (`FollowerID`),
  ADD KEY `FollowedID` (`FollowedID`);

--
-- Indexes for table `Games`
--
ALTER TABLE `Games`
  ADD PRIMARY KEY (`AppID`);

--
-- Indexes for table `Messages`
--
ALTER TABLE `Messages`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `SenderIDConstraint` (`SenderID`),
  ADD KEY `ReceiverIDContraint` (`ReceiverID`);

--
-- Indexes for table `Sessions`
--
ALTER TABLE `Sessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `userID` (`UserID`);

--
-- Indexes for table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `SteamID` (`SteamID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Catalogs`
--
ALTER TABLE `Catalogs`
  MODIFY `CatalogID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Catalog_Comments`
--
ALTER TABLE `Catalog_Comments`
  MODIFY `CommentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Followers`
--
ALTER TABLE `Followers`
  MODIFY `FollowerID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Messages`
--
ALTER TABLE `Messages`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `Sessions`
--
ALTER TABLE `Sessions`
  MODIFY `SessionID` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8460065075645404042;

--
-- AUTO_INCREMENT for table `Users`
--
ALTER TABLE `Users`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Catalogs`
--
ALTER TABLE `Catalogs`
  ADD CONSTRAINT `UserID` FOREIGN KEY (`UserID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Catalog_Comments`
--
ALTER TABLE `Catalog_Comments`
  ADD CONSTRAINT `CommentCatalogID` FOREIGN KEY (`CatalogID`) REFERENCES `Catalogs` (`CatalogID`) ON DELETE CASCADE,
  ADD CONSTRAINT `CommentUserID` FOREIGN KEY (`UserID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `Catalog_Games`
--
ALTER TABLE `Catalog_Games`
  ADD CONSTRAINT `CatalogAppID` FOREIGN KEY (`AppID`) REFERENCES `Games` (`AppID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `CatalogID` FOREIGN KEY (`CatalogID`) REFERENCES `Catalogs` (`CatalogID`) ON DELETE CASCADE;

--
-- Constraints for table `Followers`
--
ALTER TABLE `Followers`
  ADD CONSTRAINT `FollowedID` FOREIGN KEY (`FollowedID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FollowerID` FOREIGN KEY (`FollowerID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `Messages`
--
ALTER TABLE `Messages`
  ADD CONSTRAINT `ReceiverIDContraint` FOREIGN KEY (`ReceiverID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `SenderIDConstraint` FOREIGN KEY (`SenderID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `Sessions`
--
ALTER TABLE `Sessions`
  ADD CONSTRAINT `SessionUserID` FOREIGN KEY (`UserID`) REFERENCES `Users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
