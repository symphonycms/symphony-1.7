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
	
	$sql = "SELECT data_sources as `master_data_sources`, events as `master_events`
			FROM `tbl_masters` 
			WHERE `id` = '".$_REQUEST['master']."' LIMIT 1";

	$master_data_sources = $db->fetchVar('master_data_sources', 0, $sql);	
	$master_data_sources = preg_split('/,/', $master_data_sources, -1, PREG_SPLIT_NO_EMPTY);
	$master_data_sources = array_map("trim", $master_data_sources);	

	$master_events = $db->fetchVar('master_events', 0, $sql);	
	$master_events = preg_split('/,/', $master_events, -1, PREG_SPLIT_NO_EMPTY);
	$master_events = array_map("trim", $master_events);
	
	$datasources = preg_split('/,/', $_REQUEST['datasources'], -1, PREG_SPLIT_NO_EMPTY);
	$datasources = array_map("trim", $datasources);	
	
	$events = preg_split('/,/', $_REQUEST['events'], -1, PREG_SPLIT_NO_EMPTY);
	$events = array_map("trim", $events);	
	
	$datasources = array_merge($datasources, $master_data_sources);
	$events = array_merge($events, $master_events);
							
	$utilities = $db->fetch("SELECT DISTINCT t1.* 
							 FROM `tbl_utilities` as t1
							 LEFT JOIN `tbl_utilities2datasources` as t2 ON t1.id = t2.utility_id
							 LEFT JOIN `tbl_utilities2events` as t3 ON t1.id = t3.utility_id
							 WHERE (t2.`data_source` IS NULL AND t3.`event` IS NULL)
							 OR (t2.`data_source` IN ('".@implode("', '", $datasources)."') 
							 OR t3.`event` IN ('".@implode("', '", $events)."'))");	


	foreach($utilities as $u){
		$utility = new XMLElement("utility");
		$utility->addChild(new XMLElement("name", $u['name']));
		$utility->addChild(new XMLElement("link", URL . "/symphony/?page=/blueprint/utilities/edit/&amp;id=" . $u['id']));
		
		$xml->addChild($utility);
	}	
?>