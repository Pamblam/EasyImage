<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Call it like this
// /EasyImage/examples/simpleTextExample/?text=Your+Text+Here&size=12&color=%23000&width=208&height=500&rotate=0

// BG Image must be a full path or a URL
$here = realpath(dirname(__FILE__));
$font = "$here/resources/outrider.ttf";
$text = isset($_REQUEST['text']) ? $_REQUEST['text'] : "Hello, world";
$size = isset($_REQUEST['size']) ? $_REQUEST['size'] : 32;
$color = isset($_REQUEST['color']) ? $_REQUEST['color'] : "#000";
$width = isset($_REQUEST['width']) ? $_REQUEST['width'] : false;
$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : false;
$rotate = isset($_REQUEST['rotate']) ? $_REQUEST['rotate'] : 0;

$img = EasyImage::Create(
	$text, 
	$size, 
	$color, 
	realpath(dirname(__FILE__))."/resources/OldStreetSigns.ttf",
	null,
	$width
);

if(!empty($height)){
	$img->crop($width, $height);
}

if(!empty($rotate)) $img->rotate($rotate);

echo $img;
