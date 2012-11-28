<?php

	/***
	 *
	 * Symphony web publishing system
	 *
	 * Copyright 2004–2006 Twenty One Degrees Pty. Ltd.
	 *
	 * @version 1.7
	 * @licence https://github.com/symphonycms/symphony-1.7/blob/master/LICENCE
	 *
	 ***/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

	Class Image {

		var $_size;
		var $_reference;
		var $_rendered;
		var $_quality;
		var $_interlaced;
		var $_outputType;

		function Image($file, $quality=80, $output=NULL){

			$this->_meta = $this->getMeta($file);
			$this->setQuality($quality);
			$this->setInterlaced(false);
			$this->setOutputType(($output ? $output : $this->_meta['type']));

			$this->_rendered = NULL;

			switch($this->_meta['type']){

				## GIF
				case IMG_GIF:
					$this->_reference = imagecreatefromgif($file);
					break;

				## JPEG
				case IMG_JPEG:

					if($this->_meta['channels'] <= 3)
						$this->_reference = imagecreatefromjpeg($file);

					## Cant handle CMYK JPEG files
					else
						return(false);

					break;

				## PNG
				case IMG_PNG:
					$this->_reference = imagecreatefrompng($file);
					break;

				default:
					return(false);
					break;
			}
		}

		function close(){
			imagedestroy($this->_reference);
		}

		function setQuality($quality){
			$quality = min(100, intval($quality));
			$this->_quality = max(0, $quality);
		}

		function setInterlaced($val){
			$this->_interlaced = $val;
		}

		function setOutputType($type){

			## Make sure the requested type is supported
			$supported = $this->getSupportedImageTypes();

			if(!in_array($type, $supported)) $type = $supported[0];

			$this->_outputType = $type;
		}

		function crop($width, $height){

			$dst   = imagecreatetruecolor($width, $height);
			$dst_w = $this->_meta['width'];
			$dst_h = $this->_meta['height'];

			$ratio = $dst_w / $dst_h;

			while($dst_w > $width && $dst_h > $height){
				$dst_h--;
				$dst_w = ceil($dst_h * $ratio);
			}

			$dst_x = floor($width - $dst_w) / 2;
			$dst_y = floor($height - $dst_h) / 2;

			imagecopyresampled($dst, $this->_reference, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $this->_meta['width'], $this->_meta['height']);

			$this->__copyToReference($dst, $width, $height);
			imagedestroy($dst);

		}


		function scaleSpace($width, $height, $bg="FFFFFF"){
			$dst   = imagecreatetruecolor($width, $height);
			$dst_w = $this->_meta['width'];
			$dst_h = $this->_meta['height'];
			imagefill($dst, 0, 0, imagecolorallocate($dst, hexdec(substr($bg, 0, 2)), hexdec(substr($bg, 2, 2)), hexdec(substr($bg, 4, 2))));

			$ratio = $dst_w / $dst_h;
			$dst_h = $height;
			$dst_w = round($height * $ratio);
			$dst_x = floor($width - $dst_w) / 2;
			$dst_y = floor($height - $dst_h) / 2;

			imagecopyresampled($dst, $this->_reference, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $this->_meta['width'], $this->_meta['height']);

			$this->__copyToReference($dst, $width, $height);
			imagedestroy($dst);

		}

		function scale($width=false, $height=false, $bg="FFFFFF"){
			$dst_w = $this->_meta['width'];
			$dst_h = $this->_meta['height'];

			if(empty($bg))
				$bg = "FFFFFF";

			if(!empty($width) && !empty($height)) {
				$dst_w = $width;
				$dst_h = $height;

			} elseif(!empty($width)) {
				$ratio = $dst_h / $dst_w;
				$dst_w = $width;
				$dst_h = round($dst_w * $ratio);

			} elseif(!empty($height)) {
				$ratio = $dst_w / $dst_h;
				$dst_h = $height;
				$dst_w = round($dst_h * $ratio);
			}

			$dst = imagecreatetruecolor($dst_w, $dst_h);
			imagefill($dst, 0, 0, imagecolorallocate($dst, hexdec(substr($bg, 0, 2)), hexdec(substr($bg, 2, 2)), hexdec(substr($bg, 4, 2))));
			imagecopyresampled($dst, $this->_reference, 0, 0, 0, 0, $dst_w, $dst_h, $this->_meta['width'], $this->_meta['height']);

			$this->__copyToReference($dst, $dst_w, $dst_h);
			imagedestroy($dst);

		}

		function __copyToReference($tmp, $w, $h){
			$this->_reference = imagecreatetruecolor($w, $h);
			imagecopy($this->_reference, $tmp, 0, 0, 0, 0, $w, $h);
		}

		function __makeColor($hex, $dst){
			return(imagecolorallocate($dst, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))));
		}

		function render($file=NULL){

			if(empty($file)) $file = NULL;

			$im = $this->_reference;

			## Turn interlacing on for JPEG or PNG only
			if($this->_interlaced && ($this->_outputType == IMG_JPEG || $this->_outputType == IMG_PNG)){
				imageinterlace($im);
			}

			switch($this->_outputType){

				case IMG_GIF:
					if(!$file) imagegif($im);
					else imagegif($im, $file);
					break;

				case IMG_PNG:
					if(!$file) imagepng($im);
					else imagepng($im, $file);
					break;

				case IMG_JPEG:
				default:
					imagejpeg($im, $file, $this->_quality);
					break;
			}
		}

		function renderOutputHeader($filename=NULL){

			switch($this->_outputType){

				case IMG_GIF:
					header("Content-Type: image/gif");
					$extension = '.gif';
					break;

				case IMG_PNG:
					header("Content-Type: image/png");
					$extension = '.png';
					break;

				case IMG_JPEG:
				default:
					header("Content-Type: image/jpeg");
					$extension = '.jpg';
					break;
			}

			if(!$filename) return;

			$ext = strrchr($filename, '.');
			if($ext !== false){
				$filename = substr($filename, 0, -strlen($ext));
			}

			header("Content-Disposition: inline; filename=$filename$extension");

		}

		function getMeta($file){
			if(!$array = @getimagesize($file)) return false;

			$types = array(
		   		1 => IMG_GIF,
			   	2 => IMG_JPG,
			   	3 => IMG_PNG,
			   	4 => IMG_XPM,
			   	8 => IMG_WBMP
			);

			$meta = array();

			$meta['width']    = $array[0];
			$meta['height']   = $array[1];
			$meta['type']     = $types[$array[2]];
			$meta['channels'] = $array['channels'];

			return $meta;
		}

		## Thank to 'enyo' http://au2.php.net/manual/en/function.imagetypes.php
		function getSupportedImageTypes() {
		   $aSupportedTypes = array();

		   $aPossibleImageTypeBits = array(
		   	   IMG_JPG,
		       IMG_GIF,
		       IMG_PNG,
		       IMG_WBMP
		   );

		   foreach ($aPossibleImageTypeBits as $iImageTypeBits) {
		       if (imagetypes() & $iImageTypeBits) {
		           $aSupportedTypes[] = $iImageTypeBits;
		       }
		   }

		   return $aSupportedTypes;
		}



	}
?>