<?php
if ( !defined('CRLF') )
	define('CRLF', "\r\n");
if (!defined('_FUNCTIONS_INC_PHP_'))
{
    define('_FUNCTIONS_INC_PHP_', true);
	
	if ( empty($_SERVER['HTTP_REFERER']) )
		$_SERVER['HTTP_REFERER'] = '';
	
	##################################################
	## dbconnect
	##################################################
	function dbconnect()
	{
		require_once 'inc/dbfunc.inc.php';
		db_connect();
	}
	##################################################
	## register_mori_function
	##################################################
	function register_mori_function($func)
	{
		global $global_mori_function_name;
		if ( empty($func) )
			$global_mori_function_name = 'mori';
		else
			$global_mori_function_name = $func;
	}
	global $global_mori_function_name;
	$global_mori_function_name = 'mori';
	##################################################
	## mori
	##################################################
	function mori($string, $debug = true)
	{
		global $global_mori_function_name;
		if ( strcmp($global_mori_function_name, 'mori') != 0 )
			return $global_mori_function_name($string, $debug);
		elseif ( $debug )
			echo $string;
		die();
	}
	##################################################
	## s_print_r
	## print_r to string
	##################################################
	function s_print_r($info, $pre = false) {
		ob_start();
		if ($pre)
			echo '<pre>';
		print_r($info);
		if ($pre)
			echo "</pre>";
		$txt = ob_get_contents();
		ob_end_clean();
		return $txt;
	}
	##################################################
	## s_var_dump
	## var_dump to string
	##################################################
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
	##################################################
	## writeToLog
	##################################################
	function writeToLog($text)
	{
		$file = @fopen('Dumps/log.txt', 'ab');
		@fwrite($file, $text.CRLF) or mori('cannot save log file');
		fclose($file);
	}
	##################################################
	## setCurrentSession
	##################################################
	function setCurrentSession($session, $forceCookie = false)
	{
		global $global_current_session;
		if ( !empty($_COOKIE['login']) )
		{
			$u = @unserialize(base64_decode($_COOKIE['login']));
			if ( (!is_array($u)) || (count($u) != 2) )
			{
				//writeToLog('$_COOKIE[login] = '.$_COOKIE['login']);
				$u = array('', 0);
			}
		}
		else
			$u = array('', 0);
		list ($login_session, $login_time) = $u;
		unset($u);
		$cookie = base64_encode(serialize(array($session, time())));
		if ( empty($_COOKIE['login']) || ($login_session != $session) || ($login_time < (time() - 60*60*12)) || $forceCookie )
		{
		/*
			if ( empty($_COOKIE['login']) )
				writeToLog('login cookie empty');
			if ( $login_session != $session )
				writeToLog("$login_session != $session");
			if ($login_time < (time() - 60*60*1))
				writeToLog('($login_time < (time() - 60*60*1))');
		*/
			if ( setcookie('login', $cookie, time() + 60*60*24) )
			{
				$_COOKIE['login'] = $cookie;
				$global_current_session = $session;
				$global_current_session_login_time = time();
				//writeToLog('cookies sent on '.date('Y m d H:i:s').' for session '.$session.' IP: '.$_SERVER['REMOTE_ADDR'].' Referer: '.$_SERVER['HTTP_REFERER']);
				return true;
			}
		}
		else
		{
			$_COOKIE['login'] = $cookie;
			$global_current_session = '';
			return ( getCurrentSession() == $login_session );
		}
		return false;
	}
	##################################################
	## getCurrentSession
	##################################################
	function getCurrentSession()
	{
		global $global_current_session;
		global $global_current_session_login_time;
		if ( !empty($global_current_session) )
			return $global_current_session;
		elseif ( !empty($_COOKIE['login']) )
		{
			$u = @unserialize(base64_decode($_COOKIE['login']));
			if ( (!is_array($u)) || (count($u) != 2) )
				return false;
			list ($login_session, $login_time) = $u;
			unset($u);
			$global_current_session = $login_session;
			$global_current_session_login_time = $login_time;
			return $global_current_session;
		}
		return false;
	}
	##################################################
	## getCurrentSessionLoginTime
	##################################################
	function getCurrentSessionLoginTime()
	{
		global $global_current_session_login_time;
		if ( !empty($global_current_session_login_time) )
			return $global_current_session_login_time;
		elseif ( getCurrentSession() )
			return $global_current_session_login_time;
		return false;
	}
	##################################################
	## generateLSN
	##################################################
	function generateLSN()
	{
		return getCurrentSession().'-'.getCurrentUserID();
	}
	##################################################
	## stripLSN
	##################################################
	function stripLSN($lsn, $forceCheck = false)
	{
		require_once 'inc/serverinfo.inc.php';
		if ( MAINTANANCE )
			return false;
		$pos = strrpos($lsn, '-');
		if ( $pos === false )
			return false;
		$session = substr($lsn, 0, $pos);
		$userID = (int)substr($lsn, $pos + 1);
		if ( (getCurrentSession() != $session) || ($userID == 0) )
			return false;
		$forceCookie = false;
		$timeout = (($t = getCurrentSessionLoginTime()) < (time() - 60*60*0.25));
		if ( $timeout || $forceCheck )
		{
			dbconnect();
			dbq("SELECT session FROM users WHERE ID=$userID");
			list ($real_sesstion) = dbrow(1);
			if ( $real_sesstion != $session )
				return false;
			$forceCookie = true;
			//writeToLog('force cookie because time difference = '.(time() - $t));
		}
		setCurrentSession($session, $forceCookie);
		setCurrentUserID($userID);
		if ( function_exists('stripLSN_callback') )
		{
			$backtrace = debug_backtrace();
			stripLSN_callback($backtrace[1]['function'], $timeout);
			unset($backtrace);
		}
		//writeToLog(s_var_dump(debug_backtrace()));
		return array($session, $userID);
	}
	##################################################
	## setCurrentUserID
	##################################################
	function setCurrentUserID($userID)
	{
		global $global_current_userID;
		$global_current_userID = $userID;
	}
	##################################################
	## getCurrentUserID
	##################################################
	function getCurrentUserID()
	{
		global $global_current_userID;
		if ( isset($global_current_userID) )
			return $global_current_userID;
		else
			return false;
	}
	##################################################
	## checkIfLogged
	##################################################
	function checkIfLogged()
	{
		$session = getCurrentSession();
		$arr = @explode('-', $session); // format: aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
		if ( (count($arr) != 5) || (strlen($arr[0]) != 8) || (strlen($arr[1]) != 4) || (strlen($arr[2]) != 4) || (strlen($arr[3]) != 4) || (strlen($arr[4]) != 12) )
			return false;
		dbconnect();
		dbq("SELECT users.ID FROM users WHERE users.session='".dbesc($session)."'");
		list ($userID) = dbrow(1);
		if ( $userID )
		{
			dbq('UPDATE users SET lastLoginDate='.time().' WHERE ID='.$userID);
			setCurrentUserID($userID);
			return $userID;
		}
		return false;
	}
	##################################################
	## logMeIn
	##################################################
	function logMeIn($name, $pass)
	{
		require_once('inc/sha256.inc.php');
		$hashed = sha256($pass, true); // false = use internal function if it exists
		
		dbconnect();
		$failsafe_count = 0;
		do { // be sure to find an unique session id
			if ( ++$failsafe_count > 3 )
				return false;
			dbq('SELECT UUID()'); // format: aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee
			list($loginsession) = dbrow(1);
			$q = dbq("SELECT session FROM users WHERE session='".dbesc($loginsession)."'");
		} while ( mysql_num_rows($q) != 0 );
		
		dbq("UPDATE users SET session='".dbesc($loginsession)."', lastLoginDate=".time()." WHERE login='".dbesc($name)."' and password='".dbesc($hashed)."'");
		if ( mysql_affected_rows() < 1 )
			return false;
		dbq("SELECT users.ID FROM users WHERE session='".dbesc($loginsession)."' and login='".dbesc($name)."' and password='".dbesc($hashed)."'");
		list($userID) = dbrow(1);
		setCurrentSession($loginsession);
		setCurrentUserID($userID);
		writeToLog("LOGIN\t$userID\t$name\t$loginsession\t$_SERVER[REMOTE_ADDR]\t".date(DATE_HISTORY));
		return true;
	}
	##################################################
	## logMeOut
	##################################################
	function logMeOut($session = NULL)
	{
		if ( empty($session) )
			$session = getCurrentSession();
		dbconnect();
		setcookie('login' ,'' ,time() - 60*60);
		setcookie('login' ,false ,time() - 60*60);
		dbq("UPDATE users SET session='logged out ".date(DATE_TIME)."' WHERE session='".dbesc($session)."'");
		if ( mysql_affected_rows() < 1 )
			return false;
		writeToLog("LOGOUT\t$session\t$_SERVER[REMOTE_ADDR]\t".date(DATE_HISTORY));
		return true;
	}
	##################################################
	## logOutEverybody
	##################################################
	function logOutEverybody()
	{
		$userID = getCurrentUserID();
		if ( empty($userID) )
			$userID = checkIfLogged();
		if ( $userID == 1 )
		{
			dbconnect();
			dbq("UPDATE users SET session='force logout ".date(DATE_TIME)."' WHERE ID>1");
			if ( mysql_affected_rows() < 1 )
				return false;
			writeToLog("LOGOUT\t-=* ALL *=-\t$_SERVER[REMOTE_ADDR]\t".date(DATE_HISTORY));
			return true;
		}
		return false;
	}
	##################################################
	## magicquotes
	## stripslashes or not depending on get_magic_quotes_gpc
	##################################################
	function magicquotes(&$string)
	{
		if ( get_magic_quotes_gpc() )
			return stripslashes($string);
		else
			return $string;
	}	
	##################################################
	## getThisPageURI
	##################################################
	function getThisPageURI($page = '')
	{
		$host	= $_SERVER['HTTP_HOST'];
		$uri	= rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		if ( empty($page) )
			$page = basename($_SERVER['PHP_SELF']);
		else
			$page	= ltrim($page, '/\\');
		return 'http://'.$host.$uri.'/'.$page;
	}
} // _FUNCTIONS_INC_PHP_
?>