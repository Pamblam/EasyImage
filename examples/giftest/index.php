<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");
$here = realpath(dirname(__FILE__));

//require "$here/GIFDecoder.class.php";
$animation = "$here/resources/test.gif";

// Require the class
require("../../EasyImage.php");
echo EasyImage::Create($animation)->greyScale();