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
		
		##Split open the POST var into $fields
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
				
			$fields['type'] = strtolower($fields['type']);
			$fields['name'] = General::createFileName($fields['name']);			
				
            $file_rel = str_replace("workspace/", "", $fields['location']) . $fields['name'];
            $file = DOCROOT . "/" . $fields['location'] . $fields['name'];
			
            if(($_REQUEST['file'] != "/" . $file_rel) && @is_file($file))
 				$Admin->pageAlert("duplicate", array("An Asset", "name"), false, 'error'); 
           
			##Write the file
			elseif(!$write = General::writeFile($file, $fields['body'], $Admin->getConfigVar("write_mode", "file")))
				$Admin->pageAlert("write-failed", array("Asset"), false, 'error'); 			
				
			##Write Successful
			else{
						
				if($file != WORKSPACE . $_REQUEST['file']) unlink(WORKSPACE . $_REQUEST['file']);	
				
				###
				# Delegate: Edit
				# Description: After saving the asset, the file path is provided.
				$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array("file" => $file));
					
				if(@array_key_exists("save", $_POST['action']))
			        General::redirect($Admin->getCurrentPageURL() . "&file=/$file_rel&_f=saved");
			
			    General::redirect(URL . "/symphony/?page=/blueprint/components/");					
			}									
		}		
	}
	
	if(@array_key_exists("delete", $_POST['action'])) {
	  	
		###
		# Delegate: Delete
		# Description: Prior to deleting the asset file. Target file path is provided.
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array("file" => WORKSPACE . $_REQUEST['file']));

    	@unlink(WORKSPACE . $_REQUEST['file']);
											  
    	General::redirect(URL . "/symphony/?page=/blueprint/components/");	
  	}  
?>