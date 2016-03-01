<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

$here = realpath(dirname(__FILE__));

$img = "$here/resources/grid.jpg";
$i = EasyImage::Create($img)->perspective(0.22);
$i->removeColor("#000000");
echo $i;