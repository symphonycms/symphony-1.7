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

	if(array_key_exists("save", $_POST['action']) || array_key_exists("output", $_POST['action']) || array_key_exists("configure", $_POST['action']) || array_key_exists("done", $_POST['action'])) {
	
		$fields = $_POST['fields'];

		##Make sure all required fields are filled
		$required = array('body', 'title');

		for($i = 0; $i < count($required); $i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}
		
		if(is_array($errors) && !array_key_exists("configure", $_POST['action'])){
			define("__SYM_ENTRY_MISSINGFIELDS__", true);
			
		}else{
			
			##Manipulate some fields
			$fields['sortorder'] = $DB->fetchVar("next", 0, "SELECT MAX(sortorder) + 1 as `next` FROM `tbl_pages` LIMIT 1");

			$fields['master'] = ($fields['master'] != "None" ? $fields['master'] : NULL);
			
			if(empty($fields['sortorder']) || !is_numeric($fields['sortorder'])) $fields['sortorder'] = 1;
			
			if($fields['handle'] == '') $fields['handle'] = $fields['title'];			

			$fields['show_in_nav'] = (!$fields['show_in_nav'] ? "yes" : "no");
			$fields['full_caching'] = ($fields['full_caching'] ? "yes" : "no");
			
			## Clean up the refresh rate value
			$fields['cache_refresh_rate'] = intval($fields['cache_refresh_rate']);				
					
			##Manipulate some fields
			$fields['parent'] = ($fields['parent'] != "None" ? $fields['parent'] : NULL);			
			$fields['handle'] = Lang::createHandle($fields['handle']);
		
			$fields["data_sources"] = @implode(",", $fields["data_sources"]);			
			$fields["events"] = @implode(",", $fields["events"]);	
			
			##Duplicate
			if($DB->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `handle` = '" . $fields['handle'] . "' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Page", "name"), false, 'error'); 

			}else{	
											
				##Write the file
				if(!$write = General::writeFile(WORKSPACE . "/pages/" . $fields['handle'] . ".xsl" , $fields['body'], $Admin->getConfigVar("write_mode", "file")))
					$Admin->pageAlert("write-failed", array("Page"), false, 'error'); 			
					
				##Write Successful, add record to the database
				else{
					
					##No longer need the body text
					unset($fields['body']);
					
					##Insert the new data
					if(!$DB->insert($fields, "tbl_pages")){
						define("__SYM_DB_INSERT_FAILED__", true);
						
					}else{
						$page_id = $DB->getInsertID();
						
						##Ensure our metadata for the page is set
						$Admin->updateMetadata("page", $page_id);
						$Admin->flush_cache(array("pages"));
						$Admin->rebuildWorkspaceConfig();
					
						###
						# Delegate: Create
						# Description: After saving the Page. The Page's database ID is provided.
						$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('page_id' => $page_id));
										
					    if(@array_key_exists("output", $_POST['action'])){
					        General::redirect(URL . "/symphony/?page=/blueprint/pages/view/&type=page&handle=" . $fields['handle']);
					    }
					                            
	                    if(@array_key_exists("save", $_POST['action']))
	                       General::redirect(URL."/symphony/?page=/blueprint/pages/edit/&id=$page_id&_f=saved");
	                
	                    General::redirect(URL . "/symphony/?page=/blueprint/pages/&id=$page_id&_f=saved");  
																	
					}
				}
			}
		}
	}

?>
