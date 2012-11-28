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

	$cDate = new SymDate($settings["region"]["time_zone"], "d");

	$month = (isset($_REQUEST['month']) || $_REQUEST['month'] != "" ? $_REQUEST['month'] : date("F", time()));
	$year = (isset($_REQUEST['year']) || $_REQUEST['year'] != "" ? $_REQUEST['year'] : date("Y", time()));


	$startdate = strtotime("1 " . $month . " " . $year);
	$enddate = mktime(0, 0, 0, date("m", $startdate) + 1, 1, $year);

	$sql = "SELECT t1.*, t2.section_id, t3.value_raw as `title`,
			UNIX_TIMESTAMP(t1.publish_date_gmt) as `timestamp_gmt`
			FROM `tbl_entries` as t1, `tbl_sections` as t4, `tbl_entries2sections` as t2, `tbl_entries2customfields` as t3
			WHERE UNIX_TIMESTAMP(t1.publish_date) >= '$startdate'
			AND UNIX_TIMESTAMP(t1.publish_date) <= '$enddate'
			AND t1.`id` = t2.entry_id
			AND t1.`id` = t3.entry_id AND t4.primary_field = t3.field_id
			AND t2.section_id = t4.id
			ORDER BY t1.publish_date DESC ";

	$result = $db->fetch($sql);

	$xml->addChild(new XMLElement("month", $month . " " . $year));

	if(@count($result) >= 1) {
		$final = array();

		foreach($result as $row){
			if($Author->canAccessSection($row['section_id'])){
				$final[$cDate->get(true, true, $row['timestamp_gmt'])][] = $row;
			}
		}

		foreach($final as $date => $entries){
			$item = new XMLElement("item");
			$item->addChild(new XMLElement("date", intval($date)));

			foreach($entries as $row){

		        $locked = 'content';

				$entry = new XMLElement("entry");
				$entry->setAttribute("class", $locked);

				$entry->addChild(new XMLElement("title", General::limitWords(strip_tags($row['title']), 32, true, true)));
				$entry->addChild(new XMLElement("link", "?page=/publish/section/edit/&amp;_sid=".$row['section_id']."&amp;id=" . $row['id']));

				$item->addChild($entry);

			}
			$xml->addChild($item);
		}

	}

?>
