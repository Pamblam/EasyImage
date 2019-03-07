<?php
	
$here = realpath(dirname(__FILE__));
$font = "$here/resources/OldStreetSigns.ttf";
$text = empty($_REQUEST['text']) ? "Sriracha beard tattooed letterpress edison bulb four dollar toast deep v poke offal tacos tilde heirloom. Hell of bespoke williamsburg echo park venmo sriracha hexagon tattooed quinoa gluten-free. Polaroid pickled cloud bread, YOLO wayfarers shoreditch hoodie poke palo santo hexagon tbh paleo taiyaki." : $_REQUEST['text'];
$size = 20;
$color = "#000";
$wrap_width = 500;
$align = empty($_REQUEST['align']) ? 0 : intval($_REQUEST['align']);
	
if(isset($_REQUEST['img'])){
	require("../../EasyImage.php");
	
	echo EasyImage::Create(
		$text, 
		$size, 
		$color, 
		$font,
		null,
		$wrap_width,
		0,
		5,
		$align
	);
	
	exit;
}
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Text Align Example</title>
    </head>
    <body>
		<img id=img src="<?php echo $_SERVER['PHP_SELF']."/?img"; ?>" /><br>
		<textarea id="text"><?php echo $text; ?></textarea>
		<select id="align">
			<option value="0">Left Align</option>
			<option value="1">Right Align</option>
			<option value="2">Center Align</option>
			<option value="3">Justify Align</option>
		</select>
		<button id="submt">update</button>
		<script>
			document.getElementById('submt').onclick = function(){
				var txt = document.getElementById('text').value.trim();
				if(!txt) return alert("type some text in the box first");
				var sel = document.getElementById("align");
				var algn = sel.options[sel.selectedIndex].value;
				document.getElementById("img").src = "<?php echo $_SERVER['PHP_SELF']."/?img"; ?>&align="+encodeURIComponent(algn)+"&text="+encodeURIComponent(txt);
			};
		</script>
    </body>
</html>
