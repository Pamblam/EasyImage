<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// BG Image must be a full path or a URL
$here = realpath(dirname(__FILE__));
$Stripes = "$here/resources/stripes.gif";
$font = "$here/resources/outrider.ttf";
$text1 = "Why did the chicken cross the road?\n...........\n";
$text2 = " To get to the other side!";

// Create the image
echo EasyImage::Create(
		$text1, 
		32, 
		$Stripes, 
		$font, 
		EasyImage::Create($Stripes)->colorize("#FF0000", 127), 
		600, 
		0, 
		15
	)->concat(
			EasyImage::Create(
				$text2, 
				32, 
				"#FF0000", 
				$font, 
				"#0000FF", 
				600, 
				0, 
				15
			), 
			EasyImage::VERT
		)->transparentToColor("#0000FF");