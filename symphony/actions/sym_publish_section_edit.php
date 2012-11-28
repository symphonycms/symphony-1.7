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
		$entry_id = intval($_REQUEST['id']);

		if($Admin->getConfigVar('strict_section_field_validation', 'symphony') == '1')
			$errors = $entryManager->validateFieldsXSLT($section_id, $data['custom'], $entry_id);
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

			$change_handle = $Admin->getConfigVar("allow_primary_field_handles_to_change", "symphony");

			$change_handle = intval($change_handle);
			$change_handle = ($change_handle == 1 ? true : false);

			$retval = $entryManager->edit($entry_id, $data['custom'], $newPublishTimestamp, 'real', $change_handle);

			if(!$retval) define("__SYM_DB_INSERT_FAILED__", true);
			else{
				$Admin->flush_cache(array("entries", "authors"));

				###
				# Delegate: Edit
				# Description: Editing an entry. Section and Entry ID are provided.
				$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('section_id' => $section_id,
																		   'entry_id' => $entry_id));

	  		    if(@array_key_exists("save", $_POST['action']))
			         General::redirect($Admin->getCurrentPageURL() . "&_sid=$section_id&id=$entry_id&_f=saved");

			    General::redirect(URL . "/symphony/?page=/publish/section/&_sid=$section_id");
			}
		}
	}

	if(@array_key_exists("delete", $_POST['action'])){

		###
		# Delegate: Delete
		# Description: Prior to deleting an entry. Both Section and Entry ID are provided.
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('section_id' => $_REQUEST['_sid'],
											  						 'entry_id' => $_REQUEST['id']));

		include_once(TOOLKIT . "/class.entrymanager.php");
		$entryManager = new EntryManager($Admin);

		$entryManager->delete($_REQUEST['id']);

		$Admin->flush_cache(array("entries", "authors", "comments"));

		General::redirect(URL . "/symphony/?page=/publish/section/&_f=complete&_sid=" . $_REQUEST['_sid']);
	}
?>