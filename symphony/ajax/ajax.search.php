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

	$cDate = new SymDate($settings["region"]["time_zone"], $settings["region"]["date_format"]);

	##LIVE SEARCH PROCESSING

    $searchstring = mysql_escape_string($_REQUEST['query']);

	$fields = $db->fetchCol('id', "SELECT id FROM `tbl_customfields`");

	$can_access = $Author->get('allow_sections');

    $mode = ($_GET['mode'] ? $_GET['mode'] : "normal");

	switch($mode){

		case "simple":
			$sql = "SELECT DISTINCT t1.entry_id
                FROM tbl_entries2customfields AS t1
			    LEFT JOIN `tbl_entries` AS t2 ON t1.entry_id = t2.id
			    LEFT JOIN `tbl_entries2sections` AS t3 on t2.id = t3.entry_id
                WHERE t1.value LIKE '%$searchstring%'
				".($Author->get('superuser') != 1 ? " AND t3.section_id IN ($can_access)" : '')."
                ORDER BY t2.publish_date_gmt DESC LIMIT 5";
      			break;

      	case "normal":

	      	$sql = "SELECT DISTINCT t1.entry_id,
				  MATCH(t1.value) AGAINST ('$searchstring') AS score
				  FROM tbl_entries2customfields AS t1
	              LEFT JOIN `tbl_entries` AS t2 ON t1.entry_id = t2.id
			      LEFT JOIN `tbl_entries2sections` AS t3 on t2.id = t3.entry_id
	              WHERE 1 AND MATCH(t1.value) AGAINST ('$searchstring')
				  ".($Author->get('superuser') != 1 ? " AND t3.section_id IN ($can_access)" : '')."
				  ORDER BY score DESC LIMIT 5";

      			break;

      	case "boolean":
 	     	$sql = "SELECT DISTINCT t1.entry_id,
				  MATCH(t1.value) AGAINST ('$searchstring' IN BOOLEAN MODE) AS score
				  FROM tbl_entries2customfields AS t1
	              LEFT JOIN `tbl_entries` AS t2 ON t1.entry_id = t2.id
			      LEFT JOIN `tbl_entries2sections` AS t3 on t2.id = t3.entry_id
	              WHERE 1 AND MATCH(t1.value) AGAINST ('$searchstring' IN BOOLEAN MODE)
				  ".($Author->get('superuser') != 1 ? " AND t3.section_id IN ($can_access)" : '')."
				  ORDER BY score DESC LIMIT 5";

      			break;
    }

	$result = $db->fetchCol('entry_id', $sql);

	$result = array_flip($result);
	$result = array_flip($result);

	if(@count($result) >= 1) {

		$parent =& new ParentShell($db, $config);

		include_once(LIBRARY . "/core/class.manager.php");
		include_once(LIBRARY . "/core/class.symphonylog.php");
		include_once(LIBRARY . "/core/class.textformattermanager.php");
		include_once(TOOLKIT . "/class.entrymanager.php");

		$entryManager = new EntryManager($parent);

		foreach($result as $entry_id){

			$row = $entryManager->fetchEntriesByID($entry_id, false, true);

        	$locked = 'content';

			##Generate the XML
			$entry = new XMLElement("item");
			$entry->setAttribute("class", $locked);

			$entry->addChild(new XMLElement("title", strip_tags($row['fields'][$row['primary_field']]['value'])));
			$entry->addChild(new XMLElement("date", $cDate->get(true, true, strtotime($row['publish_date_gmt']))));
			$entry->addChild(new XMLElement("link", "?page=/publish/section/edit/&amp;_sid=".$row['section_id']."&amp;id=" . $row['id']));
			$entry->addChild(new XMLElement("handle", $row['primary_field']));
			if(isset($row['fields']['body']) && $row['fields']['body']['type'] == 'textarea') $entry->addChild(new XMLElement("description",  General::limitWords(strip_tags($row['fields']['body']['value']), 100, true, false)));

			$xml->addChild($entry);

		}
	}

?>