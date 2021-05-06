<?php
ob_start(); // just to be sure it's first :P
/*
TODO:
sa aiba un nume similar .csv-ul shi cu backupul de la baza de date
sa potzi alege pe ce proiect sa se faca update
*/
if ( !defined('CRLF') )
	define('CRLF', "\r\n");
####################################
### define some functions
####################################
function replace_inquotes(&$data, $quote, $find, $replace)
{
	$nLength = strlen($data);
	$bInQuotes = false;
	if ( strlen($find) == 1 )
		for ($j = 0; $j < $nLength; $j += 1)
		{
			if ( $data[$j] == $quote )
				$bInQuotes = !$bInQuotes;
			if ( $bInQuotes && ($data[$j] == $find) )
				$data[$j] = $replace;
		}
	elseif ( strlen($find) == 2 )
		for ($j = 0; $j < $nLength; $j += 1)
		{
			if ( $data[$j] == $quote )
				$bInQuotes = !$bInQuotes;
			if ( $bInQuotes )
			{
				if ( !isset($data[$j + 1]) )
					return 1;
				if ( ($data[$j] == $find[0]) && ($data[$j + 1] == $find[1]) )
				{
					$data[$j] = $replace[0];
					$data[$j + 1] = $replace[1];
				}
			}
		}
	else
		return 2; // not implemented feature ( it woud be very slow )
	return 0;
}
function csv2array(&$data, &$array, $comma=',', $quote='"', $newln=CRLF)
{
	$sReplaceNewLnWith = chr(0x1);
	$sReplaceCommaWith = chr(0x2);
	
	$sNewLnReplacement = '';
	for ($j = 0; $j < strlen($newln); $j += 1)
		$sNewLnReplacement.= $sReplaceNewLnWith;
		
	if ( $err = replace_inquotes($data, $quote, $newln, $sNewLnReplacement) )
		return $err;
	$array = explode($newln, $data);
	$nSize = count($array);
	for ( $i = 0; $i < $nSize; $i += 1 )
	{
		$row = &$array[$i];
		if ( empty($row) )
		{
			unset($array[$i]);
			continue;
		}
		
		if ( $err = replace_inquotes($row, $quote, $comma, $sReplaceCommaWith) )
			return $err;
		$row = explode($comma, $row);
		$nCols = count($row);
		for ($j = 0; $j < $nCols; $j += 1)
		{
			$cell = &$row[$j];
			if (!isset($cell) || empty($cell))
				continue;
			$cell = str_replace($sNewLnReplacement, $newln, $cell);
			$cell = str_replace($sReplaceCommaWith, $comma, $cell);
			$cell = str_replace($quote.$quote, $quote, $cell);
			if ( ($cell[0] == $quote ) && ($cell[strlen($cell) - 1] == $quote) )
				$cell = substr($cell, 1, -1);
			$cell = trim($cell);
		}
	}
	return 0;
}
/*
** DataBase related functions
*/
function dbdie($txt, $debug)
{
	if ( $debug )
		dumpdie(htmlspecialchars($txt).' error '.mysql_errno().':<br>'.mysql_error().'<br><pre>'.CRLF.htmlspecialchars(s_var_dump(debug_backtrace(), false)).'</pre>');
}
function dbconnect($debug = true)
{
	require_once('inc/config.inc.php');
	global $global_mysql_database_link;
	if ( (isset($global_mysql_database_link) && (!$global_mysql_database_link)) || (!isset($global_mysql_database_link)) )
		$global_mysql_database_link = @mysql_connect(DATABASE_HOST, DATABASE_LOGIN, DATABASE_PASSWORD) or dbdie('connect', $debug);
	mysql_select_db(DATABASE) or dbdie('DB select', $debug);
	return $global_mysql_database_link;
}
function dbq($query, $debug = true)
{
	global $global_mysql_database_link;
	global $global_mysql_database_query;
	$global_mysql_database_query = @mysql_query($query, $global_mysql_database_link) or dbdie('query <'.$query.'>', $debug);;
	return $global_mysql_database_query;
}
function dbrow($numeric = 0)
{
	global $global_mysql_database_query;
	if ( $numeric )
		return mysql_fetch_row($global_mysql_database_query);
	else
		return mysql_fetch_assoc($global_mysql_database_query);
}
function dbr($numeric = 0)
{
	global $global_mysql_database_query;
	$r = array();
	if ( $numeric )
		while ( $row = mysql_fetch_row($global_mysql_database_query) )
			$r[] = $row;
	else
		while ( $row = mysql_fetch_assoc($global_mysql_database_query) )
			$r[] = $row;
	return $r;
}
function dbesc($txt)
{
	return mysql_real_escape_string($txt);
}
/*
** Other functions
*/
function s_var_dump($info, $pre = false) {
	ob_start();
	if ($pre)
		echo '<pre>';
	var_dump($info);
	if ($pre)
		echo "</pre>";
	$txt = ob_get_contents();
	ob_end_clean();
	return $txt;
}
function print_header($body='')
{
?><html>
<head>
<title>Incident List to workbench</title>
<style type="text/css">
body {
	background-color: #a0aaa0;
	margin: 0px;
	padding: 0px;
	border: 0px;
}
h1 {
	background-color: #9999cc;
	color: black;
	font-size: 45px;
	font-family: Arial;
	font-weight: 900;
	text-align: center;
	letter-spacing: -3px;
	word-spacing: 10px;
	border-top: 1px solid black;
	border-bottom: 2px solid black;
	padding-top: 30px;
	padding-bottom: 0px;
	margin: 0px;
	margin-bottom: 10px;
}
.extraOptions {
	width: 99%;
	border: 0px solid black;
	padding: 5px;
	margin: 0px;
	margin-left: auto;
	margin-right: auto;
}
h2 {
	font-family: Courier;
	font-weight: 900;
	width: 100%;
	margin: 0px;
	padding: 0px;
	border 0px;
	border-bottom: 1px solid black;
}
input.text {
	width: 300px;
}
.center {
	text-align: center;
}
table, table tr, table td {
	border: 0px;
	margin: 0px;
	padding: 3px;
}
table {
	width: 100%;
	margin-left: auto;
	margin-right: auto;
	margin-top: 30px;
	margin-bottom: 30px;
	font-weight: bold;
	border-spacing: 0px;
}
table td.alignright {
	width: 40%;
	text-align: right;
}
table.extraOptions {
	border: 0px;
	margin: 5px;
	margin: 0px;
}
table.extraOptions th, table.extraOptions td {
/*	border-bottom: 1px solid black;*/
}
table.extraOptions th {
	text-align: center;
	border-right: 1px solid black;
	width: 5%;
	font-size: 15px;
}
table.extraOptions td {
	text-align: left;
	font-weight: 100;
}
hr {
	width: 99%;
	height: 3px;
	background-color: black;
}
hr.small {
	width: 60%;
	height: 1px;
}
em {
	font-weight: 100;
	font-size: 12px;
}
a {
	display: inline;
	font-size: 30px;
}
pre {
	margin: 5px;
	padding: 5px;
}
.hidden {
	display: none;
}
p.error {
	margin: 5px;
	padding: 5px;
	border-style: solid;
	border-width: 1px;
	border-color: red;
	background-color: pink;
	color: #990000;
	text-align: center;
}
.reopenedbug, .newbug, .waivedbug, .closedbug {
	display: none;
	border-left:1px solid black;
}
table.reopenedbug th, table.reopenedbug td, table.newbug th, table.newbug td, table.waivedbug th, table.waivedbug td, table.closedbug th, table.closedbug td {
	border: 0px transparent;
	padding: 3px;
	border-right: 1px solid black;
}
table.reopenedbug th, table.newbug th, table.waivedbug th, table.closedbug th {
	text-align: left;
	font-weight: 900;
}
table.reopenedbug td, table.newbug td, table.waivedbug td, table.closedbug td {
	text-align: left;
	font-weight: 100;
	border-top: 1px solid black;
	vertical-align: top;
}
table.reopenedbug, table.newbug, table.waivedbug, table.closedbug {
	border: 0px transparent;
	margin: 0px;
	padding: 0px;
	width: 100%;
	margin-left: 10px;
	border-left 1px solid black;
}
input:hover	{
	background-color: #9999cc;
	border-color: #9999cc;
	text-decoration: underline overline;
}
a:link		{font-color: white;		text-decoration: none;}
a:visited	{font-color: #ffd700;	text-decoration: none;}
a:hover		{font-color: yellow;	text-decoration: underline overline;}
}
</style>
<script language="JavaScript" type="text/javascript">
function showhide(obj)
{
	if ( obj.style.display == 'block' )
		obj.style.display = 'none';
	else
		obj.style.display = 'block';
}
function addOptionFields(obj)
{
	var theForm = document.options.elements; // the options form
	var submitForm = obj.elements; // the form that is about to get submited
	for (var i = 0; i < submitForm.length; i++)
		if ( (theForm[i].name == 'chkbackup') && (!theForm[i].checked) && (!confirm('Are you sure you do NOT want to use the backup feature ?')) )
			return false;

	for (var i = 0; i < theForm.length; i++)
	{
		/*
		new_element = document.createElement("input");
		new_element.setAttribute("type", "hidden");
		new_element.setAttribute("name", "element_name");
		new_element.setAttribute("id", "element_id");
		new_element.setAttribute("value", "element_value");
		document.forms['form_name'].appendChild(new_element);
		*/
		if ( theForm[i].name.length < 1 )
			continue;
		if ( !theForm[i].checked )
			continue;
		
		var newField = document.createElement("input");
		//newField.name = theForm[i].name;
		newField.setAttribute('name', theForm[i].name);
		/*if ( theForm[i].type == 'checkbox' )
		{*/
			//newField.type = 'checkbox';
			newField.setAttribute('type', theForm[i].type);
			//newField.checked = theForm[i].checked;
			newField.setAttribute('checked', theForm[i].checked);
		/*} else
		{
			//newField.type = "hidden";
			newField.setAttribute('type', 'hidden');
		}*/
		//newField.value = theForm[i].value;
		newField.setAttribute('value', theForm[i].value);
		newField.className = 'hidden';
		
		obj.appendChild(newField);
		
	}
	/*
	theForm = obj;
	for (var i = 0; i < theForm.length; i++)
		alert('field "' + theForm[i].name + '" of type "' + theForm[i].type + '" = "' + theForm[i].value + '" checked = ' + theForm[i].checked);
	*/
	return true;
}
</script>
</head>
<body <?=$body;?>>
<?php
}
function dumpdie()
{
	$args = func_get_args();
	global $now;
	$text = '';
	foreach ( $args as $arg )
		$text.= $arg;
	if ( !isset($now) )
		$now = time();
	if ( !empty($text) )
		$text = '<p class="error">'.$text.'</p>';
	$buffer = @ob_get_contents().$text;
	$size = 0;
	if ( !empty($buffer) )
	{
		$buffer.= CRLF.'<br>From IP: '.$_SERVER['REMOTE_ADDR'];
		$buffer.= CRLF.'<br>URI: '.$_SERVER['REQUEST_URI'];
		$buffer.= CRLF.'<br>Referer: '.$_SERVER['HTTP_REFERER'];
		$buffer.= CRLF.'<br>Request Method: '.$_SERVER['REQUEST_METHOD'];
		$buffer.= CRLF.'<pre>$_REQUEST = '.s_var_dump($_REQUEST).'</pre>';
		$size = @file_put_contents('Dumps/dump_'.date("Y_m_d_H_i_s", $now).'.htm', $buffer);
	}
	die($text.CRLF.'<hr>Dump saved ('.$size.' bytes)');
}
function transformToUnixTime($days)
{
	$days = trim($days);
	if ( empty($days) )
		return false;
	return ( ($days - 25569) * 24 * 60 * 60 ); // 25569 is a magic number (offset from 12/30/1899 to Unix Time)
}
function dump_bug(&$bug, &$tableHeader, $text, $htmlID, $cssClass)
{
	$ih = array_flip($tableHeader);
	$content = '<b>'.$text.':</b> <em onClick="showhide(getElementById(\''.$htmlID.'\'))">&lt;Click to show/hide details&gt;</em> ';
	$temp = '';
	if ( !is_array($bug) )
	{
		$sShowHideTag = 'pre';
		$temp.= s_var_dump($bug, false);
	}
	else
	{
		$sShowHideTag = 'table';
		$temp.= '<tr>';
		foreach ( array_keys($bug) as $arrKey )
			if ( isset($ih[$arrKey]) )
				$temp.= '<th>'.$ih[$arrKey].'</th>';
			else
				$temp.= '<th>'.$arrKey.'</th>';
		$temp.= '</tr><tr>';
		foreach ( $bug as $k=>$v )
		{
			$v = nl2br(htmlspecialchars(trim($v)));
			if ( empty($v) )
				$v = '&nbsp;';
			$temp.= '<td>'.$v.'</td>';
		}
		$temp.= '</tr>';
	}
	$content.= '<'.$sShowHideTag.' class="'.$cssClass.'" id="'.$htmlID.'">';
	$content.= $temp;
	$content.= '</'.$sShowHideTag.'>'.CRLF;
	return $content;
}
####################################
### the real code starts now
####################################
// include & set some stuff
error_reporting(E_ALL | E_NOTICE | E_STRICT);
ini_set('memory_limit', '80M');
set_time_limit(0);
$now = time();
$start_time = microtime(true);
$dir = '/data/';
$file_input = ''; // this is the default input file.
$file_backup	= 'backupDB_'.date("Y_m_d_H_i_s", $now).'.sql';
$file_output	= 'output_'.date("Y_m_d_H_i_s", $now).'.htm';
$file_csv		= 'input_'.date("Y_m_d_H_i_s", $now).'.csv';
$db_table		= 'bugs1';

