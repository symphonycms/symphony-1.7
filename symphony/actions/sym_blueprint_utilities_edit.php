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

			$current_handle = $DB->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_utilities` WHERE `id` = " . $_REQUEST['id']);

			##Duplicate
			if($fields['handle'] != $current_handle && $DB->fetchRow(0, "SELECT * FROM `tbl_utilities` WHERE `handle` = '" . $fields['handle'] . "' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Utility", "name"), false, 'error'); 

			}else{								
				
				##Write the file
				if(!$write = General::writeFile(WORKSPACE . "/utilities/" . $fields['handle'] . ".xsl" , $fields['body'], $Admin->getConfigVar("write_mode", "file")))
					$Admin->pageAlert("write-failed", array("Utility"), false, 'error'); 			
					
				##Write Successful, add record to the database
				else{
					
					##Remove the old file if it exists
					if($fields['handle'] != $current_handle && @is_file(WORKSPACE . "/utilities/" . $current_handle . ".xsl"))
						unlink(WORKSPACE . "/utilities/" . $current_handle . ".xsl");
					
					$id = $_REQUEST['id'];
					
					##No longer need the body text
					unset($fields['body']);
					
					##Insert the new data

					if(!$DB->update($fields, "tbl_utilities", "WHERE `id` = '$id'")){	
						define("__SYM_DB_INSERT_FAILED__", true);
						
					}else{
						
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
						$Admin->updateMetadata("utility", $id, false);
			
						$Admin->rebuildWorkspaceConfig();
	
						###
						# Delegate: Edit
						# Description: After saving the Utility. The Utility's database ID is provided.
						$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('utility_id' => $id));							
						
	                    if(@array_key_exists("save", $_POST['action']))
	                        General::redirect($Admin->getCurrentPageURL() . "&id=$id&_f=saved");
	                
	                    General::redirect(URL . "/symphony/?page=/blueprint/components/");	
	                            
	             
																	
					}
				}				
			}
		}
	}
	
	if(@array_key_exists("preview", $_POST['action']) && $_POST['fields']['data_source'] != "None") {	
	    $ds = $_POST['fields']['data_source'];
		General::redirect(URL . "/symphony/?page=/blueprint/datasouce/preview/&ds=$ds");    		
	}
		
	if(@array_key_exists("delete", $_POST['action'])) {
	
	    $id = $_REQUEST['id'];
	
		###
		# Delegate: Delete
		# Description: Prior to deletion. Provided with Utility's database ID
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('utility_id' => $id));	 
											   
	    $rec = $DB->fetchRow(0, "SELECT * FROM tbl_utilities WHERE `id` = '$id'");
	    
		$DB->delete("tbl_utilities","WHERE `id` = '$id'");
		$DB->delete("tbl_utilities2events","WHERE `utility_id` = '$id'");
		$DB->delete("tbl_utilities2datasources","WHERE `utility_id` = '$id'");
		$DB->delete("tbl_metadata", "WHERE `relation_id` = '$id' AND `class` = 'transformation'"); 	 
		
		@unlink(WORKSPACE . "/utilities/" . $rec['handle'] . ".xsl");
																	  			
		General::redirect(URL . "/symphony/?page=/blueprint/components/");
    		
	}

?>