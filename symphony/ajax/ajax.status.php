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

	$done_path = TMP . '/' . md5($settings['auth']['id'] . 'done');

	$cDate = new SymDate($settings["region"]["time_zone"], $settings["region"]["date_format"]);

	if($_REQUEST['done'] == 'true'){
		$xml->setValue('Status widget set to viewed');
		@file_put_contents($done_path, $cDate->get(false, false));

	}else{

		define('kFULL_MODE', (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'full' ? true : false));

		$done = @file_exists($done_path);

		$last = $db->fetchRow(0, "SELECT UNIX_TIMESTAMP(`last_refresh`) as `last_refresh_timestamp`, UNIX_TIMESTAMP(`last_session`) as `last_session_timestamp` FROM `tbl_authors` WHERE `id` = '".$settings['auth']['id']."' LIMIT 1");

		$lastlogin = $last['last_session_timestamp'];
		$lastrefresh = $last['last_refresh_timestamp'];

		$can_access = $Author->get('allow_sections');

		require_once(TOOLKIT . "/class.ajaxaccount.php");

		$entries = $db->fetchCol('id', "SELECT tbl_entries.id
							   FROM `tbl_entries`, `tbl_sections`, `tbl_entries2sections` as t2
							   WHERE 1 ".($Author->get('superuser') != 1 ? " AND t2.section_id IN ($can_access)" : '')."
							   AND `tbl_sections`.id = t2.section_id
							   AND `tbl_entries`.id = t2.entry_id
							   ORDER BY publish_date_gmt DESC LIMIT 0,6");

		$comments = $db->fetch("SELECT c.*, m.creation_date_gmt, m.referrer
								FROM `tbl_comments` AS `c`
								LEFT JOIN `tbl_metadata` AS m ON c.id = m.relation_id AND m.class = 'comment'
								ORDER BY c.id DESC
								LIMIT 0, 3");

		$account = new ajaxAccount($settings);

		if($Author->get('owner') == 1 && $Author->get('superuser') == 1){

			##UPDATE
			$update = array();
			$data = $account->grab("checkforupdate");
			$data = $account->processServerData($data, true, false);

			$update = $data['result'][0]['update'];

			$version = new XMLElement("version");

			if(!empty($update) && is_array($update)) {
			  	$sym_build = $update['attributes']['build'];

				$sym_version = $sym_build{0} . "." . $sym_build{1} . ($sym_build{2} . $sym_build{3} != '00' ? "." . $sym_build{2} . $sym_build{3} : '');
				$version->addChild(new XMLElement("update", "Symphony " . $sym_version));
				$version->setAttribute("new", "true");

				if(kFULL_MODE){
					$version->addChild(new XMLElement('releasedate', $update['attributes']['releasedate']));
					$version->addChild(new XMLElement('change-log', General::sanitize($update[1]['changelog'][0])));
					$version->addChild(new XMLElement('announcement', General::sanitize($update[0])));

				}
			}

		  	$sym_build = $settings['symphony']['build'];
			$sym_version = $sym_build{0} . "." . $sym_build{1} . ($sym_build{2} . $sym_build{3} != '00' ? "." . $sym_build{2} . $sym_build{3} : '');
			$version->addChild(new XMLElement("current", "Symphony " . $sym_version));

			$xml->addChild($version);

			$parent =& new ParentShell($db, $config);

			include_once(LIBRARY . "/core/class.service.php");
			include_once(LIBRARY . "/core/class.manager.php");
			include_once(LIBRARY . "/core/class.symphonylog.php");
			include_once(LIBRARY . "/core/class.campfiremanager.php");

			$campfireManager = new CampfireManager(array('parent' => &$parent));
			$services = $campfireManager->listAll();

			if(is_array($services) && !empty($services)){

				$installed = array();

				foreach($services as $owner => $s){
					foreach($s as $handle => $info){

						$guid = md5($owner.$handle);
						$version = $info['version'];

						$installed[$guid] = $version;

					}
				}

				$account->setopt("POSTFIELDS", array("installed-services" => $installed));
				$data = $account->grab("service-check-for-update");

				$data = $account->processServerData($data, true, false);

				if(isset($data['result']['attributes'])) unset($data['result']['attributes']);

				if(is_array($data['result']) && !empty($data['result']) && !isset($data['result'][0]['error'])){
					foreach($data['result'] as $s){

						$s = $s['service'];

						$attributes = $s['attributes'];
						unset($s['attributes']);

						$service = array();
						foreach($s as $key => $element)
							$service = array_merge($service, $element);

						$campfire = new XMLElement("campfire");
						$campfire->setAttribute('id', $attributes['id']);
						$campfire->addChild(new XMLElement("service", $service['title'][0]));
						$campfire->addChild(new XMLElement("link", $service['link'][0]));

						if(kFULL_MODE){
							$campfire->addChild(new XMLElement("version", $service['version'][0]));
							$campfire->addChild(new XMLElement("description", General::sanitize($service['description'][0])));
							$campfire->addChild(new XMLElement("postdate", $service['postdate'][0]));
						}

						$xml->addChild($campfire);
					}
				}

			}

		}

		if(@count($entries) >= 1) {

			$parent =& new ParentShell($db, $config);

			include_once(LIBRARY . "/core/class.manager.php");
			include_once(LIBRARY . "/core/class.symphonylog.php");
			include_once(LIBRARY . "/core/class.textformattermanager.php");
			include_once(TOOLKIT . "/class.entrymanager.php");

			$entryManager = new EntryManager($parent);

			foreach($entries as $entry_id){
				$entry = new XMLElement("entry");
				$e = $entryManager->fetchEntriesByID($entry_id, false, true);

				$tmp_time = strtotime($e['publish_date_gmt']);

				if($e['author_id'] != $settings['auth']['id']):

					if(!$done)
						$entry->setAttribute("new", "true");

					elseif($tmp_time > $lastrefresh){
						if($tmp_time > @file_get_contents($done_path)){
							$entry->setAttribute("new", "true");
							@unlink($done_path);
						}
					}

				endif;

		        $locked = 'content';

			    $entry->setAttribute("class", $locked);
			    $entry->addChild(new XMLElement("title", General::stripEntities(General::limitWords(strip_tags($e['fields'][$e['primary_field']]['value']), 32, true, true), ' ')));
			   	$entry->addChild(new XMLElement("link", "?page=/publish/section/edit/&amp;_sid=".$e['section_id']."&amp;id=" . $e['id']));

				if(kFULL_MODE){

					if(isset($e['fields']['body'])){

						$body = strip_tags($e['fields']['body']['value']);
						$body = ereg_replace("[^[:space:]a-zA-Z0-9,*_.-\\'\\\"&;\]]", "", $body);
						$body = General::stripEntities($body, ' ');
						$entry->addChild(new XMLElement('body', $body));
					}

					$entry->addChild(new XMLElement('date', $e['publish_date_gmt']));
					$entry->addChild(new XMLElement('author', $e['author_firstname'] . ' ' . $e['author_lastname']));

				}

				$xml->addChild($entry);
			}
		}

		if(@count($comments) >= 1) {

			foreach($comments as $c){

				$comment = new XMLElement("comment");
				$tmp_time = strtotime($c['creation_date_gmt']);

				if(!$done){
					$comment->setAttribute("new", "true");

				}elseif($tmp_time > $lastrefresh){
					if($tmp_time > @file_get_contents($done_path)){
						$comment->setAttribute("new", "true");
						@unlink($done_path);
					}
				}

				$body = strip_tags($c['body']);
				$body = ereg_replace("[^[:space:]a-zA-Z0-9,*_.-\\'\\\"&;\]]", "", $body);
				$body = General::stripEntities($body, ' ');

				$comment->setAttribute("class", "comment" . ($c['spam'] == "yes" ? "-spam" : ""));

				$comment->addChild(new XMLElement("title", General::limitWords(General::sanitize(strip_tags($body)), 100, true, false)));
				$comment->addChild(new XMLElement("link", "?page=/publish/comments/edit/&amp;id=" . $c['id']));

				if(kFULL_MODE){
					$comment->addChild(new XMLElement('body', $body));
					$comment->addChild(new XMLElement('date', $c['creation_date_gmt']));
					$comment->addChild(new XMLElement('referrer', $c['referrer']));
					$comment->addChild(new XMLElement('author-name', $c['author_name']));
					$comment->addChild(new XMLElement('author-email', $c['author_email']));
					if($c['author_url'] != '') $comment->addChild(new XMLElement('author-url', $c['author_url']));
				}

				$xml->addChild($comment);
			}
		}

	}

?>