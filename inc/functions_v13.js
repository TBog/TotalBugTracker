function init()
{
	var isset;
	try{
		if ( typeof(lsn) == 'undefined' )
			isset = false;
		else
			isset = true;
	} catch (e)
	{
		isset = false;
	}
	if ( isset )
	{
		if ( lsn == 'no lsn' )
		{
			alert('It seems you can not log in. (some weird javascript error, try some other browser)');
			xjx.$('loading').style.display = 'none';
			return;
		}
		xajax_load_style(lsn);
		if (arguments.length == 2)
		{
			xajax_print_bug(lsn, arguments[0], arguments[1]);
			xjx.$('menu').style.display = 'none';
			xjx.$('table_menu').style.display = 'none';
		}
		else
			xajax_print_menu(lsn);
		
		dhtmlHistoryInit();
	}
	else
	{
		lsn = 'no lsn';
		var arg = '';
		if (arguments.length == 2)
			arg = arguments[0] + ', "' + arguments[1] + '"';
		setTimeout('init(' + arg + ')', 2000);
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
		if ( o && o.type == 'button' )
			o.value = sOn;
	}
	else
	{
		e.style.display = 'none';
		if ( o && o.type == 'button' )
			o.value = sOff;
	}
}

function get_platforms(sId)
{	
	if ( xjx.$(sId) )
	{
		var i;
		var platforms = 0;
		var e = xjx.$(sId).options;
		for ( i = 0; i < e.length; i++ ) // iterate through all the options
			if ( e[i].selected )
				platforms |= e[i].value;
		return platforms;
	}
	return 0;
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
	var e, i;
	var must_choose_select = new Object();
	must_choose_select[0] = 'add_asset_project';
	must_choose_select[1] = 'add_asset_type';
	must_choose_select[2] = 'add_asset_severity';
	//must_choose_select[3] = 'add_asset_platform';
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
	var platforms = get_platforms('add_asset_platform');
	if ( platforms == 0 )
	{
		alert('Choose platform');
		return;
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
/*
	var must_be_date = new Object();
	must_be_date['add_asset_version'] = 'Build version must have 8 characters';
	must_be_date['DPC_add_asset_deadline'] = 'Deadline must have 8 characters';
	must_be_date['DPC_add_asset_opendate'] = 'Open date must have 8 characters';
	for (var key in must_be_date)
	{
		if ( xjx.$(key).value.length != 8 )
		{
			alert(must_be_date[key]);
			xjx.$(key).focus();
			return;
		}
	}
	if ( xjx.$('add_asset_version').value.length != 8 )
	{
		alert('Build version must have 8 characters');
		xjx.$('add_asset_version').focus();
		return;
	}
*/
	if ( xjx.$('DPC_add_asset_deadline').value.length != 8 )
	{
		alert('Deadline must have 8 characters');
		xjx.$('DPC_add_asset_deadline').focus();
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
		platforms,//xjx.$('add_asset_platform').value,
		xjx.$('DPC_add_asset_opendate').value,
		xjx.$('DPC_add_asset_deadline').value,
		xjx.$('add_asset_title').value,
		xjx.$('add_asset_description').value,
		xjx.$('add_asset_notes').value);
}
/**
 * not used any more, found non-recursive method
**/
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

function get_checkboxes(id, func_name, bBug)
{
	var args = new Object();
	var cnt = 0;
	args[cnt++] = lsn;
	args[cnt++] = bBug?1:0;
/*	var e = xjx.$(id);
	var o;
	var len;
	if ( e )
		if ( o = e.childNodes )
		{
			len = o.length;
			var r = get_chkbox_recursive(o, len, true);
			for ( var i in r )
				args[cnt++] = r[i];
		}
*/
    var rows = xjx.$(id);
	if ( rows )
		rows = rows.getElementsByTagName('tr');
	else
		return false;
    var checkbox;

    for ( var i = 0; i < rows.length; i++ )
	{
        checkbox = rows[i].getElementsByTagName( 'input' )[0];
        if ( checkbox && checkbox.type == 'checkbox' && checkbox.checked && checkbox.id )
			args[cnt++] = checkbox.id;
    }
	
	args.length = cnt;	
	return xajax.request( { xjxfun: func_name }, { parameters: args } );
}
/**
 * mark checkboxes with 'value', first from every 'tr'
**/
function check_all(id, value)
{
	if ( value )
		value = true;
	else
		value = false;
/*	var e = xjx.$(id);
	if ( e )
		if ( o = e.childNodes )
		{
			len = o.length;
			var r = get_chkbox_recursive(o, len, !value);
			var rr = get_chkbox_recursive(o, len, value);
			for ( var i in r )
				xjx.$(r[i]).checked = value;
			for ( var i in rr )
				xjx.$(rr[i]).checked = !value;
		}
*/
    var rows = xjx.$(id);
	if ( rows )
		rows = rows.getElementsByTagName('tr');
	else
		return false;
    var checkbox;

    for ( var i = 0; i < rows.length; i++ )
	{
        checkbox = rows[i].getElementsByTagName( 'input' )[0];
        if ( checkbox && checkbox.type == 'checkbox' )
            checkbox.checked = value;
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

function return_value(sId)
{
	if ( xjx.$(sId) )
		return xjx.$(sId).value;
	else
		return "";
}

function show_date(sDate, sMask)
{
	if ( sDate == '' )
		return 'MM/DD/YY';
	else if ( sDate.length != 8 )
		return 'date must have 8 characters';
	var date = new Date(sDate);
	if ( date.getFullYear() < 1970 )
		date.setFullYear(date.getFullYear() + 100);
	return dateFormat(date, sMask);
}

function select_option(sId, sOpt)
{
	var e = xjx.$(sId);
	for ( i = 0; i < e.options.length; i++ ) // iterate through all the options
		if ( e.options[i].value == sOpt )
		{
			e.selectedIndex = i;
			break;
		}
}
// open window with bug/asset details
function lnk(_this, bBug, id, app)
{
	var sPage = '?' + (bBug?'bug':'task') + '=' + id + '&app=' + app;
	window.open(sPage, '');
}
// makes onMouseOver effect for all <tr> within <tbody>
function make_omo_effect(sTableId)
{
	//~ if ( navigator.appName != 'Microsoft Internet Explorer' ) // but only for IE, other browsers are handled by :hover in css
		//~ return;

	var rows = xjx.$(sTableId).getElementsByTagName('tr');
	for ( var i = 0; i < rows.length; i++ )
	{
		if ( rows[i].parentNode.nodeName.toLowerCase() != 'tbody' )
			continue;

		if ( navigator.appName == 'Microsoft Internet Explorer' )
		{
			rows[i].onmouseover = function()
			{
				this.style.color = this.backgroundColor;
				this.style.backgroundColor = '#ffff00';
				var tmp = this.getElementsByTagName('input');
				if ( tmp.length > 0 )
					tmp[0].focus();
			}
			rows[i].onmouseout = function()
			{
				this.style.backgroundColor = this.style.color;
			}
		}
		else
			rows[i].onmouseover = function()
			{
				var tmp = this.getElementsByTagName('input');
				if ( tmp.length > 0 )
					tmp[0].focus();
			}
	}
}

function move_onclick_from_tr_to_td(sTableId)
{
	var rows = xjx.$(sTableId).getElementsByTagName('tr');
	for ( var i = 0; i < rows.length; i++ )
	{
		if ( rows[i].parentNode.nodeName.toLowerCase() != 'tbody' )
			continue;
		var cells = rows[i].getElementsByTagName('td');
		for ( var j = 0; j < cells.length; j++ )
		{
			cells[j].onclick = rows[i].onclick;
			cells[j].style.cursor = "pointer";
		}
		rows[i].onclick = function () {};
	}
}

function filter2(limitStart, customWhere, bBug)
{
	var rows = xjx.$('bug_table').getElementsByTagName('tr');
	var sel;
	var query = '1';
	for ( var i = 0; i < rows.length; i++ )
	{
		if ( rows[i].parentNode.nodeName.toLowerCase() != 'thead' )
			continue;
		sel = rows[i].getElementsByTagName('select');
		for ( var j = 0; j < sel.length; j++ )
		{
			if ( sel[j].id && sel[j].value )
			{
				if ( sel[j].id == 'platformID' )
					query = query + ' AND (' + sel[j].id + '&' + sel[j].value + ')';
				else
					query = query + ' AND ' + sel[j].id + '=' + sel[j].value;
			}
			/* debug
			else
				alert('id = "' + sel[j].id + '"; value = "' + sel[j].value + '"');
			*/
		}
	}
	//alert(query);
	xajax_print_bug_table(lsn, limitStart, customWhere, bBug, query)
}

function focus4scroll(obj)
{
/*
	var children = obj.getElementsByTagName('input');
	if ( (children.length > 0) && (navigator.appName.toLowerCase() == 'netscape') )
	{
		var scroll = obj.scrollTop;
		children[0].focus();
		obj.scrollTop = scroll;
	}
*/
}