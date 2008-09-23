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

	error_reporting(E_ALL ^ E_NOTICE);
	set_magic_quotes_runtime(0);

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
		
	require_once(DOCROOT . "/symphony/lib/boot/defines.php");
	require_once(LIBRARY . "/func.librarian.php");
			
	##Decide the boot mode. Minimal WILL NOT instanciate the entire engine.
	if(!defined("__SYMPHONY_MINIMAL_BOOT__") || !__SYMPHONY_MINIMAL_BOOT__){

		##Load all the core library files
		Librarian(LIBRARY . "/boot", array("config.php", "defines.php", "bundle.php")); 
		Librarian(LIBRARY . "/core", null, array("class.manager.php", "class.template.php", "class.log.php", "class.cacheable.php")); 
		
		//Start the Log process.
		$symLog = new SymphonyLog(array_merge($settings, array("log_path" => LOGS . "/" . date("Ymd", (time() - (date("Z") - (date("I") * 3600)))) . ".log")));

		if (get_magic_quotes_gpc()) {
			General::cleanArray($_SERVER);
			General::cleanArray($_COOKIE);
			General::cleanArray($_GET);
			General::cleanArray($_POST);	
		}		
		
	}
		
?>
