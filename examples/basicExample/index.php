<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Get paths to images and font
$here = realpath(dirname(__FILE__));

$image = "$here/resources/cat.jpeg";

// crop it and make it a circle
echo EasyImage::Create($image)
	->autocrop()
	->crop(317, 317)
	->borderRadius(150, "#000000");