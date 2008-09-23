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

	print '<?xml version="1.0" encoding="utf-8"?>'; 
	
	$date = new SymDate($Admin->getConfigVar("time_zone", "region"), $Admin->getConfigVar("date_format", "region"));	
	
	$GLOBALS['pageTitle'] = "Activity Logs";
	
	$date = new SymDate($Admin->getConfigVar("time_zone", "region"), $Admin->getConfigVar("date_format", "region"));	
		
	$log = array();
	
	if(@is_file(LOGS . "/" . $_REQUEST["_l"] . ".log"))
		$log = General::str2array(@file_get_contents(LOGS . "/" . $_REQUEST["_l"] . ".log"), false);
		
	else
		General::redirect("?page=/settings/logs/");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
	<title>Symphony &ndash; <?php print $GLOBALS['pageTitle']; ?></title>
	<link rel="stylesheet" type="text/css" media="screen" href="assets/debug.css" />
	<script type="text/javascript" src="assets/main.js"></script>
</head>

	<body id="view">
		<h1><?php print $date->get(true, true, strtotime($_REQUEST["_l"])); ?></h1>


			<ol id="xml">
<?php 	
			foreach($log as $line){
				print "				<li><code>$line</code></li>\n";
			}
?>
			</ol>


	</body>
</html>
<?php exit(); ?>