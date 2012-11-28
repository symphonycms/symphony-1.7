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

	if(array_key_exists("save", $_POST['action']) || array_key_exists("done", $_POST['action'])) {

		$fields = array(); ##IMPORTANT. MUST Initialize this incase register globals is on

		include_once(TOOLKIT . "/class.entrymanager.php");
		$entryManager = new EntryManager($Admin);

		$data = $_POST['fields'];
		$section_id = intval($_REQUEST['_sid']);

		if($Admin->getConfigVar('strict_section_field_validation', 'symphony') == '1')
			$errors = $entryManager->validateFieldsXSLT($section_id, $data['custom']);
		else
			$errors = $entryManager->validateFields($section_id, $data['custom']);

		if(is_array($errors) && !empty($errors)){
			define("__SYM_ENTRY_MISSINGFIELDS__", true);

		}elseif(!defined('__SYM_ENTRY_VALIDATION_ERROR__') && !defined('__SYM_ENTRY_FIELD_XSLT_ERROR__')){

			$newPublishTimestamp = NULL;

			if(isset($data['time']) && isset($data['publish_date'])){

				##Do some processing to ensure all the text is in the correct format
				$date = $Admin->getDateObj();
				$date->setFormat("Y-m-d H:i:s");

				##User has specified the time, grab it as GMT
				if($data['time'] == "Automatic" || $data['time'] == "")
					$data['time'] = date("h:i:sa", $date->get(true, false));

				##User has specified the time, grab it as GMT
				$date->set(strtotime($data['time'] . " " . $data['publish_date']));

				$newPublishTimestamp = $date->get(true, false);

			}

			if(isset($_FILES) && is_array($_FILES['fields']) && !empty($_FILES['fields'])){
				foreach($_FILES['fields'] as $type => $f){
					$custom = $f['custom'];

					foreach($f['custom'] as $name => $list){

						foreach($list as $id => $info){
							$data['custom'][$name]['files'][$id][$type] = $info;
						}
					}
				}
			}

			$entry_id = $entryManager->add($section_id, $data['custom'], $newPublishTimestamp);

			if(!$entry_id) define("__SYM_DB_INSERT_FAILED__", true);
			else{

				$Admin->flush_cache(array("entries", "authors"));

				###
				# Delegate: Create
				# Description: Creation of an Entry. Section and new Entry ID are provided.
				$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('section_id' => $section_id,
													  					     'entry_id' => $entry_id));

	  		    if(@array_key_exists("save", $_POST['action']))
			         General::redirect(URL . "/symphony/?page=/publish/section/edit/&_sid=$section_id&id=$entry_id&_f=saved");

			    General::redirect(URL . "/symphony/?page=/publish/section/&_sid=$section_id");

			}

		}

	}

?>