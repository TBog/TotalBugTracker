function init()
{
	//xajax_debug();
	xajax_load_style(lsn);
	if (arguments.length == 2)
	{
		xajax_print_bug(lsn, arguments[0], arguments[1]);
		xjx.$('menu').style.display = 'none';
		xjx.$('table_menu').style.display = 'none';
	}
	else
	{
		xajax_print_menu(lsn);
		xajax_home_page(lsn);
	}
}

function login_init()
{
	document.getElementById('login_name').focus();
	document.getElementById('login_name').select();
}

function toggle(theID, sOff, sOn)
{
	var e = xjx.$(theID);
	var o = xjx.$(theID + '_btn');
	if ( e.style.display == 'none' )
	{
		e.style.display = 'block';
		o.value = sOn;
	}
	else
	{
		e.style.display = 'none';
		o.value = sOff;
	}
}

function add_bug_verify()
{
	var e;
	var must_choose_select = new Object();
	must_choose_select[0] = 'add_bug_project';
	must_choose_select[1] = 'add_bug_type';
	must_choose_select[2] = 'add_bug_severity';
	must_choose_select[3] = 'add_bug_platform';
	for (var key in must_choose_select)
	{
		e = xjx.$(must_choose_select[key]);
		if ( e.value == 0 )
			for ( i = 0; i < e.options.length; i++ ) // iterate through all the options
				if ( e.options[i].value == e.value ) // if we find the must_choose_select option
				{
					alert(e.options[i].innerHTML);
					e.focus();
					return;
				}
	}
	if ( (xjx.$('add_bug_group').value == 0) && (xjx.$('add_bug_assign').value == 0) )
	{
		alert('Select a group or a user');
		xjx.$('add_bug_group').focus();
		return;
	}
	else if ( (xjx.$('add_bug_group').value != 0) && (xjx.$('add_bug_assign').value != 0) )
	{
		alert('Select a group or a user, not both');
		xjx.$('add_bug_group').focus();
		return;
	}
	if ( xjx.$('add_bug_version').value.length != 8 )
	{
		alert('Build version must have 8 characters');
		xjx.$('add_bug_version').focus();
		return;
	}
	if ( xjx.$('add_bug_title').value.length < 1 )
	{
		alert('Write a title');
		xjx.$('add_bug_title').focus();
		return;
	}
	if ( xjx.$('add_bug_description').value.length < 1 )
	{
		alert('Write a description');
		xjx.$('add_bug_description').focus();
		return;
	}
	xajax_add_bugs(lsn, 
		xjx.$('add_bug_project').value,
		xjx.$('add_bug_group').value,
		xjx.$('add_bug_assign').value,
		xjx.$('add_bug_type').value,
		xjx.$('add_bug_severity').value,
		xjx.$('add_bug_platform').value,
		xjx.$('add_bug_version').value,
		xjx.$('add_bug_frequency').value,
		xjx.$('add_bug_frequency_per').value,
		xjx.$('add_bug_title').value,
		xjx.$('add_bug_description').value);
}

function add_asset_verify()
{
	var e;
	var must_choose_select = new Object();
	must_choose_select[0] = 'add_asset_project';
	must_choose_select[1] = 'add_asset_type';
	must_choose_select[2] = 'add_asset_severity';
	must_choose_select[3] = 'add_asset_platform';
	for (var key in must_choose_select)
	{
		e = xjx.$(must_choose_select[key]);
		if ( e.value == 0 )
			for ( i = 0; i < e.options.length; i++ ) // iterate through all the options
				if ( e.options[i].value == e.value ) // if we find the must_choose_select option
				{
					alert(e.options[i].innerHTML);
					e.focus();
					return;
				}
	}
	if ( (xjx.$('add_asset_group').value == 0) && (xjx.$('add_asset_assign').value == 0) )
	{
		alert('Select a group or a user');
		xjx.$('add_asset_group').focus();
		return;
	}
	else if ( (xjx.$('add_asset_group').value != 0) && (xjx.$('add_asset_assign').value != 0) )
	{
		alert('Select a group or a user, not both');
		xjx.$('add_asset_group').focus();
		return;
	}
	if ( xjx.$('add_asset_version').value.length != 8 )
	{
		alert('Build version must have 8 characters');
		xjx.$('add_asset_version').focus();
		return;
	}
	if ( xjx.$('add_asset_deadline').value.length != 8 )
	{
		alert('Deadline must have 8 characters');
		xjx.$('add_asset_deadline').focus();
		return;
	}
	if ( xjx.$('add_asset_title').value.length < 1 )
	{
		alert('Write a title');
		xjx.$('add_asset_title').focus();
		return;
	}
	if ( xjx.$('add_asset_description').value.length < 1 )
	{
		alert('Write a description');
		xjx.$('add_asset_description').focus();
		return;
	}
	xajax_add_assets(lsn,
		xjx.$('add_asset_project').value,
		xjx.$('add_asset_group').value,
		xjx.$('add_asset_assign').value,
		xjx.$('add_asset_type').value,
		xjx.$('add_asset_severity').value,
		xjx.$('add_asset_platform').value,
		xjx.$('add_asset_version').value,
		xjx.$('add_asset_deadline').value,
		xjx.$('add_asset_title').value,
		xjx.$('add_asset_description').value,
		xjx.$('add_asset_notes').value);
}

