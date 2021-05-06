<?php
$old_err_reporting = error_reporting(E_ALL | E_NOTICE | E_STRICT);

function dbqupdate($table, $set, $where, $fields, $values) // this is not an ajax exported function
{
	dbq("UPDATE $table SET $set WHERE $where");
	$affected = mysql_affected_rows();
	if ( $affected < 1 )
		dbq("INSERT INTO $table ($fields) VALUES ($values)");
	elseif ( $affected > 1 )
	{
		dbq("SELECT ID, statCount FROM $table WHERE $where");
		$max = array();
		while ( $row = dbrow(1) )
			$max[$row[0]] = $row[1];
		if ( count($max) > 1 )
		{
			arsort($max);
			reset($max);
			dbq("DELETE FROM $table WHERE ($where) AND ID!=".key($max));
		}
		$args = func_get_args();
		writeToLog("ERR\tdbqupdate affected = $affected; max = ".s_print_r($max).' args = '.s_print_r($args));
	}
}
function stripLSN_callback($funcName, $bRegular) // this is not an ajax exported function
{
	static $count = 0;
	list ($hour, $day, $month) = explode(' ', date('G w n'));
	$userID = getCurrentUserID();
	dbconnect();
	if ( $count == 0 ) // just once per ajax request
	{
		if ( $bRegular ) // user clicks are counted at regular intervals
		{
			dbqupdate('statistics', 'statCount=statCount+1', "statType='user_hour' AND statInfo='$userID $hour'", 'statType, statInfo', "'user_hour', '$userID $hour'");
			dbqupdate('statistics', 'statCount=statCount+1', "statType='user_day' AND statInfo='$userID $day'", 'statType, statInfo', "'user_day', '$userID $day'");
			dbqupdate('statistics', 'statCount=statCount+1', "statType='user_month' AND statInfo='$userID $month'", 'statType, statInfo', "'user_month', '$userID $month'");
		}
		dbqupdate('statistics', 'statCount=statCount+1', "statType='hour' AND statInfo='$hour'", 'statType, statInfo', "'hour', '$hour'");
		dbqupdate('statistics', 'statCount=statCount+1', "statType='day' AND statInfo='$day'", 'statType, statInfo', "'day', '$day'");
		dbqupdate('statistics', 'statCount=statCount+1', "statType='month' AND statInfo='$month'", 'statType, statInfo', "'month', '$month'");
	}
	dbqupdate('statistics', 'statCount=statCount+1', "statType='func_hour' AND statInfo='$funcName $hour'", 'statType, statInfo', "'func_hour', '$funcName $hour'");
	dbqupdate('statistics', 'statCount=statCount+1', "statType='func_day' AND statInfo='$funcName $day'", 'statType, statInfo', "'func_day', '$funcName $day'");
	dbqupdate('statistics', 'statCount=statCount+1', "statType='func_month' AND statInfo='$funcName $month'", 'statType, statInfo', "'func_month', '$funcName $month'");

	dbqupdate('statistics', 'statCount=statCount+1', "statType='user_func' AND statInfo='$userID $funcName'", 'statType, statInfo', "'user_func', '$userID $funcName'");
	$count += 1;
}
function print_second_filter($header, $appID, $limitStart, $customWhere, $customWhere2, $bBug) // this is not an ajax exported function
{
	$page = '';
	$possible_second_filters = array('assignedTo', 'assToGroup', 'platformID', 'frequencyID', 'severityID');
	$get_filter_options = array(
		'assignedToName'	=>	'SELECT users.ID, login FROM users, user_to_app WHERE userID=users.ID AND appID='.$appID.' ORDER BY login',
		'assToGroupName'	=>	'SELECT ID, groupName FROM groups',
		'closedByName'		=>	'SELECT users.ID, login FROM users, user_to_app WHERE userID=users.ID AND appID='.$appID.' ORDER BY login',
		'platformID'		=>	'SELECT ID, platformName FROM platforms',
		'frequencyID'		=>	'SELECT ID, frequencyName FROM frequency',
		'severityID'		=>	'SELECT ID, severityName FROM severity ORDER BY priority',
		'statusID'			=>	'SELECT ID, statusName FROM status',
		'typeName'			=>	'SELECT ID, typeName FROM type',
		);
	if ( !$bBug )
		$get_filter_options['typeName'] = 'SELECT ID, typeName FROM asset_types';

	$convert_header = array(
		'assignedToName'	=>	'assignedTo',
		'assToGroupName'	=>	'assToGroup',
		'closedByName'		=>	'closedBy',
		'typeName'			=>	'typeID',
	);
	
	if ( isset($get_filter_options[$header]) )
	{
		$query = explode(' AND ', $customWhere2);
		array_shift($query); // remove the first param ("1")
		$option = array();
		foreach ( $query as $v )
		{
			$tmp = explode('=', $v);
			if ( !isset($tmp[1]) ) // in case it was not separated by =
			{
				$tmp = explode('&', $v);
				$tmp[0] = substr($tmp[0], 1);
				$tmp[1] = substr($tmp[1], 0, -1);
			}
			$option[$tmp[0]] = $tmp[1];
		}
		dbq($get_filter_options[$header]);
		if ( isset($convert_header[$header]) )
			$header = $convert_header[$header];
		$page.= '<br><select class="filter" id="'.$header.'" onChange="filter2('.$limitStart.', \''.$customWhere.'\', '.($bBug?1:0).')">';
		$page.= '<option value="">Any</option>';
		while ( $row = dbrow(1) )
		{
			$page.= '<option value="'.$row[0].'"';
			if ( isset($option[$header]) && ($option[$header] == $row[0]) )
				$page.= ' selected="selected"';
			$page.= '>'.$row[1].'</option>';
		}
		$page.= '</select>';
	}
	return $page;
}
function print_filter_nice($filterID, $profileData, $info, $editable, $bBug) // this is not an ajax exported function
{
	static $all_filters_val;
	dbconnect();
	if ( !function_exists('select') ) // not so nice hack
	{
		if ( $editable )
		{
			function select($op, $sel, $cssClass = '', $onChange = '')
			{
				if ( !empty($cssClass) )
					$cssClass = ' class="'.$cssClass.'"';
				if ( !empty($onChange) )
					$onChange = ' onChange="'.$onChange.'"';
				$txt = ' <select'.$cssClass.$onChange.'>';
				$nSelectedItems = 0;
				foreach ($op as $k=>$v)
				{
					if ( $sel == $k )
					{
						$selected = ' selected="selected"';
						$nSelectedItems += 1;
					}
					else
						$selected = '';
					$txt.= '<option'.$cssClass.' value="'.$k.'"'.$selected.'>'.htmlspecialchars($v).'</option>';
				}
				$txt.= '</select> ';
				global $global_select_error_text;
				if ( $nSelectedItems != 1 )
					$global_select_error_text.= 'ERROR: Coud not select element "'.$sel.'" from '.s_print_r($op).CRLF;
				return $txt;
			}
		}
		else
		{
			function select($op, $sel, $cssClass = '', $onChange = '')
			{
				$txt = '';
				foreach ($op as $k=>$v)
					if ( $sel == $k )
					{
						$txt.= ' '.htmlspecialchars($v).' ';
						break;
					}
				return $txt;
			}
		}
		$all_filters_val = array(
			'severityID'=>array(),
			'platformID'=>array(),
			'frequencyID'=>array(),
			'assignedTo'=>array(0=>'unassigned'),
			'assToGroup'=>array(0=>'no group'),
			'statusID'=>array(),
			'typeID'=>array(),
		);
		/*
		$theApps = '';
		dbq("SELECT appID FROM user_to_app WHERE user_to_app.userID=$userID");
		$myapps = dbr(1);
		$theApps = '0';
		foreach ( $myapps as $appID )
			$theApps.= ' OR user_to_app.appID='.$appID[0];
		unset($myapps);
		
		dbq("SELECT users.ID, users.login FROM users, user_to_app WHERE ($theApps) and users.ID=user_to_app.userID ORDER BY login");
		*/
		dbq("SELECT users.ID, CONCAT('(', shortName, ') ', login, ' - ', name) FROM users, groups WHERE groupID=groups.ID ORDER BY shortName, login");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['assignedTo'][$val[0]] = $val[1];
		
		dbq("SELECT ID, groupName FROM groups");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['assToGroup'][$val[0]] = $val[1];
		
		dbq("SELECT ID, severityName FROM severity ORDER BY priority ASC");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['severityID'][$val[0]] = $val[1];
		
		dbq("SELECT ID, platformName FROM platforms");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['platformID'][$val[0]] = $val[1];
		
		dbq("SELECT ID, frequencyName FROM frequency");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['frequencyID'][$val[0]] = $val[1];
		
		dbq("SELECT ID, statusName FROM status");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['statusID'][$val[0]] = $val[1];
	}
	if ( $bBug )
		dbq("SELECT ID, typeName FROM type");
	else
		dbq("SELECT ID, typeName FROM asset_types");
	$tmp = dbr(1);
	foreach ( $tmp as $val )
		$all_filters_val['typeID'][$val[0]] = $val[1];
	$operators1 = array('AND'=>'and', 'OR'=>'or');
	$operators2 = array('='=>'==', '!='=>'<>');
	$operators3 = array('='=>'==', '!='=>'<>', '&'=>'&', '!&'=>'!&');
	$all_filters = array(
		'severityID'=>'Priority',
		'platformID'=>'Platform',
		'frequencyID'=>'Frequency',
		'assignedTo'=>'Assigned to',
		'assToGroup'=>'Group assigned',
		'statusID'=>'Status',
		'typeID'=>'Type',
	);

	$txt = '<table class="filters_toggle">';
	$first_row = true;
	foreach ( $profileData as $rowID=>$row )
	{
		list ($expr_op, $expr) = $row;
		$t = '';
		$first_expr = true;
		foreach ( $expr as $rowFilterID=>$filters )
		{
			list ($filter_op, $filter) = $filters;
			list ($a, $op, $b) = $filter;
			$onChange = 'xajax_change_filter(lsn, '.$filterID.', '.$rowID.', '.$rowFilterID.', %d, this.value)';
			if ( !$first_expr )
			{
				$t.= select($operators1, $filter_op, 'operator1', sprintf($onChange, -1));
			}
			$first_expr = false;
			if ( $a == 'platformID' )
				$operators = $operators3;
			else
				$operators = $operators2;
			$t.= select($all_filters, $a, 'operand1', sprintf($onChange, 0)).select($operators, $op, 'operator2', sprintf($onChange, 1)).select($all_filters_val[$a], $b, 'operand2', sprintf($onChange, 2));
		}
		if ( !empty($t) )
		{
			$txt.= '<tr><td>';
			if ( !$first_row )
			{
				$txt.= select($operators1, $expr_op, 'operator3', 'xajax_change_filter(lsn, '.$filterID.', '.$rowID.', -1, -1, this.value)');
			}
			$txt.= '<span class="paranteza">(</span>'.$t.'<span class="paranteza">)</span>';
			$txt.= '</td>';
			if ( $editable )
			{
				$txt.= '<td class="filter_options">';
				$txt.= '<input type="button" class="filter_btn" value="Add filter" onClick="xajax_add_filter(lsn, '.$filterID.', '.$rowID.')">';
				$txt.= '<input type="button" class="filter_btn" value="Remove line" onClick="xajax_remove_filter(lsn, '.$filterID.', '.$rowID.')">';
				$txt.= '</td>';
			}
			elseif ( $first_row )
			{
				$txt.= '<td rowspan="'.count($profileData).'" class="filter_profile_details">';
				$txt.= $info;
				$txt.= '</td>';
			}
			$txt.= '</tr>';
			$first_row = false;
		}
	}
	if ( $editable )
	{
		$txt.= '<tr><td class="filter_options">';
		$txt.= '<input type="button" class="filter_btn" value="Add filter line" onClick="xajax_add_filter(lsn, '.$filterID.')">';
		$txt.= '</td><td class="filter_profile_details">';
		$txt.= $info;
		$txt.= '</td></tr>';
	}
	$txt.= '</table>';
	return $txt;
}
function get_possible_orderBy($bBug) // this is not an ajax exported function
{
	$r = array();
	$r[] =		array(	'ID',				'#');
	$r[] =		array(	'platformID',		'Platform');
	$r[] =		array(	'frequencyID',		'Frequency');
	if ( $bBug )
		$r[] =	array(	'title',			'Title');
	else
		$r[] =	array(	'title',			'Task name');
	$r[] =		array(	'assignedToName',	'Assigned to');
	$r[] =		array(	'assToGroupName',	'Group assigned');
	$r[] =		array(	'closedByName',		'Closed by');
	$r[] =		array(	'severityID',		'Priority');
	$r[] =		array(	'statusID',			'Status');
	$r[] =		array(	'typeName',			'Type');
	$r[] =		array(	'openDate',			'Open date');
	$r[] =		array(	'closeDate',		'Closed date');
	$r[] =		array(	'submitedDate',		'Submited date');
	$r[] =		array(	'versionDate',		'Build');
	$r[] =		array(	'lastEdit',			'Last edit');
	if ( !$bBug )
		$r[] =	array(	'deadLineDate',		'Dead-line');
	return $r;
}
function get_possible_columns($bBug) // this is not an ajax exported function
{
	$r = array();
	//					field name				what we print			what we query for
	$r[] =		array(	'ID',				'#',				'ID');
	$r[] =		array(	'platformName',		'Platform',			'(SELECT GROUP_CONCAT(platformName SEPARATOR ", ") FROM platforms WHERE (platforms.ID & platformID) != 0 ) AS platformName');
	$r[] =		array(	'frequencyName',	'Frequency',		'(SELECT frequencyName FROM frequency WHERE frequency.ID=frequencyID) AS frequencyName');
	$r[] =		array(	'frequencyPercent',	'Frequency%',		'frequencyPercent');
	if ( $bBug )
		$r[] =	array(	'title',			'Title',			'title');
	else
		$r[] =	array(	'title',			'Task name',		'title');
	$r[] =		array(	'assignedToName',	'Assigned to',		'(SELECT login FROM users WHERE users.ID=assignedTo) AS assignedToName');
	$r[] =		array(	'assToGroupName',	'Group assigned',	'(SELECT groupName FROM groups WHERE groups.ID=assToGroup) AS assToGroupName');
	$r[] =		array(	'closedByName',		'Closed by',		'(SELECT login FROM users WHERE users.ID=closedBy) AS closedByName');
	$r[] =		array(	'severityName',		'Priority',			'(SELECT severityName FROM severity WHERE severity.ID=severityID) AS severityName');
	$r[] =		array(	'statusName',		'Status',			'(SELECT statusName FROM status WHERE status.ID=statusID) AS statusName');
	if ( $bBug )
		$r[] =	array(	'typeName',			'Type',				'(SELECT typeName FROM type WHERE type.ID=typeID) AS typeName');
	else
		$r[] =	array(	'typeName',			'Type',				'(SELECT typeName FROM asset_types WHERE asset_types.ID=typeID) AS typeName');
	$r[] =		array(	'openDate',			'Open date',		'openDate');
	$r[] =		array(	'closeDate',		'Closed date',		'closeDate');
	$r[] =		array(	'submitedDate',		'Submited date',	'submitedDate');
	$r[] =		array(	'versionDate',		'Build',			'versionDate');
	$r[] =		array(	'lastEdit',			'Last edit',		'lastEdit');
	if ( !$bBug )
		$r[] =	array(	'deadLineDate',		'Dead-line',		'deadLineDate');
	return $r;
}
function update_bug($userID, $userName, $bugID, $appID, $bBug, $details) // this is not an ajax exported function
{
	dbconnect();
	dbq('SELECT * FROM '.($bBug?'bugs':'assets').$appID.' WHERE ID='.$bugID);
	$bug = dbrow();
	if ( empty($bug) )
		return false;
	$now = time();
	$set = '';
	$history = '';
	if ( isset($details['openDate']) && ($details['openDate'] != $bug['openDate']) )
	{
		$set.= ", openDate=$details[openDate]";
		$history.= "<li>changed Open date from ".date(DATE_NOTIME, $bug['openDate']).' to '.date(DATE_NOTIME, $details['openDate'])."</li>";
	}
	if ( isset($details['deadLineDate']) && ($details['deadLineDate'] != $bug['deadLineDate']) )
	{
		$set.= ", deadLineDate=$details[deadLineDate]";
		$history.= "<li>changed Dead-line from ".date(DATE_NOTIME, $bug['deadLineDate']).' to '.date(DATE_NOTIME, $details['deadLineDate'])."</li>";
	}
	if ( isset($details['assignedTo']) && ($details['assignedTo'] != $bug['assignedTo']) && !empty($details['assignedTo']) )
	{
		dbq("SELECT groupID, groupName FROM users, groups WHERE users.ID=$details[assignedTo] AND groups.ID=groupID");
		$r = dbrow(1);
		$set.= ", assToGroup=$r[0], assignedTo=$details[assignedTo], flags=(flags & (~".BUG_VIEWED."))";
		$history.= "<li>assigned the ".($bBug?'bug':'task')." to $details[assignedToName] from group $r[1]</li>";
	}
	elseif ( isset($details['assToGroup']) && ($details['assToGroup'] != $bug['assToGroup']) && !empty($details['assToGroup']) )
	{
		$set.= ", assToGroup=$details[assToGroup], assignedTo=0, flags=(flags & (~".BUG_VIEWED."))";
		$history.= "<li>assigned the ".($bBug?'bug':'task')." to group $details[assToGroupName]</li>";
	}
	if ( isset($details['statusID']) && ($details['statusID'] != $bug['statusID']) )
	{
		if ( $details['statusID'] == BUG_STATUS_CLOSED )
			$set.= ", closedBy=$userID, closeDate=$now";
		$set.= ", statusID=$details[statusID]";
		$history.= "<li>changed Status to $details[statusName]</li>";
		dbq("INSERT INTO status_history (time, appID, ".($bBug?'bugID':'assetID').", statusID, userID) VALUES ($now, $appID, $bugID, $details[statusID], $userID)");
	}
	if ( isset($details['typeID']) && ($details['typeID'] != $bug['typeID']) )
	{
		$set.= ", typeID=$details[typeID]";
		$history.= "<li>changed Type to $details[typeName]</li>";
	}
	if ( isset($details['severityID']) && ($details['severityID'] != $bug['severityID']) )
	{
		$set.= ", severityID=$details[severityID]";
		$history.= "<li>changed Priority to $details[severityName]</li>";
	}
	if ( isset($details['platformID']) && ($details['platformID'] != $bug['platformID']) )
	{
		$set.= ", platformID=$details[platformID]";
		$history.= "<li>changed Platform to $details[platformName]</li>";
	}
	if ( isset($details['versionDate']) && ($details['versionDate'] != $bug['versionDate']) )
	{
		$set.= ", versionDate=$details[versionDate]";
		$history.= "<li>changed Build version from ".date(DATE_BUGVER, $bug['versionDate']).' to '.date(DATE_BUGVER, $details['versionDate'])."</li>";
	}
	if ( isset($details['frequencyID']) && ($details['frequencyID'] != $bug['frequencyID']) )
	{
		$set.= ", frequencyID=$details[frequencyID]";
		$history.= "<li>changed Frequency to $details[frequencyName]</li>";
	}
	if ( isset($details['frequencyPercent']) && ($details['frequencyPercent'] != $bug['frequencyPercent']) )
	{
		$set.= ", frequencyPercent='".dbesc($details['frequencyPercent'])."'";
		$history.= "<li>changed Frequency% to $details[frequencyPercent]</li>";
	}
	if ( isset($details['title']) && ($details['title'] != $bug['title']) )
	{
		$set.= ", title='".dbesc($details['title'])."'";
		$history.= "<li>changed Title</li>";
	}
	if ( isset($details['description']) && ($details['description'] != $bug['description']) )
	{
		$set.= ", description='".dbesc($details['description'])."'";
		$history.= "<li>changed Description</li>";
	}
	if ( isset($details['info']) && (!empty($details['info'])) )
	{
		$set.= ", info=CONCAT(info,'On ".date(DATE_HISTORY, $now).' '.$userName.' wrote:'.dbesc("\n".$details['info']."\n")."')";
		$history.= "<li>added Notes (".strlen($details['info'])." characters)</li>";
	}
	if ( isset($details['notes']) && ($details['notes'] != $bug['notes']) )
	{
		$set.= ", notes='".dbesc($details['notes'])."'";
		$history.= "<li>changed the Notes</li>";
	}
	
	if ( !empty($history) )
		$history = date(DATE_HISTORY).' '.$userName."\n".$history."\n";
	$history = dbesc($history);
	if ( !empty($set) )
	{
		dbq('UPDATE '.($bBug?'bugs':'assets').$appID." SET lastEdit=$now, history=CONCAT('$history',history) $set WHERE ID=$bugID");
		return mysql_affected_rows();
	}
	return false;
}
function encodeWaypointName($funcName, $bBug) // this is not an ajax exported function
{
	global $function_list;
	return array_search($funcName, $function_list) * ($bBug?1:-1);
}
function decodeWaypointName($sWaypointName) // this is not an ajax exported function
{
	global $function_list;
	return array($function_list[abs($sWaypointName)], $sWaypointName > 0);
}

