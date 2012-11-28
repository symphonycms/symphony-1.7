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

	if(@array_key_exists("delete", $_POST['action'])) {
	
		$section_id = $_REQUEST['id'];
			
		// 1. Fetch entry details
		$query = 'SELECT `id`, `sortorder` FROM tbl_sections WHERE `id` = \''.$section_id.'\'';
		$details = $DB->fetchRow(0, $query);
			
		$entries = $DB->fetchCol("entry_id", "SELECT `entry_id` FROM `tbl_entries2sections` WHERE `section_id` = '".$section_id."'");
		
		$customfields = $DB->fetchCol("id", "SELECT `id` FROM `tbl_customfields` WHERE `parent_section` = '".$section_id."'");				

		###
		# Delegate: Delete
		# Description: Prior to deleting a Section. Arrays of Entries and Custom fields are provided. These can be manipulated
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('section_id' => $section_id,
																	 'customfields' => &$customfields,
																	 'entries' => &$entries));
			
		$DB->delete("tbl_sections", "WHERE `id` = '".$section_id."'");
		$DB->delete("tbl_entries", "WHERE `id` IN ('".@implode("', '", $entries)."')");
		$DB->delete("tbl_entries2sections", "WHERE `section_id` = '".$section_id."'");
		$DB->delete("tbl_customfields", "WHERE `parent_section` = '".$section_id."'");
		$DB->delete("tbl_entries2customfields", "WHERE `entry_id` IN ('".@implode("', '", $entries)."')");
		$DB->delete("tbl_sections_column", "WHERE `section_id` = '".$section_id."'");
		
		// Section Meta Data
		$DB->delete("tbl_metadata", "WHERE `relation_id` = '".$section_id."' AND `class` = 'section'"); 
		
		// Custom Field Meta Data
		$DB->delete("tbl_metadata", "WHERE `relation_id` IN ('" . @implode("', '", $customfields) . "')  AND `class` = 'customfield'");
		
		// Entry Meta Data
		$DB->delete("tbl_metadata", "WHERE `relation_id` IN ('" . @implode("', '", $entries) . "')  AND `class` = 'entry'");
				
		// 4. Update the sort orders
		$DB->query("UPDATE tbl_sections SET `sortorder` = (`sortorder` - 1) WHERE `sortorder` > '".$details['sortorder']."'");
	
		$Admin->rebuildWorkspaceConfig();
											  		
		General::redirect(URL."/symphony/?page=/structure/sections/&_f=complete");
	
	}

	if(@array_key_exists("save", $_POST['action']) || @array_key_exists("done", $_POST['action'])) {
	    $required = array('name');
	    $fields = $_POST['fields'];
	    
	    for($i=0;$i<count($required);$i++) {
	        if(trim($fields[$required[$i]]) == "") {
	            $errors[$required[$i]] = true;
	        }
	    }
	    
	    if(is_array($errors)){
	        define("__SYM_ENTRY_MISSINGFIELDS__", true);
	        
	    }else{
    
	        $section_id = $_REQUEST['id'];
	        $fields['handle'] = Lang::createHandle($fields['name'], $Admin->getConfigVar('handle_length', 'admin'));
		
			$current_handle = $DB->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` WHERE `id` = " . $_REQUEST['id']);	
			
			$current_primary_field = $DB->fetchVar('primary_field', 0, "SELECT `primary_field` FROM `tbl_sections` WHERE `id` = " . $_REQUEST['id']);	

			##Duplicate
			if(($current_handle != $fields['handle']) && $DB->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `handle` = '" . $fields['handle'] . "' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Section", "name"), false, 'error'); 

			}elseif(in_array($fields['handle'], array("authors", "navigation", "comments", "options"))){
				$Admin->pageAlert("reserved-section-name", NULL, false, 'error'); 

			}else{
				
				$fields['commenting'] = (isset($fields['commenting']) ? 'on' : 'off');				
				$fields['author_column'] = (isset($fields['author_column']) ? 'show' : 'hide');
				$fields['date_column'] = (isset($fields['date_column']) ? 'show' : 'hide');	
				$fields['calendar_show'] = (isset($fields['calendar_show']) ? 'show' : 'hide');				
				#$fields['valid_xml_column'] = (isset($fields['valid_xml_column']) ? 'show' : 'hide');
				
				$fields['columns'][$current_primary_field] = 'on';
				$visable = @array_keys($fields['columns']);
				
				if(isset($fields['columns'])) unset($fields['columns']);			
			
		        if($DB->update($fields,"tbl_sections", "WHERE `id` = '".$section_id."'")){
		                
					$DB->query("DELETE FROM `tbl_sections_visible_columns` WHERE `section_id` = '$section_id'");
					
					if(is_array($visable) && !empty($visable)){
						foreach($visable as $v){
							$DB->query("INSERT INTO `tbl_sections_visible_columns` VALUES ('$v', '$section_id')");
						}
					}
		  
		            $Admin->updateMetadata("section", $section_id);
			
					$Admin->rebuildWorkspaceConfig();
					$Admin->flush_cache(array("entries", "comments"));
						
					###
					# Delegate: Edit
					# Description: After editing a Section. The ID is provided.
					$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('section_id' => $section_id));
					
	                if(@array_key_exists("save", $_POST['action']))
 						General::redirect($Admin->getCurrentPageURL() . "&id=".$section_id."&_f=saved");
	            
	                General::redirect(URL . "/symphony/?page=/structure/sections/");								
		            
		        }
	        }
	    }
	}
?>