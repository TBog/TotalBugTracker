-- phpMyAdmin SQL Dump
-- version 2.11.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 28, 2008 at 05:47 PM
-- Server version: 5.0.45
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `tbtracker`
--
CREATE DATABASE IF NOT EXISTS `tbtracker` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `tbtracker`;

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `appName` varchar(64) NOT NULL,
  `appDesc` varchar(64) NOT NULL,
  `isLocal` enum('Y','N') NOT NULL default 'Y',
  `appType` enum('bug','asset') NOT NULL default 'bug',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `apps`
--


-- --------------------------------------------------------

--
-- Table structure for table `asset_types`
--

CREATE TABLE `asset_types` (
  `ID` tinyint(3) unsigned NOT NULL auto_increment,
  `typeName` varchar(32) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `asset_types`
--

INSERT INTO `asset_types` (`ID`, `typeName`) VALUES
(1, 'Asset tip unu'),
(2, 'Asset tip doi'),
(3, 'asset tip trei'),
(4, 'asset tip patru'),
(5, 'asset tip cinci'),
(6, 'asset tip sase'),
(7, 'asset tip sapte');

-- --------------------------------------------------------

--
-- Table structure for table `frequency`
--

CREATE TABLE `frequency` (
  `ID` tinyint(4) NOT NULL auto_increment,
  `frequencyName` varchar(64) NOT NULL,
  `frequencyDesc` varchar(64) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `frequency`
--

INSERT INTO `frequency` (`ID`, `frequencyName`, `frequencyDesc`) VALUES
(1, 'Always', ''),
(2, 'Frequently', ''),
(3, 'Occasionally', ''),
(4, 'Twice', ''),
(5, 'Once', '');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `ID` tinyint(4) NOT NULL auto_increment,
  `groupName` varchar(64) NOT NULL,
  `canAssignTo` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`ID`, `groupName`, `canAssignTo`) VALUES
(1, 'Administrator', 'N'),
(2, 'Developer', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `platforms`
--

CREATE TABLE `platforms` (
  `ID` tinyint(3) unsigned NOT NULL COMMENT 'flags',
  `platformName` varchar(16) NOT NULL,
  `platformDesc` varchar(64) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `platforms`
--

INSERT INTO `platforms` (`ID`, `platformName`, `platformDesc`) VALUES
(1, 'Wii', 'Wii'),
(2, 'PSP', 'PlayStationPortable'),
(4, 'PS2', 'PlayStation2'),
(8, 'PS3', 'PlayStation3'),
(16, '360', 'XBOX360'),
(32, 'dx8', 'PC dx8'),
(64, 'dx9', 'PC dx9'),
(128, 'dx10', 'PC dx10');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `userID` int(10) unsigned NOT NULL,
  `profileName` varchar(32) NOT NULL,
  `profileType` enum('filter','display') NOT NULL,
  `profileData` text NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`ID`, `userID`, `profileName`, `profileType`, `profileData`) VALUES
(1, 1, 'My bugs', 'filter', 'a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:3:{i:0;s:10:"platformID";i:1;s:2:"!=";i:2;s:1:"1";}}}}}'),
(2, 1, 'My bugs display', 'display', 'a:2:{i:0;s:2:"ID";i:1;s:5:"title";}'),
(3, 2, 'My bugs', 'filter', 'a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:3:{i:0;s:8:"statusID";i:1;s:2:"!=";i:2;i:3;}}}}}'),
(4, 2, 'My bugs display', 'display', 'a:2:{i:0;s:2:"ID";i:1;s:5:"title";}');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `userID` int(10) unsigned NOT NULL,
  `sort` varchar(8) NOT NULL,
  `appID` int(10) unsigned NOT NULL default '0',
  `orderBy` varchar(32) NOT NULL,
  `filterID` int(10) unsigned NOT NULL,
  `displayID` int(10) unsigned NOT NULL,
  `bugsPerPage` smallint(5) unsigned NOT NULL default '2000',
  `emailProfile` int(10) unsigned NOT NULL,
  `CSS` varchar(128) NOT NULL default 'css/main.css',
  PRIMARY KEY  (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`userID`, `sort`, `appID`, `orderBy`, `filterID`, `displayID`, `bugsPerPage`, `emailProfile`, `CSS`) VALUES
(1, '', 0, '', 1, 2, 2000, 0, ''),
(2, '', 0, '', 3, 4, 2000, 0, 'css/main.css');

-- --------------------------------------------------------

--
-- Table structure for table `severity`
--

CREATE TABLE `severity` (
  `ID` tinyint(4) unsigned NOT NULL auto_increment,
  `priority` int(11) NOT NULL,
  `severityName` varchar(32) NOT NULL,
  `severityColor` varchar(16) NOT NULL,
  `severityDesc` varchar(64) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `severity`
--

INSERT INTO `severity` (`ID`, `priority`, `severityName`, `severityColor`, `severityDesc`) VALUES
(1, 0, 'S', '#da6a6a', ''),
(2, 10, 'A', '#da8a8a', ''),
(3, 20, 'B', '#daaaaa', ''),
(4, 30, 'C', '#dacaca', ''),
(5, 40, 'Comment', '#eaeaea', '');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `ID` tinyint(4) unsigned NOT NULL auto_increment,
  `statusName` varchar(32) NOT NULL,
  `statusDesc` varchar(64) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`ID`, `statusName`, `statusDesc`) VALUES
(1, 'Open', ''),
(2, 'ReOpen', ''),
(3, 'Closed', ''),
(4, 'Waived', ''),
(5, 'NMI', 'Need More Info'),
(6, 'WNF', 'Will Not Fix');

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `ID` tinyint(4) unsigned NOT NULL auto_increment,
  `typeName` varchar(32) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;

--
-- Dumping data for table `type`
--

INSERT INTO `type` (`ID`, `typeName`) VALUES
(1, 'General bug'),
(2, 'Scoring'),
(3, 'Gameplay'),
(4, 'Video/Graphic'),
(5, 'Technical check'),
(6, 'Crash'),
(7, '1st Party Standards'),
(8, 'Text'),
(9, 'Collision detection'),
(10, 'Audio/Sound'),
(11, 'Controls'),
(12, 'Menu/GUI'),
(13, 'Boundary'),
(14, 'Statistics'),
(15, 'Legal'),
(16, 'Peripheral'),
(17, 'Options'),
(18, 'Save Flow');

-- --------------------------------------------------------

--
-- Table structure for table `user_to_app`
--

CREATE TABLE `user_to_app` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `userID` int(10) unsigned NOT NULL,
  `appID` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `user_to_app`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `login` varchar(20) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(128) character set ascii collate ascii_bin NOT NULL,
  `session` varchar(36) default NULL,
  `groupID` tinyint(3) unsigned NOT NULL default '0',
  `lastLoginDate` int(10) unsigned NOT NULL default '0',
  `canAssignTo` enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID`, `login`, `name`, `email`, `password`, `session`, `groupID`, `lastLoginDate`, `canAssignTo`) VALUES
(1, 'Admin', 'Administrator', 'bogdant@funlabs.com', '1b52e50c9de1e7a1338ede0cc342cfff8dfb6b2092e14b3a02c5cfdca1070653', '085d0377-0eb0-102c-92a3-12bf89729fc5', 1, 1227885489, 'Y'),
(2, 'mihai', '', '', '1b52e50c9de1e7a1338ede0cc342cfff8dfb6b2092e14b3a02c5cfdca1070653', '4f33edee-0eb0-102c-92a3-12bf89729fc5', 1, 1227885608, 'Y');
