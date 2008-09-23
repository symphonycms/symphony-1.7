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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Fatal Error</h2><p>You cannot directly access this file</p>");
	
	$ignore = array("events", "data-sources", "text-formatters", "pages", "masters", "utilities");
	$directories = General::listDirStructure(WORKSPACE, true, "asc", DOCROOT);

	$xml->addChild(new XMLElement("path", "workspace/"));

	foreach($directories as $d){
		if(!in_array($d, $ignore)){
			$xml->addChild(new XMLElement("path", ltrim($d, '/')));
		}
	}	
?>