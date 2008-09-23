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

	$checked = @array_keys($_POST['items']);
	
	switch($_POST["with-selected"]) {
	
		case 'delete':

			$sql = "SELECT `primary_field` FROM `tbl_sections`";
			$primary_fields = $DB->fetchCol('primary_field', $sql);

			$fieldsList = array_map ('intval', $checked);		
			$fieldsList = array_diff($fieldsList, $primary_fields);

			if(is_array($fieldsList) && !empty($fieldsList)){
	
				###
				# Delegate: Delete
				# Description: Prior to deleting a custom field. 
				#			   Array of fields is provided. This can be manipulated
				$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('customfields' => &$fieldsList));	
	
				include_once(TOOLKIT . "/class.customfieldmanager.php");
				$CustomFieldManager = new CustomFieldManager($Admin);

				foreach($fieldsList as $id) $CustomFieldManager->delete($id);	
		
				$Admin->rebuildWorkspaceConfig();
				$Admin->flush_cache(array("entries", "customfields"));						
	
				General::redirect($Admin->getCurrentPageURL() . "&_f=complete");
			}
			
			break;
	}

?>