##################################################
### All following functions will be exported with ajax
##################################################

function waypoint_handler($lsn, $sWaypointName, $sWaypointData = NULL)
{
    $objResponse = new xajaxResponse();
	$args = func_get_args();
	//writeToLog('waypoint_handler args = '.s_var_dump($args));
    if (!empty($sWaypointName) && !empty($sWaypointData))
    {
		list ( $function ) = decodeWaypointName($sWaypointName);
        list ( $params, $details ) = decodeWaypointData($sWaypointData);
		$params[0] = $lsn; // overwrite history lsn with current one
		blockHistoryAdd(true);
		$objResponse->loadCommands(call_user_func_array($function, $params));
		blockHistoryAdd(false);
    }
	else
	{
		//$objResponse->alert('waypoint_handler('.$sWaypointName.' , '.$sWaypointData.')');
		$objResponse->script('xajax_print_menu(lsn)');
	}
    return $objResponse;
}
function load_style($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT settings.CSS FROM settings WHERE userID=$userID");
	list ($css) = dbrow(1);
	if ( !empty($css) )
		$objResponse->includeCSS($css);
	return $objResponse;
}
function print_menu($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq('SELECT privilege FROM users WHERE ID='.$userID);
	list ($privilege) = dbrow(1);
	
	$button = '<input type="button" class="menu" value="%s" onClick="%s"><br>';
	//$button_d = '<input type="button" class="menu" disabled="disabled" value="%s" onClick="%s"><br>';
	$title = '<div class="menu_title" onClick="toggle(\'%2$s\',\'\',\'\')">%1$s</div>';

	$menu = '';
	//$menu.= sprintf($title, 'Menu');
	$menu.= sprintf($button, 'Home', 'xajax_home_page(lsn)');
	$menu.= sprintf($button, 'My Details', 'xajax_my_details(lsn)');
	$menu.= sprintf($button, 'My Profiles', 'xajax_my_profiles(lsn)');
	$menu.= sprintf($button, 'Settings', 'xajax_my_settings(lsn)');
	$menu.= sprintf($button, 'Logout', "self.location.replace('logout.php')");
	
	$menu.= sprintf($title, 'Bugs', 'menu_bugs');
	$menu.= '<span id="menu_bugs">';
	$menu.= '#<input type="text" id="qsearch" class="qsearch"><input type="button" value="Go" class="default" onClick="xajax_print_bug(lsn, xjx.$(\'qsearch\').value)"><br>';
	$menu.= sprintf($button, 'View', 'xajax_view_bugs(lsn)');
	$menu.= sprintf($button, 'Add', 'xajax_add_bugs(lsn)');
	$menu.= sprintf($button, 'Search', 'xajax_search_bugs(lsn, 1)');
	$menu.= '</span>';
	
	$menu.= sprintf($title, 'Tasks', 'menu_assets');
	$menu.= '<span id="menu_assets">';
	$menu.= sprintf($button, 'View', 'xajax_view_bugs(lsn, 0)');
	$menu.= sprintf($button, 'Add', 'xajax_add_tasks(lsn)');
	$menu.= sprintf($button, 'Search', 'xajax_search_bugs(lsn, 0)');
	$menu.= '</span>';
	
	if ( $privilege & ADMINISTRATOR )
	{
		$menu.= sprintf($title, 'Admin', 'menu_admin');
		$menu.= '<span id="menu_admin">';
		$menu.= sprintf($button, 'Users', 'xajax_admin_users(lsn)');
		$menu.= sprintf($button, 'Projects', 'xajax_admin_projects(lsn)');
		$menu.= sprintf($button, 'Groups', 'xajax_admin_groups(lsn)');
		if ( $userID == 1 )
			$menu.= sprintf($button, 'Login status', 'xajax_admin_login(lsn)');
		$menu.= '</span>';
	}
	
	$menu.= sprintf($title, 'Misc', 'menu_misc');
	$menu.= '<span id="menu_misc">';
	$menu.= sprintf($button, 'Summary', 'xajax_summary_bugs(lsn)');
	$menu.= sprintf($button, 'Statistics', 'xajax_statistics(lsn)');
	$menu.= sprintf($button, 'Free time', 'xajax_games(lsn)');
	$menu.= '</span>';
	
	$objResponse->assign('menu', 'innerHTML', $menu);
	$objResponse->loadCommands(home_page($lsn));
	return $objResponse;
}
function admin_login($lsn, $command='', $details='')
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn, true);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '';
	dbconnect();
	switch ( $command )
	{
		case 'logoutall':
			logOutEverybody();
			break;
		case 'logout':
			$details = explode(',', $details);
			$where = '0';
			foreach ( $details as $id )
				$where.= ' OR ID='.((int)$id);
			$details = implode(',', $details);
			dbq("UPDATE users SET session='force logout ".date(DATE_TIME)."' WHERE $where");
			if ( mysql_affected_rows() < 1 )
				return $objResponse->alert('ERROR: no rows updated');
			writeToLog("LOGOUT\t$details\t$_SERVER[REMOTE_ADDR]\t".date(DATE_HISTORY));
			break;
	}
	// print page
	$page.= '<h1>Login status</h1>';
	$page.= '<table class="users">';
	$page.= '<thead><tr>';
	$page.= '<th>ID</th>';
	$page.= '<th>Login</th>';
	$page.= '<th>Name</th>';
	$page.= '<th>E-Mail</th>';
	$page.= '<th>Group</th>';
	$page.= '<th>Last login</th>';
	$page.= '<th>Session</th>';
	$page.= '<th colspan="2"></th>';
	$page.= '</tr></thead><tbody>';
	dbq("SELECT users.ID, login, name, email, groupName, lastLoginDate, session FROM users, groups WHERE groups.ID=users.groupID ORDER BY users.ID");
	while ( $row = dbrow() )
	{
		$page.= '<tr>';
		$page.= '<td>'.$row['ID'].'</td>';
		$page.= '<td>'.htmlspecialchars($row['login']).'</td>';
		$page.= '<td>'.htmlspecialchars($row['name']).'</td>';
		$page.= '<td><a href="mailto:'.$row['email'].'">'.htmlspecialchars($row['email']).'</a></td>';
		$page.= '<td>'.htmlspecialchars($row['groupName']).'</td>';
		if ( empty($row['lastLoginDate']) )
			$page.='<td>Never</td>';
		else
			$page.= '<td>'.date(DATE_LOGIN, $row['lastLoginDate']).'</td>';
		$page.= '<td>'.htmlspecialchars($row['session']).'</td>';
		$page.= '<td><input type="button" class="default" value="Edit" onClick="xajax_edit_user(lsn, '.$row['ID'].')"></td>';
		$page.= '<td><input type="button" class="default" value="Logout" onClick="xajax_admin_login(lsn, \'logout\', '.$row['ID'].')"></td>';
		$page.= '</tr>';
	}
	$page.= '</tbody><tfoot>';
	$page.= '<tr><th colspan="9"><input type="button" class="default" value="Add user" onClick="xajax_edit_user(lsn)"> <input type="button" class="default" value="Logout all" onClick="xajax_admin_login(lsn, \'logoutall\')"></th></tr>';
	$page.= '</tfoot></table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function games($lsn)
{
	$strip = stripLSN($lsn, true);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>Simple, free time, games</h1>';
	$page.= '<table>';
	$page.= '<tr><td>';
	$page.= '<input type="button" class="default" value="Number Puzzle" onClick="xajax_nrpuzzle(xjx.$(\'nr_puzzle_size\').value)">';
	$page.= '</td><td>';
	$page.= 'size <input type="text" class="default" id="nr_puzzle_size" value="4" size="2" maxlength="2">';
	$page.= '</td></tr>';
	$page.= '<tr><td>';
	$page.= '<input type="button" class="default" value="TicTacToe" onClick="window.open(\''.getThisPageURI('/').'extra/ttt.php\', \'\')">';
	$page.= '</td></tr>';
	$page.= '</table>';
	$objResponse = new xajaxResponse();
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function nrpuzzle($size)
{
	$page = '<h1>Number Puzzle</h1>';
	$objResponse = new xajaxResponse();
	if ( ($size < 2) || ($size > 10) )
		return $objResponse->alert("Incorrect size:\n1 < size <= 10");
	if ( func_num_args() == 1 )
	{
		$data = range(1, $size * $size - 1);
		$data[] = 0;
		$l = $c = ($size - 1);
		$nShuffle = pow($size, 4) * 4;
		$nShuffle -= 1 - $nShuffle % 2;
		
		$dir = array(
			array( 0,-1),
			array(-1, 0),
			array( 0, 1),
			array( 1, 0),
		);
		$nDirSize = count($dir);
		set_time_limit(5);
		for ( $i = 0; $i < $nShuffle; $i += 1 )
		{
			$move = true;
			while ($move)
			{
				$d = mt_rand(0, $nDirSize - 1);
				$d = $dir[$d];
				$ln = $l + $d[0];
				$cn = $c + $d[1];
				if ( ($ln >= 0) && ($cn >= 0) && ($ln < $size) && ($cn < $size) )
				{
					$data[ $l * $size + $c ] = $data[ $ln * $size + $cn ];
					$data[ $ln * $size + $cn ] = 0;
					$l = $ln;
					$c = $cn;
					$move = false;
				}
			}
		}
		set_time_limit(5);
		$time = time();
	}
	else
	{
		$time = func_get_arg(1);
		$count = func_get_arg(2);
		$data = func_get_arg(3);
		$data = explode(',', $data);
		$win = true;
		for ( $i = 1; $i < ($size * $size); $i += 1 )
			if ( $data[$i - 1] != $i )
			{
				$win = false;
				break;
			}
		if ( $win )
		{
			$page.= 'Solved puzzle size '.$size.' in '.(time() - $time).' seconds using '.$count.' moves';
			$objResponse->assign('details', 'innerHTML', $page);
			return $objResponse;
		}
		return $objResponse;
	}
	$page.= '<table>';
	for ( $i = 0; $i < $size; $i += 1 )
	{
		$page.= '<tr>';
		for ( $j = 0; $j < $size; $j += 1 )
			$page.= '<td><input type="button" class="square" value="'.$data[$i * $size + $j].'" id="l'.$i.'c'.$j.'" onClick="nrpuzzle('.$size.','.$time.','.$i.','.$j.')"></td>';
		$page.= '</tr>';
	}
	$page.= '</table>';
	$page.= 'Number of moves: <span id="nrpuzzle_moves"></span><br>';
	$page.= '<input type="button" class="default" value="Shuffle" onClick="xajax_nrpuzzle('.$size.')">';
	$objResponse->assign('details', 'innerHTML', $page);
	for ( $i = 0; $i < ($size * $size); $i += 1 )
		if ( empty($data[$i]) )
		{
			$objResponse->script('nrpuzzle('.$size.','.$time.','.floor($i / $size).','.($i % $size).')');
			break;
		}
	return $objResponse;
}
function my_profiles($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>Profiles</h1>';
	$page.= '<table class="simplegrid">';
	$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	dbconnect();
	dbq("SELECT bugFilterID, assetFilterID, bugDisplayID, assetDisplayID FROM settings WHERE userID=".$userID);
	list ($bugFilterID, $assetFilterID, $bugDisplayID, $assetDisplayID) = dbrow(1);
	// bug filters
	$page.= '<tr><th colspan="3"><h1>Bug filters</h1></th></tr>';
	#$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	$q = dbq("SELECT ID, profileName, profileData FROM profiles WHERE userID=$userID and profileType='bugfilter'");
	while ( $row = mysql_fetch_assoc($q) )
	{
		$profileData = @unserialize($row['profileData']);
		$page.= '<tr>';
		$page.= '<th id="profile'.$row['ID'].'"><div onClick="xajax_rename_profile(lsn, \'profile'.$row['ID'].'\', '.$row['ID'].')">'.$row['profileName'].'</div></th>';
		if ( empty($profileData) )
			$page.= '<td>&nbsp;</td>';
		else
			$page.= '<td>'.print_filter_nice(0, $profileData, '', false, true).'</td>';
		$page.= '<td>';
		$page.= '<input type="button" class="default" value="Remove" onClick="xajax_remove_profile(lsn, '.$row['ID'].')">';
		if ( $row['ID'] != $bugFilterID )
			$page.= '<input type="button" class="default" value="Make default" onClick="xajax_make_default_profile(lsn, '.$row['ID'].', \'bugfilter\')">';
		$page.= '</td>';
		$page.= '</tr>';
	}
	$page.= '<tr><th colspan="2"></th><td><input type="button" class="default" value="Add new" onClick="xajax_save_profile_as(lsn, 0, \'New profile\', \'bugfilter\')"></td></tr>';
	// asset filters
	$page.= '<tr><th colspan="3"><h1>Task filters</h1></th></tr>';
	#$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	$q = dbq("SELECT ID, profileName, profileData FROM profiles WHERE userID=$userID and profileType='assetfilter'");
	while ( $row = mysql_fetch_assoc($q) )
	{
		$profileData = @unserialize($row['profileData']);
		$page.= '<tr>';
		$page.= '<th id="profile'.$row['ID'].'"><div onClick="xajax_rename_profile(lsn, \'profile'.$row['ID'].'\', '.$row['ID'].')">'.$row['profileName'].'</div></th>';
		if ( empty($profileData) )
			$page.= '<td>&nbsp;</td>';
		else
			$page.= '<td>'.print_filter_nice(0, $profileData, '', false, false).'</td>';
		$page.= '<td>';
		$page.= '<input type="button" class="default" value="Remove" onClick="xajax_remove_profile(lsn, '.$row['ID'].')">';
		if ( $row['ID'] != $assetFilterID )
			$page.= '<input type="button" class="default" value="Make default" onClick="xajax_make_default_profile(lsn, '.$row['ID'].', \'assetfilter\')">';
		$page.= '</td>';
		$page.= '</tr>';
	}
	$page.= '<tr><th colspan="2"></th><td><input type="button" class="default" value="Add new" onClick="xajax_save_profile_as(lsn, 0, \'New profile\', \'assetfilter\')"></td></tr>';
	// bugs display
	$page.= '<tr><th colspan="3"><h1>Bugs column display</h1></th></tr>';
	#$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	$display = array();
	foreach ( get_possible_columns(true) as $col )
		$display[$col[0]] = $col[1];
	$q = dbq("SELECT ID, profileName, profileData FROM profiles WHERE userID=$userID and profileType='bugdisplay'");
	while ( $row = mysql_fetch_assoc($q) )
	{
		$profileData = @unserialize($row['profileData']);
		$page.= '<tr>';
		$page.= '<th id="profile'.$row['ID'].'"><div onClick="xajax_rename_profile(lsn, \'profile'.$row['ID'].'\', '.$row['ID'].')">'.$row['profileName'].'</div></th>';
		$page.= '<td>';
		foreach ( $profileData as $col )
			$page.= $display[$col].', ';
		$page = substr($page, 0, -2);
		$page.= '</td>';
		$page.= '<td>';
		$page.= '<input type="button" class="default" value="Remove" onClick="xajax_remove_profile(lsn, '.$row['ID'].')">';
		if ( $row['ID'] != $bugDisplayID )
			$page.= '<input type="button" class="default" value="Make default" onClick="xajax_make_default_profile(lsn, '.$row['ID'].', \'bugdisplay\')">';
		$page.= '</td>';
		$page.= '</tr>';
	}
	$page.= '<tr><th colspan="2"></th><td><input type="button" class="default" value="Add new" onClick="xajax_save_profile_as(lsn, 0, \'New profile\', \'bugdisplay\')"></td></tr>';
	// assets display
	$page.= '<tr><th colspan="3"><h1>Tasks column display</h1></th></tr>';
	#$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	$display = array();
	foreach ( get_possible_columns(false) as $col )
		$display[$col[0]] = $col[1];
	$q = dbq("SELECT ID, profileName, profileData FROM profiles WHERE userID=$userID and profileType='assetdisplay'");
	while ( $row = mysql_fetch_assoc($q) )
	{
		$profileData = @unserialize($row['profileData']);
		$page.= '<tr>';
		$page.= '<th id="profile'.$row['ID'].'"><div onClick="xajax_rename_profile(lsn, \'profile'.$row['ID'].'\', '.$row['ID'].')">'.$row['profileName'].'</div></th>';
		$page.= '<td>';
		foreach ( $profileData as $col )
			$page.= $display[$col].', ';
		$page = substr($page, 0, -2);
		$page.= '</td>';
		$page.= '<td>';
		$page.= '<input type="button" class="default" value="Remove" onClick="xajax_remove_profile(lsn, '.$row['ID'].')">';
		if ( $row['ID'] != $assetDisplayID )
			$page.= '<input type="button" class="default" value="Make default" onClick="xajax_make_default_profile(lsn, '.$row['ID'].', \'assetdisplay\')">';
		$page.= '</td>';
		$page.= '</tr>';
	}
	$page.= '<tr><th colspan="2"></th><td><input type="button" class="default" value="Add new" onClick="xajax_save_profile_as(lsn, 0, \'New profile\', \'assetdisplay\')"></td></tr>';
	$page.= '<tr><th colspan="3">&nbsp;</th></tr>';
	$page.='</table>';
	$page.= 'Note: click on profile name to change it';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function rename_profile($lsn, $divID, $profileID, $profileName='')
{
	$objResponse = new xajaxResponse();
	dbconnect();
	$profileID = (int)$profileID;
	if ( empty($profileName) )
	{
		dbq("SELECT profileName FROM profiles WHERE ID=$profileID");
		list ($profileName) = dbrow(1);
		$profileName = htmlspecialchars($profileName);
		$objResponse->assign($divID, 'innerHTML', '<input type="text" maxlength="32" id="rename_'.$divID.'" value="'.$profileName.'"><br><input type="button" class="default" value="Rename" onClick="xajax_rename_profile(lsn, \''.$divID.'\', '.$profileID.', xjx.$(\'rename_'.$divID.'\').value)">');
		$objResponse->script('xjx.$(\'rename_'.$divID.'\').focus()');
		return $objResponse;
	}
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$profileName = dbesc($profileName);
	$q = dbq("SELECT ID FROM profiles WHERE profileName='$profileName' and userID=$userID");
	if ( mysql_num_rows($q) > 0 )
	{
		$objResponse->alert('One of your profiles has the same name, please choose some other name.');
		return $objResponse;
	}
	dbq("UPDATE profiles SET profileName='$profileName' WHERE ID=$profileID AND userID=$userID");
	$objResponse->assign($divID, 'innerHTML', '<div onClick="xajax_rename_profile(lsn, \''.$divID.'\', '.$profileID.')">'.$profileName.'</div>');
/*	if ( mysql_affected_rows() == 1 )
		
	else
		$objResponse->assign($divID, 'innerHTML', 'ERROR: Coud not rename the profile');
*/
	return $objResponse;
}
function remove_profile($lsn, $profileID)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$profileID = (int)$profileID;
	dbconnect();
	dbq("DELETE FROM profiles WHERE ID=$profileID and userID=$userID");
	dbq("UPDATE settings SET bugFilterID=0 WHERE userID=$userID and bugFilterID=$profileID");
	dbq("UPDATE settings SET assetFilterID=0 WHERE userID=$userID and assetFilterID=$profileID");
	dbq("UPDATE settings SET bugDisplayID=0 WHERE userID=$userID and bugDisplayID=$profileID");
	dbq("UPDATE settings SET assetDisplayID=0 WHERE userID=$userID and assetDisplayID=$profileID");
	$objResponse->loadCommands(my_profiles($lsn));
	return $objResponse;
}
function admin_projects($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn, true);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '';
	dbconnect();
	$page.= '<h1>Project admin area</h1>';
	$page.= '<table class="users">';
	$page.= '<thead><tr>';
	$page.= '<th>ID</th>';
	$page.= '<th>Name</th>';
	$page.= '<th>Description</th>';
	$page.= '<th>Assign</th>';
	$page.= '<th></th>';
	$page.= '</tr></thead><tbody>';
	$q = dbq("SELECT ID, appName, appDesc, isLocal FROM apps");
	if ( mysql_num_rows($q) < 1 )
		$page.= '<tr><td colspan="8">No projects found</td></tr>';
	while ( $row = dbrow() )
	{
		$page.= '<tr>';
		$page.= '<td>'.$row['ID'].'</td>';
		$page.= '<td>'.htmlspecialchars($row['appName']).'</td>';
		$page.= '<td>'.htmlspecialchars($row['appDesc']).'</td>';
		$page.= '<td>'.(($row['isLocal'] == 'Y')?'bugs can be added manually':'&nbsp;').'</td>';
		$page.= '<td><input type="button" class="default" value="Edit" onClick="xajax_edit_project(lsn, '.$row['ID'].')"></td>';
		$page.= '</tr>';
	}
	$page.= '</tbody><tfoot>';
	$page.= '<tr><th colspan="8"><input type="button" class="default" value="Add project" onClick="xajax_edit_project(lsn)"></th></tr>';
	$page.= '</tfoot></table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function admin_groups($lsn, $group = 0)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn, true);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$group = (int)$group;
	$page = '';
	dbconnect();
	$page.= '<h1>Group admin area</h1>';
	$page.= '<table class="users">';
	$page.= '<thead><tr>';
	$page.= '<th>ID</th>';
	$page.= '<th>Name</th>';
	$page.= '<th>short</th>';
	$page.= '<th>Assign</th>';
	$page.= '<th></th>';
	$page.= '</tr></thead><tbody>';
	dbq("SELECT ID, groupName, shortName, canAssignTo FROM groups ORDER BY shortName, ID");
	while ( $row = dbrow() )
	{
		$page.= '<tr>';
		$page.= '<td>'.$row['ID'].'</td>';
		$page.= '<td>'.htmlspecialchars($row['groupName']).'</td>';
		$page.= '<td>'.htmlspecialchars($row['shortName']).'</td>';
		$page.= '<td>'.(($row['canAssignTo'] == 'Y')?'bugs / tasks can be assigned':'&nbsp;').'</td>';
		$page.= '<td><input type="button" class="default" value="Edit" onClick="xajax_edit_group(lsn, '.$row['ID'].')"></td>';
		$page.= '</tr>';
	}
	$page.= '</tbody><tfoot>';
	$page.= '<tr><th colspan="5"><input type="button" class="default" value="Add group" onClick="xajax_edit_group(lsn)"></th></tr>';
	$page.= '</tfoot></table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function edit_group($lsn, $group = 0)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$group = (int)$group;
	$page = '';
	dbconnect();
	require_once 'inc/serverinfo.inc.php';
	if ( func_num_args() <= 2 )
	{
		if ( !empty($group) )
		{
			dbq("SELECT groupName, canAssignTo FROM groups WHERE ID=".$group);
			list ($name, $canAssignTo) = dbrow(1);
			$page.= '<h1>Edit group information</h1>';
		}
		else
		{
			$name = '';
			$canAssignTo = 'Y';
			$page.= '<h1>Add group to '.SERVER_NAME.'</h1>';
		}
		$page.= '<table class="login"><tbody>';
		$page.= '<tr><th>name</th><td><input type="text" id="name" value="'.$name.'"></td></tr>';
		$page.= '<tr><th>bugs / tasks can be assigned to this group</th><td><select id="assign">';
		$page.= '<option value="Y"'.($canAssignTo=='Y'?' selected="selected"':'').'>Y</option>';
		$page.= '<option value="N"'.($canAssignTo=='N'?' selected="selected"':'').'>N</option>';
		$page.= '</td></tr>';
		$page.= '</tbody><tfoot>';
		$page.= '<tr><td colspan="2"><input type="button" class="default" value="'.(empty($group)?'Add group':'Save group').'" onClick="xajax_edit_group(lsn, '.$group.', ';
		$page.= 'document.getElementById(\'name\').value, ';
		$page.= 'document.getElementById(\'assign\').value';
		$page.= ')"></td></tr>';
		$page.= '</tfoot></table>';
		$objResponse->assign('details', 'innerHTML', $page);
	}
	else
	{
		$args = func_get_args();
		$name = dbesc($args[2]);
		if ( empty($name) )
		{
			$objResponse->alert('The group shoud have a pretty name.');
			return $objResponse;
		}
		$canAssignTo = $args[3][0];
		dbq("SELECT ID FROM groups WHERE groupName='$name'");
		while ( $row = dbrow(1) )
			if ( $row[0] != $group )
			{
				$objResponse->alert('Group name found in database. The group shoud have a unique name.');
				return $objResponse;
			}
		if ( empty($group) )
		{
			dbq("INSERT INTO groups (groupName, canAssignTo) VALUES ('$name', '$canAssignTo')");
			$group = mysql_insert_id();
			$objResponse->loadCommands(admin_groups($lsn));
		}
		else
		{
			dbq("UPDATE groups SET groupName='$name', canAssignTo='$canAssignTo' WHERE ID=$group");
			if ( $x = mysql_affected_rows() )
			{
				// $objResponse->alert("Update affected $x row".(($x==1)?'':'s')." in database");
				$objResponse->loadCommands(admin_groups($lsn));
			}
			else
				$objResponse->alert("The update had no effect on the database");
		}
	}
	return $objResponse;
}
function edit_project($lsn, $project = 0)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$project = (int)$project;
	$page = '';
	dbconnect();
	require_once 'inc/serverinfo.inc.php';
	if ( func_num_args() <= 2 )
	{
		if ( !empty($project) )
		{
			dbq("SELECT appName, appDesc, isLocal FROM apps WHERE ID=".$project);
			list ($name, $description, $isLocal) = dbrow(1);
			$page.= '<h1>Edit project information</h1>';
		}
		else
		{
			$name = '';
			$description = '';
			$isLocal = 'Y';
			$page.= '<h1>Add project to '.SERVER_NAME.'</h1>';
		}
		$page.= '<table class="login"><tbody>';
		$page.= '<tr><th>name</th><td><input type="text" id="name" value="'.$name.'"></td></tr>';
		$page.= '<tr><th>description</th><td><input type="text" id="description" value="'.$description.'"></td></tr>';
		$page.= '<tr><th>isLocal</th><td><select id="assign">';
		$page.= '<option value="Y"'.($isLocal=='Y'?' selected="selected"':'').'>Y</option>';
		$page.= '<option value="N"'.($isLocal=='N'?' selected="selected"':'').'>N</option>';
		$page.= '</td></tr></tbody>';
		$page.= '<tfoot><tr><td colspan="2"><input type="button" class="default" value="'.(empty($project)?'Add project':'Save project').'" onClick="xajax_edit_project(lsn, '.$project.', ';
		$page.= 'document.getElementById(\'name\').value, ';
		$page.= 'document.getElementById(\'description\').value, ';
		$page.= 'document.getElementById(\'assign\').value';
		$page.= ')"></td></tr></tfoot>';
		$page.= '</table>';
		$objResponse->assign('details', 'innerHTML', $page);
	}
	else
	{
		$args = func_get_args();
		$name = dbesc($args[2]);
		if ( empty($name) )
		{
			$objResponse->alert('The project shoud have a pretty name.');
			return $objResponse;
		}
		$description = dbesc($args[3]);
		$isLocal = $args[4][0];
		dbq("SELECT ID FROM apps WHERE appName='$name'");
		while ( $row = dbrow(1) )
			if ( $row[0] != $project )
			{
				$objResponse->alert('Project name found in database. The project shoud have a unique name.');
				return $objResponse;
			}
		if ( empty($project) )
		{
			dbq("INSERT INTO apps (appName, appDesc, isLocal) VALUES ('$name', '$description', '$isLocal')");
			$project = mysql_insert_id();
			$create_bug_table = @file_get_contents('sql/bug_table.sql');
			dbq(sprintf($create_bug_table, $project));
			$create_asset_table = @file_get_contents('sql/asset_table.sql');
			dbq(sprintf($create_asset_table, $project));
			//$objResponse->alert("Project inserted in database with ID ".$project);
			$objResponse->loadCommands(admin_projects($lsn));
		}
		else
		{
			dbq("UPDATE apps SET appName='$name', appDesc='$description', isLocal='$isLocal' WHERE ID=$project");
			if ( $x = mysql_affected_rows() )
			{
				// $objResponse->alert("Update affected $x row".(($x==1)?'':'s')." in database");
				$objResponse->loadCommands(admin_projects($lsn));
			}
			else
				$objResponse->alert("The update had no effect on the database");
		}
	}
	return $objResponse;
}
function admin_users($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn, true);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>User admin area</h1>';
	dbconnect();
	$page.= '<table class="users">';
	$page.= '<thead>';
	$page.= '<tr><th colspan="8"><input type="button" class="default" value="Add user" onClick="xajax_edit_user(lsn)"></th></tr>';
	$page.= '<tr>';
	// $page.= '<th>ID</th>';
	$page.= '<th>Login</th>';
	$page.= '<th>Name</th>';
	$page.= '<th>E-Mail</th>';
	$page.= '<th>Group</th>';
	$page.= '<th>Last login</th>';
	$page.= '<th>Privileges</th>';
	$page.= '<th></th>';
	$page.= '</tr></thead><tbody>';
	$i = 0;
	dbq("SELECT users.ID, login, name, email, groupName, lastLoginDate, privilege FROM users, groups WHERE groups.ID=users.groupID ORDER BY login");
	while ( $row = dbrow() )
	{
		$i = 1 - $i;
		$page.= '<tr class="'.(($i == 0)?'even':'odd').'">';
		// $page.= '<td>'.$row['ID'].'</td>';
		$page.= '<td>'.htmlspecialchars($row['login']).'</td>';
		$page.= '<td>'.htmlspecialchars($row['name']).'</td>';
		$page.= '<td><a href="mailto:'.$row['email'].'">'.htmlspecialchars($row['email']).'</a></td>';
		$page.= '<td>'.htmlspecialchars($row['groupName']).'</td>';
		if ( empty($row['lastLoginDate']) )
			$page.='<td>Never</td>';
		else
			$page.= '<td>'.date(DATE_LOGIN, $row['lastLoginDate']).'</td>';
		$page.= '<td>'.(($row['privilege'] & ADMINISTRATOR)?'administrator<br>':'').(($row['privilege'] & CAN_BE_ASSIGNED)?'bugs / tasks can be assigned<br>':'').(($row['privilege'] & CAN_EDIT)?'can edit (advanced)<br>':'').(($row['privilege'] & CAN_CLOSE)?'can close<br>':'').(($row['privilege'] & GUEST)?'is guest<br>':'').'</td>';
		$page.= '<td><input type="button" class="default" value="Edit" onClick="xajax_edit_user(lsn, '.$row['ID'].')"></td>';
		$page.= '</tr>';
	}
	$page.= '</tbody><tfoot>';
	$page.= '<tr><th colspan="8"><input type="button" class="default" value="Add user" onClick="xajax_edit_user(lsn)"></th></tr>';
	$page.= '</tfoot></table>';
	$page.= '<h1>User to project linking</h1>';
	$page.= '<table class="simplegrid">';
	$page.= '<thead><tr><td>&nbsp;</td>';
	dbq("SELECT ID, appName FROM apps ORDER BY isLocal ASC");
	$apps = array();
	$a2u = array();
	while ( $row = dbrow(1) )
	{
		$apps[$row[0]] = $row[1];
		$page.='<th>'.htmlspecialchars($row[1]).'</th>';
		$a2u[$row[0]] = array();
	}
	$page.= '</tr><tbody>';
	dbq("SELECT userID, appID FROM user_to_app");
	while ( $row = dbrow() )
		$a2u[$row['appID']][$row['userID']] = true;
	dbq("SELECT ID, login FROM users ORDER BY login");
	while ( $user = dbrow() )
	{
		$page.= '<tr>';
		$page.= '<th>'.$user['login'].'</th>';
		foreach ( $apps as  $appID=>$appName )
			$page.='<td><input type="checkbox"'.(empty($a2u[$appID][$user['ID']])?'':' checked="checked"').' onClick="xajax_user2app(lsn, '.$user['ID'].', '.$appID.', this.checked)"></td>';
		$page.= '</tr>';
	}
	$page.= '</tbody></table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function user2app($lsn, $user, $appID, $checked)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$user = (int)$user;
	$appID = (int)$appID;
	if ( strcasecmp($checked, 'true') == 0 )
		$checked = true;
	elseif ( strcasecmp($checked, 'false') == 0 )
		$checked = false;
	elseif ( $checked == '1' )
		$checked = true;
	else
		$checked = false;
	dbconnect();
	if ( $checked )
		dbq("INSERT INTO user_to_app(userID, appID) VALUES ($user, $appID)");
	else
	{
		dbq("DELETE FROM user_to_app WHERE userID=$user and appID=$appID");
		dbq("UPDATE settings SET appID=0 WHERE userID=$user and appID=$appID");
	}
	return $objResponse;
}
function edit_user($lsn, $user = 0)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$user = (int)$user;
	dbconnect();
	require_once 'inc/serverinfo.inc.php';
	$page = '';
	if ( func_num_args() <= 2 )
	{
		if ( !empty($user) )
		{
			dbq("SELECT login, name, email, groupID, privilege FROM users WHERE ID=".$user);
			list ($login, $name, $email, $groupID, $privilege) = dbrow(1);
			$canAssignTo = ($privilege & CAN_BE_ASSIGNED)?'Y':'N';
			$page.= '<h1>Edit user information</h1>';
			$page.= 'If you leave the password field empty the password will not change';
		}
		else
		{
			$login = '';
			$name = '';
			$email = '';
			$groupID = '1';
			$privilege = CAN_EDIT | CAN_BE_ASSIGNED;
			$page.= '<h1>Add user to '.SERVER_NAME.'</h1>';
		}
		// get valid groups
		$theGroups = '';
		dbq("SELECT ID, groupName FROM groups");
		$result = dbr(1);
		foreach ( $result as $r )
			$theGroups.= '<option value="'.$r[0].'"'.(($r[0] == $groupID)?' selected=""selected':'').'>'.$r[1].'</option>';
		unset($result);

		$page.= '<table class="login"><tbody>';
		$page.= '<tr><th>login</th><td><input type="text" id="login" value="'.$login.'"></td></tr>';
		$page.= '<tr><th>name</th><td><input type="text" id="name" value="'.$name.'"></td></tr>';
		$page.= '<tr><th>email</th><td><input type="text" id="email" value="'.$email.'"></td></tr>';
		$page.= '<tr><th>pass</th><td><input type="password" id="pass" value=""></td></tr>';
		$page.= '<tr><th>group</th><td><select id="group">'.$theGroups.'</select></td></tr>';
		$page.= '<tr><th>guest (only view)</th><td><select id="guest">';
		$page.= '<option value="1"'.(($privilege & GUEST)?' selected="selected"':'').'>Yes</option>';
		$page.= '<option value="0"'.(($privilege & GUEST)?'':'selected="selected"').'>No</option>';
		$page.= '</td></tr>';
		$page.= '<tr><th>can edit (advanced)</th><td><select id="canEdit">';
		$page.= '<option value="1"'.(($privilege & CAN_EDIT)?' selected="selected"':'').'>Yes</option>';
		$page.= '<option value="0"'.(($privilege & CAN_EDIT)?'':'selected="selected"').'>No</option>';
		$page.= '</td></tr>';
		$page.= '<tr><th>can close</th><td><select id="canClose">';
		$page.= '<option value="1"'.(($privilege & CAN_CLOSE)?' selected="selected"':'').'>Yes</option>';
		$page.= '<option value="0"'.(($privilege & CAN_CLOSE)?'':'selected="selected"').'>No</option>';
		$page.= '</td></tr>';
		$page.= '<tr><th>bugs / tasks can be assigned</th><td><select id="assign">';
		$page.= '<option value="1"'.(($privilege & CAN_BE_ASSIGNED)?' selected="selected"':'').'>Yes</option>';
		$page.= '<option value="0"'.(($privilege & CAN_BE_ASSIGNED)?'':'selected="selected"').'>No</option>';
		$page.= '</td></tr>';
		$page.= '<tr><th>administrator</th><td><select id="admin">';
		$page.= '<option value="1"'.(($privilege & ADMINISTRATOR)?' selected="selected"':'').'>Yes</option>';
		$page.= '<option value="0"'.(($privilege & ADMINISTRATOR)?'':'selected="selected"').'>No</option>';
		$page.= '</td></tr>';
		$page.= '</tbody><tfoot>';
		$page.= '<tr><td colspan="2"><input type="button" class="default" value="'.(empty($user)?'Add user':'Save user').'" onClick="xajax_edit_user(lsn, '.$user.', ';
		$page.= 'document.getElementById(\'login\').value, ';
		$page.= 'document.getElementById(\'name\').value, ';
		$page.= 'document.getElementById(\'email\').value, ';
		$page.= 'document.getElementById(\'pass\').value, ';
		$page.= 'document.getElementById(\'group\').value, ';
		$page.= 'document.getElementById(\'assign\').value, ';
		$page.= 'document.getElementById(\'guest\').value, ';
		$page.= 'document.getElementById(\'canEdit\').value, ';
		$page.= 'document.getElementById(\'canClose\').value,';
		$page.= 'document.getElementById(\'admin\').value';
		$page.= ')"></td></tr></tfoot>';
		$page.= '</table>';
		$objResponse->assign('details', 'innerHTML', $page);
	}
	else
	{
		$args = func_get_args();
		$login = dbesc(trim($args[2]));
		if ( empty($login) || ($login != $args[2]) )
		{
			$objResponse->alert('The user shoud have a pretty login name.');
			return $objResponse;
		}
		$name = dbesc($args[3]);
		$email = dbesc($args[4]);
		$pass = $args[5];
		$group = (int)$args[6];
		$canAssignTo = (bool)$args[7][0];
		$isGuest = (bool)$args[8][0];
		$canEdit = (bool)$args[9][0];
		$canClose = (bool)$args[10][0];
		$admin = (bool)$args[11][0];

		dbq("SELECT ID FROM users WHERE login='$login'");
		while ( $row = dbrow(1) )
			if ( $row[0] != $user )
			{
				$objResponse->alert('Username found in database. The user shoud have a unique name.');
				return $objResponse;
			}
		if ( empty($user) || !empty($pass) )
		{
			require_once('inc/sha256.inc.php');
			$hashed = sha256($pass, true); // false = use internal function if it exists
			$pass = dbesc($hashed);
		}
		$privilege = ($canAssignTo?CAN_BE_ASSIGNED:0) | ($isGuest?GUEST:0) | ($canEdit?CAN_EDIT:0) | ($canClose?CAN_CLOSE:0) | ($admin?ADMINISTRATOR:0);
		if ( empty($user) )
		{
			dbq("INSERT INTO users(login, name, email, password, groupID, privilege, session) VALUES ('$login', '$name', '$email', '$pass', $group, $privilege, 'Created on ".date(DATE_TIME)."')");
			$user = mysql_insert_id();
			dbq("INSERT INTO profiles(userID, profileName, profileType, profileData) VALUES ($user, 'My bugs', 'bugfilter', '".dbesc(serialize(array(array('AND',array(array('AND', array('statusID', '!=', BUG_STATUS_CLOSED)), array('AND', array('assignedTo', '=', $user)))))))."')");
			$bugFilterID = mysql_insert_id();
			dbq("INSERT INTO profiles(userID, profileName, profileType, profileData) VALUES ($user, 'My bugs display', 'bugdisplay', '".dbesc(serialize(array('ID', 'platformName', 'frequencyName', 'frequencyPercent', 'title', 'assignedToName', 'assToGroupName', 'closedByName', 'severityName', 'statusName', 'typeName', 'openDate', 'closeDate', 'submitedDate', 'versionDate')))."')");
			$bugDisplayID = mysql_insert_id();
			dbq("INSERT INTO profiles(userID, profileName, profileType, profileData) VALUES ($user, 'My tasks', 'assetfilter', '".dbesc(serialize(array(array('AND',array(array('AND', array('statusID', '!=', BUG_STATUS_CLOSED)), array('AND', array('assignedTo', '=', $user)))))))."')");
			$assetFilterID = mysql_insert_id();
			dbq("INSERT INTO profiles(userID, profileName, profileType, profileData) VALUES ($user, 'My tasks display', 'assetdisplay', '".dbesc(serialize(array('ID', 'platformName', 'frequencyName', 'frequencyPercent', 'title', 'assignedToName', 'assToGroupName', 'closedByName', 'severityName', 'statusName', 'typeName', 'openDate', 'closeDate', 'submitedDate', 'versionDate')))."')");
			$assetDisplayID = mysql_insert_id();
			dbq("INSERT INTO settings(userID, bugFilterID, bugDisplayID, assetFilterID, assetDisplayID) VALUES($user, $bugFilterID, $bugDisplayID, $assetFilterID, $assetDisplayID)");
			//$objResponse->alert("User inserted in database with ID ".$user);
			$objResponse->loadCommands(admin_users($lsn));
		}
		else
		{
			dbq("UPDATE users SET login='$login', name='$name', email='$email', groupID=$group, privilege=$privilege".(empty($pass)?'':", password='$pass'")." WHERE ID=$user");
			if ( $x = mysql_affected_rows() )
				$objResponse->alert("Update affected $x row".(($x==1)?'':'s')." in database");
			else
				$objResponse->alert("The update had no effect on the database");
		}
	}
	return $objResponse;
}
function home_page($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		//mori('error in lsn '.s_var_dump($lsn).' session shoud be '.getCurrentSession());
		$objResponse->script('self.location.replace(\''.getThisPageURI('/').'\')');
	}
	else
	{
		require_once 'inc/serverinfo.inc.php';
		list ($session, $userID) = $strip;
		dbconnect();
		dbq('SELECT users.name, groupName, users.privilege FROM users, groups WHERE users.ID='.$userID.' and users.groupID=groups.ID');
		list ($r) = dbr();
		$page = '';
		$page.= "<h1>Hello $r[name] and welcome to ".SERVER_NAME." v".SERVER_VERSION."</h1>";
		if ( empty($r['groupName']) )
			$page.= 'You are in no group. Ask the Project Leader about this problem.';
		else
		{
			$page.= "You are currently a member of the \"$r[groupName]\" group and you have access to the following projects:";
			dbq("SELECT apps.ID, appName, appDesc FROM user_to_app, apps WHERE user_to_app.userID=$userID and user_to_app.appID=apps.ID");
			$myapps = array();
			while ( $row = dbrow(1) )
				$myapps[$row[0]] = $row[1].' ( '.$row[2].' )';
			dbq("SELECT ID, statusName FROM status");
			while ( $row = dbrow(1) )
				$status[$row[0]] = $row[1];
			$page.= '<br><ul>';
			foreach ($myapps as $appID=>$appName)
			{
				$page.= '<li><h2>'.$appName.'</h1>';
				
				$page.= '<table class="home"><tr>';
				
				$page.= '<th>Bugs assigned to you';
				$page.= '<table class="home"><tr>';
				$page.= '<th>New</th><th>Open</th><th>Reopen</th>';
				$page.= '</tr><tr>';
				dbq("SELECT COUNT(*) FROM bugs$appID WHERE assignedTo=$userID and (flags & ".BUG_VIEWED.")=0");
				$row = dbrow(1);
				if ( empty($row[0]) )
					$row[0] = '-';
				$page.= '<td>'.$row[0].'</td>';
				dbq("SELECT COUNT(*) FROM bugs$appID WHERE assignedTo=$userID and statusID=".BUG_STATUS_OPEN);
				$row = dbrow(1);
				if ( empty($row[0]) )
					$row[0] = '-';
				$page.= '<td>'.$row[0].'</td>';
				dbq("SELECT COUNT(*) FROM bugs$appID WHERE assignedTo=$userID and statusID=".BUG_STATUS_REOPEN);
				$row = dbrow(1);
				if ( empty($row[0]) )
					$row[0] = '-';
				$page.= '<td>'.$row[0].'</td>';
				$page.= '</tr></table>';
				$page.= '</th>';
				
				if ( $r['privilege'] & ADMINISTRATOR )
				{
					$page.= '<th>Bugs';
					$page.= '<table class="home"><tr>';
					foreach ( $status as $statusName )
						$page.= "<th>$statusName</th>";
					$page.= '</tr><tr>';
					foreach ( $status as $statusID=>$statusName )
					{
						dbq("SELECT COUNT(*) FROM bugs$appID WHERE statusID=$statusID");
						$row = dbrow(1);
						if ( empty($row[0]) )
							$row[0] = '-';
						$page.= "<td>$row[0]</td>";
					}
					$page.= '</tr></table>';
					$page.= '</th>';
				}
				
				$page.= '</tr><tr>';
				
				$page.= '<td>Tasks assigned to you';
				$page.= '<table class="home"><tr>';
				$page.= '<th>New</th><th>Open</th><th>Reopen</th>';
				$page.= '</tr><tr>';
				dbq("SELECT COUNT(*) FROM assets$appID WHERE assignedTo=$userID and (flags & ".BUG_VIEWED.")=0");
				$row = dbrow(1);
				if ( empty($row[0]) )
					$row[0] = '-';
				$page.= '<td>'.$row[0].'</td>';
				dbq("SELECT COUNT(*) FROM assets$appID WHERE assignedTo=$userID and statusID=".BUG_STATUS_OPEN);
				$row = dbrow(1);
				if ( empty($row[0]) )
					$row[0] = '-';
				$page.= '<td>'.$row[0].'</td>';
				dbq("SELECT COUNT(*) FROM assets$appID WHERE assignedTo=$userID and statusID=".BUG_STATUS_REOPEN);
				$row = dbrow(1);
				if ( empty($row[0]) )
					$row[0] = '-';
				$page.= '<td>'.$row[0].'</td>';
				$page.= '</tr></table>';
				$page.= '</td>';
				
				if ( $r['privilege'] & ADMINISTRATOR )
				{
					$page.= '<td>Tasks';
					$page.= '<table class="home"><tr>';
					foreach ( $status as $statusName )
						$page.= "<th>$statusName</th>";
					$page.= '</tr><tr>';
					foreach ( $status as $statusID=>$statusName )
					{
						dbq("SELECT COUNT(*) FROM assets$appID WHERE statusID=$statusID");
						$row = dbrow(1);
						if ( empty($row[0]) )
							$row[0] = '-';
						$page.= "<td>$row[0]</td>";
					}
					$page.= '</tr></table>';
					$page.= '</td>';
				}
				$page.= '</tr></table>';
			}
			$page.= '</ul>';
		}
		$objResponse->assign('details', 'innerHTML', $page);
	}
	return $objResponse;
}
function my_details($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	if ( func_num_args() > 1 )
	{
		$cnt = 1;
		$login = dbesc(func_get_arg($cnt++));
		$name = dbesc(func_get_arg($cnt++));
		$email = dbesc(func_get_arg($cnt++));
		$pass = func_get_arg($cnt++);
		$pass2 = func_get_arg($cnt++);
		if ( $pass != $pass2 )
			return $objResponse->alert('You entered two different passwords');
		if ( empty($pass) )
			$password = '';
		else
		{
			require_once('inc/sha256.inc.php');
			$hashed = sha256($pass, true); // false = use internal function if it exists
			$password = ", password='".dbesc($hashed)."'";
		}
		dbq("UPDATE users SET login='$login', name='$name', email='$email' $password WHERE ID=$userID");
		if ( mysql_affected_rows() == 1 )
			$objResponse->alert('account details changed');
	}
	$page = '<h1>Account details</h1>';
	if ( empty($login) )
	{
		dbq("SELECT login, name, email FROM users WHERE ID=$userID");
		list ($login, $name, $email) = dbrow(1);
	}
	$page.= '<table class="login"><tbody>';
	$page.= '<tr><th>Login</th><td><input type="text" id="login" value="'.$login.'"></td></tr>';
	$page.= '<tr><th>Full name</th><td><input type="text" id="name" value="'.$name.'"></td></tr>';
	$page.= '<tr><th>E-Mail</th><td><input type="text" id="email" value="'.$email.'"></td></tr>';
	$page.= '<tr><th>Password</th><td><input type="password" id="pass" value=""></td></tr>';
	$page.= '<tr><th>Re-enter password</th><td><input type="password" id="pass2" value=""></td></tr>';
	$page.= '</tbody><tfoot>';
	$page.= '<tr><td colspan="2"><input type="button" value="Save" onClick="xajax_my_details(lsn, ';
	$page.= 'document.getElementById(\'login\').value, ';
	$page.= 'document.getElementById(\'name\').value, ';
	$page.= 'document.getElementById(\'email\').value, ';
	$page.= 'document.getElementById(\'pass\').value, ';
	$page.= 'document.getElementById(\'pass2\').value';
	$page.= ')"></td></tr></tfoot>';
	$page.= '</table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function my_settings($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	if ( func_num_args() > 1 )
	{
		$cnt = 1;
		$css = dbesc(func_get_arg($cnt++));
		$bugsPerPage = (int)func_get_arg($cnt++);
		$bugsPerPage = max(10, $bugsPerPage);
		$allProfiles = ((bool)func_get_arg($cnt++))?SETTING_ALLPROFILES:0;
		$statusColor = ((bool)func_get_arg($cnt++))?SETTING_STATUSCOLOR:0;
		$sendMail = ((bool)func_get_arg($cnt++))?SETTING_SENDMAIL:0;
		$flags = $allProfiles | $sendMail | $statusColor;
		dbq("UPDATE settings SET CSS='$css', bugsPerPage=$bugsPerPage, flags=$flags WHERE userID=$userID");
		$objResponse->alert('settings saved');
	}
	$page = '<h1>Settings</h1>';
	if ( empty($bugsPerPage) )
	{
		dbq("SELECT CSS, bugsPerPage, flags FROM settings WHERE userID=$userID");
		list ($css, $bugsPerPage, $flags) = dbrow(1);
		$allProfiles = $flags & SETTING_ALLPROFILES;
		$sendMail = $flags & SETTING_SENDMAIL;
		$statusColor = $flags & SETTING_STATUSCOLOR;
	}
	$page.= '<table>';
	$page.= '<tr><th>CSS</th><td><input type="text" id="my_CSS" value="'.$css.'"> <input type="button" class="default" value="reset CSS to default" onClick="xjx.$(\'my_CSS\').value=\'css/main.css\'"></td></tr>';
	$page.= '<tr><th>Bugs per page</th><td><input type="text" id="bugsPerPage" value="'.$bugsPerPage.'"></td></tr>';
	$page.= '<tr><th>Profile lists</th><td><select id="allprofiles">';
	$page.= '<option value="0" '.($allProfiles==0?'selected="selected"':'').'>show only own profiles</option>';
	$page.= '<option value="1" '.($allProfiles!=0?'selected="selected"':'').'>show all profiles</option>';
	$page.= '</select></td></tr>';
	$page.= '<tr><th>Background color according to </th><td><select id="statusColor">';
	$page.= '<option value="0" '.($statusColor==0?'selected="selected"':'').'>priority</option>';
	$page.= '<option value="1" '.($statusColor!=0?'selected="selected"':'').'>status</option>';
	$page.= '</select></td></tr>';
	$page.= '<tr><th>EMail</th><td><select id="sendmail">';
	$page.= '<option value="0" '.($sendMail==0?'selected="selected"':'').'>don\'t send</option>';
	$page.= '<option value="1" '.($sendMail!=0?'selected="selected"':'').'>send</option>';
	$page.= '</select></td></tr>';
	$page.= '<tr><td colspan="2"><input type="button" class="default" value="Save" onClick="xajax_my_settings(lsn,';
	$page.= 'xjx.$(\'my_CSS\').value,';
	$page.= 'xjx.$(\'bugsPerPage\').value,';
	$page.= 'xjx.$(\'allprofiles\').value,';
	$page.= 'xjx.$(\'statusColor\').value,';
	$page.= 'xjx.$(\'sendmail\').value';
	$page.= ')"></td</tr>';
	$page.= '</table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function print_filters($lsn, $bBug, $filterID = NULL, $profileName = NULL, $profileData = NULL, $profileOwnerID = NULL)
{
	global $global_select_error_text;
	$global_select_error_text = '';
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	if ( empty($filterID) )
	{
		if ( $bBug )
			dbq("SELECT bugFilterID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.bugFilterID=profiles.ID");
		else
			dbq("SELECT assetFilterID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.assetFilterID=profiles.ID");
		list ($filterID, $profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	elseif ( empty($profileName) || empty($profileData) || empty($profileOwnerID) )
	{
		dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$filterID");
		list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
/*
	if ( empty($profileData) )
	{
		$type = 'error';
		$profileData = '';
	}
	else
	{
		$type = $profileData[0];
		$profileData = substr($profileData, 1);
	}
	switch ( $type )
	{
		case '1': // compressed with gzdeflate
			$profileData = gzinflate($profileData);
			break;
		default: // not compressed
			break;
	}
*/
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array(array('AND',array()));
		
//demo
// $profileData = array
// (
	// array
	// (
		// 'AND',
		// array
		// (
			// array('AND', array('severityID', '=', '1')),
			// array('AND', array('platformID', '=', '2')),
		// ),
	// ),
	// array
	// (
		// 'OR',
		// array
		// (
			// array('AND', array('frequencyID', '=', '3')),
		// ),
	// ),
// );

	# printing profile information
	$profile_info = '';
	if ( $userID != $profileOwnerID )
		$profile_info.= '<input type="button" class="default" value="Save profile as" onClick="xajax_save_profile_as(lsn, '.$filterID.', document.getElementById(\'save_filter_as\').value)"><input type="text" class="profile_name" value="'.$profileName.'" id="save_filter_as"><br>';
	$profile_info.= 'Current profile <select class="profile" onChange="xajax_change_profile(lsn, this.value)">';
	dbq("SELECT flags FROM settings WHERE userID=$userID");
	list($flags) = dbrow(1);
	dbq("SELECT profileName, login AS userName, profiles.ID, userID FROM profiles, users WHERE userID=users.ID and profileType='".($bBug?'bug':'asset')."filter'".(($flags & SETTING_ALLPROFILES)?'':' and userID='.$userID).' ORDER BY login, profileName');
	while ( $row = dbrow() )
	{
		if ( $row['ID'] == $filterID )
			$selected = ' selected="selected"';
		else
			$selected = '';
		if ( $row['userID'] == $userID )
			$owner = '';
		else
			$owner = ' - (owner '.$row['userName'].')';
		$profile_info.= '<option value="'.$row['ID'].'"'.$selected.'>'.$row['profileName'].$owner.'</option>';
	}
	$profile_info.= '</select>';

	$txt = '<h1>Filter "'.$profileName.'"</h1>';
	$txt.= print_filter_nice($filterID, $profileData, $profile_info, $userID == $profileOwnerID, $bBug);

	$objResponse->assign('filters_toggle', 'innerHTML', $txt);
	if ( !empty($global_select_error_text) )
		$objResponse->alert('WARNING:'.CRLF.$global_select_error_text);
	return $objResponse;
}
########################################
## used from "view bugs" or similar pages
########################################
function change_profile($lsn, $profileID)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	$profileID = (int)$profileID;
	dbq("SELECT profileType FROM profiles WHERE ID=$profileID");
	list ($profileType) = dbrow(1);
	$profileType = strtolower($profileType);
	switch ( $profileType )
	{
		case 'bugfilter':
			dbq("UPDATE settings SET bugFilterID=$profileID WHERE userID=$userID");
			$objResponse->loadCommands(print_filters($lsn, true, $profileID));
			break;
		case 'bugdisplay':
			dbq("UPDATE settings SET bugDisplayID=$profileID WHERE userID=$userID");
			$objResponse->loadCommands(print_display($lsn, true, $profileID));
			$objResponse->loadCommands(print_bug_table($lsn, 0, '', true));
			break;
		case 'assetfilter':
			dbq("UPDATE settings SET assetFilterID=$profileID WHERE userID=$userID");
			$objResponse->loadCommands(print_filters($lsn, false, $profileID));
			break;
		case 'assetdisplay':
			dbq("UPDATE settings SET assetDisplayID=$profileID WHERE userID=$userID");
			$objResponse->loadCommands(print_display($lsn, false, $profileID));
			$objResponse->loadCommands(print_bug_table($lsn, 0, '', false));
			break;
	}
	return $objResponse;
}
########################################
## used from "my_profiles" page
########################################
function make_default_profile($lsn, $profileID, $profileType)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	$profileID = (int)$profileID;
	$profileType = strtolower($profileType);
	switch ( $profileType )
	{
		case 'bugfilter':
			dbq("UPDATE settings SET bugFilterID=$profileID WHERE userID=$userID");
			break;
		case 'bugdisplay':
			dbq("UPDATE settings SET bugDisplayID=$profileID WHERE userID=$userID");
			break;
		case 'assetfilter':
			dbq("UPDATE settings SET assetFilterID=$profileID WHERE userID=$userID");
			break;
		case 'assetdisplay':
			dbq("UPDATE settings SET assetDisplayID=$profileID WHERE userID=$userID");
			break;
		default:
			$objResponse->alert('profileType "'.$profileType.'" is unknown');
			break;
	}
	$objResponse->loadCommands(my_profiles($lsn));
	return $objResponse;
}
function save_profile_as($lsn, $profileID, $name, $profileType='')
/**
Duplicates an existing profile, saves it with name $name and changes to that profile
OR if $profileID is empty, it makes a new profile with the $profileType and displays the my_profiles page
*/
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	$name = dbesc($name);
	$profileID = (int)$profileID;
	$q = dbq("SELECT ID FROM profiles WHERE profileName='$name' and userID=$userID");
	if ( mysql_num_rows($q) > 0 )
	{
		$objResponse->alert('One of your profiles has the same name, please choose some other name.');
		return $objResponse;
	}
	if ( empty($profileID) )
	{
		$profileData = '';
		// profileType taken as an argument
	}
	else
	{
		dbq("SELECT profileType, profileData FROM profiles WHERE ID=$profileID");
		list ($profileType, $profileData) = dbrow(1);
	}
	$profileData = dbesc($profileData);
	$profileType = dbesc($profileType);
	dbq("INSERT INTO profiles(userID, profileName, profileType, profileData) VALUES($userID, '$name', '$profileType', '$profileData')");
	if ( empty($profileID) )
		$objResponse->loadCommands(my_profiles($lsn));
	else
	{
		$profileID = mysql_insert_id();
		$objResponse->loadCommands(change_profile($lsn, $profileID));
		$objResponse->alert('Profile saved');
	}
	return $objResponse;
}
function change_filter($lsn, $filterID, $row, $cnt, $operand, $value)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT profileName, profileData, userID, profileType FROM profiles WHERE ID=$filterID");
	list ($profileName, $profileData, $profileOwnerID, $profileType) = dbrow(1);
	switch ( $profileType )
	{
		case 'bugfilter':
		case 'bugdisplay':
			$bBug = true;
			break;
		case 'assetfilter':
		case 'assetdisplay':
		default:
			$bBug = false;
			break;
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();
	if ( isset($profileData[$row][1][$cnt][0]) )
	{
		if ( $operand == -1 )
			$profileData[$row][1][$cnt][0] = $value;
		elseif ( $operand == 0 )
		{
			$profileData[$row][1][$cnt][1][0] = $value;
			$profileData[$row][1][$cnt][1][1] = '=';
			$profileData[$row][1][$cnt][1][2] = '1';
		}
		else
			$profileData[$row][1][$cnt][1][$operand] = $value;
	}
	elseif ( isset($profileData[$row][0]) )
	{
		$profileData[$row][0] = $value;
	}
	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_filters($lsn, $bBug, $filterID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$filterID);
	return $objResponse;
}
function remove_filter($lsn, $filterID, $row, $cnt = -1)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT profileName, profileData, userID, profileType FROM profiles WHERE ID=$filterID");
	list ($profileName, $profileData, $profileOwnerID, $profileType) = dbrow(1);
	switch ( $profileType )
	{
		case 'bugfilter':
		case 'bugdisplay':
			$bBug = true;
			break;
		case 'assetfilter':
		case 'assetdisplay':
		default:
			$bBug = false;
			break;
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();
	if ( ($cnt == -1) && isset($profileData[$row]) )
		unset($profileData[$row]);
	elseif ( isset($profileData[$row][$cnt]) )
		unset($profileData[$row][$cnt]);
	else
		$objResponse->alert('coud not remove filter (filter not found)');
	if ( empty($profileData) )
		$profileData = array(array('AND',array()));
/*
		$profileData = array
		(
			array
			(
				'AND',
				array
				(
					array('AND', array('statusID', '=', BUG_STATUS_OPEN)),
					array('OR', array('statusID', '=', BUG_STATUS_REOPEN)),
				),
			),
			array
			(
				'AND',
				array
				(
					array('AND', array('assignedTo', '=', getCurrentUserID())),
				),
			),
		);
*/
## Repair any broken or exess data in the $profileData, also re-number the fields
	$profileData2 = array();
	$i = 0;
	foreach ( $profileData as $row )
	{
		list ($expr_op, $expr) = $row;
		$profileData2[$i] = array($expr_op, array());
		foreach ( $expr as $filters )
		{
			list ($filter_op, $filter) = $filters;
			list ($a, $op, $b) = $filter;
			$profileData2[$i][1][] = array($filter_op, array($a, $op, $b));
		}
		$i += 1;
	}
	$profileData = serialize($profileData2);
	unset($profileData2);
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$filterID);
	$objResponse->loadCommands(print_filters($lsn, $bBug, $filterID, $profileName, $profileData, $profileOwnerID));
	return $objResponse;
}
function add_filter($lsn, $filterID, $row = -1)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT profileName, profileData, userID, profileType FROM profiles WHERE ID=$filterID");
	list ($profileName, $profileData, $profileOwnerID, $profileType) = dbrow(1);
	switch ( $profileType )
	{
		case 'bugfilter':
		case 'bugdisplay':
			$bBug = true;
			break;
		case 'assetfilter':
		case 'assetdisplay':
		default:
			$bBug = false;
			break;
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();
	if ( $row == -1 )
		$profileData[] = array('AND', array(array('AND', array('severityID', '=', '1'))));
	elseif ( isset($profileData[$row]) )
		$profileData[$row][1][] = array('AND', array('severityID','=','1'));
	else
		$objResponse->script('coud not add filter (filter not found)');
	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_filters($lsn, $bBug, $filterID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$filterID);
	return $objResponse;
}
function print_display($lsn, $bBug, $displayID = NULL, $profileName = NULL, $profileData = NULL, $profileOwnerID = NULL)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '';
	dbconnect();
	if ( empty($displayID) )
	{
		if ( $bBug )
			dbq("SELECT bugDisplayID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.bugDisplayID=profiles.ID");
		else
			dbq("SELECT assetDisplayID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.assetDisplayID=profiles.ID");
		list ($displayID, $profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	elseif ( empty($profileName) || empty($profileData) || empty($profileOwnerID) )
	{
		dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$displayID");
		list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	dbq("SELECT orderBy FROM settings WHERE userID=$userID");
	list ( $orderBy ) = dbrow(1);
	$orderBy = explode(',', $orderBy);
	if ( empty($orderBy[1]) )
		$orderBy[1] = 1;
	
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();
	
	$possible_cols = get_possible_columns($bBug);
	$possible_orderBy = get_possible_orderBy($bBug);
	
	if ( $userID == $profileOwnerID )
	{
		function select_col(&$possible_cols, $sel, $displayID, $col_nr)
		{
			$select = '<select class="display" onChange="xajax_change_display(lsn, '.$displayID.', '.$col_nr.', this.value)">';
			foreach ( $possible_cols as $col )
			{
				list ($name, $echo, $sql) = $col;
				if ( $sel == $name )
					$selected = ' selected="selected"';
				else
					$selected = '';
				$select.= '<option value="'.$name.'"'.$selected.'>'.htmlspecialchars($echo).'</option>';
			}
			$select.= '</select>';
			return $select;
		}
	}
	else
	{
		function select_col(&$possible_cols, $sel, $displayID, $col_nr)
		{
			$select = '';
			foreach ( $possible_cols as $col )
			{
				list ($name, $echo, $sql) = $col;
				if ( $sel == $name )
				{
					$select.= ' '.htmlspecialchars($echo).' ';
					break;
				}
			}
			$select.= '</select>';
			return $select;
		}
	}
	
	$page.= '<h1>View "'.$profileName.'"</h1>';
	$page.= '<table class="display_toggle">';
	$first_row = true;
	foreach ( $profileData as $k=>$col )
	{
		$page.= '<tr><td class="display_column">';
		$page.= select_col($possible_cols, $col, $displayID, $k);
		$page.= '</td><td class="display_options">';
		if ( $userID == $profileOwnerID )
		{
			$page.= '<input type="button" class="display_btn" value="Add column before current" onClick="xajax_add_display_before(lsn, '.$displayID.', '.$k.')">';
			$page.= '<input type="button" class="display_btn" value="Remove column" onClick="xajax_remove_display(lsn, '.$displayID.', '.$k.')">';
		}
		else
			$page.= '&nbsp;';
		if ( $first_row )
		{
			$first_row = false;
			$page.= '</td><td rowspan="'.(count($profileData) + 1).'">';
			$page.= 'Order by <select id="orderBy_select" onChange="xajax_change_orderby(lsn, this.value)"><option value="">nothing</option>';
			foreach ( $possible_orderBy as $col )
			{
				list ($name, $echo) = $col;
				if ( $orderBy[0] == $name )
					$selected = ' selected="selected"';
				else
					$selected = '';
				$page.= '<option value="'.$name.'"'.$selected.'>'.htmlspecialchars($echo).'</option>';
			}
			$page.= '</select><select id="orderBy_direction" onChange="xajax_change_orderby(lsn, 0, this.value)">';
			$page.= '<option value="1"'.(($orderBy[1]==1)?' selected="selected"':'').'>asc</option>';
			$page.= '<option value="2"'.(($orderBy[1]==2)?' selected="selected"':'').'>desc</option>';
			$page.= '</select>';
			$page.= '</td><td class="display_profile_details" rowspan="'.(count($profileData) + 1).'">';
			if ( $userID != $profileOwnerID )
				$page.= '<input type="button" class="display_btn" value="Save profile as" onClick="xajax_save_profile_as(lsn, '.$displayID.', document.getElementById(\'save_display_as\').value)"><input type="text" class="profile_name" value="'.$profileName.'" id="save_display_as"><br>';
			$page.= 'Current profile <select class="profile" onChange="xajax_change_profile(lsn, this.value)">';
			dbq("SELECT flags FROM settings WHERE userID=$userID");
			list($flags) = dbrow(1);
			dbq("SELECT profileName, login AS userName, profiles.ID, userID FROM profiles, users WHERE userID=users.ID and profileType='".($bBug?'bug':'asset')."display'".(($flags & SETTING_ALLPROFILES)?'':' and userID='.$userID));
			while ( $row = dbrow() )
			{
				if ( $row['ID'] == $displayID )
					$selected = ' selected="selected"';
				else
					$selected = '';
				if ( $row['userID'] == $userID )
					$owner = '';
				else
					$owner = ' - (owner '.$row['userName'].')';
				$page.= '<option value="'.$row['ID'].'"'.$selected.'>'.$row['profileName'].$owner.'</option>';
			}
			$page.= '</select>';
		}
		$page.= '</td></tr>';
	}
	$page.= '<tr><td></td><td class="display_options">';
	if ( $userID == $profileOwnerID )
		$page.= '<input type="button" class="display_btn" value="Add column" onClick="xajax_add_display_before(lsn, '.$displayID.', '.count($profileData).')">';
	$page.= '</td></tr>';
	$page.= '</table>';
	$objResponse->assign('col_display_toggle', 'innerHTML', $page);
	return $objResponse;
}
function change_orderby($lsn, $col = 0, $dir = 0, $refresh = false, $bBug = NULL)
{// $bBug must be set only if $refresh is true
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT orderBy FROM settings WHERE userID=$userID");
	list ( $orderBy ) = dbrow(1);
	$orderBy = explode(',', $orderBy);
	if ( empty($orderBy[1]) )
		$orderBy[1] = 1;
	if ( $dir == -1 )
		$dir = ( ($orderBy[1] == 1) && ($col == $orderBy[0]) )?2:1;
	if ( empty($col) && empty($dir) )
		dbq("UPDATE settings SET orderBy='' WHERE userID=$userID");
	elseif ( empty($dir) )
		dbq("UPDATE settings SET orderBy='".dbesc(implode(',', array($col, $orderBy[1])))."' WHERE userID=$userID");
	elseif ( empty($col) )
		dbq("UPDATE settings SET orderBy='".dbesc(implode(',', array($col = $orderBy[0], $dir)))."' WHERE userID=$userID");
	else
		dbq("UPDATE settings SET orderBy='".dbesc(implode(',', array($col, $dir)))."' WHERE userID=$userID");
	if ( $refresh )
		$objResponse->loadCommands(print_bug_table($lsn, 0, '', $bBug));
	$objResponse->script("select_option('orderBy_select', '$col');select_option('orderBy_direction', '$dir')");
	return $objResponse;
}
function change_display($lsn, $displayID, $k, $value)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT profileName, profileData, userID, profileType FROM profiles WHERE ID=$displayID");
	list ($profileName, $profileData, $profileOwnerID, $profileType) = dbrow(1);
	switch ( $profileType )
	{
		case 'bugfilter':
		case 'bugdisplay':
			$bBug = true;
			break;
		case 'assetfilter':
		case 'assetdisplay':
		default:
			$bBug = false;
			break;
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();

	if ( isset($profileData[$k]) )
		$profileData[$k] = $value;

	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_display($lsn, $bBug, $displayID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$displayID);
	return $objResponse;
}
function add_display_before($lsn, $displayID, $k)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT profileName, profileData, userID, profileType FROM profiles WHERE ID=$displayID");
	list ($profileName, $profileData, $profileOwnerID, $profileType) = dbrow(1);
	$profileData = @unserialize($profileData);
	switch ( $profileType )
	{
		case 'bugfilter':
		case 'bugdisplay':
			$bBug = true;
			break;
		case 'assetfilter':
		case 'assetdisplay':
		default:
			$bBug = false;
			break;
	}
	if ( empty($profileData) )
		$profileData = array();

	array_splice($profileData, $k, 0, 'ID');

	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_display($lsn, $bBug, $displayID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$displayID);
	return $objResponse;
}
function remove_display($lsn, $displayID, $k)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT profileName, profileData, userID, profileType FROM profiles WHERE ID=$displayID");
	list ($profileName, $profileData, $profileOwnerID, $profileType) = dbrow(1);
	switch ( $profileType )
	{
		case 'bugfilter':
		case 'bugdisplay':
			$bBug = true;
			break;
		case 'assetfilter':
		case 'assetdisplay':
		default:
			$bBug = false;
			break;
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();

	array_splice($profileData, $k, 1);

	if ( empty($profileData) )
		$profileData = array('ID');

	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_display($lsn, $bBug, $displayID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$displayID);
	return $objResponse;
}
function print_bug_table($lsn, $limitStart = 0, $customWhere = '', $bBug = true, $customWhere2 = '')
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT (SELECT profileData FROM settings, profiles WHERE settings.userID=$userID and settings.".($bBug?'bug':'asset')."FilterID=profiles.ID), 
		(SELECT profileData FROM settings, profiles WHERE settings.userID=$userID and settings.".($bBug?'bug':'asset')."DisplayID=profiles.ID)");
	list ( $filterData, $displayData ) = dbrow(1);
	dbq("SELECT appID, bugsPerPage FROM settings WHERE userID=$userID");
	list ( $defaultApp, $bugsPerPage ) = dbrow(1);
	if ( empty($defaultApp) )
		return $objResponse;
	$filterData = @unserialize($filterData);
	if ( empty($filterData) )
		$filterData = array();
	$displayData = @unserialize($displayData);
	if ( empty($displayData) )
		$displayData = array();
	$defaultApp = (int)$defaultApp;

	$page = '';
	$filter_txt = '';
	$header = array();
	$first_row = true;
	foreach ( $filterData as $rowID=>$row )
	{
		list ($expr_op, $expr) = $row;
		$t = '';
		$first_expr = true;
		foreach ( $expr as $rowFilterID=>$filters )
		{
			list ($filter_op, $filter) = $filters;
			if ( $first_expr )
				$first_expr = false;
			else
				$t.= ' '.$filter_op.' ';
			
			list ($a, $op, $b) = $filter;
			switch ( $op )
			{
				case '&':
					$t.= '('.$a.'&'.$b.')!=0';
				break;
				case '!&':
					$t.= '('.$a.'&'.$b.')=0';
				break;
				default:
					$t.= $a.$op.$b;
				break;
			}
		}
		if ( !empty($t) )
		{
			if ( !$first_row )
				$filter_txt.= ' '.$expr_op.' ';
			$filter_txt.= '('.$t.')';
			$first_row = false;
		}
	}
	if ( empty($filter_txt) )
		$filter_txt = 1;
	//writeToLog($filter_txt);
	$possible_cols = get_possible_columns($bBug);
	
// $displayData = array('ID', 'severityName', 'ID', 'title', 'assignedToName', 'closedByName', 'severityName');

	$display_txt = '';
	foreach ( $displayData as $val )
	{
		foreach ( $possible_cols as $k=>$col )
		{
			list ($name, $echo, $sql) = $col;
			if ( $val == $name )
			{
				if ( empty($col[3]) )
				{
					if ( !empty($display_txt) )
						$display_txt.= ', ';
					$display_txt.= $sql;
					$possible_cols[$k][3] = true;
				}
				$header[] = $echo;
				break;
			}
		}
	}
	if ( !empty($display_txt) )
		$display_txt.= ',';
	
	if ( !empty($customWhere) )
		$filter_txt = '('.$filter_txt.') AND ('.$customWhere.')';
	if ( !empty($customWhere2) )
		$filter_txt = '('.$filter_txt.') AND ('.$customWhere2.')';

	if ( $bBug )
		$fromTable = 'bugs'.$defaultApp;
	else
		$fromTable = 'assets'.$defaultApp;
	
	dbq("SELECT orderBy, flags FROM settings WHERE userID=$userID");
	list ( $orderBy, $flags ) = dbrow(1);
	$orderBy = explode(',', $orderBy);
	if ( empty($orderBy[1]) )
		$orderBy[1] = 1;
	if ( empty($orderBy[0]) )
		$orderBy = '';
	else
	{
		foreach ( $possible_cols as $k=>$col )
		{
			list ($name, $echo, $sql) = $col;
			if ( empty($col[3]) && ($orderBy[0] == $name) )
			{
				$display_txt.= $sql.',';
				break;
			}
		}
		$orderBy = 'ORDER BY '.$orderBy[0].' '.($orderBy[1]==2?'DESC':'ASC');
	}
	if ( $flags & SETTING_STATUSCOLOR )
		$color = "SELECT statusColor FROM status WHERE status.ID=statusID";
	else
		$color = "SELECT severityColor FROM severity WHERE severity.ID=severityID";
	$q = dbq("SELECT $display_txt ($color) AS bgColor, ID, assignedTo, flags FROM $fromTable WHERE $filter_txt $orderBy");
	$totalBugs = mysql_num_rows($q);
	$page.= '<table id="bug_table" class="bug_table">';
	// printing table header
	$page.= '<thead><tr>';
	$page.= '<th><input type="checkbox" onChange="check_all(\'bug_table\', this.checked)"></th>';
	foreach ( $header as $val )
	{
		foreach ( get_possible_orderBy($bBug) as $v )
			if ( $v[1] == $val )
			{
				$page.= '<th>';
				$page.= '<u style="cursor:pointer" onClick="xajax_change_orderby(lsn, \''.$v[0].'\', -1, 1, '.($bBug?1:0).')">'.$val.'</u>';
				$page.= print_second_filter($v[0], $defaultApp, $limitStart, $customWhere, $customWhere2, $bBug);
				$page.= '</th>';
				continue 2; // next header
			}
		$page.= '<th>'.$val.'</th>';
	}
	$page.= '</tr></thead>';
	// printing table body
	$page.= '<tbody>';
	$ignoreBugs = $limitStart;
	$displayBugs = $bugsPerPage;
	$thisPageURI = getThisPageURI('/');
	while ( $row = dbrow(0, $q) )
	{
		if ( $ignoreBugs > 0 )
		{
			$ignoreBugs -= 1;
			continue;
		}
		elseif ( $displayBugs > 0 )
			$displayBugs -= 1;
		else
		{
			$displayBugs = -1;
			break;
		}
		$page.= '<tr bgcolor="'.$row['bgColor'].'" onClick="lnk(this,'.($bBug?1:0).','.$row['ID'].','.$defaultApp.')">';
		$page.= '<th><input type="checkbox" id="a'.$defaultApp.'b'.$row['ID'].'">';
		if ( (!($row['flags'] & BUG_VIEWED)) && ($row['assignedTo'] == $userID) )
			$page.= '<br><img src="img/new.gif" alt="New">';
		$page.= '</th>';
		foreach ( $displayData as $val )
		{
			if ( empty($row[$val]) )
				$row[$val] = '&nbsp;';
			elseif ( in_array($val, array('closeDate', 'submitedDate', 'lastEdit')) )
				$row[$val] = date(DATE_TIME, $row[$val]);
			elseif ( in_array($val, array('openDate', 'deadLineDate')) )
				$row[$val] = date(DATE_NOTIME, $row[$val]);
			elseif ( $val == 'versionDate' )
				$row[$val] = date(DATE_BUGVER, $row[$val]);
			else
				$row[$val] = htmlspecialchars($row[$val]);
				
			// if ( $val == 'title' )
				// $row[$val] = '<a class="bug" target="_blank" href="'.$thisPageURI.'?'.($bBug?'bug':'asset').'='.$row['ID'].'&app='.$defaultApp.'">'.$row[$val].'</a>';
			$page.= '<td>'.$row[$val].'</td>';
			/*/
			$page.= '<td><a class="bug" target="_blank" href="?'.($bBug?'bug':'asset').'='.$row['ID'].'&app='.$defaultApp.'">'.$row[$val].'</a></td>';
			/*/
		}
		$page.= '</tr>';
	} 
	$page.= '</tbody>';
	$page.= '<tfoot><tr><th colspan="'.(count($header) + 1).'">';
	if ( empty($totalBugs) )
		$page.= 'No bugs to display';
	else
		$page.= 'Displaing '.($limitStart + 1).' - '.($limitStart + $bugsPerPage - max(0, $displayBugs)).' of '.$totalBugs.($bBug?' bugs':' tasks').' matching your filters';
	$page.= '</th></tr></tfoot>';
	$page.= '</table>';
	if ( ($displayBugs == -1) || ($limitStart > 0) )
	{
		$nrPages = $totalBugs / $bugsPerPage;
		$page.= 'Pages: ';
		$limitStart += 1;
		$etc = false;
		for ( $i = 0; $i < $nrPages; $i += 1 )
		{
			if ( (($i < (($limitStart - 1) / $bugsPerPage - 2)) || ($i > (($limitStart - 1) / $bugsPerPage + 2))) && ($i > 0) && ($i < ($nrPages - 1)) )
			{
				if ( !$etc )
				{
					$page.= ' ... ';
					$etc = true;
				}
				continue;
			}
			$etc = false;
			$page_start = $i * $bugsPerPage + 1;
			$page_end = min(($i + 1) * $bugsPerPage, $totalBugs);
			if ( ($limitStart >= $page_start) && ($limitStart <= $page_end) )
				$disabled = 'disabled="disabled"';
			else
				$disabled = '';
			$page.= '<input '.$disabled.' type="button" class="page" value="'.$page_start.' - '.$page_end.'" onClick="xajax_print_bug_table(lsn, '.($i * $bugsPerPage).', \''.$customWhere.'\', '.($bBug?1:0).', \''.$customWhere2.'\')">';
			//function print_bug_table($lsn, $limitStart = 0, $customWhere = '', $bBug = true, $customWhere2 = '')

		}
	}
	$objResponse->assign('bug_table_div', 'innerHTML', $page);
	$objResponse->script("make_omo_effect('bug_table');move_onclick_from_tr_to_td('bug_table')");
	return $objResponse;
}
function print_bug($lsn, $bugID, $appID = 'b0')
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	$bugID = (int)$bugID;
	$bBug = ($appID[0] == 'b');
	$appID = (int)substr($appID, 1);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<table class="bug">';
	dbconnect();
	if ( empty($appID) )
	{
		dbq("SELECT groupID, privilege, appID FROM users, settings WHERE users.ID=$userID AND settings.userID=$userID");
		list($groupID, $privilege, $appID) = dbrow(1);
	}
	else
	{
		dbq("SELECT groupID, privilege FROM users WHERE ID=$userID");
		list($groupID, $privilege) = dbrow(1);
	}
	if ( empty($appID) || empty($bugID) )
		return $objResponse->alert('There is an error with this request. You need to specify the ID and project');
	if ( $bBug )
		$fromTable = 'bugs'.$appID;
	else
		$fromTable = 'assets'.$appID;

	$select = '';
	foreach ( get_possible_columns($bBug) as $col )
		$select.= $col[2].', ';
	dbq("UPDATE $fromTable SET flags=(flags | ".BUG_VIEWED.") WHERE ID=$bugID AND assignedTo=$userID");
	$q = dbq("SELECT $select statusID, assToGroup, assignedTo, description, info, notes, history, versionDate, (SELECT appName FROM apps WHERE ID=$appID) AS appName, (SELECT login FROM users WHERE users.ID=submitedBy) AS submitedByName FROM $fromTable WHERE ID=$bugID");
	if ( ($x = mysql_num_rows($q)) != 1 )
	{
		$objResponse->alert(($bBug?'Bug':'Task')." '$bugID' not found");
		return $objResponse;
	}
	$bug = dbrow();
	if ( empty($bug['closeDate']) )
	{
		$bug['closeDate'] = '&nbsp;';
		$bug['closedByName'] = '&nbsp;';
	}
	else
	{
		$bug['closeDate'] = '<em>Closed</em> on </td><td>'.date(DATE_TIMELONG, $bug['closeDate']);
		$bug['closedByName'] = '<em>Closed</em> by </td><td>'.$bug['closedByName'];
	}
	if ( empty($bug['assignedTo']) )
		if ( empty($bug['assToGroup']) )
			$bug['assign'] = '<em>Not assigned</em>';
		else
			$bug['assign'] = '<em>Assigned</em> to</td><td>group '.$bug['assToGroupName'];
	else
		$bug['assign'] = "<em>Assigned</em> to </td><td>$bug[assignedToName] ($bug[assToGroupName])";
	if ( empty($bug['submitedDate']) )
		$bug['submited'] = '&nbsp;';
	else
		$bug['submited'] = '<em>Submited</em> on </td><td>'.date(DATE_TIMELONG, $bug['submitedDate']).' by '.$bug['submitedByName'];
	
	$page.= '<tr><td colspan="4"><h1>'.($bBug?'Bug':'Task').' '.$bug['ID'].', '.$bug['title'].'</h1></td></tr>';
	$page.= '<tr><td><table>';
	$page.= '<tr><td><em>Application</em></td><td>'.$bug['appName'].'</td></tr>';
	$page.= '<tr><td><em>Priority</em></td><td>'.$bug['severityName'].'</td></tr>';
	$page.= '<tr><td><em>Opened</em> on</td><td>'.date(DATE_NOTIME, $bug['openDate']).'</td></tr>';
	$page.= '<tr><td>'.$bug['assign'].'</td></tr>';
	$page.= '</table></td><td><table>';
	$page.= '<tr><td><em>Platform</em></td><td>'.$bug['platformName'].'</td></tr>';
	$page.= '<tr><td><em>Status</em></td><td>'.$bug['statusName'].'</td></tr>';
	$page.= '<tr><td><em>Type</em></td><td>'.$bug['typeName'].'</td></tr>';
	$page.= '<tr><td>'.$bug['closeDate'].'</td></tr>';
	$page.= '</table></td><td><table>';
	if ( $bBug )
		$page.= '<tr><td><em>Build</em></td><td>'.date(DATE_BUGVER, $bug['versionDate']).'</td></tr>';
	if ( $bBug )
		$page.= '<tr><td><em>Frequency</em></td><td>'.$bug['frequencyName'].' '.$bug['frequencyPercent'].'</td></tr>';
	else
		$page.= '<tr><td><em>Dead-line</em></td><td>'.date(DATE_NOTIME, $bug['deadLineDate']).'</td></tr>';
	$page.= '<tr><td>'.$bug['submited'].'</td></tr>';
	$page.= '<tr><td>'.$bug['closedByName'].'</td></tr>';
	$page.= '</table></td><td><table>';
	$page.= '<tr><td>';
	$canClose = (bool)($privilege & CAN_CLOSE);
	if ( ($bug['statusID'] != BUG_STATUS_CLOSED) && ($canClose || $bug['statusID'] != BUG_STATUS_FINISHED) )
	{
		if ( !empty($bug['assignedTo']) && ($bug['assignedTo'] != $userID) )
			$confirm2 = "&&confirm('This ".($bBug?'bug':'task')." was assigned to \'$bug[assignedToName]\'.\\r\\nAre you sure you want to report ".($bBug?'bug':'task')." $bugID as ".($canClose?'closed':'finished')."?')";
		elseif ( !empty($bug['assToGroup']) && ($bug['assToGroup'] != $groupID) )
			$confirm2 = "&&confirm('This ".($bBug?'bug':'task')." was assigned to group \'$bug[assToGroupName]\'.\\r\\nAre you sure you want to report ".($bBug?'bug':'task')." $bugID as ".($canClose?'closed':'finished')."?')";
		else
			$confirm2 = '';
		
		if ( empty($bug['assignedTo']) && empty($bug['assToGroup']) )
			$page.= 'Please assign this';
		else
			$page.= '<input type="button" value="'.($canClose?'close':'Finish').' '.($bBug?'bug':'task').'" class="default" onClick="if(confirm(\'Are you sure you want to '.($canClose?'close':'finish').' it?\')'.$confirm2.'){xajax_close_bugs(lsn, '.($bBug?1:0).','.$appID.', '.$bugID.');xajax_print_bug(lsn, '.$bugID.', '.($bBug?'b':'a').$appID.')};">';
	}
	$page.= '</td></tr><tr><td>';
	$page.= '<input type="button" class="default" value="Edit" onClick="xajax_edit_all(lsn, '.($bBug?'1':'0').', \'a'.$appID.'b'.$bugID.'\')">';
	$page.= '</td></tr>';
	$page.= '</table></td></tr>';
	
	$page.= '<tr><td colspan="4"><h1>Description:</h1>'.nl2br(htmlspecialchars($bug['description'])).'</td></tr>';
	if ( !empty($bug['notes']) )
		$page.= '<tr><td colspan="4"><h1>Notes:</h1>'.nl2br(htmlspecialchars($bug['notes'])).'</td></tr>';
	if ( !empty($bug['info']) )
		$page.= '<tr><td colspan="4"><h1>Info:</h1>'.nl2br(htmlspecialchars($bug['info'])).'</td></tr>';
	$page.= '<tr><td colspan="4"><h1>History:</h1><ul>'.$bug['history'].'</ul></td></tr>';
	$page.= '</table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function view_bugs($lsn, $bBug = true)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	
	$toggle = '<input type="button" class="toggle_btn" id="%1$s_btn" value="%2$s" onClick="toggle(\'%1$s\', \'%2$s\', \'%3$s\')">';
	/*
	if ( $bBug )
		$refresh_btn = '<input type="button" class="default" id="refresh_btn" value="Refresh buglist" onClick="xajax_print_bug_table(lsn)">';
	else
		$refresh_btn = '<input type="button" class="default" id="refresh_btn" value="Refresh assetlist" onClick="xajax_print_bug_table(lsn, 0, \'\', 0)">';
	*/
	$refresh_btn = '<input type="button" class="default" id="refresh_btn" value="Refresh '.($bBug?'bug':'task').'list" onClick="filter2(0, \'\', '.($bBug?1:0).')">';
	
	$theApps = '';
	dbq("SELECT user_to_app.appID, apps.appName, settings.appID AS defaultApp FROM user_to_app, apps, settings WHERE user_to_app.userID=$userID and settings.userID=$userID and user_to_app.appID=apps.ID ORDER BY appID DESC");
	$myapps = dbr();
	if ( empty($myapps) )
	{
		$objResponse->alert('You are not assigned to any project. Talk to your Project Manager to assign you to a project.');
		return $objResponse;
	}
	reset($myapps);
	$defaultApp = $myapps[key($myapps)]['defaultApp'];
	$bSelected = false;
	foreach ( $myapps as $app )
	{
		$theApps.= '<option value="'.$app['appID'].'"';
		if ( $app['appID'] == $defaultApp )
		{
			$bSelected = true;
			$theApps.= 'selected="selected"';
		}
		$theApps.= '>'.$app['appName'].'</option>';
	}
	if ( !$bSelected )
		$defaultApp = 0;
	unset($bSelected);
	if ( $defaultApp == 0 )
	{
		reset($myapps);
		$defaultApp = $myapps[key($myapps)]['appID'];
		$objResponse->loadCommands(change_display_project($lsn, $bBug, $defaultApp, false));
	}
	unset($myapps);
	# print the header
	$page = '';
	$page.= sprintf($toggle, 'filters_toggle', 'View filters', 'Hide filters');
	$page.= sprintf($toggle, 'col_display_toggle', 'View column toggles', 'Hide column toggles');
	$page.= $refresh_btn;
	$page.= 'Project: <select class="project" onChange="xajax_change_display_project(lsn, '.($bBug?1:0).', this.value)">'.$theApps.'</select>';
	$page.= '<input type="button" class="default" value="Edit selected" onClick="get_checkboxes(\'bug_table\', \'edit_all\', '.($bBug?'true':'false').')">';
	$page.= '<div class="toggle" id="filters_toggle">the filters</div>';
	$page.= '<div class="toggle" id="col_display_toggle">the column display</div>';
	$page.= '<div class="bug_table" id="bug_table_div">Press "'.$refresh_btn.'" to load the '.($bBug?'bug':'task').'s. If the button does not work the probable cause is that your browser can not handle the amount of information in the list. Set less '.($bBug?'bug':'task').'s per page or put more filters.<br>If you are sure this is not the case please inform the webmaster about the problem.</div>';
	
	$args = func_get_args();
	dhtmlHistoryAdd($objResponse, encodeWaypointName(__FUNCTION__, $bBug), array($args, 'other information'));
	$objResponse->assign('details', 'innerHTML', $page);
	$objResponse->script("toggle('filters_toggle', 'View filters', 'Hide filters');");
	$objResponse->script("toggle('col_display_toggle', 'View column toggles', 'Hide column toggles');");
	$objResponse->loadCommands(print_filters($lsn, $bBug));
	$objResponse->loadCommands(print_display($lsn, $bBug));
	$objResponse->script('xajax_print_bug_table(lsn, 0, \'\', '.($bBug?1:0).')');
	return $objResponse;
}
function edit_bug($lsn, $bBug, $bugID, $appID)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$fromTable = ($bBug?'bugs':'assets').$appID;
	
	global $global_select_error_text;
	$global_select_error_text = '';
	function select($op, $sel, $cssClass = '', $onChange = '', $extra = '')
	{
		if ( !empty($cssClass) )
			$cssClass = ' class="'.$cssClass.'"';
		if ( !empty($onChange) )
			$onChange = ' onChange="'.$onChange.'"';
		if ( !empty($extra) )
			$extra = ' '.$extra;
		if ( !is_array($sel) )
			$sel = array($sel);
		$txt = ' <select'.$cssClass.$onChange.$extra.'>';
		$nSelectedItems = 0;
		foreach ($op as $k=>$v)
		{
			if ( in_array($k, $sel) )
			{
				$selected = ' selected="selected"';
				$nSelectedItems += 1;
			}
			else
				$selected = '';
			$txt.= '<option'.$cssClass.' value="'.$k.'"'.$selected.'>'.htmlspecialchars($v).'</option>';
		}
		$txt.= '</select> ';
		global $global_select_error_text;
		if ( $nSelectedItems < 1 )
			$global_select_error_text.= 'ERROR: Coud not select element(s) "'.s_var_dump($sel).'" from '.s_print_r($op).CRLF;
		return $txt;
	}

	dbconnect();
	$q = dbq("SELECT assignedTo, assToGroup, statusID FROM $fromTable WHERE ID=$bugID");
	if ( ($x = mysql_num_rows($q)) != 1 )
		return $objResponse->alert(($bBug?'Bug':'Task')." '$bugID' not found");
	$bug = dbrow();
	// get users
	dbq("SELECT users.ID, CONCAT('(', shortName, ') ', login, ' - ', name) FROM users, groups WHERE users.groupID=groups.ID AND (((users.privilege & ".CAN_BE_ASSIGNED.")!=0 AND groups.canAssignTo='Y') OR users.ID=$bug[assignedTo] OR users.ID=$userID) ORDER BY shortName, login");
	$users = array(0=>'nobody');
	while ( $row = dbrow(1) )
		$users[$row[0]] = $row[1];
	// get groups
	dbq("SELECT ID, groupName FROM groups WHERE canAssignTo='Y' OR ID=$bug[assToGroup] ORDER BY groupName");
	$groups = array(0=>'no group');
	while ( $row = dbrow(1) )
		$groups[$row[0]] = $row[1];
	// get status
	dbq("SELECT ID, statusName FROM status WHERE ID!=".BUG_STATUS_CLOSED." OR ID=$bug[statusID]");
	$status = array();
	while ( $row = dbrow(1) )
		$status[$row[0]] = $row[1];
	// get types
	dbq("SELECT ID, typeName FROM ".($bBug?'type':'asset_types'));
	$type = array();
	while ( $row = dbrow(1) )
		$type[$row[0]] = $row[1];
	// get severity
	dbq("SELECT ID, severityName FROM severity ORDER BY priority ASC");
	$severity = array();
	while ( $row = dbrow(1) )
		$severity[$row[0]] = $row[1];
	// get frequency
	dbq("SELECT ID, frequencyName FROM frequency");
	$frequency = array();
	while ( $row = dbrow(1) )
		$frequency[$row[0]] = $row[1];
	// get platforms
	dbq("SELECT ID, platformName FROM platforms");
	$platforms = array();
	while ( $row = dbrow(1) )
		$platforms[$row[0]] = $row[1];

	if ( func_num_args() > 4 )
	{
		$cnt = 4;
		$details['notes'] = func_get_arg($cnt++);
		$details['title'] = func_get_arg($cnt++);
		$details['description'] = func_get_arg($cnt++);
		$details['frequencyID'] = func_get_arg($cnt++);
		$details['frequencyPercent'] = func_get_arg($cnt++);
		$details['openDate'] = func_get_arg($cnt++);
		$details['deadLineDate'] = func_get_arg($cnt++);
		$details['platformID'] = func_get_arg($cnt++);
		$details['info'] = func_get_arg($cnt++);
		$details['severityID'] = func_get_arg($cnt++);
		$details['typeID'] = func_get_arg($cnt++);
		$details['statusID'] = func_get_arg($cnt++);
		$details['assignedTo'] = func_get_arg($cnt++);
		$details['assToGroup'] = func_get_arg($cnt++);
		$details['assignedToName'] = $users[$details['assignedTo']];
		$details['assToGroupName'] = $groups[$details['assToGroup']];
		$details['statusName'] = $status[$details['statusID']];
		$details['typeName'] = $type[$details['typeID']];
		$details['severityName'] = $severity[$details['severityID']];
		$details['platformName'] = '';
		foreach ( $platforms as $platformID=>$platformName )
			if ( $details['platformID'] & $platformID )
				$details['platformName'].= $platformName.', ';
		$details['platformName'] = substr($details['platformName'], 0, -2);
		if ( !empty($details['frequencyID']) )
			$details['frequencyName'] = $frequency[$details['frequencyID']];
		if ( !$bBug )
		{
			// transform openDate
			$date = explode('/', $details['openDate']);
			if ( (strlen($details['openDate']) != 8) || (count($date) != 3) || (!gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2])) )
			{
				$objResponse->alert("Open date incorrect\nTime format is MM/DD/YY");
				return $objResponse;
			}
			$details['openDate'] = gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2]);
			//transform deadLineDate
			$date = explode('/', $details['deadLineDate']);
			if ( (strlen($details['deadLineDate']) != 8) || (count($date) != 3) || (!gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2])) )
			{
				$objResponse->alert("Dead line incorrect\nTime format is MM/DD/YY");
				return $objResponse;
			}
			$details['deadLineDate'] = gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2]);
			unset($date);
		}
		
		foreach ( $details as $k=>$v )
			if ( empty($v) )
				unset($details[$k]);
		
		//$objResponse->alert(s_print_r($details));
		$x = update_bug($userID, $users[$userID], $bugID, $appID, $bBug, $details);
		if ( !$x )
		$objResponse->alert('No change has been made.');
	}
	dbq("SELECT *, (SELECT GROUP_CONCAT(platforms.ID SEPARATOR \",\") FROM platforms WHERE (platforms.ID & platformID) != 0 ) as platformSet, (SELECT appName FROM apps WHERE ID=$appID) AS appName, (SELECT login FROM users WHERE users.ID=closedBy) AS closedByName FROM $fromTable WHERE ID=$bugID");
	$bug = dbrow();
	
	
	$page = '<h1>Editing '.($bBug?'bug':'task').' '.$bugID.' from '.$bug['appName'].'</h1>';
	$page.= '<table class="edit">';

	$table_row1 = '<tr><td>%s</td><td>%s</td></tr>';
	$table_row2 = '<tr class="even"><td>%s</td><td>%s</td></tr>';
	$i = 2;
	if ( $bBug )
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Open date', date(DATE_NOTIME, $bug['openDate']));
	else
	{
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Open date', '<input type="text" id="edit_opendate" size="8" maxlength="8" value="'.date("m/d/y", $bug['openDate']).'" onKeyUp="xjx.$(\'show_opendate\').innerHTML=show_date(this.value, \''.JS_DATE_NOTIME.'\')"> <span id="show_opendate">'.date(DATE_NOTIME, $bug['openDate']).'</span>');
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Dead-line', '<input type="text" id="edit_deadline" size="8" maxlength="8" value="'.date("m/d/y", $bug['deadLineDate']).'" onKeyUp="xjx.$(\'show_deadline\').innerHTML=show_date(this.value, \''.JS_DATE_NOTIME.'\')"> <span id="show_deadline">'.date(DATE_NOTIME, $bug['deadLineDate']).'</span>');
	}
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Close date', empty($bug['closeDate'])?'never closed':date(DATE_NOTIME, $bug['closeDate']));
	if ( !empty($bug['closeDate']) )
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Closed by', $bug['closedByName']);
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Assigned to group', select($groups, $bug['assToGroup'], '', "xjx.$('edit_assto').value=0", 'id="edit_asstogrp"'));
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Assigned to', select($users, $bug['assignedTo'], '', "xjx.$('edit_asstogrp').value=0", 'id="edit_assto"'));
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Status', select($status, $bug['statusID'], '', '', 'id="edit_status"'));
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Type', select($type, $bug['typeID'], '', '', 'id="edit_type"'));
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Priority', select($severity, $bug['severityID'], '', '', 'id="edit_sev"'));
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Platform', select($platforms, explode(',', $bug['platformSet']), '', '', 'id="edit_platform" multiple="multiple" size="5"'));
	if ( $bBug )
	{
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Frequency', select($frequency, $bug['frequencyID'], '', '', 'id="edit_freq"').' <input type="text" id="edit_freq_perc" value="'.$bug['frequencyPercent'].'">');
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Title', nl2br($bug['title']));
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Description', nl2br($bug['description']));
	}
	else
	{
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Title', '<input type="text" id="edit_title" class="edit" value="'.$bug['title'].'">');
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Description', '<textarea id="edit_desc" class="edit" rows=10>'.$bug['description'].'</textarea>');
	}
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'Info', nl2br($bug['info']).'<textarea id="add_info" class="edit" rows=4></textarea>');
	if ( $bBug )
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Notes', nl2br($bug['notes']));
	else
		$page.= sprintf(${'table_row'.($i=3-$i)}, 'Notes', '<textarea id="edit_notes" class="edit" rows=10>'.$bug['notes'].'</textarea>');
	$page.= sprintf(${'table_row'.($i=3-$i)}, 'History', '<ul>'.$bug['history'].'</ul>');
	$page.= '</table>';
	$page.= '<input type="button" class="default" value="Save changes" onClick="xajax_edit_bug(lsn, '.($bBug?1:0).', '.$bugID.', '.$appID.', ';
	$page.= 'return_value(\'edit_notes\'),';
	$page.= 'return_value(\'edit_title\'),';
	$page.= 'return_value(\'edit_desc\'),';
	$page.= 'return_value(\'edit_freq\'),';
	$page.= 'return_value(\'edit_freq_perc\'),';
	$page.= 'return_value(\'edit_opendate\'),';
	$page.= 'return_value(\'edit_deadline\'),';
	$page.= 'get_platforms(\'edit_platform\'),';
	$page.= 'xjx.$(\'add_info\').value,';
	$page.= 'xjx.$(\'edit_sev\').value,';
	$page.= 'xjx.$(\'edit_type\').value,';
	$page.= 'xjx.$(\'edit_status\').value,';
	$page.= 'xjx.$(\'edit_assto\').value,';
	$page.= 'xjx.$(\'edit_asstogrp\').value';
	$page.= ')">';
	$objResponse->assign('details', 'innerHTML', $page);
	if ( !empty($global_select_error_text) )
		$objResponse->alert($global_select_error_text);
	return $objResponse;
}
function edit_all($lsn, $bBug)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 2 )
	{
		$objResponse->alert('No selected fields');
		return $objResponse;
	}
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	if ( func_num_args() == 3 )
	{
		dbconnect();
		dbq("SELECT privilege FROM users WHERE users.ID=$userID");
		list ( $privilege ) = dbrow(1);
		if ( $privilege & GUEST )
			return $objResponse->alert('You do not have permission to edit');
		elseif ( $privilege & CAN_EDIT )
		{
			$bug = func_get_arg(2);
			$bug = explode('b', substr($bug, 1));
			unset($objResponse);
			return edit_bug($lsn, $bBug, $bug[1], $bug[0]);
		}
	}
	$page = '';
	$thisPageURI = getThisPageURI('/');
	$bugs = array();
	for ( $i = 2; $i < func_num_args(); $i += 1 )
	{
		$bug = func_get_arg($i);
		$bugs[] = explode('b', substr($bug, 1));
	}
	$theBugs = '';
	$nSize = count($bugs);
	$page.= '<h1>Multiple '.($bBug?'bug':'task').' edit</h1>Editing '.($bBug?'bug':'task').'s: ';
	for ( $i = 0; $i < $nSize; $i += 1 )
	{
		//$page.= '<b class="bug">'.$bugs[$i][1].'</b>, ';
		$page.= '<a class="bug" target="_blank" href="'.$thisPageURI.'?'.($bBug?'bug':'task').'='.$bugs[$i][1].'&app='.$bugs[$i][0].'">'.$bugs[$i][1].'</a>, ';
		$theBugs.= ', '.$bugs[$i][1];
		if ( isset($bugs[$i-1]) && ($bugs[$i-1][0] != $bugs[$i][0]) )
		{
			$objResponse->alert('Found '.($bBug?'bug':'task').'s from two projects ... ERROR !');
			return $objResponse;
		}
	}

	$page = substr($page, 0, -2);
	dbconnect();
	$page.= '<table><tbody>';
	
	$page.= '<tr><th>Change status to</th><td><select id="edit_status"><option value="0">no change</option>';
	dbq("SELECT ID, statusName FROM status WHERE ID!=".BUG_STATUS_CLOSED." and ID!=".BUG_STATUS_OPEN." and ID!=".BUG_STATUS_WAIVED);
	while ( $row = dbrow(1) )
		$page.= '<option value="'.$row[0].'">'.$row[1].'</option>';
	$page.= '</select></td></tr>';
	
	$page.= '<tr><th>Assign all to group</th><td><select id="edit_group"><option value="0">no change</option>';
	dbq("SELECT ID, groupName FROM groups WHERE canAssignTo='Y'");
	while ( $row = dbrow(1) )
		$page.= '<option value="'.$row[0].'">'.$row[1].'</option>';
	$page.= '</select> (must be set to "no change" if you assign the bug to a user)</td></tr>';
	
	$page.= '<tr><th>Assign all to user</th><td><select id="edit_user"><option value="0">no change</option>';
	dbq("SELECT ID, login, name FROM users WHERE (privilege & ".CAN_BE_ASSIGNED.")!=0");
	while ( $row = dbrow(1) )
		$page.= '<option value="'.$row[0].'">'.$row[1].' - '.$row[2].'</option>';
	$page.= '</select> (must be set to "no change" if you assign the bug to a group)</td></tr>';
	
	$page.= '<tr><th>Add note to all</th><td><textarea id="edit_note" cols="100" rows="3"></textarea>';
	
	$page.= '</tbody><tfoot>';
	
	
	$page.= '<tr><td colspan="2">';
	$page.= '<input type="button" value="Save changes" class="default" onClick="xajax_edit_multiple(lsn';
	$page.= ', document.getElementById(\'edit_status\').value';
	$page.= ', document.getElementById(\'edit_group\').value';
	$page.= ', document.getElementById(\'edit_user\').value';
	$page.= ', document.getElementById(\'edit_note\').value';
	$page.= ', '.($bBug?'1':'0').', '.$bugs[0][0].$theBugs;
	$page.= ')">';
	$page.= '<input type="button" value="Close '.($bBug?'bugs':'tasks').'" class="default" onClick="confirm(\'Are you sure you want to close all?\')?xajax_close_bugs(lsn, '.($bBug?'1':'0').', '.$bugs[0][0].$theBugs.'):alert(\''.($bBug?'Bugs':'Tasks').' have not been closed\')">';
	if ( $bBug )
		$page.= '<input type="button" value="Submit '.($bBug?'bugs':'tasks').'" class="default" onClick="confirm(\'Are you sure you want to submit all?\')?xajax_submit_bugs(lsn, '.($bBug?'1':'0').', '.$bugs[0][0].$theBugs.'):alert(\''.($bBug?'Bugs':'Tasks').' have not been submited\')">';
	$page.= '</td></tr>';
	$page.= '</tfoot>';
	$page.= '</table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function close_bugs($lsn, $bBug, $defaultApp)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 3 )
	{
		$objResponse->alert('No bugs to close');
		return $objResponse;
	}
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT privilege FROM users WHERE ID=$userID");
	list ( $privilege ) = dbrow(1);
	$status = ($privilege & CAN_CLOSE)?BUG_STATUS_CLOSED:BUG_STATUS_FINISHED;
	dbq("SELECT login, statusName FROM users, status WHERE users.ID=$userID and status.ID=$status");
	list($userName, $statusName) = dbrow(1);
	$details = array(
		'statusID'		=>	$status,
		'statusName'	=>	$statusName,
	);
	$page = '<h1>Results from closing</h1>';
	$count = 0;
	for ( $i = 3; $i < func_num_args(); $i += 1)
	{
		$bugID = func_get_arg($i);
		$x = update_bug($userID, $userName, $bugID, $defaultApp, $bBug, $details);
		if ( $x == 1 )
			$count+= 1;
		elseif ( $x === false )
			$page.= 'Coud not close '.($bBug?'bug':'task')." $bugID, probably the bug is already closed.<br>";
		else
			$page.= 'Warning: closing '.($bBug?'bug':'task')." $bugID, the returned result was ".s_var_dump($x).'<br>';
	}	
