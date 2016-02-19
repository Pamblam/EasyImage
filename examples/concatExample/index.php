<?php

// Require the class
require("../../EasyImage.php");

// BG Image must be a full path or a URL
$here = realpath(dirname(__FILE__));
$kitty1 = "$here/resources/kitty1.gif";
$kitty2 = "$here/resources/kitty2.png";

// colors
$red = "#FF4203";
$green = "#18FF03";
$blue = "#0352FF";
$pink = "#F203FF";

// Create 4 colored images
$redKitty = EasyImage::Create($kitty2)->colorize($red);
$blueKitty = EasyImage::Create($kitty2)->colorize($blue);
$greenKitty = EasyImage::Create($kitty2)->colorize($green);
$pinkKitty = EasyImage::Create($kitty2)->colorize($pink);

// concat the red and blue kittys horizontally for the top row
$top = $redKitty->concat($blueKitty);

// concat the green and pink kittys horizontally for the bottom row
$bottom = $greenKitty->concat($pinkKitty);

// concat the top an bottom images vertically for the full image
$full = $top->concat($bottom, EasyImage::VERT);

// let's put the original on top
$pos = $full->getWidth() / 4;
echo $full->addOverlay($kitty2, $pos, $pos);