if ( isset($_GET['i']) && !empty($_GET['i']) )
{
	$file_input = $_GET['i'];
	// if ( empty($_GET['t']) )
		// $file_input = '.'.$dir.$file_input;
	print_header();
	echo "<pre>";
}
elseif ( isset($_FILES['upfile']) )
{
	switch ( $_FILES['upfile']['error'] )
	{
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_INI_SIZE:
			print_header();
			dumpdie('The uploaded file exceeds the upload_max_filesize directive in php.ini');
			break;
		case UPLOAD_ERR_FORM_SIZE:
			print_header();
			dumpdie('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
			break;
		case UPLOAD_ERR_PARTIAL:
			print_header();
			dumpdie('The uploaded file was only partially uploaded');
			break;
		case UPLOAD_ERR_NO_FILE:
			print_header();
			dumpdie('No file was uploaded');
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			print_header();
			dumpdie('Missing a temporary folder');
			break;
		case UPLOAD_ERR_CANT_WRITE:
			print_header();
			dumpdie('Failed to write file to disk');
			break;
		case UPLOAD_ERR_EXTENSION:
			print_header();
			dumpdie('File upload stopped by extension');
			break;
		default:
			print_header();
			dumpdie('Unknown error ('.$_FILES['upfile']['error'].') in uploaded file');
			break;
		
	}
	$uploadfile = basename($_FILES['upfile']['name']);
	$uploadlocation = './'.trim($dir,'/\\').'/upload/';
/*	if ( file_exists($uploadlocation.$uploadfile) )
	{
		unlink($uploadlocation.$uploadfile); // delete the file
		// print_header();
		// die('<p class="error">The file "<b>'.$uploadfile.'</b>" in already on the disk. Please rename the file and upload again</p>');
	}
*/
	if (move_uploaded_file($_FILES['upfile']['tmp_name'], $uploadlocation.$uploadfile))
	{
		header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?t=1&i='.urlencode($uploadlocation.$uploadfile));
		print_header();
		die('File was successfully uploaded.');
	}
	else
	{
		print_header();
		dumpdie('Possible file upload attack!');
	}
}
else
{
//echo '<pre>';
//print_r($GLOBALS);
print_header();
require_once 'inc/serverinfo.inc.php';
echo "<h1>".SERVER_NAME." v".SERVER_VERSION." CSV import</h1>";
?>
<form enctype="multipart/form-data" action="<?=$_SERVER['PHP_SELF']?>" method="POST" onSubmit="return addOptionFields(this)">
<table>
<tr><td colspan="2" class="center">Import Method I <em>(select the file from your HDD)</em></td></tr>
<input class="hidden" type="hidden" name="MAX_FILE_SIZE" value="10485760">
<tr><td colspan="2" class="center"><hr class="small"></td></tr>
<tr><td class="alignright">File location:</td><td><input type="file" name="upfile"></td></tr>
<tr><td colspan="2" class="center"><hr class="small"></td></tr>
<tr><td colspan="2" class="center"><input type="submit" value="Start the download & update"></td></tr>
</table>
</form>
<hr>
<form action="<?=$_SERVER['PHP_SELF']?>" method="GET" onSubmit="return addOptionFields(this)">
<table>
<tr><td colspan="2" class="center">Import Method II <em>(upload to //<?=($_SERVER['SERVER_NAME'].'/'.$_SERVER['DOCUMENT_ROOT'].$dir)?> via LAN)</em></td></tr>
<input class="hidden" type="hidden" name="t" value="0">
<tr><td colspan="2" class="center"><hr class="small"></td></tr>
<tr><td class="alignright">File name only:</td><td><input type="text" class="text" name="i" value="<?=$file_input;?>"></td></tr>
<tr><td colspan="2" class="center"><hr class="small"></td></tr>
<tr><td colspan="2" class="center"><input type="submit" value="Start the update"></td></tr>
</table>
</form>
<div class="extraOptions">
<h2>Extra Options:<em>(may not work in IE, default is YES)</em></h2>
<form name="options">
<table class="extraOptions">
	<tr>
		<th>Yes / No</th>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<th><input type="radio" name="chkreopen" value="1" checked="checked"> / <input type="radio" name="chkreopen" value="0"></th>
		<td>Reopen bugs</td>
	</tr>
	<tr>
		<th><input type="radio" name="chkinsert" value="1" checked="checked"> / <input type="radio" name="chkinsert" value="0"></th>
		<td>Insert new bugs</td>
	</tr>
	<tr>
		<th><input type="radio" name="chkupdate" value="1" checked="checked"> / <input type="radio" name="chkupdate" value="0"></th>
		<td>Update bugs (frequency, class, description, notes)</td>
	</tr>
	<tr>
		<th><input type="radio" name="chkbackup" value="1" checked="checked"> / <input type="radio" name="chkbackup" value="0"></th>
		<td>Backup database</td>
	</tr>
</table>
</form>
</div>
</body>
</html><?php
die();
}
// check checkbox status after i'm sure the user submited a form
function resolve_radio_value($val)
{
	if ( !isset($_REQUEST[$val]) )
		return $_REQUEST[$val] = $_GET[$val] = $_POST[$val] = true;
	return $_REQUEST[$val] = $_GET[$val] = $_POST[$val] = (bool)$_REQUEST[$val];
}
$opt = array();
$opt['reopen'] = resolve_radio_value('chkreopen');
$opt['insert'] = resolve_radio_value('chkinsert');
$opt['update'] = resolve_radio_value('chkupdate');
$opt['backup'] = resolve_radio_value('chkbackup');
// echo "<pre>";
// var_export($_REQUEST);
// echo "</pre>";
// die();

// foreach ( $opt as $k=>$v )  $opt[$k] = true; // uncomment this to force all options ON

$file_backup = '.'.$dir.$file_backup;
$file_output = '.'.$dir.$file_output;
$file_csv = '.'.$dir.$file_csv;

if ( !file_exists($file_input) )
{
	// echo '<pre>';
	// echo '$_REQUEST = ';
	// var_dump($_REQUEST);
	dumpdie('No input file found. Maybe the upload did not work ?');
	// die();
}

// load file in memory
$data = @file_get_contents($file_input);
if ( empty($data) || empty($file_input) )
	dumpdie('Input file empty');
// transform file in array
$array = array();
$err = csv2array($data, $array);
unset($data);
if ($err)
	dumpdie('error returned by csv2array: '.$err);
//rename($file_input, $file_csv) or dumpdie('Coud not rename input file');
$time = microtime(true);
echo 'File loaded and parsed in '.($time - $start_time).' sec<br>'.CRLF;
$start_time = $time;

$nSize = count($array);
if ( $nSize < 7 )
	dumpdie('File is invalid. The file must have at least 7 rows');
unset($array[0]);	// patrick
unset($array[1]);	// empty
unset($array[2]);	// Records:
unset($array[3]);	// Project
$header = $array[4];
unset($array[4]);	// header
unset($array[5]);	// empty
unset($array[6]);	//  vDev13 Fun Labs (AMP Dev)
$start_row = 7;

// var_dump($array);
// die();

$h = array( // put here all required columns
'id'			=> -1,
'class'			=> -1,
'freq'			=> -1,
'build'			=> -1,
'loc'			=> -1,
'type'			=> -1,
'title'			=> -1,
'desc'			=> -1,
'SubProject'	=> -1,
'note'			=> -1,
'assdate'		=> -1,
'status'		=> -1,
);
$feedback = array(
'all'					=> 0,
'-- closed'				=> 0,
'---- closed_try'		=> 0,
'---- closed_done'		=> 0,
'---- waived_try'		=> 0,
'---- waived_done'		=> 0,
'-- dev-open'			=> 0,
'---- inserted_try'		=> 0,
'---- inserted_done'	=> 0,
'---- updated_try'		=> 0,
'---- updated_done'		=> 0,
'------ re-opened'		=> 0,
'-- other'				=> 0,
'---- only'				=> 0,
'ignored'				=> 0,
'-- empty'				=> 0,
'-- wrongID'			=> 0,
);
$continue = true; // skip bug if errors found (skip only one row). if false, break will be used (skip all the rest of the bugs)

if ( !is_array($header) )
	dumpdie('Corrupt header found in csv');

foreach ($header as $k=>$v)
{
	$v = trim(strtolower($v));
	switch ( $v )
	{
		case 'issue id':
			$h['id'] = $k;
			break;
		case 'class':
			$h['class'] = $k;
			break;
		case 'frequency':
			$h['freq'] = $k;
			break;
		case 'build version':
			$h['build'] = $k;
			break;
		case 'location':
			$h['loc'] = $k;
			break;
		case 'type':
			$h['type'] = $k;
			break;
		case 'title':
			$h['title'] = $k;
			break;
		case 'description':
			$h['desc'] = $k;
			break;
		case 'current status':
			$h['status'] = $k;
			break;
		case 'sub-project':
			$h['SubProject'] = $k;
			break;
		case 'notes':
			$h['note'] = $k;
			break;
		case 'date assigned';
			$h['assdate'] = $k;
			break;
	}
}
foreach ( $h as $k=>$v )
	if ( $v == -1 )
		dumpdie('Coud not find column "'.$k.'" in csv');
//echo '$h = ';
//var_dump($h);
/*
$bugtype = array();
for ($bug = &$array[$i = $start_row]; $i < $nSize; $bug = &$array[++$i])
{
	if ( empty($bug) || empty($bug[$h["id"]]) )
	{
		echo '<b><u>empty row</u>:</b> ';
		var_dump($bug);
		continue;
	}
	if ( isset($bugtype[strtolower($bug[$h['type']])]) )
		$bugtype[strtolower($bug[$h['type']])] += 1;
	else
		$bugtype[strtolower($bug[$h['type']])] = 0;
}
print_r($bugtype);
die();
*/

dbconnect();
####################################
if ( $opt['backup'] )
{
	//save database before update
	$fp = @fopen ($file_backup, "wb") or dumpdie('coud not open file stream for '.$file_backup);

	dbq("SELECT * FROM $db_table WHERE 1");
	$output2file = '-- backup of table `'.$db_table.'` on '.date("D F d Y H:i.s", time()).CRLF;
	$len = 0;
	$bFirst = true;
	$bHeaderOn = false;
	$bHeaderCreated = false;
	while ($bug = dbrow())
	{
		if ( !$bHeaderCreated )
		{
			$bHeaderCreated = true;
			$file_header = 'INSERT INTO `'.$db_table.'` (';
			foreach ( $bug as $fieldName=>$fieldContent )
				$file_header.= '`'.$fieldName.'`, ';
			$file_header = substr($file_header, 0, -2).') VALUES'.CRLF;
		}

		$temp = '(';
		foreach ( $bug as $fieldName=>$fieldContent )
			$temp.= "'".dbesc($fieldContent)."', ";
		$temp = substr($temp, 0, -2).')';
		if ( $bFirst || (($len + strlen($temp)) < 45000) )
		{
			$temp.= ','.CRLF;
			if ( !$bHeaderOn )
			{
				$bHeaderOn = true;
				$output2file.= $file_header;
				$len = strlen($file_header);
			}
			$output2file.= $temp;
			$len += strlen($temp);
			$bFirst = false;
		}
		else
		{
			$output2file.= $temp.';'.CRLF;
			fwrite($fp, $output2file, strlen($output2file));
			$output2file = '';
			$bHeaderOn = false;
			$len = 0;
		}
	}

	if ( !empty($output2file) )
	{
		$output2file = substr($output2file, 0, -3).';'.CRLF;
		fwrite($fp, $output2file, strlen($output2file));
	}
	@fclose($fp) or dumpdie('coud not close file stream');;
	//file_put_contents($file_backup, $output2file);
	unset($output2file);
	unset($file_header);
	echo 'DB backup took '.(microtime(true) - $start_time).' sec<br>'.CRLF;
	$start_time = microtime(true);
}
####################################
dbq('SELECT * FROM type');
$typeArr = array();
while ($row = dbrow(1))
	$typeArr[strtolower($row[1])] = $row[0];
####################################
dbq('SELECT * FROM frequency');
$frequencyArr = array();
while ($row = dbrow(1))
	$frequencyArr[strtolower($row[1])] = $row[0];
####################################
dbq('SELECT ID, severityName FROM severity');
$severityArr = array();
while ($row = dbrow(1))
	$severityArr[strtolower($row[1])] = $row[0];
####################################
dbq('SELECT * FROM platforms');
$platformsArr = array();
while ($row = dbrow(1))
	$platformsArr[strtolower($row[1])] = $row[0];
####################################
/*
dbq('SELECT ID, description, statusID, submitionDate FROM '.$db_table.' WHERE appID = 1'); // Paintball
$exists = array();
// $_descArr = array();
$_statusArr = array(); // this will contain all the bugs.
$_submitionArr = array(); // this will contain all the bugs.
while ($row = dbrow(1))
{
	$exists[$row[0]] = true;
	// $_descArr[$row[0]] = $row[1];
	$_statusArr[$row[0]] = $row[2];
	$_submitionArr[$row[0]] = $row[3];
}
*/
####################################
echo 'Some initializations '.(microtime(true) - $start_time).' sec<br>'.CRLF;
$start_time = microtime(true);
echo '<hr><u>Errors / Warnings / Details:</u><br>'.CRLF;
for ($i = $start_row; $i < $nSize; $i += 1 )
{
	$bug = &$array[$i];
	$feedback['all'] += 1;
	// echo '<hr>'.$i.'/'.$nSize.' ';
	// var_dump($array[$i]);
	if ( empty($bug) || empty($bug[$h["id"]]) )
	{
		$feedback['ignored'] += 1;
		$feedback['-- empty'] += 1;
		echo '<b><u>empty row</u>:</b> ';
		var_dump($bug);
		continue;
	}
	//echo '$bug = ';
	//var_dump($bug);
	//$bug[-1] = ''; // init this just in case a header is not found
	$bugID = (int)trim($bug[$h["id"]]);
	if ( empty($bugID) )
	{
		$feedback['ignored'] += 1;
		$feedback['-- wrongID'] += 1;
		echo '<b><u>wrong ID</u>:</b> ';
		var_dump($bug);
		continue;
	}
	if ( stripos($bug[$h['status']], 'closed -') !== false ) // contzine cuvantul closed
	{
		$feedback['-- closed'] += 1;
		if ( stripos($bug[$h['status']], 'waived') !== false ) // contzine cuvantul waived
		{
			$feedback['---- waived_try'] += 1;
			dbq("UPDATE $db_table SET flags=(flags & ".BUG_SUBMITED."), statusID=".BUG_STATUS_WAIVED.", history=CONCAT('".dbesc(date(DATE_HISTORY, $now)."\n<li>the status changed to Waived</li>\n")."',history)
				WHERE ID=$bugID statusID!=".BUG_STATUS_WAIVED);
			$feedback['---- waived_done'] += $x = mysql_affected_rows();
			if ( $x > 0 )
				echo dump_bug($bug, $h, 'waived '.$bugID, 'waivedbug'.$bugID, 'waivedbug');
		}
		elseif ( stripos($bug[$h['status']], 'dev') === false ) // NU contzine cuvantul dev
		{
			$feedback['---- closed_try'] += 1;
			dbq("UPDATE $db_table SET flags=(flags & ".BUG_SUBMITED."), statusID=".BUG_STATUS_CLOSED.", history=CONCAT('".dbesc(date(DATE_HISTORY, $now)."\n<li>the status changed to Closed</li>\n'").",history)
				WHERE ID=$bugID and ((flags & ".BUG_SUBMITED.")=0)");
			$feedback['---- closed_done'] += $x = mysql_affected_rows();
			if ( $x > 0 )
				echo dump_bug($bug, $h, 'closed '.$bugID, 'closedbug'.$bugID, 'closedbug');
		}
		continue;
	}
	if ( (stripos($bug[$h['status']], 'dev') === false) || (stripos($bug[$h['status']], 'open') === false) || (stripos($bug[$h['status']], 'only') !== false) )
	{
		$feedback['-- other'] += 1;
		if ( stripos($bug[$h['status']], 'only') !== false ) // contzine cuvantul only
			$feedback['---- only'] += 1;
		continue;
	}
	$feedback['-- dev-open'] += 1;
	$openDate = transformToUnixTime($bug[$h['assdate']]);
	if ( $openDate === false )
		if ( $continue ) 
		{
			$feedback['ignored'] += 1;
			echo '<p class="error">"date assigned" is empty<br>bug '.$bugID.' will be skipped</p>';
			continue;
		}
		else
		{
			echo '<p class="error">"date assigned" empty<br>bug '.$bugID.'</p>';
			break;
		}
	$openDate = (int)$openDate;
	$title = dbesc($bug[$h['title']]);
	$description = dbesc($bug[$h['desc']]);
	$notes = dbesc($bug[$h['note']]);
	if ( isset($typeArr[strtolower($bug[$h['type']])]) )
		$typeID = (int)$typeArr[strtolower($bug[$h['type']])];
	else
		if ( $continue ) 
		{
			$feedback['ignored'] += 1;
			echo '<p class="error">new bug type found: "'.$bug[$h['type']].'"<br>bug '.$bugID.' will be skipped</p>';
			continue;
		}
		else
		{
			echo '<p class="error">new bug type found: "'.$bug[$h['type']].'"<br>bug '.$bugID.'</p>';
			break;
		}
	if ( isset($severityArr[strtolower($bug[$h['class']])]) )
		$severityID = (int)$severityArr[strtolower($bug[$h['class']])];
	else
		if ( $continue ) 
		{
			$feedback['ignored'] += 1;
			echo '<p class="error">new severity found: "'.$bug[$h['class']].'"<br>bug '.$bugID.' will be skipped</p>';
			continue;
		}
		else
		{
			echo '<p class="error">new severity found: "'.$bug[$h['class']].'"<br>bug '.$bugID.'</p>';
			break;
		}
	$bug[$h['build']] = trim($bug[$h['build']]);
	$bug[$h['SubProject']] = trim($bug[$h['SubProject']]);
	$platform = array( substr($bug[$h['build']], 1 + strpos($bug[$h['build']], ' ')) , substr($bug[$h['SubProject']], 1 + strrpos($bug[$h['SubProject']], '\\')) );
	foreach ( $platform as $k=>$v )
	{
		if ( strpos($v, '360') !== false )
			$v = '360';
		$platform[$k] = strtolower(trim($v));
	}
	if ( stripos($bug[$h['SubProject']], '(pal)') !== false )
		$platform[1].= ' pal';
	if ( $platform[0] != $platform[1] )
		echo '<p class="error">just a WARNING: bug '.$bugID.' has two platforms<br>build platform = "'.$platform[0].'", SubProject platform = "'.$platform[1].'"</p>';
	$platform = $platform[1]; // take platform from $bug[$h['SubProject']]
	if ( isset($platformsArr[$platform]) )
		$platform = (int)$platformsArr[$platform];
	else
		if ( $continue ) 
		{
			$feedback['ignored'] += 1;
			echo '<p class="error">new platform found: "'.$platform.'"<br>bug '.$bugID.' will be skipped</p>';
			continue;
		}
		else
		{
			echo '<p class="error">new platform found: "'.$platform.'"<br>bug '.$bugID.'</p>';
			break;
		}
	$bug[$h['freq']] = trim($bug[$h['freq']]);
	if ( strpos($bug[$h['freq']], ' ') === false ) // no space found (i will assume it's 'once', 'twice', smth without percent)
	{
		$frequency = $bug[$h['freq']];
		$frequencyPercent = '';
	}
	else
	{
		$frequency = substr($bug[$h['freq']], 0, strpos($bug[$h['freq']], ' '));
		$frequencyPercent = dbesc(substr($bug[$h['freq']], 1 + strpos($bug[$h['freq']], ' ')));
	}
	if ( isset($frequencyArr[strtolower($frequency)]) )
		$frequency = (int)$frequencyArr[strtolower($frequency)];
	else
		if ( $continue ) 
		{
			$feedback['ignored'] += 1;
			echo '<p class="error">new frequency found: "'.$frequency.'"<br>bug '.$bugID.' will be skipped</p>';
			continue;
		}
		else
		{
			echo '<p class="error">new frequency found: "'.$frequency.'"<br>bug '.$bugID.'</p>';
			break;
		}
	$verDate = substr($bug[$h['build']], 0, strpos($bug[$h['build']], ' ') );
	$date = explode('/', $verDate);
	if ( (strlen($verDate) < 8) || (count($date) != 3) || (!gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2])) )
		if ( $continue ) 
		{
			$feedback['ignored'] += 1;
			echo '<p class="error">build version incorrect: "'.$verDate.'"<br>bug '.$bugID.' will be skipped</p>';
			continue;
		}
		else
		{
			echo '<p class="error">build version incorrect: "'.$verDate.'"<br>bug '.$bugID.'</p>';
			break;
		}
	$verDate = gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2]);
	unset($date);
	
	$bug_found = false;
	if ( $opt['insert'] )
	{
		$history = dbesc(date(DATE_HISTORY, $now).' bug inserted in database');
		$q = dbq("INSERT INTO $db_table(ID, openDate, statusID, typeID, severityID, platformID, versionDate, frequencyID, frequencyPercent, title, description, notes, history)
			VALUES ($bugID, $openDate, ".BUG_STATUS_OPEN.", $typeID, $severityID, $platform, $verDate, $frequency, '$frequencyPercent', '$title', '$description', '$notes', '$history')", false);
		if ( (!$q) && (mysql_errno() == 1062) ) // Duplicate entry ( bug found in database )
			$bug_found = true;
		elseif ( !$q )
			dbdie('insert failed for bug '.$bugID);
		else
		{
			$feedback['---- inserted_try'] += 1;
			$x = mysql_affected_rows();
			if ( $x > 0 )
			{
				echo dump_bug($bug, $h, 'inserted '.$bugID, 'newbug'.$bugID, 'newbug');
				$feedback['---- inserted_done'] += $x;
			}
		}
	}
	if ( $bug_found )
	{
		$history = false;
		if ( $opt['reopen'] )
		{
			dbq("UPDATE $db_table SET platformID=$platform, flags=(flags & (~".BUG_VIEWED.") & (~".BUG_SUBMITED.")), statusID=".BUG_STATUS_REOPEN.", history=CONCAT('".dbesc("<li>the status is now ReOpen</li>\n")."',history), notes='$notes' WHERE ID=$bugID and ((flags & ".BUG_SUBMITED.")!= 0)");
			$x = mysql_affected_rows();
			if ( $x > 0 )
			{
				echo dump_bug($bug, $h, 're-opened '.$bugID, 'reopenedbug'.$bugID, 'reopenedbug');
				$feedback['------ re-opened'] += $x;
				$history = true;
			}
		}
		if ( $opt['update'] )
		{
			// update: description, frequency, ...
			$feedback['---- updated_try'] += 1;
			dbq("UPDATE $db_table SET typeID=$typeID, platformID=$platform, severityID=$severityID , frequencyID=$frequency, frequencyPercent='$frequencyPercent', description='$description', notes='$notes' WHERE ID=$bugID and statusID!=".BUG_STATUS_CLOSED);
			$x = mysql_affected_rows();
			if ( $x > 0 )
			{
				$feedback['---- updated_done'] += $x;
				dbq("UPDATE $db_table SET history=CONCAT('".dbesc('<li>bug updated, possible changes: type, platform, severity, frequency, description, notes</li>'.($history?'':"\n"))."',history) WHERE ID=$bugID");
				if ( mysql_affected_rows() > 0 )
					$history = true;
			}
		}
		if ( $history )
			dbq("UPDATE $db_table SET history=CONCAT('".dbesc(date(DATE_HISTORY, $now)."\n")."',history) WHERE ID=$bugID");
	}
}
$time = microtime(true);
echo '<hr>DB insertion took '.($time - $start_time).' sec<hr>'.CRLF;
$start_time = $time;
print_r($feedback);
echo '</pre></body></html>';
file_put_contents($file_output, ob_get_contents());
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
header("Location: http://$host$uri/$file_output");
ob_end_clean();//ob_end_flush();
?>