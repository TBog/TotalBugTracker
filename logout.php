<?php
require_once 'inc/functions.inc.php';
require_once 'inc/serverinfo.inc.php';
ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo SERVER_NAME; ?></title>
<META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
<META NAME="KEYWORDS" CONTENT="bug, tracker, track, bug tracker, TBog, activision">
<META NAME="DESCRIPTION" CONTENT="Funlabs bug tracker web page">
<META HTTP-EQUIV="Author" CONTENT="TBog">
<meta http-equiv="refresh" content="5;URL=<?php echo $redirect=getThisPageURI('/'); ?>">
</head>
<body>
<?php
$logged = checkIfLogged();
if ( $logged )
{
	if ( logMeOut() )
		echo 'you are logged out now';
	else
		echo 'error logging out';
}
else
	echo 'you are not logged in, cannot log out';
ob_end_flush();
?>
<br>
In 5 seconds you will be redirected to <a href="<?=$redirect;?>"><?=$redirect;?></a>
</body>
</html>