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
		
		##Split open the POST var into $fields & $hierarchy
		extract($_POST);

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
			
			$fields["data_sources"] = @implode(",", $fields["data_sources"]);			
			$fields["events"] = @implode(",", $fields["events"]);	
			$fields['name'] = General::createFileName(trim($fields['name']));
			
      $file = WORKSPACE . "/masters/" . $fields['name'] . ".xsl";
          		          		
			##Duplicate
			if(@is_file($file)){
 				$Admin->pageAlert("duplicate", array("A Page Master", "name"), false, 'error'); 
			
			##Write the file
			}elseif(!$write = General::writeFile($file, $fields['body'], $Admin->getConfigVar("write_mode", "file")))
				$Admin->pageAlert("write-failed", array("Page Master"), false, 'error'); 			
				
			##Write Successful, add record to the database
			else{

				##No longer need the body text
				unset($fields['body']);

				##Insert the new data
				if(!$DB->insert($fields, "tbl_masters")){
					define("__SYM_DB_INSERT_FAILED__", true);
					
				}else{
					$id = $DB->getInsertID();
					
					##Ensure our metadata for the page is set
					$Admin->updateMetadata("master", $id);
		
					$Admin->rebuildWorkspaceConfig();
					
					###
					# Delegate: Create
					# Description: After saving the master. The Master's database ID is provided.
					$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('master_id' => $id));							
						
					if(@array_key_exists("save", $_POST['action']))
				        General::redirect(URL."/symphony/?page=/blueprint/masters/edit/&file=".$fields['name']."&_f=saved");
				
				    General::redirect(URL . "/symphony/?page=/blueprint/components/");	
					    	
					
				}
															
			}
			
		}
		
	}

?>
