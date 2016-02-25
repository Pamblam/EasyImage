<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

function drawStar($x, $y, $radius, $spikes=5) {
    // $x, $y -> Position in the image
    // $radius -> Radius of the star
    // $spikes -> Number of spikes

    $coordinates = array();
    $angel = 360 / $spikes ;

    // Get the coordinates of the outer shape of the star
    $outer_shape = array();
    for($i=0; $i<$spikes; $i++){
        $outer_shape[$i]['x'] = $x + ($radius * cos(deg2rad(270 - $angel*$i)));
        $outer_shape[$i]['y'] = $y + ($radius * sin(deg2rad(270 - $angel*$i)));
    }

    // Get the coordinates of the inner shape of the star
    $inner_shape = array();
    for($i=0; $i<$spikes; $i++){
        $inner_shape[$i]['x'] = $x + (0.5*$radius * cos(deg2rad(270-180 - $angel*$i)));
        $inner_shape[$i]['y'] = $y + (0.5*$radius * sin(deg2rad(270-180 - $angel*$i)));
    }

    // Bring the coordinates in the right order
    foreach($inner_shape as $key => $value){
        if($key == (floor($spikes/2)+1))
             break;
        $inner_shape[] = $value;
        unset($inner_shape[$key]);
    }

    // Reset the keys
    $i=0;
    foreach($inner_shape as $value){
        $inner_shape[$i] = $value;
        $i++;
    }

    // "Merge" outer and inner shape
    foreach($outer_shape as $key => $value){
         $coordinates[] = $outer_shape[$key]['x'];
         $coordinates[] = $outer_shape[$key]['y'];
         $coordinates[] = $inner_shape[$key]['x'];
         $coordinates[] = $inner_shape[$key]['y'];
    }

    // Return the coordinates
    return $coordinates ;
}

// Example
$spikes = 100;

$values = drawStar(250, 250, 150, $spikes);
$im = imagecreate(500,500);
imagecolorallocate($im,0,0,0);
$w = imagecolorallocate($im, 255, 255, 255);
imagefilledpolygon($im, $values, $spikes*2, $w);

ob_start();
imageGIF($im);
$star = base64_encode(ob_get_clean());
imagedestroy($im);

$orig = EasyImage::Create($star)->colorFill(3, 3, "#000")->convertTo(EasyImage::GIF);
	
$frames = array();

// circle
$frame_cnt = 40;
$radius = 30;

$degree_interval = 360 / $frame_cnt;
for($degrees=0; $degrees<=360; $degrees+=$degree_interval){
	$x = $radius * cos(deg2rad($degrees));
	$y = $radius * sin(deg2rad($degrees));	
	
	$horiz = $orig->getCopy()->colorFill(3, 3, "#000");
	array_push($frames, $horiz->mergeImages($orig, $x, $y));	
}

echo EasyImage::Create($frames);