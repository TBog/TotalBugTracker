<?php
if (!defined('_DBFUNC_INC_PHP_'))
{
    define('_DBFUNC_INC_PHP_', true);
	/*
	class DataBase
	{
		protected $_link = NULL;
		protected $_host = NULL;
		protected $_login = NULL;
		protected $_pass = NULL;
		protected $_debug = true;
		
		function connect($host = NULL, $login = NULL, $pass = NULL)
		{
			if ( isset($_link) )
				return false;
			if ( isset($host, $login, $pass) )
			{
				$_host = $host;
				$_login = $login;
				$_pass = $pass;
			}
			$_link = @mysql_connect($_host, $_login, $_pass);
			if ( empty($_link) )
			{
				_error('Coud not connect');
				return false;
			}
		}
		protected _error($str)
		{
			if ( $_debug )
				echo $str;
		}
	}
	*/
	function dbdie($txt, $debug = true)
	{
		if ( $debug )
		{
			require_once 'inc/functions.inc.php';
			mori(htmlspecialchars($txt).' error '.mysql_errno().':<br>'.mysql_error().'<br><pre>'.CRLF.htmlspecialchars(s_var_dump(debug_backtrace(), false)).'</pre>');
		}
		else
			die();
	}
	function db_connect($debug = true)
	{
		require_once('config.inc.php');
		global $global_database_link;
		if ( (!isset($global_database_link)) || (!$global_database_link) )
			$global_database_link = @mysql_connect(DATABASE_HOST, DATABASE_LOGIN, DATABASE_PASSWORD);
		if ( !$global_database_link )
			dbdie('connect', $debug);
		mysql_select_db(DATABASE) or dbdie('DB select', $debug);
		return $global_database_link;
	}
	function dbq($query, $debug = true)
	{
		global $global_database_link;
		global $global_mysql_database_query;
		$global_mysql_database_query = @mysql_query($query, $global_database_link);
		if ( !$global_mysql_database_query )
			dbdie('query <'.$query.'>', $debug);
		return $global_mysql_database_query;
	}
	function dbrow($numeric = 0, $query = NULL)
	{
		global $global_mysql_database_query;
		if ( !isset($query) )
			$query = $global_mysql_database_query;
		if ( $numeric ) // numeric index only
			return mysql_fetch_row($query);
		else
			return mysql_fetch_assoc($query);
	}
	function dbr($numeric = 0) // database result into an array
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
	function dbesc($string) {
		global $global_database_link;
		if ( func_num_args() > 1 )
		{
			$return = array();
			for ( $i = 0; $i < func_num_args(); $i += 1 )
			{
				$str = func_get_arg($i);
				$return[] = dbesc($str);
			}
			return $return;
		}
		elseif ( empty($global_database_link) || version_compare(PHP_VERSION, '4.3.0', '<') )
			return mysql_escape_string($string);
		else
			return mysql_real_escape_string($string, $global_database_link);
	}
} // _DBFUNC_INC_PHP_
?>