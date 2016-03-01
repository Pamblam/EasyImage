<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Where are we
$here = realpath(dirname(__FILE__));

// Get colors red to green to blue
$colors = array();
$grad = EasyImage::gradientColors(array("#FF0000", "#00FF00"), 20);
foreach($grad as $c) $colors[] = $c;
array_pop($colors);
$grad = EasyImage::gradientColors(array("#00FF00", "#FF0000"), 20);
foreach($grad as $c) $colors[] = $c;
array_pop($colors);

// create frames
$frames = array();
$degrees = 0;

$height = 431;
$width = 500;

foreach($colors as $clr){
	
	// how many degrees to rotate each frame
	$degrees = $degrees+(360/count($colors));
	
	// creata a frame
	$img = EasyImage::Create("$here/resources/chzbrgr.gif")
		->colorize($clr) // colorize
		->rotate($degrees); // rotate
	
	// calculate center
	$centerx = abs($img->getWidth() - $width) / 2;
	$centery = abs($img->getHeight() - $height) / 2;
	
	$img->crop($width, $height, $centerx, $centery); // crop so all frames are same size
	
	array_push($frames, $img);
}

// animate
echo EasyImage::Create($frames);
