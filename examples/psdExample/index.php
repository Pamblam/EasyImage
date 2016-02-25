<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

$here = realpath(dirname(__FILE__));

// Load a PSD file
$img1 = EasyImage::Create("$here/resources/angelwings.psd");
$img2 = $img1->getCopy();

$new_width = $img1->getWidth() / 2;
$new_height = $img1->getHeight();

$img2->crop($new_width, $new_height, $new_width)->reverseColors();
$img1->crop($new_width, $new_height)->concat($img2);

echo $img1;