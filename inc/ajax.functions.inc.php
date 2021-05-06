<?php
$old_err_reporting = error_reporting(E_ALL | E_NOTICE | E_STRICT);
function print_filter_nice($filterID, $profileData, $info, $editable = false) // this is not an ajax exported function
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
					$global_select_error_text.= 'Coud not select element '.$sel.'; $op = '.s_print_r($op).CRLF;
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
		dbq("SELECT ID, login FROM users ORDER BY login");
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
		
		dbq("SELECT ID, typeName FROM type");
		$tmp = dbr(1);
		foreach ( $tmp as $val )
			$all_filters_val['typeID'][$val[0]] = $val[1];
	}
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
function get_possible_columns($bug = true)
{
	$r = array();
	//					field name				what we print			what we query for
	$r[] =		array(	'ID',				'#',				'ID');
	$r[] =		array(	'platformName',		'Platform',			'(SELECT GROUP_CONCAT(platformName SEPARATOR ", ") FROM platforms WHERE (platforms.ID & platformID) != 0 ) AS platformName');
	$r[] =		array(	'frequencyName',	'Frequency',		'(SELECT frequencyName FROM frequency WHERE frequency.ID=frequencyID) AS frequencyName');
	$r[] =		array(	'frequencyPercent',	'Frequency%',		'frequencyPercent');
	$r[] =		array(	'title',			'Title',			'title');
	$r[] =		array(	'assignedToName',	'Assigned to',		'(SELECT login FROM users WHERE users.ID=assignedTo) AS assignedToName');
	$r[] =		array(	'assToGroupName',	'Group assigned',	'(SELECT groupName FROM groups WHERE groups.ID=assToGroup) AS assToGroupName');
	$r[] =		array(	'closedByName',		'Closed by',		'(SELECT login FROM users WHERE users.ID=closedBy) AS closedByName');
	$r[] =		array(	'severityName',		'Priority',			'(SELECT severityName FROM severity WHERE severity.ID=severityID) AS severityName');
	$r[] =		array(	'statusName',		'Status',			'(SELECT statusName FROM status WHERE status.ID=statusID) AS statusName');
	if ( $bug )
		$r[] =	array(	'typeName',			'Type',				'(SELECT typeName FROM type WHERE type.ID=typeID) AS typeName');
	else
		$r[] =	array(	'typeName',			'Type',				'(SELECT typeName FROM asset_types WHERE asset_types.ID=typeID) AS typeName');
	$r[] =		array(	'openDate',			'Open date',		'openDate');
	$r[] =		array(	'closeDate',		'Closed date',		'closeDate');
	$r[] =		array(	'submitedDate',		'Submited date',	'submitedDate');
	$r[] =		array(	'versionDate',		'Build',			'versionDate');
	$r[] =		array(	'deadLineDate',		'Dead-line',		'deadLineDate');
	return $r;
}
##################################################
### All this functions will be exported with ajax
##################################################

