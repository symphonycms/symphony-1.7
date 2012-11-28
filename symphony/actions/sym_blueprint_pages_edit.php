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

	if(array_key_exists("save", $_POST['action']) || array_key_exists("configure", $_POST['action']) || array_key_exists("done", $_POST['action']) || array_key_exists("output", $_POST['action'])) {

		$fields = $_POST['fields'];

		##Make sure all required fields are filled
		$required = array('body', 'title');

		for($i = 0; $i < count($required); $i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}

		if(is_array($errors)){
			define("__SYM_ENTRY_MISSINGFIELDS__", true);

		}else{

			if($fields['handle'] == '') $fields['handle'] = $fields['title'];

			##Manipulate some fields
			$fields['parent'] = ($fields['parent'] != "None" ? $fields['parent'] : NULL);
			$fields['master'] = ($fields['master'] != "None" ? $fields['master'] : NULL);

			$fields['show_in_nav'] = (!$fields['show_in_nav'] ? "yes" : "no");
			$fields['full_caching'] = ($fields['full_caching'] ? "yes" : "no");

			## Clean up the refresh rate value
			$fields['cache_refresh_rate'] = intval($fields['cache_refresh_rate']);

			$fields['handle'] = Lang::createHandle($fields['handle']);

			$current_handle = $DB->fetchVar("handle", 0, "SELECT `handle` FROM `tbl_pages` WHERE `id` = '".$_REQUEST['id']."' LIMIT 1");

			##Duplicate
			if($DB->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `handle` = '" . $fields['handle'] . "' AND `id` != '".$_REQUEST['id']."' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Page", "name"), false, 'error');

			}else{

				if($current_handle != $fields['handle'])
					unlink(WORKSPACE . "/pages/" . $current_handle . ".xsl");

				##Write the file
				if(!$write = General::writeFile(WORKSPACE . "/pages/" . $fields['handle'] . ".xsl" , $fields['body'], $Admin->getConfigVar("write_mode", "file")))
					$Admin->pageAlert("write-failed", array("Page"), false, 'error');

				if(!defined("__SYM_ERROR_MESSAGE__")){

					$fields["data_sources"] = @implode(",", $fields["data_sources"]);
					$fields["events"] = @implode(",", $fields["events"]);

					$page_id = $_REQUEST['id'];

		       		$current_handle = $DB->fetchVar("handle", 0, "SELECT `handle` FROM `tbl_pages` WHERE `id` = '" . $_REQUEST['id'] . "' LIMIT 1");

					if(($current_handle != $fields['handle']) && $DB->fetch("SELECT * FROM `tbl_pages` WHERE `handle` = '" . $fields['handle'] . "'"))
 						$Admin->pageAlert("duplicate", array("A Page", "name"));

		 			else{

						##No longer need the body text
						unset($fields['body']);

						##Update the data
						if(!$DB->update($fields, "tbl_pages", "WHERE `id` = '$page_id'")){
							define("__SYM_DB_INSERT_FAILED__", true);

						}else{
							$page_id = $_REQUEST['id'];

							##Ensure our metadata for the page is set
							$Admin->updateMetadata("page", $page_id, false);

							$Admin->rebuildWorkspaceConfig();
						    $Admin->flush_cache(array("pages"));

							###
							# Delegate: Edit
							# Description: After saving the page. The Page's database ID is provided.
							$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('page_id' => $page_id));

						    if(@array_key_exists("output", $_POST['action'])){
						        General::redirect(URL . "/" . $Admin->resolvePagePath($page_id) . "/?debug");
						    }

		            		if(@array_key_exists("save", $_POST['action']))
		            			General::redirect($Admin->getCurrentPageURL() . "&id=$page_id&_f=saved");

		            		General::redirect(URL . "/symphony/?page=/blueprint/pages/&id=$page_id&_f=saved");

						}
					}
				}
			}
	  }
	}

	if(@array_key_exists("delete", $_POST['action'])) {

	    $page_id = $_REQUEST['id'];

		###
		# Delegate: Delete
		# Description: Prior to deletion. Provided with Page's database ID
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('page' => $page_id));

	    $page = $DB->fetchRow(0, "SELECT * FROM tbl_pages WHERE `id` = '$page_id'");

		$DB->delete("tbl_pages","WHERE `id` = '$page_id'");
		$DB->delete("tbl_pages_hierarchy","WHERE `entry_id` = '$page_id'");
		$DB->delete("tbl_metadata", "WHERE `relation_id` = '$page_id' AND `class` = 'page'");
		$DB->query("UPDATE tbl_pages SET `sortorder` = (`sortorder` + 1) WHERE `sortorder` < '$page_id'");

		unlink(WORKSPACE . "/pages/" . $page['handle'] . ".xsl");

		$Admin->flush_cache(array("pages"));

		General::redirect(URL . "/symphony/?page=/blueprint/pages/&_f=deleted");

	}

?>
