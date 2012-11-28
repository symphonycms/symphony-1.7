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

		$fields = $_POST['fields'];

		##Make sure all required fields are filled
		$required = array('name', 'body');

		for($i = 0; $i < count($required); $i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}

		if(is_array($errors)){
			define("__SYM_ENTRY_MISSINGFIELDS__", true);

		}else{

			##Manipulate some fields

			$datasources = $fields['data_source'];
			$events = $fields['events'];

			unset($fields['data_source']);
			unset($fields['events']);

			$fields['name'] = General::sanitize($fields['name']);

			$fields['handle'] = preg_replace('/[\\s]++/', '-', $fields['name']);
			$fields['handle'] = preg_replace('/[^-_\\w\\d]++/', '', $fields['handle']);
			$fields['handle'] = strtolower($fields['handle']);

			##Duplicate
			if($DB->fetchRow(0, "SELECT * FROM `tbl_utilities` WHERE `handle` = '" . $fields['handle'] . "' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Utility", "name"), false, 'error');

			}else{
				##Write the file
				if(!$write = General::writeFile(WORKSPACE . "/utilities/" . $fields['handle'] . ".xsl" , $fields['body'], $Admin->getConfigVar("write_mode", "file")))
					$Admin->pageAlert("write-failed", array("Utility"), false, 'error');

				##Write Successful, add record to the database
				else{

					##No longer need the body text
					unset($fields['body']);

					##Insert the new data
					if(!$DB->insert($fields, "tbl_utilities")){
						define("__SYM_DB_INSERT_FAILED__", true);

					}else{
						$id = $DB->getInsertID();

						## Datasources
						$DB->query("DELETE FROM `tbl_utilities2datasources` WHERE `utility_id` = '$id'");
						if(is_array($datasources) && !empty($datasources)){
							foreach($datasources as $d){
								$DB->query("INSERT INTO tbl_utilities2datasources VALUES ('', '$id', '$d')");
							}
						}else
							$DB->query("INSERT INTO tbl_utilities2datasources VALUES ('', '$id', NULL)");

						## Events
						$DB->query("DELETE FROM `tbl_utilities2events` WHERE `utility_id` = '$id'");
						if(is_array($events) && !empty($events)){
							foreach($events as $e){
								$DB->query("INSERT INTO tbl_utilities2events VALUES ('', '$id', '$e')");
							}
						}else
							$DB->query("INSERT INTO tbl_utilities2events VALUES ('', '$id', NULL)");

						##Ensure our metadata for the page is set
						$Admin->updateMetadata("utility", $id);

						$Admin->rebuildWorkspaceConfig();

						###
						# Delegate: Create
						# Description: After saving the Utility. The Utility's database ID is provided.
						$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('utility_id' => $id));

	                    if(@array_key_exists("save", $_POST['action']))
	                        General::redirect(URL."/symphony/?page=/blueprint/utilities/edit/&id=$id&_f=saved");

	                    General::redirect(URL . "/symphony/?page=/blueprint/components/");

					}
				}
			}
		}
	}

?>