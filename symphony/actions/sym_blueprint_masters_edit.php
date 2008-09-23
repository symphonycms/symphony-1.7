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
				
			if(($_REQUEST['file'] != $fields['name']) && @is_file($file))
 				$Admin->pageAlert("duplicate", array("A Page Master", "name"), false, 'error'); 
           
			##Write the file
			elseif(!$write = General::writeFile($file, $fields['body'], $Admin->getConfigVar("write_mode", "file")))
				$Admin->pageAlert("write-failed", array("Page Master"), false, 'error'); 			

			##Write Successful
			else{
				$id = $DB->fetchVar("id", 0, "SELECT `id` FROM tbl_masters WHERE `name` = '".$_REQUEST['file']."'");
				
				##No longer need the body text
				unset($fields['body']);
				
				##Insert the new data

				if(!$DB->update($fields, "tbl_masters", "WHERE `id` = '$id'")){	
					define("__SYM_DB_INSERT_FAILED__", true);
					
				}else{

					$DB->query("UPDATE `tbl_pages` SET `master` = '".$fields['name'].".xsl' WHERE `master` = '".$_REQUEST['file']. ".xsl'");

					##Ensure our metadata for the template is set
					$Admin->updateMetadata("master", $id, false);
		
					$Admin->rebuildWorkspaceConfig();
 			
					if($file != WORKSPACE . "/masters/" . $_REQUEST['file']. ".xsl")
						unlink(WORKSPACE . "/masters/" . $_REQUEST['file']. ".xsl");	
					
					###
					# Delegate: Edit
					# Description: After saving the master. The Master's database ID is provided.
					$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('master_id' => $id));						
							
					if(@array_key_exists("save", $_POST['action']))
				        General::redirect(URL."/symphony/?page=/blueprint/masters/edit/&file=".$fields['name']."&_f=saved");
				
				    General::redirect(URL . "/symphony/?page=/blueprint/components/");	

				}
				
			}									
		}
		
	}
	
	if(@array_key_exists("delete", $_POST['action'])) {
	
	    $handle = $_REQUEST['file'];

		###
		# Delegate: Delete
		# Description: Prior to deletion. Provided with Master handle
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('handle' => $handle));
	    
	    $rec = $DB->fetchRow(0, "SELECT * FROM tbl_masters WHERE `name` = '$handle'");
	    
		$DB->delete("tbl_masters","WHERE `id` = '".$rec['id']."'");
		$DB->delete("tbl_metadata", "WHERE `relation_id` = '".$rec['id']."' AND `class` = 'master'"); 	
		$DB->query("UPDATE `tbl_pages` SET `master` = NULL WHERE `master` = '$handle.xsl'"); 
		
		@unlink(WORKSPACE . "/masters/" . $handle . ".xsl");		
		
		General::redirect(URL . "/symphony/?page=/blueprint/components/");
    		
	}	
  
?>