/*	$theBugs = '(0';
	for ( $i = 3; $i < func_num_args(); $i += 1 )
		$theBugs.= ' or ID='.func_get_arg($i);
	$theBugs.= ')';
	$now = time();
	$history = date(DATE_HISTORY, $now).' '.$userName."\n<li>changed the status to Closed</li>\n";
	$history = dbesc($history);
	dbq("UPDATE ".($bBug?'bugs':'assets')."$defaultApp SET statusID=".BUG_STATUS_CLOSED.", closeDate=$now, closedBy=$userID, history=CONCAT('$history', history) WHERE assignedTo!=0 and statusID!= ".BUG_STATUS_CLOSED.' and '.$theBugs);
	$count = mysql_affected_rows();
*/
	$page.= $statusName.' '.$count.' '.($bBug?'bug':'task').($count == 1?'':'s').' from '.(func_num_args() - 3).' sent.';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function submit_bugs($lsn, $bBug, $defaultApp)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 3 )
	{
		$objResponse->alert('No bugs to close');
		return $objResponse;
	}
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	$page = '<h1>Results from submitting</h1>';
	$now = time();
	$history = date(DATE_HISTORY, $now).' '.$userName."\n<li>submited the ".($bBug?'bug':'task')."</li>\n";
	$count = 0;
	for ( $i = 3; $i < func_num_args(); $i += 1)
	{
		$bugID = func_get_arg($i);
		//$x = update_bug($userID, $userName, $bugID, $defaultApp, $bBug, $details);
		dbq("UPDATE ".($bBug?'bugs':'assets')."$defaultApp SET submitedDate=$now, submitedBy=$userID, history=CONCAT('$history', history) WHERE statusID= ".BUG_STATUS_CLOSED.' and ID='.$bugID);
		$x = mysql_affected_rows();
		if ( $x == 1 )
			$count+= 1;
		elseif ( $x === false )
			$page.= 'Coud not submit '.($bBug?'bug':'task')." $bugID, probably the bug is not closed.<br>";
		else
			$page.= 'Warning: submitting '.($bBug?'bug':'task')." $bugID, the returned result was ".s_var_dump($x).'<br>';
	}	
	$page.= 'Submited '.$count.' '.($bBug?'bug':'task').($count == 1?'':'s').' from '.(func_num_args() - 3).' sent.';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function edit_multiple($lsn, $statusID, $group, $user, $notes, $bBug, $defaultApp)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 7 )
	{
		$objResponse->alert('ERROR, insufficient parameters');
		return $objResponse;
	}
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	dbq("SELECT login, privilege FROM users WHERE ID=$userID");
	list($userName, $privilege) = dbrow(1);
	$defaultApp = (int)$defaultApp;
	$statusID = (int)$statusID;
	$group = (int)$group;
	$user = (int)$user;
	$notes = trim($notes);
	
	if ( (!empty($user)) && (!empty($group)) )
	{
		$objResponse->alert('Select eather a user or a group, not both');
		return $objResponse;
	}
	
	if ( (!($privilege & ADMINISTRATOR)) && ((strlen($notes) <= 4) || in_array(strtolower($notes), array('assigned', 'reassigned', 'assign', 'reassign'))) )
	{
		$objResponse->alert('How about some usefull notes ?');
		return $objResponse;
	}
	if ( empty($notes) )
	{
		$objResponse->alert('You need to add some notes to the bug');
		return $objResponse;
	}
