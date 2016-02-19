<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

/**
 * Supports only <span> and <div> elements with style parameters, as well as <br>
 * Only the following styles are supported
 * background-color - Must be hex, ie #FFF, #FF000 - transparent by default
 * background-image - a local path or a URL
 * font-face - the name of the font, as passed into the $fonts array
 * font-size - the font size in pixels
 * color - The color of the text, must be in hex 
 * disply - "block" or "inline"
 * width - The width of the text 
 * opacity - 0-127
 * padding - element padding
 * nesting tags not supported
 */

// BG Image must be a full path or a URL
$here = realpath(dirname(__FILE__));
$BGImage = "$here/resources/background.jpg";

// Make some HTML
$html = "This text is not styled.";
$html .= "<span style='background-image:$BGImage; color:#0000FF; font-face:Outrider;'>This text is Styled</span>";
$html .= "<div style='background-color: #FF0000; color: #000000; font-size: 18; width:500; opacity:70;'>Divs are block level elements</div>";
$html .= "<span style='font-size:12; padding:10;'>And here's some tiny text</span>";
$html .= "<div style='font-size:12; padding:10;'>This is a really lonfonasdf sodf</div>";

// Array of fonts with the name as the key and the filepath as the value
// Any text that doesn't have a specified font will use the first font in the array
// Must use TTF fonts only
$fonts = array(
	"AllerDisplay" => "$here/resources/AllerDisplay.ttf",
	"Outrider" => "$here/resources/outrider.ttf"
);

// Create the image
echo EasyImage::Create($html, $fonts)
	->removeTransparency(); // give it a white background