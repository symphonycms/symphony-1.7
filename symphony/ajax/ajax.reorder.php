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

	$table = null;
	$cache_sections = array($_REQUEST['handle']);

	switch(strtolower($_REQUEST['handle'])){

		case "pages":
			$table = "tbl_pages";
			break;

		case "customfields":
			$table = "tbl_customfields";
			break;

		case "sections":
			$table = "tbl_sections";
			break;

	}


	if($table != NULL && is_array($_REQUEST['items']) && !empty($_REQUEST['items'])){

		require_once(LIBRARY . "/core/class.cacheable.php");
		$cache = new Cacheable(array("db" => $db));

		foreach($_REQUEST['items'] as $id => $val){
			$sql = "UPDATE $table SET `sortorder` = '$val' WHERE `id` = $id";

			if($db->query($sql))
				$xml->setAttribute("success", "true");

			else
				$xml->setAttribute("success", "false");

		}

		$cache->flush_cache($cache_sections);

	}else
		$xml->setAttribute("success", "false");

?>