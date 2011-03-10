<?php
function pnpoly($poly, $lat, $lng) {
  $c = false;
  $npol = count($poly);
  for ($i = 0, $j = $npol-1; $i < $npol; $j = $i++) {
    if (((($poly[$i][0]<=$lat) && ($lat<$poly[$j][0])) || (($poly[$j][0]<=$lat) && ($lat<$poly[$i][0]))) &&
        ($lng < ($poly[$j][1] - $poly[$i][1]) * ($lat - $poly[$i][0]) / ($poly[$j][0] - $poly[$i][0]) + $poly[$i][1]))
       $c = !$c;
  }
  return $c;
}

function FindCentroid($pts = array()){
	$c = count($pts);
	if((int)$c == '0') return NULL;
	$cn = array('x' => '0', 'y' => '0');
	$a = FindArea($pts);
	foreach($pts as $k => $p){
		$j = ($k + 1) % $c;
		$P = ($p[0] * $pts[$j][1]) - ($p[1] * $pts[$j][0]);
		$cn['x'] = $cn['x'] + ($p[0] + $pts[$j][0]) * $P;
		$cn['y'] = $cn['y'] + ($p[1] + $pts[$j][1]) * $P;
	}	
	$cn['x'] = $cn['x'] / ( 6 * $a);
	$cn['y'] = $cn['y'] / ( 6 * $a);
	return $cn;
}

function FindArea($pts = array()){
	$c = count($pts);
	if((int)$c == '0') return NULL;
	$a = '0';
	foreach($pts as $k => $p){ $j = ($k + 1) % $c;
		$a = $a + ($p[0] * $pts[$j][1]) - ($p[1] * $pts[$j][0]); }
	return abs(($a / 2));
}

// Thanks to City Squares
