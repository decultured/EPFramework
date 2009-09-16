<?php

function image_to_jpeg($sourceImg) {
	$info = getimagesize($sourceImg);
		
	$imgIn = null;
	switch($info['mime']) {
		case 'image/jpeg':
			return true;
		break;
		case 'image/gif':
			$imgIn = imagecreatefromgif($sourceImg);
		break;
		case 'image/png':
			$imgIn = imagecreatefrompng($sourceImg);
		break;
		case 'image/wbmp':
			$imgIn = imagecreatefromwbmp($sourceImg);
		break;
		default:
			return false;
		break;
	}
	
	if($imgIn != null) {
		// Output
		imagejpeg($imgIn, $sourceImg, 100);
		imagedestroy($imgIn);
		
		return true;
	}
	
	return false;
}

/*
	image_resize()
		- utility function for resizing images that have been uploaded.	 can be used to make thumbnails.
		
		- returns the resized image.
*/
function image_resize($sourceImg, $width, $height, $output_to_disk = true) {
	$size = getimagesize($sourceImg);
	
	if($size[0] <= $width && $size[1] <= $height) {
		return;
	}
	
	$imgIn = null;
	
	switch($size['mime']) {
		case 'image/jpeg':
			$imgIn = imagecreatefromjpeg($sourceImg);
		break;
		case 'image/gif':
			$imgIn = imagecreatefromgif($sourceImg);
		break;
		case 'image/png':
			$imgIn = imagecreatefrompng($sourceImg);
		break;
		case 'image/wbmp':
			$imgIn = imagecreatefromwbmp($sourceImg);
		break;
	}
	
	if($imgIn != null) {
		// Get new dimensions
		list($width_orig, $height_orig) = getimagesize($sourceImg);

		$ratio_orig = $width_orig/$height_orig;
		$resized_height = $width/$ratio_orig;

		// Resample
		$image_p = imagecreatetruecolor($width, $resized_height);
		imagecopyresampled($image_p, $imgIn, 0, 0, 0, 0, $width, $resized_height, $width_orig, $height_orig);

		// Output
		if($output_to_disk == true) {
			imagejpeg($image_p, $sourceImg, 100);
		} else {
			imagejpeg($image_p, NULL, 95);
		}
		imagedestroy($image_p);
		imagedestroy($imgIn);
	}
}

function image_watermark($source_image, $string, $font_path = 'arial.ttf') {
	
}

/*
 Decodes an image encoded as a base64 data URL and saves it in $filename
*/
function image_from_base64($data, $filename) {
	$file = fopen($filename, 'w');
	if(!$file) {
		return false;
	}

	fwrite($file, base64_decode(substr($data, strpos($data, ',') + 1)));	

	fclose($file);
}

?>
