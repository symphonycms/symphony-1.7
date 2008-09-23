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

	if(@array_key_exists("save", $_POST['action']) || @array_key_exists("done", $_POST['action'])) {
	    $required = array('name');
	    extract($_POST); //['custom_field'];
	    
	    for($i=0;$i<count($required);$i++) {
	        if(trim($fields[$required[$i]]) == "") {
	            $errors[$required[$i]] = true;
	        }
	    }
	    
	    if(is_array($errors)){
	        define("__SYM_ENTRY_MISSINGFIELDS__", true);
	        
	    }else{
    
	        $query  = 'SELECT MAX(`sortorder`) + 1 AS `next` FROM tbl_customfields LIMIT 1';
	        $next = $DB->fetchVar("next", 0, $query);
	                    
	        $fields['sortorder'] = ($next ? $next : '1');
	        
	        if($fields['type'] == 'input' && isset($fields['create_input_as_list'])) $fields['type'] = 'list';
	        unset($fields['create_input_as_list']);
	        
	        if($fields['type'] == 'select' && isset($fields['select_multiple'])) $fields['type'] = 'multiselect';
	        unset($fields['select_multiple']);
		        
	        if($fields['type'] == 'foreign' && isset($fields['foreign_select_multiple'])) $fields['foreign_select_multiple'] = 'yes';
			else $fields['foreign_select_multiple'] = 'no';
		            
	        $fields['handle'] = Lang::createHandle($fields['name'], $Admin->getConfigVar('handle_length', 'admin'));	
		        		
			##Duplicate
			if($DB->fetchRow(0, "SELECT * FROM `tbl_customfields` WHERE `handle` = '" . $fields['handle'] . "' AND `parent_section` = '". $fields['parent_section'] ."' LIMIT 1")){
 				$Admin->pageAlert("duplicate", array("A Custom Field, in selected parent Section,", "name"), false, 'error'); 

			}else{							

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
					
				foreach($fields as $key => $val)
					$CustomField->set($key, $val);		
						
				$field_id = $CustomField->commit();	
						
		        if($field_id){
	
					$Admin->rebuildWorkspaceConfig();
					$Admin->flush_cache(array("entries", "customfields"));
						
					###
					# Delegate: Create
					# Description: After creation of a new custom field. The ID is provided.
					$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('customfield_id' => $field_id));
					
	                if(@array_key_exists("save", $_POST['action']))
	                    General::redirect(URL."/symphony/?page=/structure/customfields/edit/&id=".$field_id."&_f=saved");
	            
	                General::redirect(URL . "/symphony/?page=/structure/customfields/");								
		            
		        }
		
	        }
	    }
	}
?>