/*
	$theBugs = '(0';
	for ( $i = 6; $i < func_num_args(); $i += 1)
		$theBugs.= ' or ID='.func_get_arg($i);
	$theBugs.= ')';
*/
	dbq('SELECT statusName FROM status WHERE ID='.$statusID);
	list($statusName) = dbrow(1);
	dbq('SELECT groupName FROM groups WHERE ID='.$group);
	list($groupName) = dbrow(1);
	dbq('SELECT login FROM users WHERE ID='.$user);
	list($name) = dbrow(1);
	$details = array(
		'info'				=>	$notes,
		'statusID'			=>	$statusID,
		'statusName'		=>	$statusName,
		'assToGroup'		=>	$group,
		'assToGroupName'	=>	$groupName,
		'assignedTo'		=>	$user,
		'assignedToName'	=>	$name,
	);
	foreach ( $details as $key=>$val )
		if ( empty($val) )
			unset($details[$key]);
	$page = '<h1>Results from the edit</h1>';
	$count = 0;
	for ( $i = 7; $i < func_num_args(); $i += 1)
	{
		$bugID = func_get_arg($i);
		$x = update_bug($userID, $userName, $bugID, $defaultApp, $bBug, $details);
		if ( $x == 1 )
			$count+= 1;
		else
			$page.= 'Warning: updateing '.($bBug?'bug':'task')." $bugID, the returned result was ".s_var_dump($x).'<br>';
	}	
	$page.= $count.' of '.(func_num_args() - 7).' bugs have beed updated';
