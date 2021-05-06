<?php
error_reporting(E_ALL | E_NOTICE | E_STRICT);
require_once 'inc/functions.inc.php';
$logged = checkIfLogged();
//var_dump($logged);
if ( !$logged )
{
	// header('Location: '.getThisPageURI('login.php'));
	// die();
	$success = include 'login.php';
	if ( !$success )
		die();
}
require_once 'inc/serverinfo.inc.php';
$old_err_level = error_reporting( (E_ALL | E_STRICT) ^ E_STRICT );
require_once 'inc/ajax.inc.php';
error_reporting($old_err_level);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php
if ( (!empty($_GET['bug'])) && (!empty($_GET['app'])) )
	if ( $_GET['app'][0] == 'b' )
		echo 'Bug '.$_GET['bug'];
	else
		echo 'Asset '.$_GET['bug'];
else
	echo SERVER_NAME;
?></title>
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
<META NAME="KEYWORDS" CONTENT="bug, tracker, track, bug tracker, TBog, activision">
<META NAME="DESCRIPTION" CONTENT="Funlabs bug tracker web solution">
<META HTTP-EQUIV="Author" CONTENT="TBog">
<!--<noscript>
<meta http-equiv="refresh" content="5;URL=noscript.html">
</noscript>-->
<?php
$xajax->printJavascript('inc');
?>
<script type="text/javascript">
/* <![CDATA[ */
xajax.callback.global.onRequest = function() {xajax.$('loading').style.display = 'block';}
xajax.callback.global.onComplete = function() {xajax.$('loading').style.display='none';}
var lsn = "<?php echo generateLSN(); ?>";
// var listdisplaymemory = new Object();
// var timer_counter = 0;
// var timer_handle;
/* ]]> */
</script>
<script type="text/javascript" src="inc/functions.js"></script>
<link rel="stylesheet" type="text/css" href="css/main.css">
<!--[if lt IE 7]>
<link rel="stylesheet" type="text/css" href="css/main_ie.css">
<![endif]-->
</head>
<body onload="init(<?php
if ( (!empty($_GET['bug'])) && (!empty($_GET['app'])) )
	echo $_GET['bug'].", '".$_GET['app']."'";
?>)" class="thebody">
<div id="loading"><img src="img/ajax-loader.gif" alt="Loading..."></div>
<table id="page_table">
<tr>
	<td colspan="2" id="table_logo">&nbsp;</td>
</tr><tr>
	<td id="table_menu">&nbsp;</td>
	<td id="details">details</td>
</tr>
</table>
<div id="div_logo"><img id="logo" src="img/header.png"></div>
<div id="menu">menu</div>
</body>
</html>