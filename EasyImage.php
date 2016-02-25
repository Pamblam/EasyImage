<?php
/**
 * 
 * EasyImage: An easy-to use image manipulation class.
 * 
 * @package EasyImage
 * @author Robert Parham
 * @license wtfpl.net WTFPL
 * @version 2.5
 */

class EasyImage{
	
	/***************************************************************************
	 ******************************* CONSTANTS *********************************
	 **************************************************************************/
	const PDF = "application/pdf";		// File Format Constant
	const PNG = "image/png";			// File Format Constant
	const GIF = "image/gif";			// File Format Constant
	const JPG = "image/jpg";			// File Format Constant
	const VERT = "V";					// Flip type constant
	const HORIZ = "H";					// Flip type constant
	const BOTH = "B";					// Flip type constant
	const LEFT = "left";				// Position constant
	const RIGHT = "right";				// Position constant
	const TOP = "top";					// Position constant
	const BOTTOM = "bottom";			// Position constant
	const CENTER = "center";			// Position constant
	const FILL = "fill";				// Fill/cover constant
	const COVER = "cover";				// Fill/cover constant
	
	/***************************************************************************
	 **************************** IMAGE PROPERTIES *****************************
	 **************************************************************************/
	private static $DPI = 72;			// Dots per inch
	private $filepath;					// Path to the image
	private $width;						// Image width
	private $height;					// Image height
	private $mime;						// The mimetype of the image, ie self::PNG
	private $landscape;					// Is it landscape (vs. portrait)
	private $imageFunct;				// The naem of the function needed to render the image
	private $compression;				// The compression/quality parameter used to create the image
	private $image;						// Image resource identifier
	private $isTemp;					// Is it a temporary image
	private $cropOffsets;				// Array of offsets for an image cropped by autocrop funtion
	private $outputAs;					// Format to output the file as
	private $fileExt;					// File Extention
	
	/***************************************************************************
	 ***************************** PDF PROPERTIES ******************************
	 **************************************************************************/
	private $pdf_page = 0;				// current page number
	private $pdf_n = 2;					// current object number
	private $pdf_buffer = '';			// buffer holding in-memory PDF
	private $pdf_state = 0;				// current document state
	private $pdf_DrawColor = '0 G';		// commands for drawing color
	private $pdf_FillColor = '0 g';		// commands for filling color
	private $pdf_ColorFlag = false;		// indicates whether fill and text colors are different
	private $pdf_images = array();		// array of used images
	private $pdf_WithAlpha = false;		// has an alpha channel
	
	/***************************************************************************
	 ************************** ANIMATED GIF PROPERTIES ************************
	 **************************************************************************/
	private $gif_sources;				// Array of EasyImage instances for each frame
	private $gif_delayTimes;			// Array of delay times for each frame
	private $gif_loops=0;				// How many times to loop animation
	private $gif_disposal=2;			// Gif disposal
	private $gif_transRed=0;			// Transparent red
	private $gif_transGreen=0;			// Transparent green
	private $gif_transBlue=0;			// Transparent blue
	
	/***************************************************************************
	 ****************************** PSD PROPERTIES *****************************
	 **************************************************************************/
	private static $psd_infoArray;		// PSD file data
    private static $psd_fp;				// PSD File pointer
    private static $psd_fn;				// PSD filename
    private static $psd_tempname;		// PSD temp filename
    private static $psd_cbLength;		// PSD color bytes length
	
	/***************************************************************************
	 *************************** PUBLIC CONSTRUCTOR ****************************
	 **************************************************************************/
	
	/**
	 * Convenience constructor - Determines the correct constructor based on arguments passed
	 * @return \EasyImage 
	 */
	public static function Create(){
		$args = func_get_args();
		$num = func_num_args();
		$return = null;
		switch($num){
			case(1):
				if(is_array($args[0]))
					$return = self::createAnimatedGIF($args[0]);
				else if(filter_var($args[0], FILTER_VALIDATE_URL))
					$return = self::createFromURL($args[0]);
				else if(file_exists($args[0])){
					if(strpos(strtoupper(mime_content_type($args[0])), "PHOTOSHOP") !== false) $return = self::createFromPSD($args[0]);
					else $return = self::createFromFile($args[0]);
				}else $return = self::createFromBase64($args[0]);
				break;
			case(2):
				if(is_array($args[0])) $return = self::createAnimatedGIF($args[0], $args[1]);
				else if(file_exists($args[0])) $return = createFromFile($args[0], $args[1]);
				else $return = self::createHTMLImage($args[0], $args[1]);
				break;
			case(3):
				$return = self::createAnimatedGIF($args[0], $args[1], $args[2]);
			case(4):
				if(is_array($args[0])) $return = self::createAnimatedGIF($args[0], $args[1], $args[2], $args[3]);
				else $return = self::createTextImage($args[0], $args[1], $args[2], $args[3]);
				break;
			case(5):
				if(is_array($args[0])) $return = self::createAnimatedGIF($args[0], $args[1], $args[2], $args[3], $args[4]);
				else $return = self::createTextImage($args[0], $args[1], $args[2], $args[3], $args[4]);
				break;
			case(6):
				if(is_array($args[0])) $return = self::createAnimatedGIF($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
				else $return = self::createTextImage($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
				break;
			case(7):
				if(is_array($args[0])) $return = self::createAnimatedGIF($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
				else $return = self::createTextImage($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
				break;
			case(8):
				$return = self::createTextImage($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
				break;
			default:
				self::Error("Incorrect parameter number for EasyImage::Create.");
		}
		return $return;
	}
	
	/***************************************************************************
	 ***************************** EDITING METHODS *****************************
	 **************************************************************************/
	
	/**
	 * Add a border radius
	 * @param int $radius - radius
	 * @param string $colour - hex color
	 * @return type
	 */
	public function borderRadius($radius=10, $colour="#FFFFFF"){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->borderRadius($radius=10, $colour);
			return $this;
		}
		
		$source_width = $this->width;
		$source_height = $this->height;		
		$corner_image = imagecreatetruecolor($radius, $radius);
		$clear_colour = imagecolorallocatealpha($corner_image, 255, 255, 255, 127);
		$solid_colour = self::getColorFromHex($corner_image, $colour);
		imagealphablending($this->image, false);
		imagealphablending($corner_image, false);
		imagecolortransparent($corner_image, $clear_colour);
		imagefill($corner_image, 0, 0, $solid_colour);
		imagefilledellipse($corner_image, $radius, $radius, $radius * 2, $radius * 2, $clear_colour);
		imagecopymerge($this->image, $corner_image, 0, 0, 0, 0, $radius, $radius, 100);
		$corner_image = imagerotate($corner_image, 90, 0);
		imagecopymerge($this->image, $corner_image, 0, $source_height - $radius, 0, 0, $radius, $radius, 100);
		$corner_image = imagerotate($corner_image, 90, 0);
		imagecopymerge($this->image, $corner_image, $source_width - $radius, $source_height - $radius, 0, 0, $radius, $radius, 100);
		$corner_image = imagerotate($corner_image, 90, 0);
		imagecopymerge($this->image, $corner_image, $source_width - $radius, 0, 0, 0, $radius, $radius, 100);
		
		return $this;
	}
	
	/**
	 * Merge two images
	 * @param type $src
	 * @param type $dst_x
	 * @param type $dst_y
	 * @param type $src_x
	 * @param type $src_y
	 * @param type $src_w
	 * @param type $src_h
	 * @param type $pct
	 * @return \EasyImage
	 */
	public function mergeImages($src, $dst_x=0, $dst_y=0, $src_x=0, $src_y=0, $src_w=null, $src_h=null, $pct=50){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->mergeImages($src, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
			return $this;
		}
		
		if(!is_object($src)) $src = EasyImage::Create($src);
		if(empty($src_w)) $src_w = $src->getWidth();
		if(empty($src_h)) $src_h = $src->getWidth();
		
		imagecopymerge($this->image, $src->getImageResource(), $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
		
		return $this;
	}
	
	/**
	 * Add a watermark to the image
	 * @param string $text - Text for the watermark
	 * @param int $font_size - Font size
	 * @param string $color - hex color
	 * @param string $font_file - path to .ttf font file
	 * @param string $horizontal_pos - "left", "right" or "center"
	 * @param string $vertical_pos - "top", "bottom" or "center"
	 * @param int $opacity - opacity level, 0-127
	 * @param int $padding - pixels to pad
	 * @return \EasyImage
	 */
	public function addWatermark($text, $font_size, $color, $font_file, $horizontal_pos="right", $vertical_pos="bottom", $opacity=65, $padding=3){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->addWatermark($text, $font_size, $color, $font_file, $horizontal_pos, $vertical_pos, $opacity, $padding);
			return $this;
		}
		
		$watermark = self::createTextImage($text, $font_size, $color, $font_file, null, false, $opacity);
		switch($horizontal_pos){
			case(self::RIGHT): $x = $this->width - $padding - $watermark->getWidth(); break;
			case(self::CENTER): $x = ($this->width / 2) - ($watermark->getWidth() / 2); break;
			case(self::LEFT): default: $x = $padding;
		}
		switch($vertical_pos){
			case(self::BOTTOM): $y = $this->height - $padding - $watermark->getHeight(); break;
			case(self::CENTER): $y = ($this->height / 2) - ($watermark->getHeight() / 2); break;
			case(self::TOP): default: $y = $padding;
		}
		$this->addOverlay($watermark, $x, $y, $watermark->getWidth(), $watermark->getHeight());
		return $this;
	}
	
	/**
	 * Adjust transparencey of entire image
	 * @param int $alpha - a number 0 - 127
	 * @return \EasyImage
	 */
	public function makeOpaque($alpha){	
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->makeOpaque($alpha);
			return $this;
		}
		
		$image_width = imagesx($this->image);
		$image_height = imagesy($this->image);
		for($x = $image_width; $x--;){
			for($y = $image_height; $y--;){
				$color = self::getPixelRGBA($x, $y);
				if($color['alpha'] < $alpha){
					$newColor = imagecolorallocatealpha($this->image, $color['red'], $color['green'], $color['blue'], $alpha);
					imagesetpixel($this->image, $x, $y, $newColor);
				}
			}
		}
		return $this;
	}
	
	/**
	 * Add an Overlay
	 * @param mixed $EasyImage - the image; a URL, Filepath or EasyImage object
	 * @param int $dst_x - the destination x point
	 * @param int $dst_y - the destination y point
	 * @param int $src_w - source width
	 * @param int $src_h - source height
	 * @return \EasyImage
	 */
	public function addOverlay($EasyImage, $dst_x, $dst_y, $src_w=null, $src_h=null){ 
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->addOverlay($EasyImage, $dst_x, $dst_y, $src_w=null, $src_h=null);
			return $this;
		}
		
		if(!is_object($EasyImage)) $EasyImage = self::Create($EasyImage);
		if(empty($src_w)) $src_w = $EasyImage->getWidth();
		if(empty($src_h)) $src_h = $EasyImage->getHeight();
		
		
		imagealphablending( $this->image, true );
		imagesavealpha( $this->image, true );
		
		$src = $EasyImage->getImageResource();
		imagecopy($this->image, $src, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
		return $this;
	}
	
	/**
	 * Convert image to black and white
	 * @return \EasyImage
	 */
	public function blackAndWhite(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->blackAndWhite();
			return $this;
		}
		
		$black = self::getColorFromHex($this->image, "#000000");
		$white = self::getColorFromHex($this->image, "#FFFFFF");
		for ($x = $this->width; $x--;) {
			for ($y = $this->height; $y--;) {
				$a = $this->getPixelRGBA($x, $y);
				//$a = self::hexToRGB("#888888");
				$luma = (0.2126 * $a['red'] + 0.7152 * $a['green'] + 0.0722 * $a['blue']) / 255;
				$newColor = $luma > 0.53333333333333 ? $white : $black;
				imagesetpixel($this->image, $x, $y, $newColor);
			}
		}
		return $this;
	}
	
	/**
	 * Crops the image to the given dimensions, at the given starting point
	 * Defaults to top left
	 * @param int $new_width - new width of image
	 * @param int $new_height - new height of the image
	 * @param int $x - x position to start cropping from
	 * @param int $y - y position to start cropping from
	 * @return \EasyImage
	 */
	public function crop($new_width, $new_height, $x = 0, $y = 0){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->crop($new_width, $new_height, $x, $y);
			return $this;
		}
		
		$img = imagecrop($this->image, array(
			"x" => $x, 
			"y" => $y,
			"width" => $new_width,
			"height" => $new_height
		));
		
		$this->height = $new_height;
		$this->width = $new_width;
		
		if(false !== $img) $this->image = $img;
		else self::Error("Could not crop image.");
		
		return $this;
	}
	
	/**
	 * Automatically crops an image based on the surrounding color
	 * @param int $threshold - Sensitivity level
	 * @param int $padding - how many pixels of surrounding background to keep
	 * @return \EasyImage
	 */
	public function autoCrop($threshold = 50, $padding = 3) {
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->autoCrop($threshold, $padding);
			return $this;
		}
		
		$bgColor = false;
		
		$image_width = imagesx($this->image);
		$image_height = imagesy($this->image);
		
		$leftPixel = $image_width;
		$topPixel = 0;
		$bottomPixel = $image_height;
		$rightPixel = 0;
		
		for ($x = $image_width; $x--;) {
			for ($y = $image_height; $y--;) {
				$currentPixelColor = $this->getPixelRGBA($x, $y);
				if(false === $bgColor) $bgColor = $currentPixelColor;
				$dist = self::getColorDistance($currentPixelColor, $bgColor);
				if($threshold < $dist){
					if($y > $topPixel) $topPixel = $y;
					if($y < $bottomPixel) $bottomPixel = $y;
					if($x > $rightPixel) $rightPixel = $x;
					if($x < $leftPixel) $leftPixel = $x;
				}
			}
		}
		
		// handle padding
		if($rightPixel+$padding < $image_width) $rightPixel += $padding; else $rightPixel=$image_width;
		if($topPixel+$padding < $image_height) $topPixel += $padding; else $topPixel = $image_height;
		if($leftPixel-$padding > 0) $leftPixel -= $padding; else $leftPixel = 0;
		if($bottomPixel-$padding > 0) $bottomPixel -= $padding; else $bottomPixel = 0;
		
		$this->crop($rightPixel-$leftPixel, $topPixel-$bottomPixel, $leftPixel, $bottomPixel);
		$this->cropOffsets = array("top"=>$topPixel, "bottom"=>$bottomPixel, "left"=>$leftPixel, "right"=>$rightPixel);
		
		return $this;
	}
	
