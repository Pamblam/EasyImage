<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

// Require the class
require("../../EasyImage.php");

// Get paths to image
$here = realpath(dirname(__FILE__));

$image = "$here/resources/apple.jpg";

// get EasyImage object
$img = EasyImage::Create($image);

$colors = $img->getColors(true);
?>
<html>
	<head><title>Info Example</title></head>
	<body>
		<table>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Image Preview</b></td>
				<td><img src="<?php echo $img->getBase64(); ?>"></td>
			</tr>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Original Filepath</b></td>
				<td><?php echo $img->getFilepath(); ?></td>
			</tr>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Width</b></td>
				<td><?php echo $img->getWidth(); ?></td>
			</tr>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Height</b></td>
				<td><?php echo $img->getHeight(); ?></td>
			</tr>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Mime Type</b></td>
				<td><?php echo $img->getMimeType(); ?></td>
			</tr>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Orientation</b></td>
				<td><?php echo $img->getOrientation(); ?></td>
			</tr>
			<tr>
				<td align="TOP" style="vertical-align:top"><b>Colors Used</b></td>
				<td><?php foreach($colors as $color) echo "<span style='background:$color; margin:.25em; padding:.25em;'>$color</span> "; ?></td>
			</tr>
		</table>
	</body>
</html>