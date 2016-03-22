 <?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Get paths to images and font
$here = realpath(dirname(__FILE__));

$image = "$here/resources/cat.jpeg";

$pencil = "$here/resources/pencil.png";
$font_file = "$here/resources/OldStreetSigns.ttf";	

// Create and output image of the cat
echo EasyImage::Create($image)
	->replaceColor("#94A070", "#BCF511", 30) // Replace some colors
	//->replaceColor("#15110E", "#C75602", 30)
	//->replaceColor("#D5C9B1", "#F2B63D", 30)
	//->replaceColor("#6B5C57", "#9E837A", 30)
	//->replaceColor("#322E2D", "#B36856", 30)
	//->replaceColor("#C9B18D", "#F5C071", 30)
	->autoCrop()	// Remove the extra whitespace
	->flip()		// Flip the image
	->rotate(22.5)	// Rotate it a little to look like paper
	->transparentToColor("#fff") // Remove trnsparency from the rotated image
	->addWatermark("Made by Rob", 12, "#000", $font_file, EasyImage::RIGHT, EasyImage::TOP) // Add a watermark
	->addOverlay(	// Add the image of the pencil
		EasyImage::Create($pencil)->scale(175)->flip("b"), 
		-25, 265
	)
	->convertTo(EasyImage::PDF);

