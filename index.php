<?php
error_reporting(E_ALL | E_NOTICE | E_STRICT);
require_once 'inc/serverinfo.inc.php';
require_once 'inc/functions.inc.php';
$logged = checkIfLogged();
//var_dump($logged);
if ( !$logged && !MAINTANANCE )
{
	// header('Location: '.getThisPageURI('login.php'));
	// die();
	$success = include 'login.php';
	if ( !$success )
		die();
}
$old_err_level = error_reporting( (E_ALL | E_STRICT) ^ E_STRICT );
require_once 'inc/ajax.inc.php';
error_reporting($old_err_level);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php
if ( MAINTANANCE )
	echo 'Maintanance';
elseif ( (!empty($_GET['bug'])) && (!empty($_GET['app'])) )
	if ( $_GET['app'][0] == 'b' )
		echo 'Bug '.$_GET['bug'];
	else
		echo 'Asset '.$_GET['bug'];
else
	echo SERVER_NAME;
?></title>
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
<META NAME="KEYWORDS" CONTENT="bug, tracker, track, bug tracker, bug track, TBog, FunLabs">
<META NAME="DESCRIPTION" CONTENT="FunLabs bug tracker web solution">
<META HTTP-EQUIV="Author" CONTENT="TBog">
<!--<noscript>
<meta http-equiv="refresh" content="5;URL=noscript.html">
</noscript>-->
<?php
if ( MAINTANANCE )
{
?>
<link rel="stylesheet" type="text/css" href="css/main.css">
</head>
<body>
<table id="page_table">
<tr>
	<td id="table_logo"><div id="div_logo"><img id="logo" src="img/header.png"></div></td>
</tr><tr>
	<td id="details"><p class="maintanance">Under Maintanance. Come back in 5 minutes.</p></td>
</tr>
</table>
</body>
</html>
<?php
	die();
}
$xajax->printJavascript('inc');
?>
<script type="text/javascript">
/* <![CDATA[ */
xajax.callback.global.onRequest = function() {xajax.$('loading').style.display='block';}
xajax.callback.global.onComplete = function() {xajax.$('loading').style.display='none';}
lsn = "<?php echo generateLSN(); ?>";
/* ]]> */
</script>
<script type="text/javascript" src="inc/<?php echo FUNCTIONS_JS;?>"></script>
<script type="text/javascript" src="inc/date.format.js"></script>
<script type="text/javascript" src="inc/datepickercontrol.js"></script>
<link type="text/css" rel="stylesheet" href="css/datepickercontrol.css"> 
<!--<link rel="stylesheet" type="text/css" href="css/main.css">-->
<!--[if lte IE 7]>
<link rel="stylesheet" type="text/css" href="css/main_ie.css">
<![endif]-->
</head>
<body onload="init(<?php
if ( !empty($_GET['app']) )
{
	if ( !empty($_GET['bug']) )
		echo "$_GET[bug], 'b$_GET[app]'";
	elseif ( !empty($_GET['task']) )
		echo "$_GET[task], 'a$_GET[app]'";
}
?>)" class="thebody">
<table id="page_table">
<tr>
	<td colspan="2" id="table_logo"><div id="div_logo"><img id="logo" src="img/header.png"></div></td>
</tr><tr>
	<td id="table_menu"><div id="menu">&nbsp;</div><img src="img/transparent.png" width="95px"></td>
	<td id="details">&nbsp;</td>
</tr>
</table>
<div id="loading"><img src="img/ajax-loader.gif" alt="Loading..."></div>
</body>
</html>