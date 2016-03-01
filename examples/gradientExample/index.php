<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Get paths to image
$here = realpath(dirname(__FILE__));

$image = "$here/resources/apple.jpg";

$gradientColors = EasyImage::gradientColors(array("#E05A65", "#7AA846"), 50);
echo count($gradientColors)." colors..<br>";
foreach($gradientColors as $clr) echo "<div style='background:$clr; text-align:center;'>$clr</div>";