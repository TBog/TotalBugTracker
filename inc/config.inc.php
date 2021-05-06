<?php
//Host of the computer that holds the database. if the same computer as the web-host, leave as "localhost".
define('DATABASE_HOST', 'localhost');
//The name of the database used
define('DATABASE', 'tbtracker');
//The username required to access the database
define('DATABASE_LOGIN', 'root');
//The password required to access the database.
define('DATABASE_PASSWORD', '');

//Name of the js functions file (to be easy to force users to redownload the js on change)
define('FUNCTIONS_JS', 'functions_v13.js');

// bug status
define('BUG_STATUS_OPEN',		1);
define('BUG_STATUS_REOPEN',		2);
define('BUG_STATUS_CLOSED',		3);
define('BUG_STATUS_WAIVED',		4);
define('BUG_STATUS_FINISHED',	9);

// bug flags
define('BUG_SUBMITED',		bindec('00000001'));
define('BUG_VIEWED',		bindec('00000010'));
define('BUG_MUSTFIX1',		bindec('00000100'));
define('BUG_MUSTFIX2',		bindec('00001000'));

// date format
define('DATE_HISTORY',		'D jS F Y H:i:s');
define('DATE_LOGIN',		'jS M Y G:i');
define('DATE_TIME',			'd.m.y G:i:s');
define('DATE_TIMELONG',		'D jS M y G:i:s');
define('DATE_NOTIME',		'D jS M y');
define('DATE_BUGVER',		'm/d/y');
define('JS_DATE_NOTIME',	'ddd dS mmm yyyy');
define('JS_DATE_BUGVER',	'mm/dd/yy');

// settings flags
define('SETTING_SENDMAIL',		bindec('00000001'));
define('SETTING_ALLPROFILES',	bindec('00000010'));
define('SETTING_STATUSCOLOR',	bindec('00000100'));
define('SETTING_CANEDIT',		bindec('00001000'));

// user privilege flags
define('GUEST',					bindec('00000001'));
define('CAN_EDIT',				bindec('00000010'));
define('CAN_BE_ASSIGNED',		bindec('00000100'));
define('CAN_CLOSE',				bindec('00001000'));
define('ADMINISTRATOR',			bindec('00010000'));
?>