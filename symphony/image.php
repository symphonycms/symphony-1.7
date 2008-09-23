<?php

	###
	#
	#  Symphony web publishing system
	# 
	#  Copyright 2004 - 2006 Twenty One Degrees Pty. Ltd. This code cannot be
	#  modified or redistributed without permission.
	#
	#  For terms of use please visit http://21degrees.com.au/products/symphony/terms/
	#
	###

	##We dont need to instanicate the entire Symphony engine so set boot to minimal
	define("__SYMPHONY_MINIMAL_BOOT__", true); 
	
	##Include some parts of the engine
	require_once('../manifest/config.php');
	
	include(LIBRARY . "/core/class.utilities.php");
	include(TOOLKIT . "/class.image.php");
	
	$cache = ($settings['image']['cache'] == '1' ? true : false);
	$quality = intval($settings['image']['quality']);
	$crop = false;
	
	if($_REQUEST['external']){
	    $file = $_REQUEST['_f'];
	    
	    $actual_name = basename($_REQUEST['_f']);	    
	    $cacheFile = CACHE . '/' . md5($_SERVER['REQUEST_URI'] . $quality . date("YmdH")) . "_" . $actual_name;

	    if(!@is_file($cacheFile)){
	    
		    if(!$tmp = @file_get_contents("http://" . $file)){
				header("HTTP/1.0 404 Not Found");
		    	die("<h1>Symphony Fatal Error</h1><p>404 File Not Found - The requested Image '<strong>http://" . $file . "</strong>' could not be found.<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
			}
			
		    if(!@file_put_contents(TMP . '/' . md5("tmp.$file"), $tmp))
		    	die("<h1>Symphony Fatal Error</h1><p>Temporary file for '<strong>http://" . $file . "</strong>' could not be created.<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
		    
		    $file = TMP . '/' . md5("tmp.$file");
	
   	 	}else
	    	$file = $cacheFile;
	    
	
	}else{
		$file  = WORKSPACE . '/' . $_REQUEST['_f'];	
		$actual_name = basename($file);
		$cacheFile = CACHE . '/' . md5($_SERVER['REQUEST_URI'] . $quality . date("YmdH")) . "_" . $actual_name;
		
		if(!@is_file($file)){
			header("HTTP/1.0 404 Not Found");
			die("<h1>Symphony Fatal Error</h1><p>404 File Not Found - The requested Image '<strong>".$_REQUEST['_f']."</strong>' was not found on this server.<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
		}
	}
		
	if(!$info = Image::getMeta($file))
		die("<h1>Symphony Fatal Error</h1><p>The requested Image '<strong>$file</strong>' could not be read.<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
	
	if($_REQUEST['width'] == 0) unset($_REQUEST['width']);
	if($_REQUEST['height'] == 0) unset($_REQUEST['height']);
	if($_REQUEST['crop'] == 0) unset($_REQUEST['crop']);
	
	if(strlen($_REQUEST['bg']) != 6 && strlen($_REQUEST['bg']) != 3) $_REQUEST['bg'] = "FFFFFF";	
	elseif(strlen($_REQUEST['bg']) == 3){		
		$_REQUEST['bg'] = substr($bg, 0, 1) . substr($bg, 0, 1) . substr($bg, 1, 1) . substr($bg, 1, 1) . substr($bg, 2, 1) . substr($bg, 2, 1);
	}
	
	if(isset($_REQUEST['crop'])) $crop = $_REQUEST['crop'];
	if(!isset($_REQUEST['width'])) $width = $info['width']; else $width = $_REQUEST['width'];
	if(!isset($_REQUEST['height'])) $height = $info['height']; else $height = $_REQUEST['height'];
	
	if(isset($_REQUEST['width']) && $_REQUEST['height'] == ""){
	    $width = $_REQUEST['width'];
	    $height = $height * ($_REQUEST['width'] * (1 / $info['width']));
	}
	
	if(isset($_REQUEST['height']) && $_REQUEST['width'] == ""){
	    $height = $_REQUEST['height'];
	    $width = $width * ($_REQUEST['height'] * (1 / $info['height']));
	}
	
	if($width == $info['width'] && $height == $info['height']) {
		switch($info['type']) {
			
			## GIF
			case IMG_GIF:
				header("Content-type: image/gif");
				break;
				
			## JPEG	
			case IMG_JPEG:
				header("Content-type: image/jpeg");
				break;
				
			## PNG	
			case IMG_PNG:
				header("Content-type: image/png");
				break;
				
			## Other	
			case IMG_XPM:
				header("Content-type: application/x-shockwave-flash");
				break;
				
			## Windows BMP	
			case IMG_WBMP:
				header("Content-type: image/bmp");
				break;				
		}
		
		print file_get_contents($file);
		exit();
		
	} else {	
		
		if($cache) {
			
			if(!@is_file($cacheFile)) {
				
				if(!$image = new Image($file, $quality))
					die("<h1>Symphony Fatal Error</h1><p>Error creating Image in memory<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
			
				if($crop) $image->scaleSpace($width, $height, $_REQUEST['bg']);
				else $image->scale($width, $height, $_REQUEST['bg']);
				
				$image->renderOutputHeader($actual_name);
				$image->render($cacheFile);
				
				print file_get_contents($cacheFile);
				
			}else{
				
				if(!$image = new Image($cacheFile, $quality))
					die("<h1>Symphony Fatal Error</h1><p>Error opening cached image<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
					
				$image->renderOutputHeader($actual_name);
				$image->render();
				
			}			
			
		} else {		

			if(!$image = new Image($file, $quality))
				die("<h1>Symphony Fatal Error</h1><p>Error creating Image in memory<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em>");
		
			if($crop) $image->scaleSpace($width, $height, $_REQUEST['bg']);
			else $image->scale($width, $height, $_REQUEST['bg']);
			
			$image->renderOutputHeader($actual_name);
			$image->render();
				
		}
		
		$image->close();
		unset($image);
		exit();
						
	}
	
?>