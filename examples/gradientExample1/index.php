<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

$gradientColors = EasyImage::gradientColors(array("#f0f0f0", "#7AA846"), 50);

echo count($gradientColors)." colors...<br>";
foreach($gradientColors as $clr) echo "<div style='background:$clr; text-align:center;'>$clr</div>";