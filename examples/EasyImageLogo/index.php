<?php

# show errors
error_reporting(E_ALL);
ini_set("display_errors", "1");

# get the class handy
require("../../EasyImage.php");

# relative path
$here = realpath(dirname(__FILE__));

$font = "$here/resources/outrider.ttf";

# text to display
$text = "EASYIMAGE";

# get some colors to use
$colors = EasyImage::gradientColors(array("#0AC2FF", "#47FF0A"), strlen($text));

# generate some html
$htmlColored = "";
$htmlBlack = "";
for($i=0; $i<strlen($text); $i++){
	$color = $colors[$i];
	$letter = substr($text, $i, 1);
	$htmlColored .= "<span style='color: $color; font-size: 100; padding:10;'>$letter</span>";
	$htmlBlack .= "<span style='color: #000000; font-size: 100; padding:10;'>$letter</span>";
}

# make a base image and a colored one
$image = EasyImage::Create($htmlBlack, array("font"=>$font))->resize(500, 100);
$colored = EasyImage::Create($htmlColored, array("font"=>$font))->resize(500, 100);

# drop the black one behind the colored one to make a shadow
$image->addOverlay($colored, 4, 7);

$text = "<span style='color: #0AC2FF; font-size: 100; padding:10;'>v2.7</span>";
$textbg = "<span style='color: #000; font-size: 100; padding:10;'>v2.7</span>";
$beta = EasyImage::Create($textbg, array("font"=>$font))->resize(150, 25);
$foreground = EasyImage::Create($text, array("font"=>$font))->resize(150, 25);
$beta->addOverlay($foreground, 2, 3);
$x = $image->getWidth() - $beta->getWidth() - 15;
$y = $image->getHeight() - $beta->getHeight() - 6;
$image->addOverlay($beta, $x, $y);

# make a reflection image
$reflection = 
	$image->getCopy()
		->flip(EasyImage::VERT)
		->resize(500, 45)
		->makeOpaque(100);

# glue the reflection to the bottom
$image->concat($reflection, EasyImage::VERT);

//echo "<pre>"; echo $image->getBase64(); exit;
echo $image;