function get_chkbox_recursive(obj, size, chked)
{
	if ( size < 1 )
		return false;
	if ( !obj || (typeof(obj) == 'undefined' ) )
		return false;
	var args = new Object();
	var cnt = 0;
	var p;
	for ( var i = 0; i < size; ++i )
	{
		p = obj[i];
		if ( typeof(p) == 'undefined' )
			continue;
		if ( (p.type == 'checkbox') && (p.checked == chked) && (p.id) )
			args[cnt++] = p.id;
		else if ( p.childNodes )
		{
			var len = p.childNodes.length;
			var r = get_chkbox_recursive(p.childNodes, len, chked);
			if ( r )
				for ( var j in r )
					args[cnt++] = r[j];
		}
	}
	return args;
}

function get_checkboxes(id, func_name)
{
	xjx.$('loading').style.display = 'block';
	var e = xjx.$(id);
	var o;
	var len;
	var args = new Object();
	var cnt = 0;
	args[cnt++] = lsn;
	if ( e )
		if ( o = e.childNodes )
		{
			len = o.length;
			var r = get_chkbox_recursive(o, len, true);
			for ( var i in r )
				args[cnt++] = r[i];
		}
	args.length = cnt;
	return xajax.request( { xjxfun: func_name }, { parameters: args } );
}

function check_all(id, value)
{
	if ( value )
		value = true;
	else
		value = false;
	var e = xjx.$(id);
	if ( e )
		if ( o = e.childNodes )
		{
			len = o.length;
			var r = get_chkbox_recursive(o, len, !value);
			//~ var rr = get_chkbox_recursive(o, len, value);
			for ( var i in r )
				xjx.$(r[i]).checked = value;
			//~ for ( var i in rr )
				//~ xjx.$(rr[i]).checked = !value;
		}
}

function nrpuzzle(size, time, l, c)
{
	var move_done = false;
	if ( (l >= size) || (l < 0) || (c >= size) || (c < 0) )
		return move_done;
	if ( xjx.$('l' + l + 'c' + c).value == 0 ) // we will init the table like this
	{
		global_nrpuzzle_count = 0;
		xjx.$('l' + l + 'c' + c).style.display = 'none';
	}
	else
	{
		var dir = new Array();
		dir[0] = new Array();
		dir[0][0] = 0;
		dir[0][1] = -1;
		dir[1] = new Array();
		dir[1][0] = -1;
		dir[1][1] = 0;
		dir[2] = new Array();
		dir[2][0] = 0;
		dir[2][1] = 1;
		dir[3] = new Array();
		dir[3][0] = 1;
		dir[3][1] = 0;
		var i = 0;
		var ln = 0;
		var cn = 0;
		for ( i = 0; i < 4; i++)
		{
			if ( arguments.length == 4 )
			{
				ln = l + dir[i][0];
				cn = c + dir[i][1];
			}
			else
			{
				ln = l + arguments[4];
				cn = c + arguments[5];
			}
			if ( (ln < size) && (ln >= 0) && (cn < size) && (cn >= 0) )
				if ( xjx.$('l' + ln + 'c' + cn).value == 0 )
				{
					xjx.$('l' + ln + 'c' + cn).value = xjx.$('l' + l + 'c' + c).value;
					xjx.$('l' + ln + 'c' + cn).style.display = 'block';
					xjx.$('l' + l + 'c' + c).value = 0;
					xjx.$('l' + l + 'c' + c).style.display = 'none';
					global_nrpuzzle_count += 1;
					move_done = true;
					break;
				}
			if ( arguments.length != 4 )
				break;
		}
		if ( !move_done )
		{
			if ( arguments.length == 4 )
			{
				//~ alert('no move found at line ' + l + ' col ' + c);
				for ( i = 0; i < 4; i++)
				{
					//~ alert('1. try line ' + (l + dir[i][0]) + ' col ' + (c + dir[i][1]));
					if ( nrpuzzle(size, time, l + dir[i][0], c + dir[i][1], dir[i][0], dir[i][1]) )
					{
						//~ alert('1. moved line ' + (l + dir[i][0]) + ' col ' + (c + dir[i][1]));
						return nrpuzzle(size, time, l, c);
					}
				}
			}
			else
			{
				//~ alert('2. try line ' + (l + arguments[4]) + ' col ' + (c + arguments[5]));
				if ( nrpuzzle(size, time, l + arguments[4], c + arguments[5], arguments[4], arguments[5]) )
				{
					//~ alert('2. moved line ' + (l + arguments[4]) + ' col ' + (c + arguments[5]));
					return nrpuzzle(size, time, l, c, arguments[4], arguments[5]);
				}
			}
		}
	}
	xjx.$('nrpuzzle_moves').innerHTML = global_nrpuzzle_count;
	if ( (l == (size - 1)) && (c == (size - 1)) && (xjx.$('l' + l + 'c' + c).value == 0) )
	{
		var data, j;
		data = '';
		for ( i = 0; i < size; i++ )
			for ( j = 0; j < size; j++ )
				data = data + xjx.$('l' + i + 'c' + j).value + ',';
		xajax_nrpuzzle(size, time, global_nrpuzzle_count, data);
	}
	return move_done;
}
