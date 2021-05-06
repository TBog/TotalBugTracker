<?php
//Host of the computer that holds the database. if the same computer as the web-host, leave as "localhost".
define('DATABASE_HOST', 'localhost');

//The name of the database used
define('DATABASE', 'tbtracker');

//The username required to access the database
define('DATABASE_LOGIN', 'root');

//The password required to access the database.
define('DATABASE_PASSWORD', 'qweasdzxc');

// some other stuff
define('BUG_STATUS_OPEN',		1);
define('BUG_STATUS_REOPEN',		2);
define('BUG_STATUS_CLOSED',		3);
define('BUG_STATUS_WAIVED',		4);

define('GROUP_ADMIN',			1);
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
?>