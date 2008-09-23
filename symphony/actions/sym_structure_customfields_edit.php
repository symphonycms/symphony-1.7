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

	if(@array_key_exists("delete", $_POST['action'])) {
		
		$field_id = $_REQUEST['id'];
				
		###
		# Delegate: Delete
		# Description: Prior to deletion of a custom field. ID is provided.
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array("customfield_id" => $field_id));		
		
		include_once(TOOLKIT . "/class.customfieldmanager.php");
		$CustomFieldManager = new CustomFieldManager($Admin);
		
		$CustomFieldManager->delete($field_id);
		
		$Admin->rebuildWorkspaceConfig();	
												  		
		General::redirect(URL."/symphony/?page=/structure/customfields/&_f=complete");
		
	}
	
	if(@array_key_exists("save", $_POST['action']) || @array_key_exists("done", $_POST['action'])) {	
		$required = array('name');
		extract($_POST); 
		
		for($i=0;$i<count($required);$i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}
		
		if(is_array($errors)){
			define("__SYM_ENTRY_MISSINGFIELDS__", true);
			
		}else{
			
            $field_id = $_REQUEST['id'];
            
			if(!empty($field_id)) {
					
                $fields['handle'] = Lang::createHandle($fields['name'], $Admin->getConfigVar('handle_length', 'admin'));

	        	if($fields['type'] == 'input' && $fields['create_input_as_list'] == 'on') $fields['type'] = 'list';
	        	unset($fields['create_input_as_list']);
	        		        
	       	 	if($fields['type'] == 'select' && isset($fields['select_multiple'])) $fields['type'] = 'multiselect';
	       	 	unset($fields['select_multiple']);	 
	       
		        if($fields['type'] == 'foreign' && isset($fields['foreign_select_multiple'])) $fields['foreign_select_multiple'] = 'yes';
				else $fields['foreign_select_multiple'] = 'no';
					                    	
				$current_handle = $DB->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_customfields` WHERE `id` = " . $_REQUEST['id']);
				
	       		if(($current_handle != $fields['handle']) && $DB->fetchRow(0, "SELECT * FROM `tbl_customfields` WHERE `handle` = '" . $fields['handle'] . "' AND `parent_section` = '". $fields['parent_section'] ."' LIMIT 1"))
	 				$Admin->pageAlert("duplicate", array("A Custom Field, in selected parent Section,", "name"), false, 'error');
				
	 			else{					

					$fields['size'] = max(6, intval($fields['size'])); 	                

					$fields['format'] = ($fields['format'] == 'on' ? '1' : '0');	
					$fields['required'] = ((isset($fields['required']) && $fields['location'] != 'drawer') ? 'yes' : 'no');

					if ($fields['validator'] == '' || ($fields['type'] != 'input' && $fields['type'] != 'list')){
						$fields['validator'] = NULL;
						$fields['validation_rule'] = NULL;

					}elseif ($fields['validator'] == 'custom'){
						$fields['validator'] = "custom";
						$fields['validation_rule'] = trim($fields['validation_rule']);

					}else{
						include(TOOLKIT . '/util.validators.php');
						$fields['validator'] = intval($fields['validator']);
						$fields['validation_rule'] = NULL;
					}
								
					if($fields['type'] == 'checkbox') 
						$fields['default_state'] = (isset($fields['default_state']) ? 'checked' : 'unchecked');
					else
						$fields['default_state'] = 'na';

					include_once(TOOLKIT . "/class.customfieldmanager.php");
					$CustomFieldManager = new CustomFieldManager($Admin);
					$CustomField =& $CustomFieldManager->create();
					$CustomField->set('id', $field_id);

					foreach($fields as $key => $val)
						$CustomField->set($key, $val);			

					if($CustomField->commit()){
							
						$Admin->rebuildWorkspaceConfig();
						$Admin->flush_cache(array("entries", "customfields"));	
							
						###
						# Delegate: Edit
						# Description: After editing a customfield. ID is provided.
						$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('customfield_id' => $field_id));							
						
	                    if(@array_key_exists("save", $_POST['action']))
	                        General::redirect($Admin->getCurrentPageURL() . "&id=".$field_id."&_f=saved");	
	                
	                    General::redirect(URL . "/symphony/?page=/structure/customfields/");							
								
					}
				}				
			} 
		}
	}

?>
