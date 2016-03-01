<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// BG Image must be a full path or a URL
$here = realpath(dirname(__FILE__));
$BGImage = "$here/resources/stripes.gif";
$font = "$here/resources/outrider.ttf";
$text = "Why did the chicken cross the road?\n...........\n To get to the other side!";

// Create the image
$bg = EasyImage::Create($BGImage)->colorize("#ccc", 127);
$fg = EasyImage::Create($BGImage)->rotate(90);
echo EasyImage::Create($text, 32, $fg, $font, $bg, 600, 0, 15);