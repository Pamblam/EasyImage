<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

$here = realpath(dirname(__FILE__));
$Stripes = "$here/resources/stripes.gif";
$VerticalStripes = EasyImage::Create($Stripes)->colorize("#0000FF");
$HoriznotalStripes1 = EasyImage::Create($Stripes)->rotate(90)->colorize("#FF0000");
$HoriznotalStripes2 = EasyImage::Create($Stripes)->rotate(90)->colorize("#00FF00");

$font = "$here/resources/outrider.ttf";
$text1 = "Why did the chicken cross the road?\n--------\n";
$text2 = "...To get to the other side!";

// Create the image
$top = EasyImage::Create($text1, 32, $VerticalStripes, $font, $HoriznotalStripes1, 400, 0, 15);
$bottom = EasyImage::Create($text2, 38, $VerticalStripes, $font, $HoriznotalStripes2, 400, 0, 15);
echo $bottom->concat($top, EasyImage::VERT);