function debug() {
	$objResponse = new xajaxResponse();
	$script='alert(\'this is a debug text\');';
	$objResponse->script($script);
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
	dbq('SELECT groupID FROM users WHERE ID='.$userID);
	list ($groupID) = dbrow(1);
	
	$button = '<input type="button" class="menu" value="%s" onClick="%s"><br>';
	$button_d = '<input type="button" class="menu" disabled="disabled" value="%s" onClick="%s"><br>';
	$title = '<div class="menu_title">%s</div>';

	$menu = '';
	$menu.= sprintf($title, 'Menu');
	$menu.= sprintf($button, 'Home', 'xajax_home_page(lsn)');
	$menu.= sprintf($button_d, 'My Details', 'xajax_my_details(lsn)');
	$menu.= sprintf($button, 'My Profiles', 'xajax_my_profiles(lsn)');
	$menu.= sprintf($button, 'Settings', 'xajax_my_settings(lsn)');
	$menu.= sprintf($button, 'Logout', "self.location.replace('logout.php')");
	
	$menu.= sprintf($title, 'Bugs');
	$menu.= '#<input type="text" id="qsearch" class="qsearch"><input type="button" value="Go" class="default" onClick="xajax_print_bug(lsn, xjx.$(\'qsearch\').value)">';
	$menu.= sprintf($button, 'View', 'xajax_view_bugs(lsn);xajax_print_bug_table(lsn)');
	$menu.= sprintf($button, 'Add', 'xajax_add_bugs(lsn)');
	$menu.= sprintf($button, 'Search', 'xajax_search_bugs(lsn, 1)');
	
	$menu.= sprintf($title, 'Assets');
	$menu.= sprintf($button, 'View', 'xajax_view_bugs(lsn, 0);xajax_print_bug_table(lsn, 0, \'\', 0)');
	$menu.= sprintf($button, 'Add', 'xajax_add_assets(lsn)');
	$menu.= sprintf($button, 'Search', 'xajax_search_bugs(lsn, 0)');
	
	if ( $groupID == GROUP_ADMIN )
	{
		$menu.= sprintf($title, 'Admin');
		$menu.= sprintf($button, 'Users', 'xajax_admin_users(lsn)');
		$menu.= sprintf($button, 'Projects', 'xajax_admin_projects(lsn)');
		$menu.= sprintf($button_d, 'Groups', 'xajax_admin_groups(lsn)');
	}
	
	$menu.= sprintf($title, 'Misc');
	$menu.= sprintf($button_d, 'Summary', 'xajax_summary_bugs(lsn)');
	$menu.= sprintf($button_d, 'Statistics', 'xajax_statistics(lsn)');
	$menu.= sprintf($button, 'Free time', 'xajax_games()');
	
	$objResponse->assign('menu', 'innerHTML', $menu);
	return $objResponse;
}
function games()
{
	$page = '';
	$page.= '<h1>Simple free time games</h1>';
	$page.= '<input type="button" class="default" value="Number Puzzle" onClick="xajax_nrpuzzle(xjx.$(\'nr_puzzle_size\').value)"> size <input type="text" class="default" id="nr_puzzle_size" value="4" size="2" maxlength="2"><br>';
	
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
	$page = '';
	dbconnect();
	dbq("SELECT filterID, displayID FROM settings WHERE userID=".$userID);
	list ($filterID, $displayID) = dbrow(1);
	// filter
	$page.= '<h1>Filters</h1>';
	$page.= '<table class="simplegrid">';
	$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	$q = dbq("SELECT ID, profileName, profileData FROM profiles WHERE userID=$userID and profileType='filter'");
	while ( $row = mysql_fetch_assoc($q) )
	{
		$profileData = @unserialize($row['profileData']);
		$page.= '<tr>';
		$page.= '<th>'.$row['profileName'].'</th>';
		$page.= '<td>'.print_filter_nice(-1, $profileData, '', false).'</td>';
		$page.= '<td>';
		$page.= '<input type="button" class="default" value="Remove" onClick="xajax_remove_profile(lsn, '.$row['ID'].')">';
		if ( $row['ID'] != $filterID )
			$page.= '<input type="button" class="default" value="Make default" onClick="xajax_make_default_profile(lsn, '.$row['ID'].', \'filter\')">';
		$page.= '</td>';
		$page.= '</tr>';
	}
	$page.='</table>';
	// display
	$page.= '<h1>Column display</h1>';
	$page.= '<table class="simplegrid">';
	$page.= '<tr><th>Name</th><th>Data</th><th>Options</th></tr>';
	$display = array();
	foreach ( get_possible_columns() as $col )
		$display[$col[0]] = $col[1];
	$q = dbq("SELECT ID, profileName, profileData FROM profiles WHERE userID=$userID and profileType='display'");
	while ( $row = mysql_fetch_assoc($q) )
	{
		$profileData = @unserialize($row['profileData']);
		$page.= '<tr>';
		$page.= '<th>'.$row['profileName'].'</th>';
		$page.= '<td>';
		foreach ( $profileData as $col )
			$page.= $display[$col].', ';
		$page = substr($page, 0, -2);
		$page.= '</td>';
		$page.= '<td>';
		$page.= '<input type="button" class="default" value="Remove" onClick="xajax_remove_profile(lsn, '.$row['ID'].')">';
		if ( $row['ID'] != $displayID )
			$page.= '<input type="button" class="default" value="Make default" onClick="xajax_make_default_profile(lsn, '.$row['ID'].', \'display\')">';
		$page.= '</td>';
		$page.= '</tr>';
	}
	$page.='</table>';
	$objResponse->assign('details', 'innerHTML', $page);
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
	$objResponse->loadCommands(my_profiles($lsn));
	return $objResponse;
}
function admin_projects($lsn)
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
		$page.= '<option value="N"'.($isLocal=='N'?'selected="selected"':'').'>N</option>';
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
				$objResponse->alert("Update affected $x row".(($x==1)?'':'s')." in database");
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
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->alert('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>User admin area</h1>';
	dbconnect();
	$page.= '<table class="users">';
	$page.= '<thead><tr>';
	$page.= '<th>ID</th>';
	$page.= '<th>Login</th>';
	$page.= '<th>Name</th>';
	$page.= '<th>E-Mail</th>';
	$page.= '<th>Group</th>';
	$page.= '<th>Last login</th>';
	$page.= '<th>Assign</th>';
	$page.= '<th></th>';
	$page.= '</tr></thead><tbody>';
	dbq("SELECT users.ID, login, name, email, groupName, lastLoginDate, users.canAssignTo FROM users, groups WHERE groups.ID=users.groupID");
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
		$page.= '<td>'.(($row['canAssignTo'] == 'Y')?'bugs can be assigned to him':'&nbsp;').'</td>';
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
	dbq("SELECT ID, login FROM users");
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
		dbq("DELETE FROM user_to_app WHERE userID=$user and appID=$appID");
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
			dbq("SELECT login, name, email, groupID, canAssignTo FROM users WHERE ID=".$user);
			list ($login, $name, $email, $groupID, $canAssignTo) = dbrow(1);
			$page.= '<h1>Edit user information</h1>';
			$page.= 'If you leave the password field empty the password will not change';
		}
		else
		{
			$login = '';
			$name = '';
			$email = '';
			$groupID = '1';
			$canAssignTo = 'Y';
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
		$page.= '<tr><th>canAssignTo</th><td><select id="assign">';
		$page.= '<option value="Y"'.($canAssignTo=='Y'?' selected="selected"':'').'>Y</option>';
		$page.= '<option value="N"'.($canAssignTo=='N'?'selected="selected"':'').'>N</option>';
		$page.= '</td></tr></tbody>';
		$page.= '<tfoot><tr><td colspan="2"><input type="button" value="'.(empty($user)?'Add user':'Save user').'" onClick="xajax_edit_user(lsn, '.$user.', ';
		$page.= 'document.getElementById(\'login\').value, ';
		$page.= 'document.getElementById(\'name\').value, ';
		$page.= 'document.getElementById(\'email\').value, ';
		$page.= 'document.getElementById(\'pass\').value, ';
		$page.= 'document.getElementById(\'group\').value, ';
		$page.= 'document.getElementById(\'assign\').value';
		$page.= ')"></td></tr></tfoot>';
		$page.= '</table>';
		$objResponse->assign('details', 'innerHTML', $page);
	}
	else
	{
		$args = func_get_args();
		$login = dbesc($args[2]);
		if ( empty($login) )
		{
			$objResponse->alert('The user shoud have a pretty login name.');
			return $objResponse;
		}
		$name = dbesc($args[3]);
		$email = dbesc($args[4]);
		$pass = $args[5];
		$group = (int)$args[6];
		$canAssignTo = $args[7][0];
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
		if ( empty($user) )
		{
			dbq("INSERT INTO profiles(profileName, profileType, profileData) VALUES ('My bugs', 'filter', '".dbesc(serialize(array(array('AND',array(array('AND', array('statusID', '!=', BUG_STATUS_CLOSED)))))))."')");
			$filterID = mysql_insert_id();
			dbq("INSERT INTO profiles(profileName, profileType, profileData) VALUES ('My bugs display', 'display', '".dbesc(serialize(array('ID','title')))."')");
			$displayID = mysql_insert_id();
			dbq("INSERT INTO users(login, name, email, password, groupID, canAssignTo) VALUES ('$login', '$name', '$email', '$pass', $group, '$canAssignTo')");
			$user = mysql_insert_id();
			dbq("UPDATE profiles SET userID=$user WHERE ID=$filterID or ID=$displayID");
			dbq("INSERT INTO settings(userID, filterID, displayID) VALUES($user, $filterID, $displayID)");
			$objResponse->alert("User inserted in database with ID ".$user);
			$objResponse->loadCommands(admin_users($lsn));
		}
		else
		{
			dbq("UPDATE users SET login='$login', name='$name', email='$email', groupID=$group, canAssignTo='$canAssignTo'".(empty($pass)?'':", password='$pass'")." WHERE ID=$user");
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
		dbq('SELECT users.name, groupName FROM users, groups WHERE users.ID='.$userID.' and users.groupID=groups.ID');
		list ($r) = dbr();
		$page = '';
		$page.= "<h1>Hello $r[name] and welcome to ".SERVER_NAME." v".SERVER_VERSION."</h1>";
		if ( empty($r['groupName']) )
			$page.= 'You are in no group. Ask the Project Leader about this problem.';
		else
		{
			$page.= "You are currently a member of the \"$r[groupName]\" group and you have access to the following projects:";
			dbq("SELECT appName FROM user_to_app, apps WHERE user_to_app.userID=$userID and user_to_app.appID=apps.ID");
			$page.= '<br><ul>';
			foreach (dbr() as $app)
				$page.= "<li>$app[appName]</li>";
			$page.= '</ul><br>';
			/*
			$page.= 'Bugs assigned to you:';
			dbq("SELECT ID, severityName FROM severity ORDER BY priority");
			$severity = array();
			while ( $row = dbrow(1) )
				$severity[$row[0]] = $row[1];
			foreach ( $severity as $id=>$severityName )
			{
				dbq("SELECT COUNT(*) FROM bugs");
			}
			*/
		}
		$objResponse->assign('details', 'innerHTML', $page);
	}
	return $objResponse;
}
function my_details($lsn)
{
	$objResponse = new xajaxResponse();
	$script='alert(\'this is a my_details placeholder text\');';
	$objResponse->script($script);
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
		$css = dbesc(func_get_arg(1));
		$bugsPerPage = (int)func_get_arg(2);
		dbq("UPDATE settings SET CSS='$css', bugsPerPage=$bugsPerPage WHERE userID=$userID");
		$objResponse->alert('settings saved');
	}
	$page = '<h1>Settings</h1>';
	if ( empty($css) || empty($bugsPerPage) )
	{
		dbq("SELECT CSS, bugsPerPage FROM settings WHERE userID=$userID");
		list ($css, $bugsPerPage) = dbrow(1);
	}
	$page.= '<table>';
	$page.= '<tr><th>CSS</th><td><input type="text" id="my_CSS" value="'.$css.'"> (leave empty for default CSS)</td></tr>';
	$page.= '<tr><th>Bugs per page</th><td><input type="text" id="bugsPerPage" value="'.$bugsPerPage.'"></td></tr>';
	$page.= '<tr><td colspan="2"><input type="button" class="default" value="Save" onClick="xajax_my_settings(lsn, xjx.$(\'my_CSS\').value, xjx.$(\'bugsPerPage\').value)"></td</tr>';
	$page.= '</table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function print_filters($lsn, $filterID = NULL, $profileName = NULL, $profileData = NULL, $profileOwnerID = NULL)
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
		dbq("SELECT filterID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.filterID=profiles.ID");
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
	dbq("SELECT profileName, (SELECT login FROM users WHERE userID=users.ID) AS userName, ID, userID FROM profiles WHERE profileType='filter'");
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

	$txt = print_filter_nice($filterID, $profileData, $profile_info, $userID == $profileOwnerID);

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
		case 'filter':
			dbq("UPDATE settings SET filterID=$profileID WHERE userID=$userID");
			$objResponse->loadCommands(print_filters($lsn, $profileID));
			break;
		case 'display':
			dbq("UPDATE settings SET displayID=$profileID WHERE userID=$userID");
			$objResponse->loadCommands(print_display($lsn, $profileID));
			$objResponse->loadCommands(print_bug_table($lsn));
			break;
	}
	return $objResponse;
}
########################################
## used from "my profiles" page
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
		case 'filter':
			dbq("UPDATE settings SET filterID=$profileID WHERE userID=$userID");
			break;
		case 'display':
			dbq("UPDATE settings SET displayID=$profileID WHERE userID=$userID");
			break;
	}
	$objResponse->loadCommands(my_profiles($lsn));
	return $objResponse;
}
function save_profile_as($lsn, $profileID, $name)
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
	dbq("SELECT profileType, profileData FROM profiles WHERE ID=$profileID");
	list ($profileType, $profileData) = dbrow(1);
	dbq("INSERT INTO profiles(userID, profileName, profileType, profileData) VALUES($userID, '$name', '$profileType', '".dbesc($profileData)."')");
	$objResponse->alert('Profile saved');
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
	dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$filterID");
	list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
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
	$objResponse->loadCommands(print_filters($lsn, $filterID, $profileName, $profileData, $profileOwnerID));
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
	dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$filterID");
	list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
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
	$objResponse->loadCommands(print_filters($lsn, $filterID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$filterID);
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
	dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$filterID");
	list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
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
	$objResponse->loadCommands(print_filters($lsn, $filterID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$filterID);
	return $objResponse;
}
function print_display($lsn, $displayID = NULL, $profileName = NULL, $profileData = NULL, $profileOwnerID = NULL)
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
	if ( empty($displayID) )
	{
		dbq("SELECT displayID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.displayID=profiles.ID");
		list ($displayID, $profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	elseif ( empty($profileName) || empty($profileData) || empty($profileOwnerID) )
	{
		dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$displayID");
		list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();
	
// $profileData = array('ID','title','assignedToName','closedByName','severityName');

	$possible_cols = get_possible_columns();
	
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
	
	$page = '<table class="display_toggle">';
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
			$page.= '</td><td class="display_profile_details" rowspan="'.(count($profileData) + 1).'">';
			if ( $userID != $profileOwnerID )
				$page.= '<input type="button" class="display_btn" value="Save profile as" onClick="xajax_save_profile_as(lsn, '.$displayID.', document.getElementById(\'save_display_as\').value)"><input type="text" class="profile_name" value="'.$profileName.'" id="save_display_as"><br>';
			$page.= 'Current profile <select class="profile" onChange="xajax_change_profile(lsn, this.value)">';
			dbq("SELECT profileName, (SELECT login FROM users WHERE userID=users.ID) AS userName, ID, userID FROM profiles WHERE profileType='display'");
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
function change_display($lsn, $displayID, $k, $value)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->script('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	if ( (empty($profileName) || empty($profileData) || empty($profileOwnerID)) && empty($displayID) )
	{
		dbq("SELECT displayID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.displayID=profiles.ID");
		list ($displayID, $profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	elseif ( empty($profileName) || empty($profileData) || empty($profileOwnerID) )
	{
		dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$displayID");
		list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();

	if ( isset($profileData[$k]) )
		$profileData[$k] = $value;

	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_display($lsn, $displayID, $profileName, $profileData, $profileOwnerID));
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
	if ( (empty($profileName) || empty($profileData) || empty($profileOwnerID)) && empty($displayID) )
	{
		dbconnect();
		dbq("SELECT displayID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.displayID=profiles.ID");
		list ($displayID, $profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	elseif ( empty($profileName) || empty($profileData) || empty($profileOwnerID) )
	{
		dbconnect();
		dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$displayID");
		list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();

	array_splice($profileData, $k, 0, 'ID');

	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_display($lsn, $displayID, $profileName, $profileData, $profileOwnerID));
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
	if ( (empty($profileName) || empty($profileData) || empty($profileOwnerID)) && empty($displayID) )
	{
		dbconnect();
		dbq("SELECT displayID, profileName, profileData, profiles.userID FROM settings, profiles WHERE settings.userID=$userID and settings.displayID=profiles.ID");
		list ($displayID, $profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	elseif ( empty($profileName) || empty($profileData) || empty($profileOwnerID) )
	{
		dbconnect();
		dbq("SELECT profileName, profileData, userID FROM profiles WHERE ID=$displayID");
		list ($profileName, $profileData, $profileOwnerID) = dbrow(1);
	}
	$profileData = @unserialize($profileData);
	if ( empty($profileData) )
		$profileData = array();

	array_splice($profileData, $k, 1);

	if ( empty($profileData) )
		$profileData = array('ID');

	$profileData = serialize($profileData);
	$objResponse->loadCommands(print_display($lsn, $displayID, $profileName, $profileData, $profileOwnerID));
	dbq("UPDATE profiles SET profileData='".dbesc($profileData)."' WHERE ID=".$displayID);
	return $objResponse;
}
function print_bug_table($lsn, $limitStart = 0, $customWhere = '', $bug = true)
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
	dbq("SELECT (SELECT profileData FROM settings, profiles WHERE settings.userID=$userID and settings.filterID=profiles.ID), 
		(SELECT profileData FROM settings, profiles WHERE settings.userID=$userID and settings.displayID=profiles.ID),
		(SELECT appID FROM settings WHERE userID=$userID),
		(SELECT bugsPerPage FROM settings WHERE userID=$userID)");
	list ($filterData, $displayData, $defaultApp, $bugsPerPage) = dbrow(1);
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
	$possible_cols = get_possible_columns($bug);
	
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

	if ( $bug )
		$fromTable = 'bugs'.$defaultApp;
	else
		$fromTable = 'assets'.$defaultApp;
	$q = dbq("SELECT $display_txt (SELECT severityColor FROM severity WHERE severity.ID=severityID) AS severityColor, ID, assignedTo, flags FROM $fromTable WHERE $filter_txt");
	$totalBugs = mysql_num_rows($q);
	$page.= '<table id="bug_table" class="bug_table">';
	$page.= '<thead><tr>';
	$page.= '<th><input type="checkbox" onChange="check_all(\'bug_table\', this.checked)"></th>';
	foreach ( $header as $val )
		$page.= '<th>'.$val.'</th>';
	$page.= '</tr></thead>';
	$page.= '<tbody>';
	$ignoreBugs = $limitStart;
	$displayBugs = $bugsPerPage;
	$thisPageURI = getThisPageURI('/');
	while ( $row = dbrow() )
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
		$page.= '<tr bgcolor="'.$row['severityColor'].'">';
		$page.= '<th><input type="checkbox" id="a'.$defaultApp.'b'.$row['ID'].'">';
		if ( (!($row['flags'] & BUG_VIEWED)) && ($row['assignedTo'] == $userID) )
			$page.= '<br><img src="img/new.gif" alt="New">';
		$page.= '</th>';
		foreach ( $displayData as $val )
		{
			if ( empty($row[$val]) )
				$row[$val] = '&nbsp;';
			elseif ( in_array($val, array('closeDate', 'submitedDate')) )
				$row[$val] = date(DATE_TIME, $row[$val]);
			elseif ( in_array($val, array('openDate', 'deadLineDate')) )
				$row[$val] = date(DATE_NOTIME, $row[$val]);
			elseif ( $val == 'versionDate' )
				$row[$val] = date(DATE_BUGVER, $row[$val]);
			else
				$row[$val] = htmlspecialchars($row[$val]);
				
			if ( $val == 'title' )
				$row[$val] = '<a class="bug" target="_blank" href="'.$thisPageURI.'?bug='.$row['ID'].'&app='.($bug?'b':'a').$defaultApp.'">'.$row[$val].'</a>';
			$page.= '<td>'.$row[$val].'</td>';
		}
		$page.= '</tr>';
	} 
	$page.= '</tbody>';
	$page.= '<tfoot><tr><th colspan="'.(count($header) + 1).'">';
	if ( empty($totalBugs) )
		$page.= 'No bugs to display';
	else
		$page.= 'Displaing '.($limitStart + 1).' - '.($limitStart + $bugsPerPage - max(0, $displayBugs)).' of '.$totalBugs.' bugs matching your filters';
	$page.= '</th></tr></tfoot>';
	$page.= '</table>';
	if ( ($displayBugs == -1) || ($limitStart > 0) )
	{
		$nrPages = $totalBugs / $bugsPerPage;
		$page.= 'Pages: ';
		$limitStart += 1;
		for ( $i = 0; $i < $nrPages; $i += 1 )
		{
			$page_start = $i * $bugsPerPage + 1;
			$page_end = min(($i + 1) * $bugsPerPage, $totalBugs);
			if ( ($limitStart >= $page_start) && ($limitStart <= $page_end) )
				$disabled = 'disabled="disabled"';
			else
				$disabled = '';
			$page.= '<input '.$disabled.' type="button" class="page" value="'.$page_start.' - '.$page_end.'" onClick="xajax_print_bug_table(lsn, '.($i * $bugsPerPage).')">';
		}
	}
	$objResponse->assign('bug_table_div', 'innerHTML', $page);
	return $objResponse;
}
function print_bug($lsn, $bugID, $appID = 'b0')
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	$bugID = (int)$bugID;
	$bBug = ($appID[0] == 'b');
	$appID = (int)substr($appID, 1);
	if ( $bBug )
		$fromTable = 'bugs'.$appID;
	else
		$fromTable = 'assets'.$appID;
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
		dbq("SELECT groupID, appID FROM users, settings WHERE users.ID=$userID and settings.userID=$userID");
		list($groupID, $appID) = dbrow(1);
	}
	else
	{
		dbq("SELECT groupID FROM users WHERE ID=$userID");
		list($groupID) = dbrow(1);
	}
	$possible_cols = get_possible_columns($bBug);
	$select = '';
	// $translate = array(
	// 'description'	=>	'Description',
	// 'history'		=>	'History',
	// );
	foreach ( $possible_cols as $col )
	{
		$select.= $col[2].', ';
		// $translate[$col[0]] = $col[1];
	}
	//$select = substr($select, 0, -2);
	dbq("UPDATE $fromTable SET flags=(flags | ".BUG_VIEWED.") WHERE ID=$bugID AND assignedTo=$userID");
	$q = dbq("SELECT $select statusID, assToGroup, assignedTo, description, info, notes, history, versionDate, deadLineDate, (SELECT appName FROM apps WHERE ID=$appID) AS appName, (SELECT login FROM users WHERE users.ID=submitedBy) AS submitedByName FROM $fromTable WHERE ID=$bugID");
	if ( ($x = mysql_num_rows($q)) != 1 )
	{
		$objResponse->alert(($bBug?'Bug':'Asset')." '$bugID' not found");
		return $objResponse;
	}
	$bug = dbrow();
	// foreach ($bug as $h=>$b)
	// {
		// if ( empty($translate[$h]) )
			// $translate[$h] = $h;
		// $page.= '<tr><th>'.$translate[$h].'</th><td>'.$b.'</td></tr>';
	// }
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
		$bug['assign'] = '<em>Assigned</em> to </td><td>'.$bug['assignedToName'];
	if ( empty($bug['submitedDate']) )
		$bug['submited'] = '&nbsp;';
	else
		$bug['submited'] = '<em>Submited</em> on </td><td>'.date(DATE_TIMELONG, $bug['submitedDate']).' by '.$bug['submitedByName'];
	
	$page.= '<tr><td colspan="4"><h1>'.($bBug?'Bug':'Asset').' '.$bug['ID'].'</h1></td></tr>';
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
	$page.= '<tr><td><em>Build</em></td><td>'.date(DATE_BUGVER, $bug['versionDate']).'</td></tr>';
	if ( $bBug )
		$page.= '<tr><td><em>Frequency</em></td><td>'.$bug['frequencyName'].' '.$bug['frequencyPercent'].'</td></tr>';
	else
		$page.= '<tr><td><em>Dead-line</em></td><td>'.date(DATE_NOTIME, $bug['deadLineDate']).'</td></tr>';
	$page.= '<tr><td>'.$bug['submited'].'</td></tr>';
	$page.= '<tr><td>'.$bug['closedByName'].'</td></tr>';
	$page.= '</table></td><td><table>';
	$page.= '<tr><td>';
	if ( $bug['statusID'] != BUG_STATUS_CLOSED )
	{
		if ( !empty($bug['assignedTo']) && ($bug['assignedTo'] != $userID) )
			$confirm2 = "&&confirm('This bug was assigned to \'$bug[assignedToName]\'.\\r\\nAre you sure you want to report bug $bugID as closed?')";
		elseif ( !empty($bug['assToGroup']) && ($bug['assToGroup'] != $groupID) )
			$confirm2 = "&&confirm('This bug was assigned to group \'$bug[assToGroupName]\'.\\r\\nAre you sure you want to report bug $bugID as closed?')";
		else
			$confirm2 = '';
		
		if ( empty($bug['assignedTo']) && empty($bug['assToGroup']) )
			$page.= 'Please assign this';
		else
			$page.= '<input type="button" value="Close bug" class="default" onClick="if(confirm(\'Are you sure you want to close it?\')'.$confirm2.'){xajax_close_bugs(lsn, '.$appID.', '.$bugID.');xajax_print_bug(lsn, '.$bugID.', '.($bBug?'b':'a').$appID.')};">';
	}
	$page.= '</td></tr><tr><td>';
	$page.= '<input type="button" class="default" value="Edit" onClick="xajax_edit_all(lsn, \'a'.$appID.'b'.$bugID.'\')">';
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
function view_bugs($lsn, $bug = true)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->script('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	dbconnect();
	
	$toggle = '<input type="button" class="toggle_btn" id="%1$s_btn" value="%2$s" onClick="toggle(\'%1$s\', \'%2$s\', \'%3$s\')">';
	if ( $bug )
		$refresh_btn = '<input type="button" class="default" value="Refresh buglist" onClick="xajax_print_bug_table(lsn)">';
	else
		$refresh_btn = '<input type="button" class="default" value="Refresh assetlist" onClick="xajax_print_bug_table(lsn, 0, \'\', 0)">';

	$theApps = '';
	dbq("SELECT user_to_app.appID, apps.appName, settings.appID AS defaultApp FROM user_to_app, apps, settings WHERE user_to_app.userID=$userID and settings.userID=$userID and user_to_app.appID=apps.ID ORDER BY appID DESC");
	$myapps = dbr();
	if ( empty($myapps) )
	{
		$objResponse->alert('You are not assigned to any project. Talk to your Project Manager to assign you to a project.');
		return $objResponse;
	}
	$defaultApp = $myapps[key($myapps)]['defaultApp'];
	if ( $defaultApp == 0 )
	{
		change_display_project($lsn, $myapps[key($myapps)]['appID']); // ignore the return
		$defaultApp = $myapps[key($myapps)]['appID'];
	}
	foreach ( $myapps as $app )
	{
		$theApps.= '<option value="'.$app['appID'].'"';
		if ( $app['appID'] == $defaultApp )
			$theApps.= 'selected="selected"';
		$theApps.= '>'.$app['appName'].'</option>';
	}
	unset($myapps);
	
	# print the header
	$page = '';
	$page.= sprintf($toggle, 'filters_toggle', 'View filters', 'Hide filters');
	$page.= sprintf($toggle, 'col_display_toggle', 'View column toggles', 'Hide column toggles');
	$page.= $refresh_btn;
	$page.= 'Project: <select class="project" onChange="xajax_change_display_project(lsn, this.value)">'.$theApps.'</select>';
	$page.= '<input type="button" class="default" value="Edit selected" onClick="get_checkboxes(\'bug_table\', \'edit_all\')">';
	$page.= '<div class="toggle" id="filters_toggle">the filters</div>';
	$page.= '<div class="toggle" id="col_display_toggle">the column display</div>';
	$page.= '<div class="bug_table" id="bug_table_div">Press "'.$refresh_btn.'" to load the '.($bug?'bug':'asset').'s. If the button does not work the probable cause is that your browser can not handle the amount of information in the list. Set less '.($bug?'bug':'asset').'s per page or put more filters.<br>If you are sure this is not the case please inform the webmaster about the problem.</div>';
	
	$objResponse->assign('details', 'innerHTML', $page);
	$objResponse->script("toggle('filters_toggle', 'View filters', 'Hide filters');");
	$objResponse->script("toggle('col_display_toggle', 'View column toggles', 'Hide column toggles');");
	// $objResponse->script('var e=document.getElementById("filters_toggle_btn");e.value="View filters";');
	// $objResponse->script('var e=document.getElementById("col_display_toggle_btn");e.value="View column toggles";');
	// $objResponse->script('var e=document.getElementById("filters_toggle");e.style.display="none";');
	// $objResponse->script('var e=document.getElementById("col_display_toggle");e.style.display="none";');
	$objResponse->loadCommands(print_filters($lsn));
	$objResponse->loadCommands(print_display($lsn));
	//$objResponse->loadCommands(print_bug_table($lsn));
	return $objResponse;
}
function edit_all($lsn, $bug = true)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 1 )
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
	$page = '';
	$thisPageURI = getThisPageURI('/');
	$args = func_get_args();
	$bugs = array();
	for ( $i = 1; $i < func_num_args(); $i += 1 )
		$bugs[] = explode('b', substr($args[$i], 1));
	$theBugs = '';
	$nSize = count($bugs);
	$page.= '<h1>Multiple bug edit</h1>Editing bug'.(($nSize == 1)?' ':'s: ');
	for ( $i = 0; $i < $nSize; $i += 1 )
	{
		//$page.= '<b class="bug">'.$bugs[$i][1].'</b>, ';
		$page.= '<a class="bug" target="_blank" href="'.$thisPageURI.'?bug='.$bugs[$i][1].'&app='.($bug?'b':'a').$bugs[$i][0].'">'.$bugs[$i][1].'</a>, ';
		$theBugs.= ', '.$bugs[$i][1];
		if ( isset($bugs[$i-1]) && ($bugs[$i-1][0] != $bugs[$i][0]) )
		{
			$objResponse->alert('Found bugs from two projects ... ERROR !');
			return $objResponse;
		}
	}

	$page = substr($page, 0, -2);
	dbconnect();
	$page.= '<table><tbody>';
	
	$page.= '<tr><th>Change status to</th><td><select id="edit_status"><option value="0">no change</option>';
	dbq("SELECT ID, statusName FROM status WHERE ID!=".BUG_STATUS_CLOSED." and ID!=".BUG_STATUS_OPEN." and ID!=".BUG_STATUS_REOPEN." and ID!=".BUG_STATUS_WAIVED);
	while ( $row = dbrow(1) )
		$page.= '<option value="'.$row[0].'">'.$row[1].'</option>';
	$page.= '</select></td></tr>';
	
	$page.= '<tr><th>Assign all to group</th><td><select id="edit_group"><option value="0">no change</option>';
	dbq("SELECT ID, groupName FROM groups WHERE canAssignTo='Y'");
	while ( $row = dbrow(1) )
		$page.= '<option value="'.$row[0].'">'.$row[1].'</option>';
	$page.= '</select> (must be set to "no change" if you assign the bug to a user)</td></tr>';
	
	$page.= '<tr><th>Assign all to user</th><td><select id="edit_user"><option value="0">no change</option>';
	dbq("SELECT ID, login, name FROM users WHERE canAssignTo='Y'");
	while ( $row = dbrow(1) )
		$page.= '<option value="'.$row[0].'">'.$row[1].' - '.$row[2].'</option>';
	$page.= '</select> (must be set to "no change" if you assign the bug to a group)</td></tr>';
	
	$page.= '<tr><th>Add note'.(($nSize == 1)?'':' to all').'</th><td><textarea id="edit_note" cols="100" rows="3"></textarea>';
	
	$page.= '</tbody><tfoot>';
	
	
	$page.= '<tr><td colspan="2">';
	$page.= '<input type="button" value="Save changes" class="default" onClick="xajax_edit_multiple(lsn';
	$page.= ', document.getElementById(\'edit_status\').value';
	$page.= ', document.getElementById(\'edit_group\').value';
	$page.= ', document.getElementById(\'edit_user\').value';
	$page.= ', document.getElementById(\'edit_note\').value';
	$page.= ', '.$bugs[0][0].$theBugs;
	$page.= ')">';
	$page.= '<input type="button" value="Close'.(($nSize == 1)?'':' all').'" class="default" onClick="confirm(\'Are you sure you want to close all?\')?xajax_close_bugs(lsn, '.$bugs[0][0].$theBugs.'):alert(\'bugs have not been closed\')">';
	$page.= '</td></tr>';
	$page.= '</tfoot>';
	$page.= '</table>';
	$objResponse->assign('details', 'innerHTML', $page);
	return $objResponse;
}
function close_bugs($lsn, $defaultApp)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 2 )
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
	dbq("SELECT login FROM users WHERE ID=$userID");
	list($userName) = dbrow(1);
	$userName = dbesc($userName);
	$theBugs = '(0';
	for ( $i = 2; $i < func_num_args(); $i += 1 )
		$theBugs.= ' or ID='.func_get_arg($i);
	$theBugs.= ')';
	$now = date(DATE_HISTORY);
	dbq("UPDATE bugs$defaultApp SET statusID=".BUG_STATUS_CLOSED.", closeDate=".time().", closedBy=$userID, history=CONCAT('$now: $userName<li>changed the status to Closed</li><br>', history) WHERE assignedTo!=0 and statusID!= ".BUG_STATUS_CLOSED.' and '.$theBugs);
	$nr = mysql_affected_rows();
	$objResponse->alert('Closed '.$nr.' bug'.($nr == 1?'':'s').' from '.(func_num_args() - 2).' sent');
	return $objResponse;
}
function edit_multiple($lsn, $statusID, $group, $user, $notes, $defaultApp)
{
	$objResponse = new xajaxResponse();
	if ( func_num_args() <= 6 )
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
	dbq("SELECT login, groupID FROM users WHERE ID=$userID");
	list($userName, $groupID) = dbrow(1);
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
	
	if ( ($groupID != GROUP_ADMIN) && ((strlen($notes) <= 4) || in_array(strtolower($notes), array('assigned', 'reassigned', 'assign', 'reassign'))) )
	{
		$objResponse->alert('How about some usefull notes ?');
		return $objResponse;
	}

	$theBugs = '(0';
	for ( $i = 6; $i < func_num_args(); $i += 1)
		$theBugs.= ' or ID='.func_get_arg($i);
	$theBugs.= ')';
	
	$page = '<h1>Results from the edit</h1>';
	
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
	
	$page.= mysql_affected_rows().' of '.(func_num_args() - 6).' bugs have beed updated';
	$objResponse->assign('details', 'innerHTML', $page);
	// $objResponse->loadCommands(view_bugs($lsn));
	// $objResponse->loadCommands(print_bug_table($lsn));
	return $objResponse;
}
function change_display_project($lsn, $projectID)
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
	$objResponse->loadCommands(print_bug_table($lsn));
	return $objResponse;
}
function add_bugs($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->script('error with the login session');
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
		dbq("SELECT ID, CONCAT(login, ' (', name, ')') FROM users WHERE canAssignTo='Y' ORDER BY login ASC");
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
		$page.= '<select id="add_bug_group"><option value="0">Empty</option>'.$theGroups.'</select> (must be left empty if you assign the bug to a user)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Assigned to</th><td>';
		$page.= '<select id="add_bug_assign"><option value="0">Empty</option>'.$theUsers.'</select> (must be left empty if you assign the bug to a group)';
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
		$page.= '<input id="add_bug_version" type="text" maxlength="8"> (MM/DD/YY)';
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
function add_assets($lsn)
{
	$objResponse = new xajaxResponse();
	$strip = stripLSN($lsn);
	if ( empty($strip) )
	{
		$objResponse->script('error with the login session');
		return $objResponse;
	}
	list ($session, $userID) = $strip;
	$page = '<h1>Add an asset to a project</h1>';
	dbconnect();
	if ( func_num_args() == 1 )
	{
		// get projects that we can add bugs to
		$theApps = '';
		dbq("SELECT user_to_app.appID, apps.appName FROM user_to_app, apps WHERE user_to_app.userID=$userID and user_to_app.appID=apps.ID");
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
		dbq("SELECT ID, CONCAT(login, ' (', name, ')') FROM users WHERE canAssignTo='Y' ORDER BY login ASC");
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
		
		$page.= '<tr><th>Assigned to group</th><td>';
		$page.= '<select id="add_asset_group"><option value="0">Empty</option>'.$theGroups.'</select> (must be left empty if you assign the asset to a user)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Assigned to</th><td>';
		$page.= '<select id="add_asset_assign"><option value="0">Empty</option>'.$theUsers.'</select> (must be left empty if you assign the asset to a group)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Type</th><td>';
		$page.= '<select id="add_asset_type"><option value="0">Choose asset type</option>'.$theTypes.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Priority</th><td>';
		$page.= '<select id="add_asset_severity"><option value="0">Choose priority</option>'.$theSeverity.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Final dead-line</th><td>';
		$page.= '<input id="add_asset_deadline" type="text" size="8" maxlength="8"> (MM/DD/YY)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Platform</th><td>';
		$page.= '<select id="add_asset_platform"><option value="0">Choose platform</option>'.$thePlatforms.'</select>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Build version</th><td>';
		$page.= '<input id="add_asset_version" type="text" size="8" maxlength="8"> (MM/DD/YY)';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Asset name</th><td>';
		$page.= '<input id="add_asset_title" type="text" size="100" maxlength="256">';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Description</th><td>';
		$page.= '<textarea id="add_asset_description" cols="100" rows="9"></textarea>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th>Notes</th><td>';
		$page.= '<textarea id="add_asset_notes" cols="100" rows="3"></textarea>';
		$page.= '</td></tr>';
		
		$page.= '<tr><th colspan="2"><input type="button" class="default" value="Add asset" onClick="add_asset_verify()"></th></tr>';
		
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
		$history = dbesc(date(DATE_HISTORY, $now).' asset inserted in database');
		dbq("INSERT INTO assets$args[1] (openDate, assToGroup, assignedTo, typeID, severityID, platformID, versionDate, deadLineDate, title, description, notes, history, statusID) 
			VALUES (".time().", $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], '$args[9]', '$args[10]', '$args[11]', '$history', ".BUG_STATUS_OPEN.")");
		$objResponse->alert("Asset inserted in database with ID ".mysql_insert_id());
		$objResponse->assign('add_asset_title', 'value', '');
		$objResponse->assign('add_asset_description', 'value', '');
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
		$page.= '<input type="button" class="default" value="Edit selected" onClick="get_checkboxes(\'bug_table\', \'edit_all\')">';
		$page.= '<div class="bug_table" id="bug_table_div">There shoud be a bug table here</div>';
		$page.= '<h1>Filters</h1>';
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
	
	$page.= '<h1>Search in '.($bBug?'bugs':'assets').'</h1>';
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
	$page.= 'of Application <select class="projects" id="search_project">'.$theApps.'</select>';
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
	$page.= 'xjx.$(\'search_date1\').value,';
	$page.= 'xjx.$(\'search_date2\').value,';
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
	$page.= '<input type="text" id="search_date1" value="'.$date1.'"><small class="center">(MM/DD/YY)</small>';
	$page.= '</td><td>';
	$page.= 'and<small class="center">&nbsp;</small>';
	$page.= '</td><td>';
	$page.= '<input type="text" id="search_date2" value="'.$date2.'"><small class="center">(MM/DD/YY)</small>';
	$page.= '</td></tr>';
	$page.= '</table>';

	$objResponse->assign('details', 'innerHTML', $page);
	if ( $bShowResults )
	{
		$objResponse->loadCommands(print_filters($lsn));
		$objResponse->loadCommands(print_bug_table($lsn, 0, $customWhere, $bBug));
	}
	return $objResponse;
}
function summary_bugs($lsn)
{
	$objResponse = new xajaxResponse();
	$script='alert(\'this is a summary_bugs placeholder text\');';
	$objResponse->script($script);
	return $objResponse;
}
function statistics($lsn)
{
	$objResponse = new xajaxResponse();
	$script='alert(\'this is a statistics placeholder text\');';
	$objResponse->script($script);
	return $objResponse;
}

error_reporting($old_err_reporting);
?>