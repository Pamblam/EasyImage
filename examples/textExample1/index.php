<?php

// Require the class
require("../../EasyImage.php");

// BG Image must be a full path or a URL
$here = realpath(dirname(__FILE__));
$BGImage = "$here/resources/stripes.gif";
$font = "$here/resources/outrider.ttf";
$text = "Why did the chicken cross the road?\n...........\n To get to the other side!";

// Create the image
$bg = EasyImage::Create($BGImage)->colorize("#ccc", 130);
echo EasyImage::Create($text, 32, "#0000FF", $font, $bg, 600, 0, 15);