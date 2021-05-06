CREATE TABLE `assets%1$s` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `openDate` int(10) unsigned NOT NULL default '0',
  `lastEdit` int(10) unsigned NOT NULL default '0',
  `deadLineDate` int(10) unsigned NOT NULL default '0',
  `closeDate` int(10) unsigned NOT NULL default '0',
  `closedBy` int(10) unsigned NOT NULL default '0',
  `assToGroup` int(10) unsigned NOT NULL default '0',
  `assignedTo` int(10) unsigned NOT NULL default '0',
  `statusID` tinyint(3) unsigned NOT NULL default '0',
  `typeID` tinyint(3) unsigned NOT NULL default '0',
  `severityID` tinyint(3) unsigned NOT NULL default '0',
  `platformID` tinyint(3) unsigned NOT NULL default '0',
  `versionDate` int(10) unsigned NOT NULL default '0',
  `submitedBy` int(10) unsigned NOT NULL default '0',
  `submitedDate` int(10) unsigned NOT NULL default '0',
  `flags` int(10) unsigned NOT NULL default '0',
  `frequencyID` tinyint(3) unsigned NOT NULL default '0',
  `frequencyPercent` varchar(16) NOT NULL,
  `title` varchar(256) NOT NULL,
  `description` mediumtext NOT NULL,
  `info` mediumtext NOT NULL,
  `notes` mediumtext NOT NULL,
  `history` mediumtext NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1