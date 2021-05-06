-- phpMyAdmin SQL Dump
-- version 2.11.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 10, 2009 at 10:02 AM
-- Server version: 5.0.45
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `tbtracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `appName` varchar(64) NOT NULL,
  `appDesc` varchar(64) NOT NULL,
  `isLocal` enum('Y','N') NOT NULL default 'Y',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `asset_types`
--

CREATE TABLE `asset_types` (
  `ID` tinyint(3) unsigned NOT NULL auto_increment,
  `typeName` varchar(32) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Dumping data for table `asset_types`
--

INSERT INTO `asset_types` (`ID`, `typeName`) VALUES
(1, 'Model texture shader'),
(2, 'Animation'),
(3, 'Animation set'),
(4, 'Next-gen tile texture'),
(5, 'Menu layouts'),
(6, 'Fx'),
(7, 'Sculpt & Texture'),
(8, 'Sculpt'),
(9, 'Texture'),
(10, 'Programming');

-- --------------------------------------------------------

--
-- Table structure for table `frequency`
--

CREATE TABLE `frequency` (
  `ID` tinyint(4) NOT NULL auto_increment,
  `frequencyName` varchar(64) NOT NULL,
  `frequencyDesc` varchar(64) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

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
  `shortName` varchar(3) NOT NULL,
  `canAssignTo` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`ID`, `groupName`, `shortName`, `canAssignTo`) VALUES
(1, 'Administrator', 'A', 'N'),
(2, 'Programming', 'PRG', 'Y'),
(3, '3D Artist', '3D', 'Y'),
(4, '2D Artist', '2D', 'Y'),
(5, 'Sound', 'SND', 'Y'),
(6, 'Level Design', 'LVL', 'Y'),
(7, 'Game Design', 'GD', 'Y'),
(8, 'Project Manager', 'PM', 'Y'),
(9, 'Engine&Tools', 'ENG', 'Y');

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
  `profileType` enum('bugfilter','bugdisplay','assetfilter','assetdisplay') NOT NULL,
  `profileData` text NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

INSERT INTO `profiles` (`ID`, `userID`, `profileName`, `profileType`, `profileData`) VALUES
(1, 1, 'Bugs', 'bugfilter', 'a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:3:{i:0;s:10:"assignedTo";i:1;s:1:"=";i:2;s:1:"0";}}}}}'),
(2, 1, 'Assets', 'assetfilter', 'a:1:{i:0;a:2:{i:0;s:3:"AND";i:1;a:0:{}}}'),
(3, 1, 'Bugs view', 'bugdisplay', 'a:15:{i:0;s:2:"ID";i:1;s:12:"platformName";i:2;s:11:"versionDate";i:3;s:13:"frequencyName";i:4;s:16:"frequencyPercent";i:5;s:5:"title";i:6;s:14:"assignedToName";i:7;s:12:"closedByName";i:8;s:12:"severityName";i:9;s:10:"statusName";i:10;s:8:"typeName";i:11;s:8:"openDate";i:12;s:9:"closeDate";i:13;s:12:"submitedDate";i:14;s:14:"assToGroupName";}'),
(4, 1, 'Tasks view', 'assetdisplay', 'a:9:{i:0;s:2:"ID";i:1;s:12:"severityName";i:2;s:12:"platformName";i:3;s:5:"title";i:4;s:8:"typeName";i:5;s:14:"assToGroupName";i:6;s:14:"assignedToName";i:7;s:10:"statusName";i:8;s:12:"deadLineDate";}');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `userID` int(10) unsigned NOT NULL,
  `appID` int(10) unsigned NOT NULL default '0',
  `orderBy` varchar(32) NOT NULL,
  `bugFilterID` int(10) unsigned NOT NULL,
  `assetFilterID` int(10) unsigned NOT NULL,
  `bugDisplayID` int(10) unsigned NOT NULL,
  `assetDisplayID` int(10) unsigned NOT NULL,
  `bugsPerPage` smallint(5) unsigned NOT NULL default '2000',
  `flags` int(10) unsigned NOT NULL default '0',
  `CSS` varchar(128) NOT NULL default 'css/main.css',
  PRIMARY KEY  (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `settings` (`userID`, `appID`, `orderBy`, `bugFilterID`, `assetFilterID`, `bugDisplayID`, `assetDisplayID`, `bugsPerPage`, `flags`, `CSS`) VALUES
(1, 0, '', 1, 2, 3, 4, 256, 2, 'css/main.css');

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

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
-- Table structure for table `statistics`
--

CREATE TABLE `statistics` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `statType` enum('hour','day','month','func_hour','func_day','func_month','user_hour','user_day','user_month','user_func') NOT NULL,
  `statInfo` varchar(32) NOT NULL,
  `statCount` int(10) unsigned NOT NULL default '1',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `ID` tinyint(4) unsigned NOT NULL auto_increment,
  `statusName` varchar(32) NOT NULL,
  `statusColor` varchar(16) NOT NULL,
  `statusDesc` varchar(64) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`ID`, `statusName`, `statusColor`, `statusDesc`) VALUES
(1, 'Open', '#da6a6a', ''),
(2, 'ReOpen', '#da6a6a', ''),
(3, 'Closed', '#eaeaea', ''),
(4, 'Waived', '#dacaca', ''),
(5, 'NMI', '#dacaca', 'Need More Info'),
(6, 'WNF', '#dacaca', 'Will Not Fix'),
(7, 'Posponed', '#dacaca', 'Work on asset is posponed'),
(8, 'Working', '#dacaca', ''),
(9, 'Finished', '#dacaca', 'To Verify');

-- --------------------------------------------------------

--
-- Table structure for table `status_history`
--

CREATE TABLE `status_history` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `time` int(10) unsigned NOT NULL,
  `appID` int(10) unsigned NOT NULL,
  `bugID` int(10) unsigned NOT NULL default '0',
  `assetID` int(10) unsigned NOT NULL default '0',
  `statusID` tinyint(3) unsigned NOT NULL,
  `userID` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `ID` tinyint(4) unsigned NOT NULL auto_increment,
  `typeName` varchar(32) NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

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
  `privilege` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

INSERT INTO `users` (`ID`, `login`, `name`, `email`, `password`, `session`, `groupID`, `lastLoginDate`, `privilege`) VALUES
(1, 'TBog', 'Tautu Bogdan', 'thetbog@gmail.com', '1b52e50c9de1e7a1338ede0cc342cfff8dfb6b2092e14b3a02c5cfdca1070653', 'logged out 09.02.09 17:53:49', 1, 1234194829, 30);
-- --------------------------------------------------------

--
-- Table structure for table `user_to_app`
--

CREATE TABLE `user_to_app` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `userID` int(10) unsigned NOT NULL,
  `appID` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