/*
	$set = '';
	$history = '';
	
	if ( empty($notes) )
	{
		$objResponse->alert('You need to add some notes to the bug');
		return $objResponse;
	}
	else
	{
		$history.= "<li>added notes (".strlen($notes)." characters)</li>";
		$notes = ", info=CONCAT(info,'On ".date(DATE_HISTORY).' '.$userName.' wrote:'.dbesc("\n".$notes."\n")."')";
	}
	
	if ( !empty($statusID) )
	{
		$set.= ', statusID='.$statusID;
		dbq('SELECT statusName FROM status WHERE ID='.$statusID);
		list($statusName) = dbrow(1);
		$history.= "<li>changed the status to $statusName</li>";
	}
	if ( !empty($group) )
	{
		$set.= ", assToGroup=$group, assignedTo=0, flags=(flags & (~".BUG_VIEWED."))";
		dbq('SELECT groupName FROM groups WHERE ID='.$group);
		list($groupName) = dbrow(1);
		$history.= "<li>assigned the bug to group $groupName</li>";
	}
	elseif ( !empty($user) )
	{
		$set.= ", assToGroup=0, assignedTo=$user, flags=(flags & (~".BUG_VIEWED."))";
		dbq('SELECT login FROM users WHERE ID='.$user);
		list($name) = dbrow(1);
		$history.= "<li>assigned the bug to $name</li>";
	}
	if ( !empty($history) )
		$history = date(DATE_HISTORY).' '.$userName."\n".$history."\n";
	$history = dbesc($history);
	dbq("UPDATE bugs$defaultApp SET history=CONCAT('$history',history) $notes $set WHERE $theBugs");

	$page.= mysql_affected_rows().' of '.(func_num_args() - 7).' bugs have beed updated';
*/
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function change_display_project($lsn, $bBug, $projectID, $bPrint = true)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$projectID = (int)$projectID;
	dbconnect();
	dbq("UPDATE settings SET appID=$projectID WHERE userID=$userID");
	if ( $bPrint )
		$objResponse->loadCommands(print_bug_table($lsn, 0, '', $bBug));
	return $objResponse;
}
function add_bugs($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>Add a bug to the local projects</h1>';
	dbconnect();
	if ( func_num_args() == 1 )
	{
		// get projects that we can add bugs to
		$theApps = '';
		dbq("SELECT user_to_app.appID, apps.appName FROM user_to_app, apps WHERE user_to_app.userID=$userID and user_to_app.appID=apps.ID and apps.isLocal='Y'");
		$result = dbr(1);
		foreach ( $result as $r )
			$theApps.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get valid groups
		$theGroups = '';
		dbq("SELECT ID, groupName FROM groups WHERE canAssignTo='Y'");
		$result = dbr(1);
		foreach ( $result as $r )
			$theGroups.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get users
		$theUsers = '';
		dbq("SELECT ID, CONCAT(login, ' (', name, ')') FROM users WHERE (privilege & ".CAN_BE_ASSIGNED.")!=0 ORDER BY login ASC");
		$result = dbr(1);
		foreach ( $result as $r )
			$theUsers.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		//get bug types
		$theTypes = '';
		dbq("SELECT ID, typeName FROM type");
		$result = dbr(1);
		foreach ( $result as $r )
			$theTypes.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get severity classes
		$theSeverity = '';
		dbq("SELECT ID, severityName FROM severity ORDER BY priority ASC");
		$result = dbr(1);
		foreach ( $result as $r )
			$theSeverity.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get platforms
		$thePlatforms = '';
		dbq("SELECT ID, platformName FROM platforms");
		$result = dbr(1);
		foreach ( $result as $r )
			$thePlatforms.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get frequency
		$theFrequency = '';
		dbq("SELECT ID, frequencyName FROM frequency");
		$result = dbr(1);
		foreach ( $result as $r )
			$theFrequency.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		unset($result);
		
		$page.= '<table class="add_bug">';
		
		$page.= '<tr><th>Project</th><td>';
		$page.= '<select id="add_bug_project" class="project"><option value="0">Choose a project</option>'.$theApps.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Assigned to group</th><td>';
		$page.= '<select id="add_bug_group" onChange="xjx.$(\'add_bug_assign\').value=0"><option value="0">Empty</option>'.$theGroups.'</select> (must be left empty if you assign the bug to a user)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Assigned to</th><td>';
		$page.= '<select id="add_bug_assign" onChange="xjx.$(\'add_bug_group\').value=0"><option value="0">Empty</option>'.$theUsers.'</select> (must be left empty if you assign the bug to a group)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Type</th><td>';
		$page.= '<select id="add_bug_type"><option value="0">Choose bug type</option>'.$theTypes.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Priority</th><td>';
		$page.= '<select id="add_bug_severity"><option value="0">Choose priority</option>'.$theSeverity.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Platform</th><td>';
		$page.= '<select id="add_bug_platform"><option value="0">Choose platform</option>'.$thePlatforms.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Build version</th><td>';
		$page.= '<input id="add_bug_version" type="text" maxlength="8" onKeyUp="xjx.$(\'show_buildver\').innerHTML=show_date(this.value, \''.JS_DATE_BUGVER.'\')"> <span id="show_buildver">(MM/DD/YY)</span>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Frequency</th><td>';
		$page.= '<select id="add_bug_frequency">'.$theFrequency.'</select><input id="add_bug_frequency_per" type="text" size="16" maxlength="16">';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Title</th><td>';
		$page.= '<input id="add_bug_title" type="text" size="100" maxlength="256">';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Description</th><td>';
		$page.= '<textarea id="add_bug_description" cols="100" rows="9"></textarea>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th colspan="2"><input type="button" class="default" value="Add bug" onClick="add_bug_verify()"></th></tr>';
		
		$page.= '</table>';
		$objResponse->assign('details', 'innerHTML', $page);
	}
	else
	{
		$args = func_get_args();
		//$objResponse->assign('details', 'innerHTML', s_print_r($args, true));
		$args[7] = trim($args[7]); // versionDate
		$date = explode('/', $args[7]);
		if ( (strlen($args[7]) != 8) || (count($date) != 3) || (!gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2])) )
		{
			$objResponse->alert("build version incorrect\nTime format is MM/DD/YY");
			return $objResponse;
		}
		$args[7] = gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2]);
		unset($date);
		for ( $i = 1; $i <= 8; $i += 1 )
			$args[$i] = (int)$args[$i];
		for ( $i = 9; $i <= 11; $i += 1 )
			$args[$i] = dbesc(trim($args[$i]));
		$now = time();
		$history = dbesc(date(DATE_HISTORY, $now).' bug inserted in database');
		dbq("INSERT INTO bugs$args[1] (openDate, assToGroup, assignedTo, typeID, severityID, platformID, versionDate, frequencyID, frequencyPercent, title, description, history, statusID) 
			VALUES ($now, $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], '$args[9]', '$args[10]', '$args[11]', '$history', ".BUG_STATUS_OPEN.")");
		$objResponse->alert("Bug inserted in database with ID ".mysql_insert_id());
		$objResponse->assign('add_bug_title', 'value', '');
		$objResponse->assign('add_bug_description', 'value', '');
	}
	return $objResponse;
}
function add_tasks($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>Add an task to a project</h1>';
	dbconnect();
	if ( func_num_args() == 1 )
	{
		// get projects that we can add bugs to
		$theApps = '';
		dbq("SELECT user_to_app.appID, CONCAT(appName, ' - ', appDesc), settings.appID FROM user_to_app, apps, settings WHERE user_to_app.userID=$userID and user_to_app.appID=apps.ID and settings.userID=$userID");
		$result = dbr(1);
		foreach ( $result as $r )
			$theApps.= '<option value="'.$r[0].'"'.(($r[0] == $r[2])?' selected="selected"':'').'>'.$r[1].'</option>';
		// get valid groups
		$theGroups = '';
		dbq("SELECT ID, groupName FROM groups WHERE canAssignTo='Y' ORDER BY shortName");
		$result = dbr(1);
		foreach ( $result as $r )
			$theGroups.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get users
		$theUsers = '';
		dbq("SELECT users.ID, CONCAT(login, ' - ', groupName, ' - ', name) FROM users, groups WHERE groupID=groups.ID AND (users.privilege & ".CAN_BE_ASSIGNED.")!=0 ORDER BY shortName, login");
		$result = dbr(1);
		foreach ( $result as $r )
			$theUsers.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		//get asset types
		$theTypes = '';
		dbq("SELECT ID, typeName FROM asset_types");
		$result = dbr(1);
		foreach ( $result as $r )
			$theTypes.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get severity classes
		$theSeverity = '';
		dbq("SELECT ID, severityName FROM severity ORDER BY priority ASC");
		$result = dbr(1);
		foreach ( $result as $r )
			$theSeverity.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		// get platforms
		$thePlatforms = '';
		dbq("SELECT ID, platformName FROM platforms");
		$result = dbr(1);
		foreach ( $result as $r )
			$thePlatforms.= '<option value="'.$r[0].'">'.$r[1].'</option>';
		
		$page.= '<table class="add_bug">';
		
		$page.= '<tr><th>Project</th><td>';
		$page.= '<select id="add_asset_project" class="project"><option value="0">Choose a project</option>'.$theApps.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Open date</th><td>';
		$page.= '<input id="DPC_add_asset_opendate" type="text" size="8"> (leave empty for current day)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Assigned to group</th><td>';
		$page.= '<select id="add_asset_group" onChange="xjx.$(\'add_asset_assign\').value=0"><option value="0">Empty</option>'.$theGroups.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Assigned to</th><td>';
		$page.= '<select id="add_asset_assign" onChange="xjx.$(\'add_asset_group\').value=0"><option value="0">Empty</option>'.$theUsers.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Type</th><td>';
		$page.= '<select id="add_asset_type"><option value="0">Choose task type</option>'.$theTypes.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Priority</th><td>';
		$page.= '<select id="add_asset_severity"><option value="0">Choose priority</option>'.$theSeverity.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Final dead-line</th><td>';
		//$page.= '<input id="add_asset_deadline" type="text" size="8" maxlength="8" onKeyUp="xjx.$(\'show_deadline\').innerHTML=show_date(this.value, \''.JS_DATE_NOTIME.'\')"> <span id="show_deadline">(MM/DD/YY)</span>';
		$page.= '<input id="DPC_add_asset_deadline" type="text" size="8"> (MM/DD/YY)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Platform</th><td>';
		$page.= '<table><tr><td>';
		$page.= '<select id="add_asset_platform" multiple="multiple" size="5">'.$thePlatforms.'</select></td>';
		$page.= '<td>(hold Ctrl for multiple selection)</td></tr></table>';
		$page.= '</td></tr>';
		
		// $page.= '<tr><th>Build version</th><td>';
		// $page.= '<input id="add_asset_version" type="text" size="8" maxlength="8"> (MM/DD/YY)';
		// $page.= '</td></tr>';
		
		$page.= '<tr><th>Task name</th><td>';
		$page.= '<input id="add_asset_title" type="text" size="100" maxlength="256">';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Description</th><td>';
		$page.= '<textarea id="add_asset_description" cols="100" rows="9"></textarea>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Notes</th><td>';
		$page.= '<textarea id="add_asset_notes" cols="100" rows="3"></textarea>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th colspan="2"><input type="button" class="default" value="Add task" onClick="add_asset_verify()"></th></tr>';
		
		$page.= '</table>';
		$objResponse->assign('details', 'innerHTML', $page);
		$objResponse->script('DatePickerControl.init();');
	}
	else
	{
		$args = func_get_args();
		//return $objResponse->assign('details', 'innerHTML', s_print_r($args, true));
		$args[7] = trim($args[7]); // openDate
		$date = explode('/', $args[7]);
		if ( (strlen($args[7]) != 8) || (count($date) != 3) || (!gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2])) )
			$args[7] = 0;
		else
			$args[7] = gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2]);
		$args[8] = trim($args[8]); // deadLineDate
		$date = explode('/', $args[8]);
		if ( (strlen($args[8]) != 8) || (count($date) != 3) || (!gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2])) )
		{
			$objResponse->alert("dead-line date incorrect\nTime format is MM/DD/YY");
			return $objResponse;
		}
		$args[8] = gmmktime(0, 0, 0, $date[0], $date[1], '20'.$date[2]);
		unset($date);
		for ( $i = 1; $i <= 8; $i += 1 )
			$args[$i] = (int)$args[$i];
		for ( $i = 9; $i <= 11; $i += 1 )
			$args[$i] = dbesc(trim($args[$i]));
		$now = time();
		if ( empty($args[7]) ) // openDate
			$args[7] = $now;
		dbq("SELECT CONCAT(login, ' ', name) FROM users WHERE ID=$userID");
		list ( $userName ) = dbrow(1);
		$history = date(DATE_HISTORY, $now)." task inserted\n";
		$history.= '<li>by '.$userName.'</li>';
		$history.= '<li>assigned to ';
		if ( !empty($args[3]) ) // assignedTo
		{
			dbq("SELECT groupID, groupName, login FROM users, groups WHERE users.ID=$args[3] AND groupID=groups.ID");
			list ( $args[2], $groupName, $assignedToName ) = dbrow(1);
			$history.= "$assignedToName from group $groupName";
		}
		else
		{
			dbq("SELECT groupName FROM groups WHERE ID=$args[2]");
			list ( $groupName ) = dbrow(1);
			$history.= "group $groupName";
		}
		$history.= '</li>';
		$history = dbesc($history);
		dbq("INSERT INTO assets$args[1] (openDate, assToGroup, assignedTo, typeID, severityID, platformID, deadLineDate, title, description, notes, history, statusID) 
			VALUES ($args[7], $args[2], $args[3], $args[4], $args[5], $args[6], $args[8], '$args[9]', '$args[10]', '$args[11]', '$history', ".BUG_STATUS_OPEN.")");
		$objResponse->alert("Task inserted in database with ID ".mysql_insert_id());
		$objResponse->assign('add_asset_title', 'value', '');
	}
	return $objResponse;
}
function search_bugs($lsn, $bBug)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	$theApps = '';
	$page = '';
	$text = '';
	$date1 = '';
	$date2 = '';
	$appID = 0;
	$searchIn = array(
	//			in DB				what we print		check - default
		array('title',			'Title',		true),
		array('description',	'Description',	true),
		array('info',			'Info',			false),
		array('notes',			'Notes',		false),
	);
	$restrictTo = array(
		array('openDate',		'Opened',		false),
		array('closeDate',		'Closed',		false),
		array('deadLineDate',	'Dead-line',	false),
	);
	$nSearchInSize = count($searchIn);
	$nRestrictToSize = count($restrictTo);
	$bShowResults = ( func_num_args() > (1 + $nSearchInSize + $nRestrictToSize) );
	if ( $bShowResults )
	{
		$page.= '<h1>Results</h1>';
		$page.= '<input type="button" class="default" value="Edit selected" onClick="get_checkboxes(\'bug_table\', \'edit_all\', '.($bBug?'true':'false').')">';
		$page.= '<div class="bug_table" id="bug_table_div">There shoud be a bug table here</div>';
		//$page.= '<h1>Filters</h1>';
		$page.= '<div class="toggle" id="filters_toggle">the filters</div>';
		$cnt = 2;
		for ( $i = 0; $i < $nSearchInSize; $i += 1 )
			$searchIn[$i][2] = (bool)func_get_arg($cnt++);
		for ( $i = 0; $i < $nRestrictToSize; $i += 1 )
			$restrictTo[$i][2] = (bool)func_get_arg($cnt++);
		$text = dbesc(trim(func_get_arg($cnt++),'%'));
		$date1 = trim(func_get_arg($cnt++));
		$date1 = explode('/', $date1);
		if ( (count($date1) == 3) && (gmmktime(0, 0, 0, $date1[0], $date1[1], '20'.$date1[2])) )
			$date1 = gmmktime(0, 0, 0, $date1[0], $date1[1], '20'.$date1[2]);
		else
			$date1 = 0;
		$date2 = trim(func_get_arg($cnt++));
		$date2 = explode('/', $date2);
		if ( (count($date2) == 3) && (gmmktime(0, 0, 0, $date2[0], $date2[1], '20'.$date2[2])) )
			$date2 = gmmktime(0, 0, 0, $date2[0], $date2[1], '20'.$date2[2]);
		else
			$date2 = 0;
		$appID = (int)func_get_arg($cnt++);
		$customWhere = '0';
		for ( $i = 0; $i < $nSearchInSize; $i += 1 )
			if ( $searchIn[$i][2] )
				$customWhere.= " OR (".$searchIn[$i][0]." LIKE '%$text%')";
		$first_restrict = true;
		for ( $i = 0; $i < $nRestrictToSize; $i += 1 )
			if ( $restrictTo[$i][2] )
			{
				if ( $first_restrict )
				{
					$first_restrict = false;
					$customWhere = '('.$customWhere.') AND (0';
				}
				$customWhere.= ' OR (('.$restrictTo[$i][0].'>'.$date1.') AND ('.$restrictTo[$i][0].'<'.$date2.'))';
			}
		if ( !$first_restrict )
			$customWhere.= ')';
	}
	else
	{
		dbq("SELECT appID FROM settings WHERE userID=$userID");
		list( $appID ) = dbrow(1);
	}
	if ( empty($date1) )
		$date1 = '';
	else
		$date1 = date("m/d/y", $date1);
	if ( empty($date2) )
		$date2 = '';
	else
		$date2 = date("m/d/y", $date2);
	// get projects that we can search in
	dbq("SELECT user_to_app.appID, apps.appName FROM user_to_app, apps WHERE user_to_app.userID=$userID and user_to_app.appID=apps.ID");
	$result = dbr(1);
	foreach ( $result as $r )
		$theApps.= '<option value="'.$r[0].'" '.(($r[0]==$appID)?'selected="selected"':'').'>'.$r[1].'</option>';
	unset($result);
	
	$page.= '<h1>Search in '.($bBug?'bugs':'tasks').'</h1>';
	$page.= '<table class="search">';
	$page.= '<tr><td>';
	$page.= 'for <input type="text" id="search_text" value="'.$text.'"> in<small class="center">(use % as a wildcard)</small>';
	$page.= '</td><td>';
	foreach ( $searchIn as $search )
	{
		list ($name, $echo, $chk) = $search;
		$page.='<input type="checkbox" '.($chk?'checked="checked"':'').' id="search_'.$name.'"> '.$echo.'<br>';
	}
	$page.= '</td><td>';
	$page.= 'of Application <select class="project" id="search_project">'.$theApps.'</select>';
	$page.= '</td><td>';
	$page.= '<input type="button" class="default" value="Search" onClick="xajax_search_bugs(lsn,'.($bBug?1:0).',';
	foreach ( $searchIn as $search )
	{
		list ($name, $echo, $chk) = $search;
		$page.= "(xjx.$('search_$name').checked?1:0),";
	}
	foreach ( $restrictTo as $search )
	{
		list ($name, $echo, $chk) = $search;
		$page.= "(xjx.$('search_$name').checked?1:0),";
	}
	$page.= 'xjx.$(\'search_text\').value,';
	$page.= 'xjx.$(\'DPC_search_date1\').value,';
	$page.= 'xjx.$(\'DPC_search_date2\').value,';
	$page.= 'xjx.$(\'search_project\').value';
	$page.= ')"></td><td>';
	$page.= 'also restrict';
	$page.= '</td><td>';
	foreach ( $restrictTo as $search )
	{
		list ($name, $echo, $chk) = $search;
		$page.='<input type="checkbox" '.($chk?'checked="checked"':'').' id="search_'.$name.'"> '.$echo.'<br>';
	}
	$page.= '</td><td>';
	$page.= 'date between<small class="center">&nbsp;</small>';
	$page.= '</td><td>';
	$page.= '<input type="text" id="DPC_search_date1" value="'.$date1.'" size="8"><small class="center">(MM/DD/YY)</small>';
	$page.= '</td><td>';
	$page.= 'and<small class="center">&nbsp;</small>';
	$page.= '</td><td>';
	$page.= '<input type="text" id="DPC_search_date2" value="'.$date2.'" size="8"><small class="center">(MM/DD/YY)</small>';
	$page.= '</td></tr>';
	$page.= '</table>';

	$objResponse->assign('details', 'innerHTML', $page);
	$objResponse->script('DatePickerControl.init();');
	if ( $bShowResults )
	{
		$objResponse->loadCommands(print_filters($lsn, $bBug));
		$objResponse->loadCommands(print_bug_table($lsn, 0, $customWhere, $bBug));
	}
	return $objResponse;
}
function summary_bugs($lsn, $bBug = true, $appID = 0)
{
	$objResponse = new xajaxResponse();
	dbconnect();
	$page = '';
	if ( empty($appID) )
	{
		$strip = stripLSN($lsn);
		if ( empty($strip) )
			return $objResponse->alert('error with the login session');
		list ($session, $userID) = $strip;
		dbq("SELECT appID, appName FROM settings, apps WHERE userID=$userID and apps.ID=appID");
		list ( $appID, $appName ) = dbrow(1);
	}
	function print_array_as_table($arr)
	{
		$ret = '<table class="simplegrid">';
		$ret.= '<tr>';
		$ret.= '<thead><td></td>';
		$header = array();
		foreach ( $arr as $row )
			foreach ( $row as $col=>$val )
				if ( !in_array($col, $header) )
					$header[] = $col;
		sort($header);
		foreach ($header as $head)
		{
			$ret.= '<th>'.$head.'</th>';
			$arr['Total'][$head] = 0;
		}
		$ret.= '<th>Total</th>';
		$ret.= '</tr></thead><tbody>';
		//foreach ( $arr as $head=>$row )
		reset($arr);
		while (list($head, $row) = each($arr))
		{
			$ret.= '<tr>';
			$ret.= '<th>'.$head.'</th>';
			$t = 0;
			foreach ( $header as $col )
				if ( isset($row[$col]) )
				{
					$t += $row[$col];
					$arr['Total'][$col] += $row[$col];
					$ret.= '<td>'.$row[$col].'</td>';
				}
				else
					$ret.= '<td>&nbsp;</td>';
			$ret.= '<td>'.$t.'</td>';
			$ret.= '</tr>';
		}
		$ret.= '</tbody></table>';
		return $ret;
	}
	if ( empty($appID) )
		$page = 'No application selected';
	else
	{
		$page.= "<h1>Summary of $appName</h1>";
		$needed_columns = array('severityName', 'assignedToName', 'assToGroupName');
		$sel = '';
		foreach ( get_possible_columns(true) as $col )
			if ( in_array($col[0], $needed_columns) )
				$sel.= $col[2].', ';
		$sel = substr($sel, 0, -2);
		$userXsev = array();
		dbq("SELECT $sel FROM ".($bBug?'bugs':'assets').$appID);
		while ( $row = dbrow() )
		{
			if ( !isset($userXsev[$row['assignedToName']]) )
				$userXsev[$row['assignedToName']] = array();
			if ( empty($row['assignedToName']) && !empty($row['assToGroupName']) )
				$row['assignedToName'] = 'group '.$row['assToGroupName'];
			if ( !isset($userXsev[$row['assignedToName']][$row['severityName']]) )
				$userXsev[$row['assignedToName']][$row['severityName']] = 0;
			$userXsev[$row['assignedToName']][$row['severityName']] += 1;
		}
		ksort($userXsev);
		if ( isset($userXsev['']) )
		{
			$userXsev['unassigned'] = $userXsev[''];
			unset($userXsev['']);
		}
		$page.= print_array_as_table($userXsev);
	}
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function statistics($lsn, $print='')
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	
	function print_hour_table(&$details, $mask='hour%s')
	{
		$page = '<table border=1>';
		$page.= '<tr>';
		for ( $i = 0; $i < 24; $i += 1 )
		{
			if ( !isset($details[sprintf($mask, $i)]) )
				$details[sprintf($mask, $i)] = '-';
			$page.= '<td>'.$details[sprintf($mask, $i)].'</td>';
		}
		$page.= '</tr><tr>';
		for ( $i = 0; $i < 24; $i += 1 )
			$page.= '<td>'.str_pad($i, 2, '0', STR_PAD_LEFT).':00<br>'.str_pad($i, 2, '0', STR_PAD_LEFT).':59</td>';
		$page.= '</tr>';
		$page.= '</table>';
		return $page;
	}
	function print_day_table(&$details, $mask='day%s')
	{
		$page = '<table border=1>';
		$page.= '<tr>';
		for ( $i = 0; $i < 7; $i += 1 )
		{
			if ( !isset($details[sprintf($mask, $i)]) )
				$details[sprintf($mask, $i)] = '-';
			$page.= '<td>'.$details[sprintf($mask, $i)].'</td>';
		}
		$page.= '</tr><tr>';
		$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
		for ( $i = 0; $i < 7; $i += 1 )
			$page.= '<td>'.$days[$i].'</td>';
		$page.= '</tr>';
		$page.= '</table>';
		return $page;
	}
	function print_month_table(&$details, $mask='month%s')
	{
		$page = '<table border=1>';
		$page.= '<tr>';
		for ( $i = 1; $i <= 12; $i += 1 )
		{
			if ( !isset($details[sprintf($mask, $i)]) )
				$details[sprintf($mask, $i)] = '-';
			$page.= '<td>'.$details[sprintf($mask, $i)].'</td>';
		}
		$page.= '</tr><tr>';
		$months = array(1=>'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		for ( $i = 1; $i <= 12; $i += 1 )
			$page.= '<td>'.$months[$i].'</td>';
		$page.= '</tr>';
		$page.= '</table>';
		return $page;
	}
	
	switch ( $print )
	{
		case 'users':
			$page = '<h1>Users activity statistics</h1>';
			$userName = array();
			dbq("SELECT ID, login FROM users");
			while ( $row = dbrow(1) )
				$userName[$row[0]] = $row[1];
			$users = array();
			dbq("SELECT statType, statInfo, statCount FROM statistics WHERE statType='user_hour' OR statType='user_day' OR statType='user_month'");
			while ( $row = dbrow(1) )
			{
				$tmp = explode(' ', $row[1]);
				if ( !isset($users[$tmp[0]]) )
					$users[$tmp[0]] = array();
				switch ( $row[0] )
				{
					case 'user_hour':
						$users[$tmp[0]]['hour'.$tmp[1]] = $row[2];
						break;
					case 'user_day':
						$users[$tmp[0]]['day'.$tmp[1]] = $row[2];
						break;
					case 'user_month':
						$users[$tmp[0]]['month'.$tmp[1]] = $row[2];
						break;
				}
			}
			ksort($users);
			foreach ( $users as $id=>$details )
			{
				$page.= $userName[$id].'<br><br>';
				$page.= print_hour_table($details).'<br>';
				$page.= print_day_table($details).'<br>';
				$page.= print_month_table($details).'<br>';
				$page.= '<hr>';
			}
		break;
		case 'functions':
			$page = '<h1>Function call statistics</h1>';
			$functions = array();
			dbq("SELECT statType, statInfo, statCount FROM statistics WHERE statType='func_hour' OR statType='func_day' OR statType='func_month' OR statType='user_func'");
			while ( $row = dbrow(1) )
			{
				$tmp = explode(' ', $row[1]);
				switch ( $row[0] )
				{
					case 'func_hour':
						if ( !isset($functions[$tmp[0]]) )
							$functions[$tmp[0]] = array();
						$functions[$tmp[0]]['hour'.$tmp[1]] = $row[2];
						break;
					case 'func_day':
						if ( !isset($functions[$tmp[0]]) )
							$functions[$tmp[0]] = array();
						$functions[$tmp[0]]['day'.$tmp[1]] = $row[2];
						break;
					case 'func_month':
						if ( !isset($functions[$tmp[0]]) )
							$functions[$tmp[0]] = array();
						$functions[$tmp[0]]['month'.$tmp[1]] = $row[2];
						break;
					case 'user_func':
						if ( !isset($functions[$tmp[1]]) )
							$functions[$tmp[1]] = array();
						$functions[$tmp[1]]['user'.$tmp[0]] = $row[2];
						break;
				}
			}
			$userName = array();
			dbq("SELECT ID, login FROM users ORDER BY login");
			while ( $row = dbrow(1) )
				$userName[$row[0]] = $row[1];
			ksort($functions);
			foreach ( $functions as $funcName=>$details )
			{
				$page.= '<div onClick="toggle(\'func_'.$funcName.'\',\'\',\'\')">'.$funcName.'</div><div style="display:none" id="func_'.$funcName.'"><br>';
				$page.= print_hour_table($details).'<br>';
				$page.= print_day_table($details).'<br>';
				$page.= print_month_table($details).'<br>';
				$page.= '<table border=1>';
				foreach ( $userName as $id=>$name )
					if ( isset($details['user'.$id]) )
						$page.= '<tr><td>'.$name.'</td><td>'.$details['user'.$id].'</td></tr>';
				$page.= '</table><br>';
				$page.= '</div>';
			}
		break;
		default:
			$page = '<h1>Statistics</h1>';
			$details = array();
			dbq("SELECT statType, statInfo, statCount FROM statistics WHERE statType='hour' OR statType='day' OR statType='month'");
			while ( $row = dbrow(1) )
				$details[$row[0].$row[1]] = $row[2];
			$page.= print_hour_table($details).'<br>';
			$page.= print_day_table($details).'<br>';
			$page.= print_month_table($details).'<br>';
			$page.= '<input type="text" class="default" value="Function statistics" onClick="xajax_statistics(lsn, \'functions\')">';
			$page.= '<input type="text" class="default" value="User activity" onClick="xajax_statistics(lsn, \'users\')">';
		break;
	}
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}

error_reporting($old_err_reporting);
?>