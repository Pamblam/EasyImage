<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Get paths to image
$here = realpath(dirname(__FILE__));

$image = EasyImage::Create("$here/resources/face.png")->removeColor("#FFFFFF");
echo EasyImage::Create($image->getWidth(), $image->getHeight(), array("#f0f0f0", "#7AA846"))
	->addOverlay($image, 0, 0);