<html>
<head>
<title>Tic-Tac-Toe</title>
</head>
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
	border-top: 1px solid black;
	border-bottom: 2px solid black;
	padding-top: 30px;
	padding-bottom: 0px;
	margin: 0px;
	margin-bottom: 10px;
}
table {
	margin-left: auto;
	margin-right: auto;
	top: 30%;
}
table tr td {
	border: 1px solid black;
	width: 30px;
	height: 30px;
	text-align: center;
	vertical-align: middle;
}
table.win {
	border: 1px solid #666666;
}
p.win {
	text-align: center;
}
.info {
	position: absolute;
	right: 10px;
	bottom: 10px;
}
.expected {
	text-align: center;
}
</style>
<body>
<h1>Tic-Tac-Toe</h1>
Restart game with <a href="<?php echo getThisPageURI();?>?ai=1">computer first</a> or <a href="<?php echo getThisPageURI();?>">you first</a>.<br>
<?php
error_reporting(E_ALL | E_NOTICE | E_STRICT);
set_time_limit(10);

if ( isset($_GET['ai']) && $_GET['ai'] )
	define('COM', 1);
else
	define('COM', 2);
define('HUM', switchPlayer(COM));
define('DEPTH', 10);

$solutions = array();
$timer_start = microtime(true);

$a = array(
		array(0,0,0),
		array(0,0,0),
		array(0,0,0),
		);
if ( empty($_GET['a']) )
{
	$player = min(HUM, COM);
} else
{
	$player = $_GET['p'];
	$a = decodeTable(hexdec($_GET['a']));
	$count = array(0=>0, 1=>0, 2=>0);
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
			$count[$a[$i][$j]] += 1;
	$count[$player] += 1;
	if ( $count[2] > $count[1] )
		die('hacker attempt');
}

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
function switchPlayer($player)
{
	return (3 - $player);
}
function checkLine($a, $x, $y, $xx, $yy)
{
	$p = $a[$x][$y];
	if ( !empty($p) )
	do {
		$x += $xx;
		$y += $yy;
		if ( ($x>2) || ($y>2) )
			break;
		if ( $a[$x][$y] != $p )
			return 0;
	} while ( true );
	// if ( $p )
		// echo "...checkLine($x, $y, $xx, $yy) -> $p<br>";
	return $p;
}
function printWin($a, $player)
{
	echo '<table class="win">';
	for ( $i=0; $i<3; $i+=1 )
	{
		echo '<tr>';
		for ( $j=0; $j<3; $j+=1 )
		{
			echo '<td>';
			switch ( $a[$i][$j] )
			{
				case 1:
					echo 'X';
					break;
				case 2:
					echo 'O';
					break;
				default:
					echo '&nbsp;';
			}
			echo '</td>';
		}
		echo '</tr>';
	}
	echo '</table>';
	echo '<p class="win">';
	switch ( $player )
	{
		case HUM:
			echo 'You won ? ... THIS IS IMPOSSIBLE ... WTF ??';
			break;
		case COM:
			echo 'The computer WON !';
			break;
		default:
			echo 'DRAW ... as expected';
			break;
	}
	echo '</p>';
}
function checkWin($a)
{
	$win = 0;
	for ( $i=0; $i<3; $i+=1 )
	{
		if ( ($win = checkLine($a, $i, 0, 0, 1)) || ($win = checkLine($a, 0, $i, 1, 0)) )
			break;
	}
	if (!$win && (($win = checkLine($a, 0, 0, 1, 1)) || ($win = checkLine($a, 0, 2, 1, -1))) )
	{}
	return $win;
}
function encodeTable($a)
{
	$bin = '';
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
		{
			switch ( $a[$i][$j] )
			{
				case 1:
					$bin.= '01';
					break;
				case 2:
					$bin.= '10';
					break;
				case 0:
				default:
					$bin.= '11';
					break;
			}
		}
	return (int)bindec($bin);
}
function decodeTable($dec)
{
	$bin = decbin($dec);
	$a = array();
	if ( strlen($bin) % 2 )
		$bin = '0'.$bin;
	if ( strlen($bin) != 18 )
		die('bad hacker attempt');
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
		{
			$a[$i][$j] = substr($bin, 0, 2);
			$bin = substr($bin, 2);
			switch ( $a[$i][$j] )
			{
				case '01':
					$a[$i][$j] = 1;
					break;
				case '10':
					$a[$i][$j] = 2;
					break;
				case '11':
				default:
					$a[$i][$j] = 0;
					break;
			}
		}
	return $a;
}
function rotate90(&$a)
{
	$b = $a;
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
			$a[2 - $j][$i] = $b[$i][$j];
}
function mirror(&$a)
{
	$b = $a;
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
			$a[$i][2 - $j] = $b[$i][$j];
}
function minimax($player, $depth)
{
	global $solutions;
	global $a;
	$enc = encodeTable($a);
	if ( !isset($solutions[$enc]) )
	{
		$minimax = minimax_bf($player, $depth);
		for ( $i=0; $i<4; $i+=1 )
		{
			rotate90($a);
			$solutions[encodeTable($a)] = $minimax;
		}
		mirror($a);
		for ( $i=0; $i<4; $i+=1 )
		{
			rotate90($a);
			$solutions[encodeTable($a)] = $minimax;
		}
		mirror($a);
	}
	return $solutions[$enc];
}
function minimax_bf($player, $depth)
{
	global $a;
	// if ( $depth < 0 )
		// return 0;
	
	$win = checkWin($a);

	if ( $win == $player )
	{
		return 1 * $depth;
	}
	elseif ( $win == switchPlayer($player) )
	{
		return -1 * $depth;
	}
	$m = 0;
	$max = -1000;
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
			if ( empty($a[$i][$j]) )
			{
				$a[$i][$j] = $player;
				#echo '..:: deeper ::..';
				$m = -1 * minimax(switchPlayer($player), $depth - 1);
				#printTable($a);
				#echo "minimin = $m ... player = $player<br>";
				$a[$i][$j] = 0;
				if ( ($max < $m) )
				{
					$x = $i;
					$y = $j;
					$max = $m;
				}
			}
	if ( !isset($x) )
		return 0;
	return $max;
}
function printTable($a)
{
	echo '<table>';
	for ( $i=0; $i<3; $i+=1 )
	{
		echo '<tr>';
		for ( $j=0; $j<3; $j+=1 )
		{
			echo '<td>';
			switch ( $a[$i][$j] )
			{
				case 1:
					echo 'X';
					break;
				case 2:
					echo 'O';
					break;
				default:
					echo '<a href="'.getThisPageURI().'?ai='.(COM == 1?1:0).'&xy='.($i * 3 + $j).'&p='.HUM.'&a='.dechex(encodeTable($a)).'">_</a>';
			}
			echo '</td>';
		}
		echo '</tr>';
	}
	echo '</table>';
}

