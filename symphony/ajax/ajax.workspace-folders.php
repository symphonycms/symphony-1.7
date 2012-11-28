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