	/**
	 * Convert tranparency to a solid color
	 * @param string $hexColor - color in HEX format
	 * @return \EasyImage
	 */
	public function transparentToColor($hexColor){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->transparentToColor($hexColor);
			return $this;
		}
		
		$newColor = self::getColorFromHex($this->image, $hexColor);		
		$image_width = imagesx($this->image);
		$image_height = imagesy($this->image);
		for($x = $image_width; $x--;){
			for($y = $image_height; $y--;){
				$color = self::getPixelRGBA($x, $y);
				if($color['alpha'] == 127)
					imagesetpixel($this->image, $x, $y, $newColor);
			}
		}
		return $this;
	}
	
	/**
	 * Converts all pixels of a certain color to transparent
	 * @param string $hexColor - color in HEX format
	 * @return \EasyImage
	 */
	public function removeColor($hexColor){	
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->removeColor($hexColor);
			return $this;
		}
		
		imagealphablending($this->image, false);
		imagesavealpha($this->image, true);
		
		$image_width = imagesx($this->image);
		$image_height = imagesy($this->image);
		
		for($x = $image_width; $x--;){
			for($y = $image_height; $y--;){
				
				$color = self::getPixelRGBA($x, $y);
				$color = self::RGBToHex($color);
				
				if($color === $hexColor){
					$newColor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
					imagesetpixel($this->image, $x, $y, $newColor);
				}
			}
		}
		return $this;
	}
	
	/**
	 * Replaces colors in an image
	 * @param string $oldColor - color in HEX format
	 * @param string $newColor - color in HEX format
	 * @param int $threshold - sensitivity level
	 * @return \EasyImage
	 */
	public function replaceColor($oldColor, $newColor, $threshold=50){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->replaceColor($oldColor, $newColor, $threshold);
			return $this;
		}
		
		$rgb1 = array();
		list($rgb1['red'], $rgb1['green'], $rgb1['blue']) = self::hexToRGB($oldColor);
		list($new['red'], $new['green'], $new['blue']) = self::hexToRGB($newColor);
		$image_width = imagesx($this->image);
		$image_height = imagesy($this->image);
		for($x = $image_width; $x--;){
			for($y = $image_height; $y--;){
				$rgb2 = self::getPixelRGBA($x, $y);
				$dist = self::getColorDistance($rgb1, $rgb2);
				if($threshold > $dist){
					$color = imagecolorallocatealpha($this->image, $new['red'], $new['green'], $new['blue'], $rgb2['alpha']);
					imagesetpixel($this->image, $x, $y, $color);
				}
			}
		}
		return $this;
	}
	
	/**
	 * Scales the image to cover the dimensions provided
	 * @param int $width - width of canvas
	 * @param int $height - height of canvas
	 * @param string $cover - "fill" or "cove"
	 * @return \EasyImage
	 */
	public function scale($width, $height=null, $cover='fill'){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->scale($width, $height, $cover);
			return $this;
		}
		
		if(empty($height)) $height = $width;
		
		// Get new dimensions
		$imgRatio = $this->height/$this->width;
		$canvasRatio = $height/$width;
		if(
			($canvasRatio > $imgRatio && $cover==self::FILL) || 
			($canvasRatio <= $imgRatio && $cover!=self::FILL)
		){
			$finalHeight = $height;
			$scale = $finalHeight / $this->height;
			$finalWidth = $this->width * $scale;
		}else{
			$finalWidth = $width;
			$scale = $finalWidth / $this->width;
			$finalHeight = $this->height * $scale;
		}
		
		// Resize the image
		$thumb = imagecreatetruecolor($finalWidth, $finalHeight);
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
		imagecopyresampled($thumb, $this->image, 0, 0, 0, 0, $finalWidth, $finalHeight, $this->width, $this->height);
		
		$this->resize($finalWidth, $finalHeight);
		$this->width = $finalWidth;
		$this->height = $finalHeight;
		
		return $this;
	}
	
	/**
	 * Resizes the image to the dimensions provided
	 * @param int $width - width of canvas
	 * @param int $height - height of canvas
	 * @return \EasyImage
	 */
	public function resize($width, $height){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->resize($width, $height);
			return $this;
		}
		
		//echo $this; exit;
		
		// Resize the image
		$thumb = imagecreatetruecolor($width, $height);
		imagealphablending($thumb, true);
		imagesavealpha($thumb, true);
		$transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
		imagefill($thumb, 0, 0, $transparent);
		
		imagecopyresampled($thumb, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

		$this->image = $thumb;
		
		$this->width = $width;
		$this->height = $height;
		
		return $this;
	}
	
	/**
	 * Rotate the image
	 * @param int $degrees - degrees to rotate
	 * @return \EasyImage
	 */
	public function rotate($degrees){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->rotate($degrees);
			return $this;
		}
		
		$degrees += 360;
		$pngTransparency = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
		$this->image = imagerotate($this->image, $degrees, $pngTransparency);
		$this->width = imagesx($this->image);
		$this->height = imagesy($this->image);
		
		return $this;
	}
	
	/**
	 * Tile the image to the provided dimensions
	 * @param int $width - width of canvas
	 * @param int $height - height of canvas
	 * @return \EasyImage
	 */
	public function tile($width, $height){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->tile($width, $height);
			return $this;
		}
		
		// Our output image to be created
		$out = imagecreatetruecolor($width, $this->height);
		imagealphablending($out, false);
		imagesavealpha($out, true);
		
		// Tile that shit horiz
		$curr_x = 0;
		while($curr_x < $width){
			imagecopy($out, $this->image, $curr_x, 0, 0, 0, $this->width, $this->height);
			$curr_x += $this->width;
		}
		
		// our final output image to be created
		$thumb = imagecreatetruecolor($width, $height);
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);
		
		// Tile that shit vert
		$curr_y = 0;
		while($curr_y < $height){
			imagecopy($thumb, $out, 0, $curr_y, 0, 0, $width, $this->height);
			$curr_y += $this->height;
		}
		
		imagedestroy($out);
		
		$this->image = $thumb;
		$this->width = $width;
		$this->height = $height;
		return $this;
	}
	
	/**
	 * Reverse all colors of the image
	 * @return \EasyImage
	 */
	public function reverseColors(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->reverseColors();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_NEGATE);
		return $this;
	}
	
	/**
	 * Convert image to greyscale
	 * @return \EasyImage
	 */
	public function greyScale(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->greyScale();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		return $this;
	}
	
	/**
	 * Adjust brightness level.
	 * @param int $brightness - a number between 255 and -255
	 * @return \EasyImage
	 */
	public function adjustBrightness($brightness){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->adjustBrightness($brightness);
			return $this;
		}
		
		if($brightness > 255) $brightness = 255;
		if($brightness < -255) $brightness = -255;
		imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $brightness);
		return $this;
	}
	
	/**
	 * Adjust the contrast level
	 * @param int $contrast
	 * @return \EasyImage
	 */
	public function adjustContrast($contrast){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->adjustContrast($contrast);
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_CONTRAST, $contrast);
		return $this;
	}
	
	/**
	 * Turns on edgeDetect Filter
	 * @return \EasyImage
	 */
	public function edgeDetect(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->edgeDetect();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_EDGEDETECT);
		return $this;
	}
	
	/**
	 * Turns on emboss Filter
	 * @return \EasyImage
	 */
	public function emboss(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->emboss();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_EMBOSS);
		return $this;
	}
	
	/**
	 * Turns on gaussianBlur Filter
	 * @return \EasyImage
	 */
	public function gaussianBlur(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->gaussianBlur();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
		return $this;
	}
	
	/**
	 * Turns on selectiveBlur Filter
	 * @return \EasyImage
	 */
	public function selectiveBlur(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->selectiveBlur();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_SELECTIVE_BLUR);
		return $this;
	}
	
	/**
	 * Turns on sketch Filter
	 * @return \EasyImage
	 */
	public function sketch(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->sketch();
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}
	
	/**
	 * Adds a vignette
	 * @return \EasyImage
	 */
	public function vignette(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->vignette();
			return $this;
		}
		
		for($x = 0; $x < imagesx($this->image); ++$x){
			for($y = 0; $y < imagesy($this->image); ++$y){  
				$rgb = $this->getPixelRGBA($x, $y);
				
				$sharp = 0.4; // 0 - 10 small is sharpnes, 
				$level = 0.7; // 0 - 1 small is brighter
				$l = sin(M_PI / $this->width * $x) * sin(M_PI / $this->height * $y);
				$l = pow($l, $sharp);
				$l = 1 - $level * (1 - $l);
				$rgb['red'] *= $l;
				$rgb['green'] *= $l;
				$rgb['blue'] *= $l;
				
				$color = imagecolorallocatealpha($this->image, $rgb['red'], $rgb['green'], $rgb['blue'], $rgb['alpha']);
				imagesetpixel($this->image, $x, $y, $color);  
			}
		}
		return $this;
	}
	
	/**
	 * Maps the alpha layer for each pixel - useful mostly for debugging
	 * @return \EasyImage
	 */
	public function alphaMap(){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->alphaMap();
			return $this;
		}
		
		for($x = 0; $x < imagesx($this->image); ++$x){
			for($y = 0; $y < imagesy($this->image); ++$y){  
				$rgb = $this->getPixelRGBA($x, $y);
				$color = imagecolorallocatealpha($this->image, $rgb['alpha'], $rgb['alpha'], $rgb['alpha'], 0);
				imagesetpixel($this->image, $x, $y, $color);  
			}
		}
		return $this;
	}
	
	/**
	 * Sets all pixels to have an alpha layer of 0
	 * @return \EasyImage
	 */
	public function removeTransparency($newHexColor="#FFFFFF"){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->removeTransparency();
			return $this;
		}
		
		for($x = 0; $x < imagesx($this->image); ++$x){
			for($y = 0; $y < imagesy($this->image); ++$y){  
				$rgb = $this->getPixelRGBA($x, $y);
				if($rgb['alpha'] > 0){
					$color = self::getColorFromHex($this->image, $newHexColor);
					imagesetpixel($this->image, $x, $y, $color); 
				}
			}
		}
		return $this;
	}
	
	/**
	 * Pixelate the image
	 * @param int $blocksize - size of pixel blocks
	 * @param bool $advanced - use advanced pixelation?
	 * @return \EasyImage
	 */
	public function pixelate($blocksize, $advanced=true){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->pixelate($blocksize, $advanced);
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_PIXELATE, $blocksize, $advanced);
		return $this;
	}
	
	/**
	 * Adjust smoothness level
	 * @param int $level - smoothness level
	 * @return \EasyImage
	 */
	public function adjustSmoothness($level){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->adjustSmoothness($level);
			return $this;
		}
		
		imagefilter($this->image, IMG_FILTER_SMOOTH, $level);
		return $this;
	}
	
	/**
	 * Colorize an image
	 * @param string $hexColor - a color in HEX format
	 * @return \EasyImage
	 */
	public function colorize($hexColor, $alpha=0){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->colorize($hexColor, $alpha);
			return $this;
		}
		
		list($r, $g, $b) = self::hexToRGB($hexColor);
		if($alpha < 0) $alpha = 0;
		if($alpha > 127) $alpha = 127;
		imagefilter($this->image, IMG_FILTER_COLORIZE, $r, $g, $b, $alpha);
		return $this; 
	}
	
	/**
	 * Does a flood fill on the image
	 * @param int $startx - Starting x ordinate
	 * @param int $starty - Starting y ordinate
	 * @param string $hexColor - The hex color of the image to fill it with
	 * @return \EasyImage
	 */
	public function colorFill($startx, $starty, $hexColor){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->colorFill($startx, $starty, $hexColor);
			return $this;
		}
		
		$color = self::getColorFromHex($this->image, $hexColor);
		imagefill($this->image, $startx, $starty, $color);
		return $this;
	}
	
	/**
	 * Flip an image vertical, horizontal, or both
	 * @param string $mode - "H" for horizontal, "V" for vertical, "B" for both
	 * @return \EasyImage
	 */
	public function flip($mode = "H"){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->flip($mode);
			return $this;
		}
		
		$mode = strtoupper($mode);
		switch($mode){
			case(self::HORIZ):
				$m = IMG_FLIP_HORIZONTAL; break;
			case(self::VERT):
				$m = IMG_FLIP_VERTICAL; break;
			case(self::BOTH):
				$m = IMG_FLIP_BOTH; break;
			default: $this->Error("Invalid flip mode");
		}
		imageflip($this->image, $m);
		return $this;
	}
	
	/**
	 * Converts the filetype
	 * @param - $type - the new mimetype
	 * @return \EasyImage
	 */
	public function convertTo($type){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->convertTo($type);
			return $this;
		}
		
		switch($type){
			case(self::PDF): 
				$this->outputAs = self::PDF; 
				$this->fileExt = ".pdf";
				break;
			case(self::PNG):
				if($this->mime !== self::PNG){
					$this->setMime(self::PNG);
				}
				break;
			case(self::JPG):
				if($this->mime !== self::JPG){
					$this->setMime(self::JPG);
				}
				break;
			case(self::GIF):
				if($this->mime !== self::GIF){
					$this->setMime(self::GIF);
				}
				break;
		}
		return $this;
	}
	
	/**
	 * Concatenate the image to another image
	 * @param mixed $image - a URL, filepath or EasyImage object
	 * @param string $inline - "H" for horizontal concatenation, "V" for vertical
	 * @return \EasyImage
	 */
	public function concat($image, $inline="H"){
		
		// If it's a gif, apply to each
		if(is_array($this->gif_sources)){
			foreach($this->gif_sources as $img)
				$img->concat($image, $inline);
			return $this;
		}
		
		if(!is_object($image)) $image = self::Create($image);
		
		$widest = $image->getWidth() > $this->width ? $image->getWidth() : $this->width;
		$tallest = $image->getHeight() > $this->height ? $image->getHeight() : $this->height;
		
		$newWidth = $inline==self::HORIZ ? $image->getWidth() + $this->width : $widest;
		$hewHeight = $inline==self::HORIZ ? $tallest : $image->getHeight() + $this->height;
		
		$new = imagecreatetruecolor($newWidth, $hewHeight);
		imagealphablending($new, false);
		imagesavealpha($new, true);
		$transparent = imagecolorallocatealpha($new, 255, 255, 255, 127);
		imagefill($new, 0, 0, $transparent);
		
		if($inline==self::HORIZ){
			imagecopy($new, $this->image, 0, 0, 0, 0, $this->width, $this->height);
			imagecopy($new, $image->getImageResource(), $this->width, 0, 0, 0, $image->getWidth(), $image->getHeight());
		}else{
			imagecopy($new, $this->image, 0, 0, 0, 0, $this->width, $this->height);
			imagecopy($new, $image->getImageResource(), 0, $this->height, 0, 0, $image->getWidth(), $image->getHeight());
		}
		
		$this->image = $new;
		$this->width = $newWidth;
		$this->height = $hewHeight;
		return $this;
	}
	
	/***************************************************************************
	 ****************************** OUTPUT METHODS *****************************
	 **************************************************************************/
	
	/**
	 * Send the image to the browser as a download
	 * @param string $filename - Filename to download as
	 */
	public function download($filename){
		if( strlen($filename)>strlen($this->fileExt) && 
			substr($filename, strlen($filename)-strlen($this->fileExt)) === $this->fileExt
		) $filename = substr($filename, 0, strlen($filename)-strlen($this->fileExt));
		$filename .= $this->fileExt;
		
		if(is_array($this->gif_sources) && !empty($this->gif_sources)){
			header("Cache-Control: private");
			header("Content-Length: ".$size);
			header("Content-Disposition: attachment; filename=".$filename);
			echo $this->encodeGIF();
		}else if($this->outputAs === self::PDF){
			$this->outputPDF("D", $filename);
		}else{
			if(headers_sent()) self::Error("EasyImage can't output the image, headers already set.");
			list($mimetype, $funct, $compression) = $this->getOutputDetails();
			ob_start();
			$this->output();
			$size = ob_get_length();
			header("Cache-Control: private");
			header("Content-Length: ".$size);
			header("Content-Disposition: attachment; filename=".$filename);
			ob_end_flush();
		}
	}
	
	/**
	 * Save the image to the server
	 * @param string $filepath - filepath to save to
	 */
	public function save($filepath){
		if( strlen($filepath)>strlen($this->fileExt) && 
			substr($filepath, strlen($filepath)-strlen($this->fileExt)) === $this->fileExt
		) $filepath = substr($filepath, 0, strlen($filepath)-strlen($this->fileExt));
		$filepath .= $this->fileExt;
		
		if(is_array($this->gif_sources) && !empty($this->gif_sources)){
			$gifData = $this->encodeGIF();
			$handle = fopen($filepath, "w");
			fwrite($handle, $gifData);
			fclose($handle);
		}else if($this->outputAs === self::PDF){
			$this->outputPDF("F", $filepath);
		}else{
			list($mimetype, $funct, $compression) = $this->getOutputDetails();
			$funct($this->image, $filepath, $compression);
		}
	}
	
	/**
	 * Outputs the image directly to the browser
	 * @access private
	 */
	private function output(){
		if(headers_sent()) self::Error("EasyImage can't output the image, headers already set.");
		if(is_array($this->gif_sources) && !empty($this->gif_sources)){
			list($mimetype, $funct, $compression) = $this->getOutputDetails();
			header('Content-Type: '.$mimetype);
			echo $this->encodeGIF();
		}if($this->outputAs === self::PDF){
			$this->outputPDF("I");
		}else{
			list($mimetype, $funct, $compression) = $this->getOutputDetails();
			header('Content-Type: '.$mimetype);
			$funct($this->image, null, $compression);
		}
	}
	
	/**
	 * Outputs the image as a PDF File
	 * @param string $dest - destination; "D" for download, "I" to send to browser, "F" to save to server
	 * @param string $name - filename to save as
	 * @param bool $isUTF8 - is UTF encoded?
	 * @access private
	 */
	private function outputPDF($dest='', $name='', $isUTF8=false){
		$type = ltrim($this->mime, "image/");
		$orientation = $this->landscape ? "L" : "P";
		$size = array(
			self::pixelsToMM($this->width),
			self::pixelsToMM($this->height)
		);
		$tmpname = tempnam('/tmp', 'IMG');
		list($mimetype, $funct, $compression) = $this->getOutputDetails();
		$funct($this->image, $tmpname, $compression);
			
		// Check mbstring overloading
		if(ini_get('mbstring.func_overload') & 2) self::Error('mbstring overloading must be disabled');
		
		// Ensure runtime magic quotes are disabled
		if(get_magic_quotes_runtime()) @set_magic_quotes_runtime(0);

		// Scale factor
		$this->pdf_k = 72/25.4;
		$size = $this->pdf_getpagesize($size);
		$this->pdf_DefPageSize = $size;
		$this->pdf_CurPageSize = $size;
		
		// Page orientation
		$orientation = strtolower($orientation);
		if($orientation=='p' || $orientation=='portrait'){
			$this->pdf_DefOrientation = 'P';
			$this->pdf_w = $size[0];
			$this->pdf_h = $size[1];
		}elseif($orientation=='l' || $orientation=='landscape'){
			$this->pdf_DefOrientation = 'L';
			$this->pdf_w = $size[1];
			$this->pdf_h = $size[0];
		}
		else self::Error('Incorrect orientation: '.$orientation);
		$this->pdf_CurOrientation = $this->pdf_DefOrientation;
		$this->pdf_wPt = $this->pdf_w*$this->pdf_k;
		$this->pdf_hPt = $this->pdf_h*$this->pdf_k;
		
		// Page rotation
		$this->pdf_CurRotation = 0;
		
		// Page margins (1 cm)
		$margin = 28.35/$this->pdf_k;
		$this->pdf_SetMargins($margin,$margin);
		
		// Interior cell margin (1 mm)
		$this->pdf_cMargin = $margin/10;
		
		// Line width (0.2 mm)
		$this->pdf_LineWidth = .567/$this->pdf_k;
		
		// Automatic page break
		$this->pdf_SetAutoPageBreak(true,2*$margin);
		
		// Default display mode
		$this->pdf_SetDisplayMode('default');
		
		// Enable compression
		$this->pdf_SetCompression(true);
		
		// Set default PDF version number
		$this->pdf_PDFVersion = '1.3';
		
		$this->pdf_SetAutoPageBreak(false);
		$this->pdf_SetMargins(0, 0, 0);
		$this->pdf_AddPage();
		$this->pdf_Image(
            $tmpname, 
			null,
            null,
            self::pixelsToMM($this->width),
            self::pixelsToMM($this->height),
			$type
        );
		$this->pdf_Output($dest, $name, $isUTF8);
		unlink($tmpname);
	}

	/***************************************************************************
	 ********************************* GETTERS *********************************
	 **************************************************************************/	
	
	/**
	 * Get an array of colors in the image
	 * @param bool $hex - true will return HEX vals, otherwize will return RGB array
	 * @return array - an array of colors
	 */
	public function getColors($hex = false) {
		
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getColors($hex));
			return $ret;
		}
		
		$colors = array();
		for ($x = $this->width; $x--;) {
			for ($y = $this->height; $y--;) {
				$color = $this->getPixelRGBA($x, $y);
				unset($color['alpha']);
				if(!in_array($color, $colors))
					array_push($colors, $color);
			}
		}
		$colors = self::colorSortLuma($colors);
		if($hex)
			foreach($colors as $k=>$v)
				$colors[$k] = self::RGBToHex($v);
		return $colors;
	}
	
	/**
	 * Get the image resource
	 * @return resource - the image resource
	 */
	public function getImageResource(){
		
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getImageResource());
			return $ret;
		}
		
		return $this->image;
	}
	
	/**
	 * Get letf, right, top and bottom offsets of an image cropped by autocrop
	 * @return array - an array of offsets
	 */
	public function getOffsets(){ 
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getOffsets());
			return $ret;
		}
		return $this->cropOffsets; 
	}
	
	/**
	 * Get the original filepath to the physical image
	 * @return string - path to the unedited image
	 */
	public function getFilepath(){ 
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getFilepath());
			return $ret;
		}
		return $this->filepath; 
	}
	
	/**
	 * Get the width of the image
	 * @return int - the width of the image
	 */
	public function getWidth(){ 
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getWidth());
			return $ret;
		}
		return $this->width; 
	}	
	
	/**
	 * Get the height of the image
	 * @return int - the height of the image
	 */
	public function getHeight(){ 
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getHeight());
			return $ret;
		}
		return $this->height; 
	}	
	
	/**
	 * Get the mimetype of the current image
	 * @return string - the current mimetype
	 */
	public function getMimeType(){ 
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getMimeType());
			return $ret;
		}
		return $this->mime; 
	}
	
	/**
	 * Get the orientation of the image
	 * @return string - "landscape" or "Portrait"
	 */
	public function getOrientation(){ 
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getOrientation());
			return $ret;
		}
		return $this->landscape ? "Landscape" : "Portrait"; 
	}
	
	/**
	 * Get the color index from a pixel in the image
	 * @param int $x - x position of pixel
	 * @param int $y - y position of pixel
	 * @return int - color index of the pixel
	 */
	public function getPixelColorIndex($x, $y){
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getPixelColorIndex($x, $y));
			return $ret;
		}
		return imagecolorat($this->image, $x, $y);
	}
	
	/**
	 * Get the RGB colors for the index of a pixel
	 * @param int $index - color index from a color in the image
	 * @return array - array of colors
	 */
	public function getColorsFromIndex($index){
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getColorsFromIndex($index));
			return $ret;
		}
		return imagecolorsforindex($this->image, $index);
	}
	
	/**
	 * Get an array of RGBA values for a given pixel
	 * @param int $x - x position of the pixel
	 * @param int $y - y position of the pixel
	 * @return array - array of colors
	 */
	public function getPixelRGBA($x, $y){
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getPixelRGBA($x, $y));
			return $ret;
		}
		$index = $this->getPixelColorIndex($x, $y);
		return $this->getColorsFromIndex($index);
	}
	
	/**
	 * Get the hex color value of a pixel
	 * @param int $x - the x position of the pixel
	 * @param int $y - the y position of the pixel
	 * @return string - HEX color string
	 */
	public function getPixelHexColor($x, $y) {
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$ret = array();
			foreach($this->gif_sources as $img)
				array_push($ret, $img->getPixelHexColor($x, $y));
			return $ret;
		}
		$colors = $this->getPixelRGBA($x, $y);
		return self::RGBToHex($colors);
	}
	
	/**
	 * Get base64 data string for the image
	 * @param string $output_mimetype - new mimetype
	 * @return string - base64 string
	 */
	public function getBase64($output_mimetype = null){
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			return "data:image/gif;base64,".base64_encode($this->encodeGIF());
		}
		
		// Get output details
		list($mimetype, $funct, $compression) = $this->getOutputDetails($output_mimetype);
		ob_start();
		// Get and call the image creation funtion
		$funct($this->image, null, $compression);
		$data = ob_get_clean();
		// Encode and return
		return "data:$mimetype;base64,".base64_encode($data);
	}
	
	/**
	 * Get string data for the image
	 * @param string $output_mimetype - new mimetype
	 * @return string - raw image data
	 */
	public function getString($output_mimetype = null){
		if(is_array($this->gif_sources)){
			return $this->encodeGIF();
		}

		// Get output details
		list($mimetype, $funct, $compression) = $this->getOutputDetails($output_mimetype);
		ob_start();
		$funct($this->image, null, $compression);
		return ob_get_clean();
	}
	
	/**
	 * Get a copy of the current EasyImage Object
	 * @return \EasyImage
	 */
	public function getCopy(){
		
		// If it's a gif, return each
		if(is_array($this->gif_sources)){
			$sources = array();
			foreach($this->gif_sources as $src)
				array_push($sources, $src->getBase64());
			return self::Create($sources, $this->gif_delayTimes, $this->gif_loops, $this->gif_disposal, $this->gif_transRed, $this->gif_transGreen, $this->gif_transBlue);
		}
		
		$b = $this->getBase64();
		return self::Create($b);
	}
	
	/***************************************************************************
	 *************************** UTILITIES & HELPERS ***************************
	 **************************************************************************/
	
	/**
	 * Compare two different colors in different formats
	 * @param mixed $color1 - a color as an array of RGB values or a HEX color string
	 * @param mixed $color2 - a color as an array of RGB values or a HEX color string
	 * @return bool
	 */
	public static function compareColors($color1, $color2){
		if(!is_string($color1) || strlen($color1) > 7) return false;
		if(!is_string($color2) || strlen($color2) > 7) return false;
		if(is_string($color1)) $color1 = self::hexToRGB($color1);
		if(is_array($color1)) $color1 = array_values($color1);
		$color1 = json_encode($color1);
		if(is_string($color2)) $color2 = self::hexToRGB($color2);
		if(is_array($color2)) $color2 = array_values($color2);
		$color2 = json_encode($color2);
		return $color1 === $color2;
	}
	
	/**
	 * Comapre luma of 2 colors
	 * @param array $a - array of RGB values
	 * @param array $b - an array of RGB values
	 * @return int - returns 1 if $a is more luminous than $b, else -1
	 */
	public static function compareLuma($a, $b){
		$c1 = (0.2126 * $a['red'] + 0.7152 * $a['green'] + 0.0722 * $a['blue']) / 255;
		$c2 = (0.2126 * $b['red'] + 0.7152 * $b['green'] + 0.0722 * $b['blue']) / 255;
		return $c1 > $c2 ? 1 : -1;
	}
	
	/**
	 * Sort colors by color luminance
	 * @param array $colors - array of colors with a 'red', 'green', and 'blue' index
	 * @return array - the sorted array
	 */
	public static function colorSortLuma($colors){
		usort($colors, array('EasyImage', 'compareLuma'));
		return $colors;
	}
	
	/**
	 * Sort colors by color dominance
	 * @param array $colors - array of colors with a 'red', 'green', and 'blue' index
	 * @return array - the sorted array
	 */
	public static function colorSort($colors) {
		$reds = array();
		$greens = array();
		$blues = array();
		$otherColors = array();
		$sortedArray = array();
		foreach ($colors as $color) {
			if ($color['red'] > $color['green'] && $color['red'] > $color['blue']) {
				$reds[] = $color;
			} elseif ($color['green'] > $color['red'] && $color['green'] > $color['blue']) {
				$greens[] = $color;
			} elseif ($color['blue'] > $color['red'] && $color['blue'] > $color['green']) {
				$blues[] = $color;
			} else {
				$otherColors[] = $color;
			}
		}
		return $sortedArray;
	}
	
	/**
	 * Get the distance between two colors
	 * @param array $rgb1 - array of rgb colors
	 * @param array $rgb2 - array of rgb colors
	 * @return float - the distance between the colors
	 */
	public static function getColorDistance($rgb1, $rgb2){
		return sqrt(
			pow($rgb1['red'] - $rgb2['red'], 2) + 
			pow($rgb1['green'] - $rgb2['green'], 2) + 
			pow($rgb1['blue'] - $rgb2['blue'], 2)
		);
	}
	
	/**
	 * Convert Hex colors RGB to HEX color
	 * @param array $colors - array of rgb values
	 * @return string - the HEX color string
	 */
	public static function RGBToHex($colors){
		$n[0] = $colors['red'];
		$n[1] = $colors['green'];
		$n[2] = $colors['blue'];
		$str = "#";
		for ($x = 0; $x < 3; $x++) {
			$n[$x] = intval($n[$x], 10);
			if (is_nan($n[$x])) return "00";
			$n[$x] = max(0, min($n[$x], 255));
			$bam = "0123456789ABCDEF";
			$str .= $bam{($n[$x] - $n[$x] % 16) / 16} . $bam{$n[$x] % 16};
		}
		return $str;
	}
	
	/**
	 * Convert a hex color to RGB color
	 * @param string $hex - a HEX color string
	 * @return array - an array of rgb colors
	 */
	public static function hexToRGB($hex){
		// Remove the # if there is one
		$hex = str_replace("#", "", $hex);

		// Convert the hex to rgb
		if(strlen($hex) == 3){
			$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
			$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
			$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
		}else{
			$r = hexdec(substr($hex, 0, 2));
			$g = hexdec(substr($hex, 2, 2));
			$b = hexdec(substr($hex, 4, 2));
		}
		
		return array($r, $g, $b);
	}
	
	/**
	 * Get all colors in a gradient between two colors
	 * @param string $hex1 - the starting HEX color string
	 * @param string $hex2 - the ending HEX oclor string
	 * @param int $steps - number of colors to return
	 * @return array - array of colors in the gradient
	 */
	public static function gradientColors($HexFrom, $HexTo, $ColorSteps=10){
		
		$FromRGB = array();
		list($FromRGB['r'], $FromRGB['g'], $FromRGB['b']) = self::hexToRGB($HexFrom);
		
		$ToRGB = array();
		list($ToRGB['r'], $ToRGB['g'], $ToRGB['b']) = self::hexToRGB($HexTo);

		$StepRGB['r'] = ($FromRGB['r'] - $ToRGB['r']) / ($ColorSteps - 1);
		$StepRGB['g'] = ($FromRGB['g'] - $ToRGB['g']) / ($ColorSteps - 1);
		$StepRGB['b'] = ($FromRGB['b'] - $ToRGB['b']) / ($ColorSteps - 1);

		$GradientColors = array();
		$RGB = array();
		$HexRGB = array();
		
		for($i = 0; $i <= $ColorSteps; $i++){
			$RGB['r'] = floor($FromRGB['r'] - ($StepRGB['r'] * $i));
			$RGB['g'] = floor($FromRGB['g'] - ($StepRGB['g'] * $i));
			$RGB['b'] = floor($FromRGB['b'] - ($StepRGB['b'] * $i));

			$HexRGB['red'] = sprintf('%02x', ($RGB['r']));
			$HexRGB['green'] = sprintf('%02x', ($RGB['g']));
			$HexRGB['blue'] = sprintf('%02x', ($RGB['b']));
			
			$val = implode("", $HexRGB);
			if(strlen($val) == 6) $GradientColors[] = strtoupper("#$val");
		}
		return $GradientColors;
	}

	/***************************************************************************
	 ****************************** MAGIC METHODS ******************************
	 **************************************************************************/
	
	/**
	 * Allows the class to output the image when echoed or printed
	 * @ignore
	 */
	public function __toString(){
		if(headers_sent()) self::Error("Can't output image, headers already sent.");
		else $this->output();
		return "";
	}
	
	/**
	 * Destroys the generated image to free up resources,
	 * Delete the file if it's a temporary file.
	 * should be the last method called as the object is unusable after this.
	 * @ignore
	 */
	public function __destruct(){
		if(!empty($this->image)) imagedestroy($this->image); 
		if($this->isTemp) unlink($this->filepath);
	}
	
	/**
	 * constructor
	 * @param type $fp
	 * @param type $isTemp - if true, will delete the image file on the destroy call
	 * @throws Exception
	 * @access private
	 */
	private function __construct($fp, $isTemp, $loops=0, $disposal=2, $transRed=0, $transGreen=0, $transBlue=0){
		if(!is_array($fp)){
			if(empty($isTemp)) $isTemp = false;
			
			// Make sure file exists
			if(!file_exists($fp)) self::Error("Image source file does not exist: $fp"); 
			$this->cropOffsets = array("top"=>0, "bottom"=>0, "left"=>0, "right"=>0);
			$this->isTemp = $isTemp;
			$this->filepath = $fp;
			$data = getimagesize($fp);
			$this->width = $data[0];
			$this->height = $data[1];
			$this->landscape = $this->width > $this->height;
			$this->setMime($data['mime']);
		}else{
			// It's an animation
			$this->gif_sources = $fp;
			$this->gif_delayTimes = $isTemp;
			$this->gif_loops = $loops;
			$this->gif_disposal = $disposal;
			$this->gif_transRed = $transRed;
			$this->gif_transGreen = $transGreen;
			$this->gif_transBlue = $transBlue;
		}
	}
	
	/***************************************************************************
	 ************************** PRIVATE CONSTRUCTORS ***************************
	 **************************************************************************/
	
	/**
	 * Create an animated gif
	 * @param array $sources - image sources
	 * @param int|array $delayTimes - delay times
	 * @param int $loops - number of loops to do
	 * @param int $disposal - gif disposal
	 * @param int $transRed - transparent red
	 * @param int $transGreen - transparent green
	 * @param int $transBlue - transparent blue
	 * @return \EasyImage
	 * @access private
	 * @static
	 */
	private static function createAnimatedGIF($sources, $delayTimes=5, $loops=0, $disposal=2, $transRed=0, $transGreen=0, $transBlue=0){
		// make sure sources is an array
		if(!is_array($sources)) self::Error("First param for EasyImage::createAnimatedGIF must be array.");
		
		// make sure all sources are EasyImage
		$newSources = array();
		foreach($sources as $src)
			array_push($newSources, is_object($src) ? $src : EasyImage::Create($src));
		$sources = $newSources; 
		unset($newSources);
		
		// make sure delay times array matches length of sources
		if(!is_array($delayTimes)){
			$delayTimes = array_pad(array(), count($sources), intval($delayTimes));
		}else if(count($delayTimes) < count($sources)){
			$delayTimes = array_pad(array(), count($sources), intval(5));
		}
		
		return new EasyImage($sources, $delayTimes, $loops, $disposal, $transRed, $transGreen, $transBlue);
	}
	
	/**
	 * Create an image from a PhotoShop file
	 * @param type $filename
	 * @return \EasyImage
	 * @access private
	 * @static
	 */
	private static function createFromPSD($filename){
		ob_start();
		imagepng(self::psdReader($filename), null, 9);
		$b64 = base64_encode(ob_get_clean());
		return EasyImage::Create($b64);
	}
	
	/**
	 * Create an image from a local image file
	 * @param string $filepath - path to image file to use
	 * @param bool $temp - is it a temporary image?
	 * @return \EasyImage
	 * @access private
	 * @static
	 */
	private static function createFromFile($filepath, $temp=false){
		return new EasyImage($filepath, $temp);
	}
	
	/**
	 * Creates an image from a URL
	 * @param string $url - the url to create the image from
	 * @return \EasyImage
	 * @access private
	 * @static
	 */
	private static function createFromURL($url){
		$data = file_get_contents($url);
		$im = imagecreatefromstring($data);
		$tmpname = tempnam('/tmp', 'IMG');
		imagepng($im, $tmpname, 9);
		return new EasyImage($tmpname, true);
	}
	
	/**
	 * Create an image from a base64 string
	 * @param string $string - base 64 encoded image string
	 * @return \EasyImage
	 * @access private
	 * @static
	 */
	private static function createFromBase64($string){ 
		
		// decode base64 string
		$imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $string));
		
		// create the image resource
		$formImage = imagecreatefromstring($imgData);
		
		// Fill with transparent background
		imagealphablending($formImage, false);
		imagesavealpha($formImage, true);
		$transparent = imagecolorallocatealpha($formImage, 255, 255, 255, 127);
		imagefill($formImage, 0, 0, $transparent);
		
		// Save the image to a temp png file to use in our constructor
		$tmpname = tempnam('/tmp', 'IMG');
		
		// Generate and save image
		imagepng($formImage, $tmpname, 9);
		
		// Return an instance of the class
		$img = new EasyImage($tmpname, true);
		return $img;
	} 
	
	/**
	 * Create an image from simple HTML string
	 * @param string $html - an HTML string
	 * @param array $fonts - an array of fonts witht eh key as the title and the value as the path
	 * @access private
	 * @static
	 */
	private static function createHTMLImage($html, $fonts){
		$chunks = self::parseHTML($html, $fonts);
		$images = array();
		$breakLine = false;
		for($i=0; $i<count($chunks); $i++){
			$img_data = $chunks[$i];
			$img = EasyImage::createTextImage(
				$img_data['text'], 
				$img_data['fontsize'], 
				$img_data['color'], 
				$img_data['font'], 
				$img_data['background'], 
				$img_data['width'], 
				$img_data['opacity'], 
				$img_data['padding']
			);
			array_push($images, array("img"=>$img, "break"=>$breakLine));
			$breakLine = $img_data['display'] == "block" || (isset($chunks[1+$i]) && $chunks[1+$i]['display'] == "block");
		}
		
		$return = null;
		$rows = array(); 
		for($i=0; $i<count($images); $i++){
			$img = $images[$i];
			if(empty($return))
				$return = $img['img'];
			else if(!$img['break']){
				$return->concat($img['img'], self::HORIZ);
			}else{
				array_push($rows, $return);
				$return = $img['img'];
			}
		}
		if(!empty($return)){
			array_push($rows, $return);
			$return = null;
		}
		
		for($i=0; $i<count($rows); $i++){
			$img = $rows[$i];
			if(empty($return)) $return = $img;
			else{
				$return->concat($img, self::VERT);
			}
		}
		
		return $return;
	}
	
	/**
	 * Make an image of text
	 * @param string $text - text to use
	 * @param int $font_size - font size
	 * @param string $color - HEX color string
	 * @param string $font_file - path to the font file
	 * @param mixed $background - the color or image to use as the background of the image
	 * @param int|bool $wrap_width - how wide to allow text to go before wrapping or false for no wrapping
	 * @param int $alpha - alpha level
	 * @param int $padding - how much padding to allow arround the text
	 * @return \EasyImage
	 * @access private
	 * @static
	 */
	private static function createTextImage($text, $font_size, $color, $font_file, $background=null, $wrap_width=false, $alpha=0, $padding=5){
		// Make sure font file exists
		if(!file_exists($font_file)) self::Error("Font file does not exist: {$font_file}");
		
		// Generate wrapping text
		if(is_numeric($wrap_width) && $wrap_width != 0) 
			$text = self::wrapText($font_size, $font_file, $text, $wrap_width);		
		
		// Retrieve bounding box:
		$type_space = imagettfbbox($font_size, 0, $font_file, $text);

		// Determine image width and height, 10 pixels are added for 5 pixels padding:
		$image_width = abs($type_space[4] - $type_space[0]) + ($padding*2);
		$image_height = abs($type_space[5] - $type_space[1]) + ($padding*2);
		$line_height = self::getLineHeight($font_size, $font_file) + ($padding*2);
		
		// Create image
		$image = imagecreatetruecolor($image_width, $image_height);
		imagealphablending($image, false);
		imagesavealpha($image, true);
		$white = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $white);

		// black for the text
		$black = imagecolorallocate($image, 0, 0, 0);
		
		// Fix starting x and y coordinates for the text:
		$x = $padding; // Padding of 5 pixels.
		$y = $line_height - $padding; // So that the text is vertically centered.

		// Add TrueType text to image:
		imagettftext($image, $font_size, 0, $x, $y, $black, $font_file, $text);
		
		// Save the image to a temp png file to use in our constructor
		$tmpname = tempnam('/tmp', 'IMG');
		
		// Generate and save image
		imagepng($image, $tmpname, 9);
		
		// Get an instance of the class
		$img = EasyImage::createFromFile($tmpname, true)
			->removeTransparency()
			->crop($image_width, $image_height)
			->blackAndWhite();
		
		// the background is white, text is black at this point
		$bgClr = "#FFFFFF";
		$txtClr = "#000000"; 
		
		// if the text is white, change the white background to grey
		if(self::compareColors("#FFFFFF", $color)){
			$bgClr = "#CCCCCC";
			$img->replaceColor("#FFFFFF", $bgClr);
		}
		
		// Drop it on the background, if there is one
		if(!empty($background)){
			
			// if the background is a filepath or url
			if(is_string($background) && false !== @file_get_contents($background, false, null, 0, 10)){ 
				$bg = EasyImage::Create($background)
					->tile($image_width, $image_height);
			}
			
			// if the background is an EasyImage instance
			else if(is_object($background)){ 
				$bg = $background
					->tile($image_width, $image_height);
			}
			
			// else assume it's a hex color
			else{ 
				$im = imagecreatetruecolor($image_width, $image_height);
				$bgColor = self::getColorFromHex($im, $background, $alpha);
				imagefill($im, 0, 0, $bgColor);
				$tmpname = tempnam('/tmp', 'IMG');
				imagepng($im, $tmpname, 9);
				$bg = EasyImage::createFromFile($tmpname, true);
			}
			
			// loop thru pixels and replace the background pixels
			$src_w = $img->getWidth();
			$src_h = $img->getHeight();
			$image_width = $bg->getWidth();
			$image_height = $bg->getHeight();
			$src_x_pos = 0;
			$src_y_pos = 0;
			$overlaying = false;
			for ($x=0; $x<$image_width; $x++) {
				$src_y_pos = 0;
				for ($y=0; $y<$image_height; $y++) {
					$overlaying = 
						$x >= 0			// are we to the right of $dst_x
						&& $x < $src_w+0	// are we to the left of the far right pixel
						&& $y >= 0			// are we above the bottom 
						&& $y < $src_h+0;  // are we below the top
					if($overlaying){
						$tcolor = $img->getPixelRGBA($src_x_pos, $src_y_pos);
						
						// if this is a background pixel, change it
						if(self::RGBToHex($tcolor) === $bgClr){ 
							$bgcolor = $bg->getPixelRGBA($src_x_pos, $src_y_pos);
							$newColor = imagecolorallocatealpha($img->getImageResource(), $bgcolor['red'], $bgcolor['green'], $bgcolor['blue'], 0);
							imagesetpixel($img->getImageResource(), $x, $y, $newColor);
						}
						
						$src_y_pos++;
					}
				}
				if($overlaying) $src_x_pos++;
			}
		}
		
		// if there's no background, then remove the background color
		else $img->removeColor($bgClr);
		
		/////////////////
		// begin changing color of text
		/////////////////
		
		// if the textcolor is a filepath or url
		if(is_string($color) && false !== @file_get_contents($color, false, null, 0, 10)){ 
			$txt = EasyImage::Create($color)
				->tile($image_width, $image_height);
		}

		// if the textcolor is an EasyImage instance
		else if(is_object($color)){ 
			$txt = $color
				->tile($image_width, $image_height);
		}

		// else assume it's a hex color
		else{ 
			$im = imagecreatetruecolor($image_width, $image_height);
			$clr = self::getColorFromHex($im, $color, $alpha);
			imagefill($im, 0, 0, $clr);
			$tmpname = tempnam('/tmp', 'IMG');
			imagepng($im, $tmpname, 9);
			$txt = EasyImage::createFromFile($tmpname, true);
		}

		// loop thru pixels and replace the text pixels
		$src_w = $img->getWidth();
		$src_h = $img->getHeight();
		$image_width = $txt->getWidth();
		$image_height = $txt->getHeight();
		$src_x_pos = 0;
		$src_y_pos = 0;
		$overlaying = false;
		for ($x=0; $x<$image_width; $x++) {
			$src_y_pos = 0;
			for ($y=0; $y<$image_height; $y++) {
				$overlaying = 
					$x >= 0			// are we to the right of $dst_x
					&& $x < $src_w+0	// are we to the left of the far right pixel
					&& $y >= 0			// are we above the bottom 
					&& $y < $src_h+0;  // are we below the top
				if($overlaying){
					$tcolor = $img->getPixelRGBA($src_x_pos, $src_y_pos);

					// if this is a background pixel, change it
					if(self::RGBToHex($tcolor) === $txtClr && 127 !== $tcolor['alpha']){ 
						$clr = $txt->getPixelRGBA($src_x_pos, $src_y_pos);
						$newColor = imagecolorallocatealpha($img->getImageResource(), $clr['red'], $clr['green'], $clr['blue'], 0);
						imagesetpixel($img->getImageResource(), $x, $y, $newColor);
					}

					$src_y_pos++;
				}
			}
			if($overlaying) $src_x_pos++;
		}
			
		/////////////////
		// end changing color of text
		/////////////////
		
		return $img;
	}
	
	/***************************************************************************
	 ***************************** PRIVATE METHODS *****************************
	 **************************************************************************/
	
	/**
	 * Get gif image stream from gif files
	 * @ignore
	 */
	private function encodeGIF() {
		
		$sources = $this->gif_sources;
		$delayTimes = $this->gif_delayTimes;
		$loops = $this->gif_loops;
		$disposal = $this->gif_disposal;
		$transRed = $this->gif_transRed;
		$transGreen = $this->gif_transGreen;
		$transBlue = $this->gif_transBlue;
		
		$GIF = "GIF89a";
		$BUF = Array();
		$LOP = 0;
		$DIS = 2;
		$COL = -1;
		$IMG = -1;

		$LOP = $$loops=0 > -1 ? $$loops=0 : 0;
		$DIS = $disposal > -1 ?
				($disposal < 3 ? $disposal : 3) : 2;
		$COL = $transRed > -1 && $transGreen > -1 && $$transBlue=0 > -1 ?
				($transRed | ($transGreen << 8) | ($$transBlue=0 << 16)) : -1;

		for ($i = 0; $i < count($sources); $i++) {
			$BUF[] = $sources[$i]->getString(self::GIF);

			if (substr($BUF[$i], 0, 6) != "GIF87a" && substr($BUF[$i], 0, 6) != "GIF89a")
				die("Source is not a GIF image! '".substr($BUF[$i], 0, 6)."'");

			for ($j = (13 + 3 * (2 << (ord($BUF[$i] {10}) & 0x07))), $k = TRUE; $k; $j++) {
				switch ($BUF[$i] {$j}) {
					case "!":
						if ((substr($BUF[$i], ($j + 3), 8)) == "NETSCAPE")
							die("Does not make animation from animated GIF source");
						break;
					case ";":
						$k = FALSE;
						break;
				}
			}
		}

		// Add header
		$cmap = 0;
		if (ord($BUF[0] {10}) & 0x80) {
			$cmap = 3 * (2 << (ord($BUF[0] {10}) & 0x07));
			$GIF .= substr($BUF[0], 6, 7);
			$GIF .= substr($BUF[0], 13, $cmap);
			$gifWord = (chr($LOP & 0xFF) . chr(($LOP >> 8) & 0xFF));
			$GIF .= "!\377\13NETSCAPE2.0\3\1" . $gifWord . "\0";
		}

		for ($i = 0; $i < count($BUF); $i++) {
			$ii = $i;
			$d = $delayTimes[$i];
			$Locals_str = 13 + 3 * (2 << (ord($BUF[$ii] {10}) & 0x07));
			$Locals_end = strlen($BUF[$ii]) - $Locals_str - 1;
			$Locals_tmp = substr($BUF[$ii], $Locals_str, $Locals_end);
			$Global_len = 2 << (ord($BUF[0] {10}) & 0x07);
			$Locals_len = 2 << (ord($BUF[$ii] {10}) & 0x07);
			$Global_rgb = substr($BUF[0], 13, 3 * (2 << (ord($BUF[0] {10}) & 0x07)));
			$Locals_rgb = substr($BUF[$ii], 13, 3 * (2 << (ord($BUF[$ii] {10}) & 0x07)));
			$Locals_ext = "!\xF9\x04" . chr(($DIS << 2) + 0) . chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . "\x0\x0";
			if ($COL > -1 && ord($BUF[$ii] {10}) & 0x80) {
				for ($j = 0; $j < (2 << (ord($BUF[$ii] {10}) & 0x07)); $j++) {
					if (
							ord($Locals_rgb { 3 * $j + 0 }) == (($COL >> 16) & 0xFF) &&
							ord($Locals_rgb { 3 * $j + 1 }) == (($COL >> 8) & 0xFF) &&
							ord($Locals_rgb { 3 * $j + 2 }) == (($COL >> 0) & 0xFF)
					) {
						$Locals_ext = "!\xF9\x04" . chr(($DIS << 2) + 1) .
								chr(($d >> 0) & 0xFF) . chr(($d >> 8) & 0xFF) . chr($j) . "\x0";
						break;
					}
				}
			}
			switch ($Locals_tmp {0}) {
				case "!":
					$Locals_img = substr($Locals_tmp, 8, 10);
					$Locals_tmp = substr($Locals_tmp, 18, strlen($Locals_tmp) - 18);
					break;
				case ",":
					$Locals_img = substr($Locals_tmp, 0, 10);
					$Locals_tmp = substr($Locals_tmp, 10, strlen($Locals_tmp) - 10);
					break;
			}
			if (ord($BUF[$ii] {10}) & 0x80 && $IMG > -1) {
				if ($Global_len == $Locals_len) {

					$compare = 1;
					$GlobalBlock = $Global_rgb;
					$LocalBlock = $Locals_rgb;
					$Len = $Global_len;

					for ($iii = 0; $iii < $Len; $iii++) {
						if (
								$GlobalBlock {3 * $iii + 0} != $LocalBlock {3 * $iii + 0} ||
								$GlobalBlock {3 * $iii + 1} != $LocalBlock {3 * $iii + 1} ||
								$GlobalBlock {3 * $iii + 2} != $LocalBlock {3 * $iii + 2}
						) {
							$compare = 0;
							break;
						}
					}

					if ($compare)
						$GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
					else {
						$byte = ord($Locals_img {9});
						$byte |= 0x80;
						$byte &= 0xF8;
						$byte |= (ord($BUF[0] {10}) & 0x07);
						$Locals_img {9} = chr($byte);
						$GIF .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
					}
				} else {
					$byte = ord($Locals_img {9});
					$byte |= 0x80;
					$byte &= 0xF8;
					$byte |= (ord($BUF[$ii] {10}) & 0x07);
					$Locals_img {9} = chr($byte);
					$GIF .= ($Locals_ext . $Locals_img . $Locals_rgb . $Locals_tmp);
				}
			} else
				$GIF .= ($Locals_ext . $Locals_img . $Locals_tmp);
			$IMG = 1;
		}
		$GIF .= ";";
		return $GIF;
	}
	
	/**
	 * Set the image's mime type
	 * @ignore
	 */
	private function setMime($mime){
		$this->mime = $mime;
		switch($this->mime){
			case(self::PNG):
				if(empty($this->image)){
					$this->image = imagecreatefrompng($this->filepath);
					imagealphablending($this->image, false);
					imagesavealpha($this->image, true);
				}
				$this->imageFunct = 'imagepng';
				$this->compression = 9;
				$this->fileExt = ".png";
				$this->outputAs = self::PNG;
				break;
			case(self::JPG):
			case('image/jpeg'):
			case('image/pjpeg'):
			case('image/x-jps'):
				if(empty($this->image))
					$this->image = imagecreatefromjpeg($this->filepath);
				$this->imageFunct = 'imagejpeg';
				$this->compression = 100;
				$this->fileExt = ".jpg";
				$this->outputAs = self::JPG;
				break;
			case(self::GIF):
				if(empty($this->image)){
					$this->image = imagecreatefromgif($this->filepath);
					imagealphablending($this->image, false);
					imagesavealpha($this->image, true);
				}
				$this->imageFunct = 'imagegif';
				$this->fileExt = ".gif";
				$this->outputAs = self::GIF;
				break;
			default:
				self::Error("Invalid image type. Only accepts PNG, JPG, and GIF. You entered a {$this->mime} type image.");
		}
	}
	
	/**
	 * Parses an HTML string
	 * @ignore
	 */
	private static function parseHTML($html, $fonts){
		$chunks = array();
		$defaultParams = array(
			"color" => "#000",
			"font" => reset($fonts),
			"background" => null,
			"fontsize" => 18,
			"display" => "inline",
			"text" => "",
			"width" => false,
			"opacity" => 0,
			"padding" => 5
			
		);
		$currentChunk = $defaultParams;
		$html = strip_tags($html, "<div><span>"); //remove all unsupported tags
		$html = str_replace("\n", '', $html); //replace carriage returns by spaces
		$html = str_replace("\t", '', $html); //replace carriage returns by spaces
		$html = str_replace("<br>", "\n", $html); //add <br> line breaks
		$a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE); //explodes the string
		foreach($a as $chunk){
			if(empty($chunk)) continue;
			$chunk = trim($chunk);
			$first4 = substr($chunk, 0, 4);
			if("span" === $first4 || "div " === $first4){
				$currentChunk['display'] = "span" === $first4 ? "inline" : "block";
				$styleStart = strpos($chunk, "style=");
				if($styleStart !== false){
					$chunk = substr($chunk, $styleStart + 7);
					$styles = explode(";", $chunk);
					array_pop($styles);
					foreach($styles as $style){
						list($name, $value) = explode(":", $style);
						$name = trim($name);
						$value = trim($value);
						switch($name){
							case("background-color"):	$currentChunk['background'] = $value;	break;
							case("background-image"):	$currentChunk['background'] = $value;	break;
							case("font-face"):			$currentChunk['font'] = $fonts[$value];		break;
							case("font-size"):			$currentChunk['fontsize'] = $value; break;
							case("color"):				$currentChunk['color'] = $value;	break;
							case("display"):			$currentChunk['display'] = $value;	break;
							case("width"):				$currentChunk['width'] = $value;	break;
							case("opacity"):			$currentChunk['opacity'] = $value;	break;
							case("padding"):			$currentChunk['padding'] = $value;	break;
						}
					}
				}
			}else if("/spa" !== $first4 && "/div" !== $first4){
				$currentChunk['text'] = $chunk;
				array_push($chunks, $currentChunk);
				$currentChunk = $defaultParams;
			}
		}
		return $chunks;
	}
	
	/**
	 * Get output information
	 * @param type $output_mimetype
	 * @return array($mimetype, $output_funct, $compression)
	 * @ignore
	 */
	private function getOutputDetails($output_mimetype=null){
		
		switch(strtoupper($output_mimetype)){
			case(strtoupper(self::JPG)):
			case('JPG'):
			case('JPEG'):
				$mimetype = self::JPG;
				$funct = 'imagejpeg';
				$compression = 100;
				break;
			case('PNG'):
			case(strtoupper(self::PNG)):
				$mimetype = self::PNG;
				$funct = 'imagepng';
				$compression = 9;
				break;
			case('GIF'):
			case(strtoupper(self::GIF)):
				$mimetype = self::GIF;
				$funct = 'imagegif';
				$compression = null;
				break;
			default:
				$mimetype = $this->mime;
				$funct = $this->imageFunct;
				$compression = $this->compression;
		}
		return array($mimetype, $funct, $compression);
	}
	
	/**
	 * Convert Hex Colors To color  identifier
	 * @param type $image - image identifier
	 * @param type $hex - the hex color code
	 * @param type $alpha - 0 for solid - 127 for transparent
	 * @return type color identifier
	 * @throws Exception
	 * @ignore
	 */
	private static function getColorFromHex($image, $hex, $alpha=0){
		list($r, $g, $b) = self::hexToRGB($hex);
		
		// The alpha layer seems to make things gritty, 
		// so let's avoid it if there's no transparency
		$return = ($alpha!==0) ? 
			imagecolorallocatealpha($image, $r, $g, $b, $alpha) :
			imagecolorallocate($image, $r, $g, $b) ;
		
		// Make sure it worked
		if($return === false) self::Error("Could not create color $hex.");
		
		return $return;
	}
	
	/**
	 * Inserts linebreaks to wrap text
	 * @param type $fontSize
	 * @param type $fontFace
	 * @param type $string
	 * @param type $width
	 * @return string
	 * @ignore
	 */
	private static function wrapText($fontSize, $fontFace, $string, $width){

		$ret = "";
		$arr = explode(" ", $string);

		foreach($arr as $word){
			$testboxWord = imagettfbbox($fontSize, 0, $fontFace, $word);

			// huge word larger than $width, we need to cut it internally until it fits the width
			$len = strlen($word);
			while($testboxWord[2] > $width && $len > 0){
				$word = substr($word, 0, $len);
				$len--;
				$testboxWord = imagettfbbox($fontSize, 0, $fontFace, $word);
			}

			$teststring = $ret . ' ' . $word;
			$testboxString = imagettfbbox($fontSize, 0, $fontFace, $teststring);
			if($testboxString[2] > $width){
				$ret.=($ret == "" ? "" : "\n") . $word;
			}else{
				$ret.=($ret == "" ? "" : ' ') . $word;
			}
		}

		return $ret;
	}
	
	/**
	 * Returns the line height based on the font and font size
	 * @param type $fontSize
	 * @param type $fontFace
	 * @ignore
	 */
	private static function getLineHeight($fontSize, $fontFace){
		// Arbitrary text is drawn, can't be blank or just a space
		$type_space = imagettfbbox($fontSize, 0, $fontFace, "Robert is awesome!ygj");
		$line_height = abs($type_space[5] - $type_space[1]);
		return $line_height;
	}
	
	/**
	 * Convert image to
	 * @param type $val
	 * @return type
	 * @ignore
	 */
	private static function pixelsToMM($val) {
        return $val * 25.4 / self::$DPI;
    }
	
	/**
	 * Throws an error
	 * @param type $msg
	 * @throws Exception
	 * @ignore
	 */
	private static function Error($msg){
		throw new Exception('Image error: '.$msg);
	}

	/***************************************************************************
	 ******************************* PSD METHODS *******************************
	 **************************************************************************/
	
	/**
	 * Get an image resource from a PSD file
	 * @ignore
	 */
	private static function psdReader($fileName){
		set_time_limit(0);
		self::$psd_infoArray = array();
		self::$psd_fn = $fileName;
		self::$psd_fp = fopen(self::$psd_fn, 'r');
		if(fread(self::$psd_fp, 4) == '8BPS'){
			self::$psd_infoArray['version id'] = self::psd_getInteger(2);
			fseek(self::$psd_fp, 6, SEEK_CUR); // 6 bytes of 0's
			self::$psd_infoArray['channels'] = self::psd_getInteger(2);
			self::$psd_infoArray['rows'] = self::psd_getInteger(4);
			self::$psd_infoArray['columns'] = self::psd_getInteger(4);
			self::$psd_infoArray['colorDepth'] = self::psd_getInteger(2);
			self::$psd_infoArray['colorMode'] = self::psd_getInteger(2);
			self::$psd_infoArray['colorModeDataSectionLength'] = self::psd_getInteger(4);
			fseek(self::$psd_fp, self::$psd_infoArray['colorModeDataSectionLength'], SEEK_CUR); 
			self::$psd_infoArray['imageResourcesSectionLength'] = self::psd_getInteger(4);
			fseek(self::$psd_fp, self::$psd_infoArray['imageResourcesSectionLength'], SEEK_CUR); 
			self::$psd_infoArray['layerMaskDataSectionLength'] = self::psd_getInteger(4);
			fseek(self::$psd_fp, self::$psd_infoArray['layerMaskDataSectionLength'], SEEK_CUR); 
			self::$psd_infoArray['compressionType'] = self::psd_getInteger(2);
			self::$psd_infoArray['oneColorChannelPixelBytes'] = self::$psd_infoArray['colorDepth'] / 8;
			self::$psd_cbLength = self::$psd_infoArray['rows'] * self::$psd_infoArray['columns'] * self::$psd_infoArray['oneColorChannelPixelBytes'];
			if(self::$psd_infoArray['colorMode'] == 2){
				self::$psd_infoArray['error'] = 'images with indexed colours are not supported yet';
				return false;
			}
		}
		else{
			self::$psd_infoArray['error'] = 'invalid or unsupported psd';
			return false;
		}
		
		if(isset(self::$psd_infoArray['error'])) self::Error(self::$psd_infoArray['error']);

		switch(self::$psd_infoArray['compressionType']){
			case 1:
				self::$psd_infoArray['scanLinesByteCounts'] = array();
				for($i = 0; $i < (self::$psd_infoArray['rows'] * self::$psd_infoArray['channels']); $i++) self::$psd_infoArray['scanLinesByteCounts'][] = self::psd_getInteger(2);
				self::$psd_tempname = tempnam(realpath('/tmp'), 'decompressedImageData');
				$tfp = fopen(self::$psd_tempname, 'wb');
				foreach(self::$psd_infoArray['scanLinesByteCounts'] as $scanLinesByteCount){
					fwrite($tfp, self::psd_getPackedBitsDecoded(fread(self::$psd_fp, $scanLinesByteCount)));
				}
				fclose($tfp);
				fclose(self::$psd_fp);
				self::$psd_fp = fopen(self::$psd_tempname, 'r');
			default:
				break;
		}
		$image = imagecreatetruecolor(self::$psd_infoArray['columns'], self::$psd_infoArray['rows']);
		for($rowPointer = 0; ($rowPointer < self::$psd_infoArray['rows']); $rowPointer++){
			for($columnPointer = 0; ($columnPointer < self::$psd_infoArray['columns']); $columnPointer++){
				switch(self::$psd_infoArray['colorMode']){
					case 2:
						exit;
						break;
					case 0:
						if($columnPointer == 0) $bitPointer = 0;
						if($bitPointer == 0) $currentByteBits = str_pad(base_convert(bin2hex(fread(self::$psd_fp, 1)), 16, 2), 8, '0', STR_PAD_LEFT);
						$r = $g = $b = (($currentByteBits[$bitPointer] == '1') ? 0 : 255);
						$bitPointer++;
						if($bitPointer == 8) $bitPointer = 0;
						break;
					case 1:
					case 8:
						$r = $g = $b = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						break;
					case 4:
						$c = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						$currentPointerPos = ftell(self::$psd_fp);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$m = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$y = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$k = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, $currentPointerPos);
						$r = round(($c * $k) / (pow(2, self::$psd_infoArray['colorDepth']) - 1));
						$g = round(($m * $k) / (pow(2, self::$psd_infoArray['colorDepth']) - 1));
						$b = round(($y * $k) / (pow(2, self::$psd_infoArray['colorDepth']) - 1));
						break;
					case 9:
						$l = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						$currentPointerPos = ftell(self::$psd_fp);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$a = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$b = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, $currentPointerPos);
						$r = $l;
						$g = $a;
						$b = $b;
						break;
					default:
						$r = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						$currentPointerPos = ftell(self::$psd_fp);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$g = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, self::$psd_cbLength - 1, SEEK_CUR);
						$b = self::psd_getInteger(self::$psd_infoArray['oneColorChannelPixelBytes']);
						fseek(self::$psd_fp, $currentPointerPos);
						break;
				}
				if((self::$psd_infoArray['oneColorChannelPixelBytes'] == 2)){
					$r = $r >> 8;
					$g = $g >> 8;
					$b = $b >> 8;
				}
				elseif((self::$psd_infoArray['oneColorChannelPixelBytes'] == 4)){
					$r = $r >> 24;
					$g = $g >> 24;
					$b = $b >> 24;
				}
				$pixelColor = imagecolorallocate($image, $r, $g, $b);
				imagesetpixel($image, $columnPointer, $rowPointer, $pixelColor);
			}
		}
		fclose(self::$psd_fp);
		if(isset(self::$psd_tempname)) unlink(self::$psd_tempname);
		return $image;
	}
	
	/**
	 * @ignore
	 */
	private static function psd_getPackedBitsDecoded($string){
		$stringPointer = 0;
		$returnString = '';
		while(1){
			if(isset($string[$stringPointer])) $headerByteValue = self::psd_unsignedToSigned(hexdec(bin2hex($string[$stringPointer])), 1);
			else return $returnString;
			$stringPointer++;
			if($headerByteValue >= 0){
				for($i = 0; $i <= $headerByteValue; $i++){
					$returnString .= $string[$stringPointer];
					$stringPointer++;
				}
			}
			else{
				if($headerByteValue != -128){
					$copyByte = $string[$stringPointer];
					$stringPointer++;
					for($i = 0; $i < (1 - $headerByteValue); $i++){
						$returnString .= $copyByte;
					}
				}
			}
		}
	}
	
	/**
	 * @ignore
	 */
	private static function psd_unsignedToSigned($int, $byteSize = 1){
		switch($byteSize){
			case 1:
				if($int < 128) return $int;
				else return -256 + $int;
				break;
			case 2:
				if($int < 32768) return $int;
				else return -65536 + $int;
			case 4:
				if($int < 2147483648) return $int;
				else return -4294967296 + $int;
			default:
				return $int;
		}
	}
	
	/**
	 * @ignore
	 */
	private static function psd_hexReverse($hex){
		$output = '';
		if(strlen($hex) % 2) return false;
		for($pointer = strlen($hex); $pointer >= 0; $pointer-=2) $output .= substr($hex, $pointer, 2);
		return $output;
	}
	
	/**
	 * @ignore
	 */
	private static function psd_getInteger($byteCount = 1){
		switch($byteCount){
			case 4:
				// for some strange reason this is still broken...
				return @reset(unpack('N', fread(self::$psd_fp, 4)));
				break;

			case 2:
				return @reset(unpack('n', fread(self::$psd_fp, 2)));
				break;

			default:
				return hexdec(self::psd_hexReverse(bin2hex(fread(self::$psd_fp, $byteCount))));
		}
	}

	/***************************************************************************
	 ******************************* PDF METHODS *******************************
	 **************************************************************************/

	/**
	 * @ignore
	 */
	private function pdf_SetMargins($left, $top, $right=null){
		// Set left, top and right margins
		$this->pdf_lMargin = $left;
		$this->pdf_tMargin = $top;
		if($right===null) $right = $left;
		$this->pdf_rMargin = $right;
	}
	
	/**
	 * @ignore
	 */
	private function pdf_SetAutoPageBreak($auto, $margin=0){
		// Set auto page break mode and triggering margin
		$this->pdf_AutoPageBreak = $auto;
		$this->pdf_bMargin = $margin;
		$this->pdf_PageBreakTrigger = $this->pdf_h-$margin;
	}
	
	/**
	 * @ignore
	 */
	private function pdf_SetDisplayMode($zoom, $layout='default'){
		// Set display mode in viewer
		if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
			$this->pdf_ZoomMode = $zoom;
		else
			self::Error('Incorrect zoom display mode: '.$zoom);
		if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
			$this->pdf_LayoutMode = $layout;
		else
			self::Error('Incorrect layout display mode: '.$layout);
	}

	/**
	 * @ignore
	 */
	private function pdf_SetCompression($compress){
		// Set page compression
		if(function_exists('gzcompress'))
			$this->pdf_compress = $compress;
		else $this->pdf_compress = false;
	}

	/**
	 * @ignore
	 */
	private function pdf_AddPage($orientation='', $size='', $rotation=0){
		// Start a new page
		if($this->pdf_state==3) self::Error('The document is closed');
		$lw = $this->pdf_LineWidth;
		$dc = $this->pdf_DrawColor;
		$fc = $this->pdf_FillColor;
		$cf = $this->pdf_ColorFlag;
		if($this->pdf_page>0){
			
			// Page footer
			$this->pdf_InFooter = true;
			$this->pdf_InFooter = false;
			
			// Close page
			$this->pdf_state = 1;
		}
		
		// Start new page
		$this->pdf_beginpage($orientation,$size,$rotation);
		
		// Set line cap style to square
		$this->pdf_out('2 J');
		
		// Set line width
		$this->pdf_LineWidth = $lw;
		$this->pdf_out(sprintf('%.2F w',$lw*$this->pdf_k));
		
		// Set colors
		$this->pdf_DrawColor = $dc;
		if($dc!='0 G') $this->pdf_out($dc);
		$this->pdf_FillColor = $fc;
		if($fc!='0 g') $this->pdf_out($fc);
		$this->pdf_ColorFlag = $cf;
		
		// Page header
		$this->pdf_InHeader = true;
		$this->pdf_InHeader = false;
		
		// Restore line width
		if($this->pdf_LineWidth!=$lw){
			$this->pdf_LineWidth = $lw;
			$this->pdf_out(sprintf('%.2F w',$lw*$this->pdf_k));
		}
		
		// Restore colors
		if($this->pdf_DrawColor!=$dc){
			$this->pdf_DrawColor = $dc;
			$this->pdf_out($dc);
		}
		if($this->pdf_FillColor!=$fc){
			$this->pdf_FillColor = $fc;
			$this->pdf_out($fc);
		}
		$this->pdf_ColorFlag = $cf;
	}

	/**
	 * @ignore
	 */
	private function pdf_Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link=''){
		// Put an image on the page
		if($file=='') self::Error('Image file name is empty');
		if(!isset($this->pdf_images[$file])){
			
			// First use of this image, get info
			if($type==''){
				$pos = strrpos($file,'.');
				if(!$pos) self::Error('Image file has no extension and no type was specified: '.$file);
				$type = substr($file,$pos+1);
			}
			$type = strtolower($type);
			if($type=='jpeg') $type = 'jpg';
			$mtd = 'pdf_parse'.$type;
			if(!method_exists($this,$mtd)) self::Error('Unsupported image type: '.$type);
			$info = $this->$mtd($file);
			$info['i'] = count($this->pdf_images)+1;
			$this->pdf_images[$file] = $info;
		}else $info = $this->pdf_images[$file];

		// Automatic width and height calculation if needed
		if($w==0 && $h==0){
			// Put image at 96 dpi
			$w = -96;
			$h = -96;
		}
		if($w<0) $w = -$info['w']*72/$w/$this->pdf_k;
		if($h<0) $h = -$info['h']*72/$h/$this->pdf_k;
		if($w==0) $w = $h*$info['w']/$info['h'];
		if($h==0) $h = $w*$info['h']/$info['w'];

		// Flowing mode
		if($y===null){
			if($this->pdf_y+$h>$this->pdf_PageBreakTrigger && !$this->pdf_InHeader && !$this->pdf_InFooter && $this->pdf_AcceptPageBreak()){
				// Automatic page break
				$x2 = $this->pdf_x;
				$this->pdf_AddPage($this->pdf_CurOrientation,$this->pdf_CurPageSize,$this->pdf_CurRotation);
				$this->pdf_x = $x2;
			}
			$y = $this->pdf_y;
			$this->pdf_y += $h;
		}
		if($x===null) $x = $this->pdf_x;
		$this->pdf_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->pdf_k,$h*$this->pdf_k,$x*$this->pdf_k,($this->pdf_h-($y+$h))*$this->pdf_k,$info['i']));
		if($link) $this->pdf_Link($x,$y,$w,$h,$link);
	}

	/**
	 * @ignore
	 */
	private function pdf_Output($dest='', $name='', $isUTF8=false){
		// Output PDF to some destination
		// Terminate document
		if($this->pdf_state==3) return;
		if($this->pdf_page==0) $this->pdf_AddPage();
		
		// Page footer
		$this->pdf_InFooter = true;
		$this->pdf_InFooter = false;
		
		// Close page
		$this->pdf_state = 1;
		
		// Close document
		$this->pdf_put('%PDF-'.$this->pdf_PDFVersion);
		$nb = $this->pdf_page;
		for($n=1;$n<=$nb;$n++) $this->pdf_PageInfo[$n]['n'] = $this->pdf_n+1+2*($n-1);
		for($n=1;$n<=$nb;$n++) $this->pdf_putpage($n);
		
		// Pages root
		$this->pdf_newobj(1);
		$this->pdf_put('<</Type /Pages');
		$kids = '/Kids [';
		for($n=1;$n<=$nb;$n++) $kids .= $this->pdf_PageInfo[$n]['n'].' 0 R ';
		$this->pdf_put($kids.']');
		$this->pdf_put('/Count '.$nb);
		if($this->pdf_DefOrientation=='P'){
			$w = $this->pdf_DefPageSize[0];
			$h = $this->pdf_DefPageSize[1];
		}else{
			$w = $this->pdf_DefPageSize[1];
			$h = $this->pdf_DefPageSize[0];
		}
		$this->pdf_put(sprintf('/MediaBox [0 0 %.2F %.2F]',$w*$this->pdf_k,$h*$this->pdf_k));
		$this->pdf_put('>>');
		$this->pdf_put('endobj');
		
		foreach(array_keys($this->pdf_images) as $file){
			$this->pdf_putimage($this->pdf_images[$file]);
			unset($this->pdf_images[$file]['data']);
			unset($this->pdf_images[$file]['smask']);
		}
		
		// Resource dictionary
		$this->pdf_newobj(2);
		$this->pdf_put('<<');
		$this->pdf_put('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
		$this->pdf_put('/Font <<');
		$this->pdf_put('>>');
		$this->pdf_put('/XObject <<');
		foreach($this->pdf_images as $image)
			$this->pdf_put('/I'.$image['i'].' '.$image['n'].' 0 R');
		$this->pdf_put('>>');
		$this->pdf_put('>>');
		$this->pdf_put('endobj');
		
		// Info
		$this->pdf_newobj();
		$this->pdf_put('<<');
		$this->pdf_metadata['Producer'] = 'EasyImage';
		$this->pdf_metadata['CreationDate'] = 'D:'.@date('YmdHis');
		foreach($this->pdf_metadata as $key=>$value)
			$this->pdf_put('/'.$key.' '.$this->pdf_textstring($value));
		$this->pdf_put('>>');
		$this->pdf_put('endobj');
		
		// Catalog
		$this->pdf_newobj();
		$this->pdf_put('<<');
		$n = $this->pdf_PageInfo[1]['n'];
		$this->pdf_put('/Type /Catalog');
		$this->pdf_put('/Pages 1 0 R');
		if($this->pdf_ZoomMode=='fullpage') $this->pdf_put('/OpenAction ['.$n.' 0 R /Fit]');
		elseif($this->pdf_ZoomMode=='fullwidth') $this->pdf_put('/OpenAction ['.$n.' 0 R /FitH null]');
		elseif($this->pdf_ZoomMode=='real') $this->pdf_put('/OpenAction ['.$n.' 0 R /XYZ null null 1]');
		elseif(!is_string($this->pdf_ZoomMode))
			$this->pdf_put('/OpenAction ['.$n.' 0 R /XYZ null null '.sprintf('%.2F',$this->pdf_ZoomMode/100).']');
		if($this->pdf_LayoutMode=='single') $this->pdf_put('/PageLayout /SinglePage');
		elseif($this->pdf_LayoutMode=='continuous') $this->pdf_put('/PageLayout /OneColumn');
		elseif($this->pdf_LayoutMode=='two') $this->pdf_put('/PageLayout /TwoColumnLeft');
		
		$this->pdf_put('>>');
		$this->pdf_put('endobj');
		
		// Cross-ref
		$offset = strlen($this->pdf_buffer);
		$this->pdf_put('xref');
		$this->pdf_put('0 '.($this->pdf_n+1));
		$this->pdf_put('0000000000 65535 f ');
		for($i=1;$i<=$this->pdf_n;$i++)
			$this->pdf_put(sprintf('%010d 00000 n ', $this->pdf_offsets[$i]));
		
		// Trailer
		$this->pdf_put('trailer');
		$this->pdf_put('<<');
		$this->pdf_put('/Size '.($this->pdf_n+1));
		$this->pdf_put('/Root '.$this->pdf_n.' 0 R');
		$this->pdf_put('/Info '.($this->pdf_n-1).' 0 R');
		$this->pdf_put('>>');
		$this->pdf_put('startxref');
		$this->pdf_put($offset);
		$this->pdf_put('%%EOF');
		$this->pdf_state = 3;
		
		if(strlen($name)==1 && strlen($dest)!=1){
			// Fix parameter order
			$tmp = $dest;
			$dest = $name;
			$name = $tmp;
		}
		if($dest=='') $dest = 'I';
		if($name=='') $name = 'doc.pdf';
		switch(strtoupper($dest)){
			case 'I':
				
				// Send to standard output
				$this->pdf_checkoutput();
				if(PHP_SAPI!='cli'){
					// We send to a browser
					header('Content-Type: application/pdf');
					header('Content-Disposition: inline; '.$this->pdf_httpencode('filename',$name,$isUTF8));
					header('Cache-Control: private, max-age=0, must-revalidate');
					header('Pragma: public');
				}
				echo $this->pdf_buffer;
				break;
			case 'D':
				
				// Download file
				$this->pdf_checkoutput();
				header('Content-Type: application/x-download');
				header('Content-Disposition: attachment; '.$this->pdf_httpencode('filename',$name,$isUTF8));
				header('Cache-Control: private, max-age=0, must-revalidate');
				header('Pragma: public');
				echo $this->pdf_buffer;
				break;
			case 'F':
				
				// Save to local file
				if(!file_put_contents($name,$this->pdf_buffer))
					self::Error('Unable to create output file: '.$name);
				break;
			case 'S':
				
				// Return as a string
				return $this->pdf_buffer;
			default:
				self::Error('Incorrect output destination: '.$dest);
		}
		return '';
	}

	/**
	 * @ignore
	 */
	private function pdf_checkoutput(){
		if(PHP_SAPI!='cli'){
			if(headers_sent($file,$line)) self::Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
		}
		if(ob_get_length()){
			
			// The output buffer is not empty
			if(preg_match('/^(\xEF\xBB\xBF)?\s*$/',ob_get_contents())){
				
				// It contains only a UTF-8 BOM and/or whitespace, let's clean it
				ob_clean();
			}
			else self::Error("Some data has already been output, can't send PDF file");
		}
	}

	/**
	 * @ignore
	 */
	private function pdf_getpagesize($size){
		if(is_string($size)){
			$size = strtolower($size);
			if(!isset($this->pdf_StdPageSizes[$size])) self::Error('Unknown page size: '.$size);
			$a = $this->pdf_StdPageSizes[$size];
			return array($a[0]/$this->pdf_k, $a[1]/$this->pdf_k);
		}else{
			if($size[0]>$size[1]) return array($size[1], $size[0]);
			else return $size;
		}
	}

	/**
	 * @ignore
	 */
	private function pdf_beginpage($orientation, $size, $rotation){
		$this->pdf_page++;
		$this->pdf_pages[$this->pdf_page] = '';
		$this->pdf_state = 2;
		$this->pdf_x = $this->pdf_lMargin;
		$this->pdf_y = $this->pdf_tMargin;
		// Check page size and orientation
		if($orientation=='') $orientation = $this->pdf_DefOrientation;
		else $orientation = strtoupper($orientation[0]);
		if($size=='') $size = $this->pdf_DefPageSize;
		else $size = $this->pdf_getpagesize($size);
		if($orientation!=$this->pdf_CurOrientation || $size[0]!=$this->pdf_CurPageSize[0] || $size[1]!=$this->pdf_CurPageSize[1]){
			// New size or orientation
			if($orientation=='P'){
				$this->pdf_w = $size[0];
				$this->pdf_h = $size[1];
			}else{
				$this->pdf_w = $size[1];
				$this->pdf_h = $size[0];
			}
			$this->pdf_wPt = $this->pdf_w*$this->pdf_k;
			$this->pdf_hPt = $this->pdf_h*$this->pdf_k;
			$this->pdf_PageBreakTrigger = $this->pdf_h-$this->pdf_bMargin;
			$this->pdf_CurOrientation = $orientation;
			$this->pdf_CurPageSize = $size;
		}
		if($orientation!=$this->pdf_DefOrientation || $size[0]!=$this->pdf_DefPageSize[0] || $size[1]!=$this->pdf_DefPageSize[1])
			$this->pdf_PageInfo[$this->pdf_page]['size'] = array($this->pdf_wPt, $this->pdf_hPt);
		if($rotation!=0){
			if($rotation%90!=0) self::Error('Incorrect rotation value: '.$rotation);
			$this->pdf_CurRotation = $rotation;
			$this->pdf_PageInfo[$this->pdf_page]['rotation'] = $rotation;
		}
	}

	/**
	 * @ignore
	 */
	private function pdf_isascii($s){
		// Test if string is ASCII
		$nb = strlen($s);
		for($i=0;$i<$nb;$i++){
			if(ord($s[$i])>127) return false;
		}
		return true;
	}

	/**
	 * @ignore
	 */
	private function pdf_httpencode($param, $value, $isUTF8){
		// Encode HTTP header field parameter
		if($this->pdf_isascii($value)) return $param.'="'.$value.'"';
		if(!$isUTF8) $value = utf8_encode($value);
		if(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')!==false)
			return $param.'="'.rawurlencode($value).'"';
		else return $param."*=UTF-8''".rawurlencode($value);
	}

	/**
	 * @ignore
	 */
	private function pdf_textstring($s){
		// Format a text string
		if(!$this->pdf_isascii($s)) $s = $this->pdf_UTF8toUTF16($s);
		
		// Escape for PDF
		$condition = (strpos($s,'(')!==false || strpos($s,')')!==false || strpos($s,'\\')!==false || strpos($s,"\r")!==false);
		$escaped = $condition ? str_replace(array('\\','(',')',"\r"), array('\\\\','\\(','\\)','\\r'), $s) : $s;
		
		return '('.$escaped.')';
	}

	/**
	 * @ignore
	 */
	private function pdf_parsejpg($file){
		// Extract info from a JPEG file
		$a = getimagesize($file);
		if(!$a) self::Error('Missing or incorrect image file: '.$file);
		if($a[2]!=2) self::Error('Not a JPEG file: '.$file);
		if(!isset($a['channels']) || $a['channels']==3) $colspace = 'DeviceRGB';
		elseif($a['channels']==4) $colspace = 'DeviceCMYK';
		else $colspace = 'DeviceGray';
		$bpc = isset($a['bits']) ? $a['bits'] : 8;
		$data = file_get_contents($file);
		return array('w'=>$a[0], 'h'=>$a[1], 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'DCTDecode', 'data'=>$data);
	}

	/**
	 * @ignore
	 */
	private function pdf_parsepng($file){
		// Extract info from a PNG file
		$f = fopen($file,'rb');
		if(!$f) self::Error('Can\'t open image file: '.$file);
		$info = $this->pdf_parsepngstream($f,$file);
		fclose($f);
		return $info;
	}

	/**
	 * @ignore
	 */
	private function pdf_parsepngstream($f, $file){
		// Check signature
		if($this->pdf_readstream($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
			self::Error('Not a PNG file: '.$file);
		
		// Read header chunk
		$this->pdf_readstream($f,4);
		if($this->pdf_readstream($f,4)!='IHDR') self::Error('Incorrect PNG file: '.$file);
		$w = $this->pdf_readint($f);
		$h = $this->pdf_readint($f);
		$bpc = ord($this->pdf_readstream($f,1));
		if($bpc>8) self::Error('16-bit depth not supported: '.$file);
		$ct = ord($this->pdf_readstream($f,1));
		if($ct==0 || $ct==4) $colspace = 'DeviceGray';
		elseif($ct==2 || $ct==6) $colspace = 'DeviceRGB';
		elseif($ct==3) $colspace = 'Indexed';
		else self::Error('Unknown color type: '.$file);
		if(ord($this->pdf_readstream($f,1))!=0)
			self::Error('Unknown compression method: '.$file);
		if(ord($this->pdf_readstream($f,1))!=0)
			self::Error('Unknown filter method: '.$file);
		if(ord($this->pdf_readstream($f,1))!=0)
			self::Error('Interlacing not supported: '.$file);
		$this->pdf_readstream($f,4);
		$dp = '/Predictor 15 /Colors '.($colspace=='DeviceRGB' ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w;
		
		// Scan chunks looking for palette, transparency and image data
		$pal = '';
		$trns = '';
		$data = '';
		do{
			$n = $this->pdf_readint($f);
			$type = $this->pdf_readstream($f,4);
			if($type=='PLTE'){
				
				// Read palette
				$pal = $this->pdf_readstream($f,$n);
				$this->pdf_readstream($f,4);
			}elseif($type=='tRNS'){
				
				// Read transparency info
				$t = $this->pdf_readstream($f,$n);
				if($ct==0) $trns = array(ord(substr($t,1,1)));
				elseif($ct==2)
					$trns = array(ord(substr($t,1,1)), ord(substr($t,3,1)), ord(substr($t,5,1)));
				else{
					$pos = strpos($t,chr(0));
					if($pos!==false) $trns = array($pos);
				}
				$this->pdf_readstream($f,4);
			}elseif($type=='IDAT'){
				
				// Read image data block
				$data .= $this->pdf_readstream($f,$n);
				$this->pdf_readstream($f,4);
			}elseif($type=='IEND') break;
			else $this->pdf_readstream($f,$n+4);
		}while($n);

		if($colspace=='Indexed' && empty($pal))
			self::Error('Missing palette in '.$file);
		$info = array('w'=>$w, 'h'=>$h, 'cs'=>$colspace, 'bpc'=>$bpc, 'f'=>'FlateDecode', 'dp'=>$dp, 'pal'=>$pal, 'trns'=>$trns);
		if($ct>=4){
			
			// Extract alpha channel
			if(!function_exists('gzuncompress'))
				self::Error('Zlib not available, can\'t handle alpha channel: '.$file);
			$data = gzuncompress($data);
			$color = '';
			$alpha = '';
			if($ct==4){
				
				// Gray image
				$len = 2*$w;
				for($i=0;$i<$h;$i++){
					$pos = (1+$len)*$i;
					$color .= $data[$pos];
					$alpha .= $data[$pos];
					$line = substr($data,$pos+1,$len);
					$color .= preg_replace('/(.)./s','$1',$line);
					$alpha .= preg_replace('/.(.)/s','$1',$line);
				}
			}else{
				
				// RGB image
				$len = 4*$w;
				for($i=0;$i<$h;$i++){
					$pos = (1+$len)*$i;
					$color .= $data[$pos];
					$alpha .= $data[$pos];
					$line = substr($data,$pos+1,$len);
					$color .= preg_replace('/(.{3})./s','$1',$line);
					$alpha .= preg_replace('/.{3}(.)/s','$1',$line);
				}
			}
			unset($data);
			$data = gzcompress($color);
			$info['smask'] = gzcompress($alpha);
			$this->pdf_WithAlpha = true;
			if($this->pdf_PDFVersion<'1.4') $this->pdf_PDFVersion = '1.4';
		}
		$info['data'] = $data;
		return $info;
	}

	/**
	 * @ignore
	 */
	private function pdf_readstream($f, $n){
		// Read n bytes from stream
		$res = '';
		while($n>0 && !feof($f)){
			$s = fread($f,$n);
			if($s===false)
				self::Error('Error while reading stream');
			$n -= strlen($s);
			$res .= $s;
		}
		if($n>0) self::Error('Unexpected end of stream');
		return $res;
	}

	/**
	 * @ignore
	 */
	private function pdf_readint($f){
		// Read a 4-byte integer from stream
		$a = unpack('Ni',$this->pdf_readstream($f,4));
		return $a['i'];
	}

	/**
	 * @ignore
	 */
	private function pdf_parsegif($file){
		// Extract info from a GIF file (via PNG conversion)
		if(!function_exists('imagepng'))
			self::Error('GD extension is required for GIF support');
		if(!function_exists('imagecreatefromgif'))
			self::Error('GD has no GIF read support');
		$im = imagecreatefromgif($file);
		if(!$im) self::Error('Missing or incorrect image file: '.$file);
		imageinterlace($im,0);
		ob_start();
		imagepng($im);
		$data = ob_get_clean();
		imagedestroy($im);
		$f = fopen('php://temp','rb+');
		if(!$f) self::Error('Unable to create memory stream');
		fwrite($f,$data);
		rewind($f);
		$info = $this->pdf_parsepngstream($f,$file);
		fclose($f);
		return $info;
	}

	/**
	 * @ignore
	 */
	private function pdf_out($s){
		// Add a line to the document
		if($this->pdf_state==2) $this->pdf_pages[$this->pdf_page] .= $s."\n";
		elseif($this->pdf_state==1) $this->pdf_put($s);
		elseif($this->pdf_state==0) self::Error('No page has been added yet');
		elseif($this->pdf_state==3) self::Error('The document is closed');
	}

	/**
	 * @ignore
	 */
	private function pdf_put($s){
		$this->pdf_buffer .= $s."\n";
	}

	/**
	 * @ignore
	 */
	private function pdf_newobj($n=null){
		// Begin a new object
		if($n===null) $n = ++$this->pdf_n;
		$this->pdf_offsets[$n] = strlen($this->pdf_buffer);
		$this->pdf_put($n.' 0 obj');
	}

	/**
	 * @ignore
	 */
	private function pdf_putstreamobject($data){
		if($this->pdf_compress){
			$entries = '/Filter /FlateDecode ';
			$data = gzcompress($data);
		}
		else $entries = '';
		$entries .= '/Length '.strlen($data);
		$this->pdf_newobj();
		$this->pdf_put('<<'.$entries.'>>');
		$this->pdf_put('stream');
		$this->pdf_put($data);
		$this->pdf_put('endstream');
		$this->pdf_put('endobj');
	}

	/**
	 * @ignore
	 */
	private function pdf_putpage($n){
		$this->pdf_newobj();
		$this->pdf_put('<</Type /Page');
		$this->pdf_put('/Parent 1 0 R');
		if(isset($this->pdf_PageInfo[$n]['size']))
			$this->pdf_put(sprintf('/MediaBox [0 0 %.2F %.2F]',$this->pdf_PageInfo[$n]['size'][0],$this->pdf_PageInfo[$n]['size'][1]));
		if(isset($this->pdf_PageInfo[$n]['rotation']))
			$this->pdf_put('/Rotate '.$this->pdf_PageInfo[$n]['rotation']);
		$this->pdf_put('/Resources 2 0 R');
		if(isset($this->pdf_PageLinks[$n])){
			// Links
			$annots = '/Annots [';
			foreach($this->pdf_PageLinks[$n] as $pl){
				$rect = sprintf('%.2F %.2F %.2F %.2F',$pl[0],$pl[1],$pl[0]+$pl[2],$pl[1]-$pl[3]);
				$annots .= '<</Type /Annot /Subtype /Link /Rect ['.$rect.'] /Border [0 0 0] ';
				if(is_string($pl[4]))
					$annots .= '/A <</S /URI /URI '.$this->pdf_textstring($pl[4]).'>>>>';
				else{
					$l = $this->pdf_links[$pl[4]];
					if(isset($this->pdf_PageInfo[$l[0]]['size']))
						$h = $this->pdf_PageInfo[$l[0]]['size'][1];
					else
						$h = ($this->pdf_DefOrientation=='P') ? $this->pdf_DefPageSize[1]*$this->pdf_k : $this->pdf_DefPageSize[0]*$this->pdf_k;
					$annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>',$this->pdf_PageInfo[$l[0]]['n'],$h-$l[1]*$this->pdf_k);
				}
			}
			$this->pdf_put($annots.']');
		}
		if($this->pdf_WithAlpha)
			$this->pdf_put('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
		$this->pdf_put('/Contents '.($this->pdf_n+1).' 0 R>>');
		$this->pdf_put('endobj');
		
		// Page content
		if(!empty($this->pdf_AliasNbPages))
			$this->pdf_pages[$n] = str_replace($this->pdf_AliasNbPages,$this->pdf_page,$this->pdf_pages[$n]);
		$this->pdf_putstreamobject($this->pdf_pages[$n]);
	}

	/**
	 * @ignore
	 */
	private function pdf_putimage(&$info){
		$this->pdf_newobj();
		$info['n'] = $this->pdf_n;
		$this->pdf_put('<</Type /XObject');
		$this->pdf_put('/Subtype /Image');
		$this->pdf_put('/Width '.$info['w']);
		$this->pdf_put('/Height '.$info['h']);
		if($info['cs']=='Indexed')
			$this->pdf_put('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->pdf_n+1).' 0 R]');
		else{
			$this->pdf_put('/ColorSpace /'.$info['cs']);
			if($info['cs']=='DeviceCMYK') $this->pdf_put('/Decode [1 0 1 0 1 0 1 0]');
		}
		$this->pdf_put('/BitsPerComponent '.$info['bpc']);
		if(isset($info['f'])) $this->pdf_put('/Filter /'.$info['f']);
		if(isset($info['dp'])) $this->pdf_put('/DecodeParms <<'.$info['dp'].'>>');
		if(isset($info['trns']) && is_array($info['trns'])){
			$trns = '';
			for($i=0;$i<count($info['trns']);$i++)
				$trns .= $info['trns'][$i].' '.$info['trns'][$i].' ';
			$this->pdf_put('/Mask ['.$trns.']');
		}
		if(isset($info['smask'])) $this->pdf_put('/SMask '.($this->pdf_n+1).' 0 R');
		$this->pdf_put('/Length '.strlen($info['data']).'>>');
		$this->pdf_put('stream');
		$this->pdf_put($info['data']);
		$this->pdf_put('endstream');
		$this->pdf_put('endobj');
		
		// Soft mask
		if(isset($info['smask'])){
			$dp = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns '.$info['w'];
			$smask = array('w'=>$info['w'], 'h'=>$info['h'], 'cs'=>'DeviceGray', 'bpc'=>8, 'f'=>$info['f'], 'dp'=>$dp, 'data'=>$info['smask']);
			$this->pdf_putimage($smask);
		}
		
		// Palette
		if($info['cs']=='Indexed') $this->pdf_putstreamobject($info['pal']);
	}
	
}