if ( $player == HUM )
{
	if ( isset($_GET['xy']) )
	{
		$a[$_GET['xy'] / 3][$_GET['xy'] % 3] = $player;
		$player = switchPlayer($player);
	}
}
if ( $player == COM )
{
	// calculate COM moves
	$max = -1000;
	$b = array();
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
			if ( empty($a[$i][$j]) )
			{
				$a[$i][$j] = COM;
				$m = -1 * minimax(HUM, DEPTH);
				#echo "try $i $j -> minimax = $m<br>";
				$b[] = array($m, $i, $j);
				$a[$i][$j] = 0;
				if ( $max < $m )
				{
					$x = $i;
					$y = $j;
					$max = $m;
				}
			}
	#echo "max = $max<br>";
	// shuffle moves and take the first best
	shuffle($b);
	foreach ($b as $sol)
		if ( $sol[0] == $max )
		{
			$x = $sol[1];
			$y = $sol[2];
			break;
		}
	// make move
	if ( isset($x) )
		$a[$x][$y] = COM;
	else
		$bDraw = true;
	if ( count($b) == 1 )
		$bDraw = true;
	// calculate HUM moves
	$max = -1000;
	for ( $i=0; $i<3; $i+=1 )
		for ( $j=0; $j<3; $j+=1 )
			if ( empty($a[$i][$j]) )
			{
				$a[$i][$j] = HUM;
				$m = -1 * minimax(COM, DEPTH);
				#echo "try $i $j -> minimax = $m<br>";
				$a[$i][$j] = 0;
				if ( $max < $m )
					$max = $m;
			}
	#echo "max = $max<br>";
}
if ( isset($bDraw) || checkWin($a) )
	printWin($a, checkWin($a));
else
{
	printTable($a);
	if ( isset($max) )
	{
		echo '<p class="expected">';
		if ( $max == 0 )
			echo 'expecting draw';
		elseif ( $max < 0 )
			echo 'Computer will win';
		else
			echo 'Something went WRONG<br>There is the posibility of you winning';
		echo '</p>';
	}
}
echo '<div class="info">';
echo 'move made in '.number_format(microtime(true) - $timer_start, 4, '.', '').' sec<br>';
echo 'solutions considered: '.count($solutions).'<br>';
echo 'memory usage '.number_format(memory_get_usage() / 1024, 2, '.', '').' KB<br>';
echo '</div>';
?>
</body>
</html>