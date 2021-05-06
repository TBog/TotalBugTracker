<?php
require_once 'inc/functions.inc.php';
register_mori_function('dumpdie');
function dumpdie($text, $debug)
{
	if ( !defined('CRLF') )
		define('CRLF', "\r\n");
	global $now;
	if ( !isset($now) )
		$now = time();
	if ( !empty($text) )
		$text = '<p class="error">'.$text.'</p>';
	$buffer = @ob_get_contents();
	if ( isset($GLOBALS['xajaxErrorHandlerText']) )
		$buffer.= $GLOBALS['xajaxErrorHandlerText'];
	$buffer.= $text;
	if ( !empty($buffer) )
	{
		$buffer.= CRLF.'<br>From IP: '.$_SERVER['REMOTE_ADDR'];
		$buffer.= CRLF.'<br>URI: '.$_SERVER['REQUEST_URI'];
		$buffer.= CRLF.'<br>Referer: '.$_SERVER['HTTP_REFERER'];
		$buffer.= CRLF.'<br>Request Method: '.$_SERVER['REQUEST_METHOD'];
		$buffer.= CRLF.'<pre>$_REQUEST = '.s_var_dump($_REQUEST).'</pre>';
		@file_put_contents('dumps/dump_'.date("Y_m_d_H_i_s", $now).'.htm', $buffer);
	}
	die($text.CRLF.'<hr>Dump saved');
}

require_once 'inc/xajax_core/xajax.inc.php';
# Set "call back to the server" page
$xajax = new xajax("ajax.server.php");
require_once 'inc/xajax_plugins/response/dhtmlHistory.inc.php';

// $xajax->configure('defaultMode', 'synchronous');
// $xajax->configure('debug', true);
$xajax->configure('errorHandler', true);
$xajax->configure('logFile', 'Dumps/ajax_dump.txt');

# Here are all the exported functions
$function_list = array(
	1=>'waypoint_handler',
	'load_style',
	'print_menu',
	'admin_login',
	'games',
	'nrpuzzle',
	'my_profiles',
	'rename_profile',
	'remove_profile',
	'admin_projects',
	'admin_groups',
	'edit_group',
	'edit_project',
	'admin_users',
	'user2app',
	'edit_user',
	'home_page',
	'my_details',
	'my_settings',
	'print_filters',
	'change_profile',
	'make_default_profile',
	'save_profile_as',
	'change_filter',
	'remove_filter',
	'add_filter',
	'print_display',
	'change_orderby',
	'change_display',
	'add_display_before',
	'remove_display',
	'print_bug_table',
	'print_bug',
	'view_bugs',
	'edit_bug',
	'edit_all',
	'close_bugs',
	'submit_bugs',
	'edit_multiple',
	'change_display_project',
	'add_bugs',
	'add_tasks',
	'search_bugs',
	'summary_bugs',
	'statistics',
);
/*/
# this can be used to export all user created functions
$function_list_before = get_defined_functions();
require_once 'ajax.functions.inc.php'; // this include must be removed from ajax.server.php in order for this method 2 work
$function_list_after = get_defined_functions();
$function_list = array_diff($function_list_after['user'], $function_list_before['user']);
/**/
foreach ($function_list as $function_name)
	$xajax->register(XAJAX_FUNCTION, $function_name);
?>