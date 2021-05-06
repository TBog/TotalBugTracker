<?php
require_once 'inc/functions.inc.php';

function print_header()
{
	require_once('inc/config.inc.php');
?><html>
<head>
<title>Login page</title>
<script type="text/javascript" src="inc/<?php echo FUNCTIONS_JS;?>"></script>
<link rel="stylesheet" type="text/css" href="css/main.css">
<!--[if lt IE 7]>
<link rel="stylesheet" type="text/css" href="css/main_ie.css">
<![endif]-->
</head>
<body onload="login_init()">
<table id="page_table">
<tr>
	<td colspan="2" id="table_logo">&nbsp;</td>
</tr>
</table>
<div id="div_logo"><img id="logo" src="img/header.png"></div>
<?php
}
//require_once ('inc/ajax.inc.php');
$error = '';
if ( !empty($_REQUEST['name']) )
{	
	$name = magicquotes($_REQUEST['name']);
	$pass = magicquotes($_REQUEST['pass']);
	if ( logMeIn($name, $pass) )
	{
		return true;
	}
	else
	{
		$error = '<p class="error">Login error</p>';
	}
}
if ( empty($_REQUEST['name']) || !empty($error) )
{
	print_header();
	echo $error;
?>
<form action="" method="POST">
<table class="login">
<tbody>
<tr><th>User name:</th><td><input type="text" name="name" id="login_name"></td></tr>
<tr><th>Password:</th><td><input type="password" name="pass"></td></tr>
</tbody>
<tfoot><tr><td colspan="2"><input type="submit" id="login_btn" value="Log me in"></td></tr></tfoot>
</table>
</form>
</body>
</html>
<?php
	return false;
}
?>