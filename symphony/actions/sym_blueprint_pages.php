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

	$checked  = @array_keys($_POST['items']);
	
	switch($_POST["with-selected"]) {
		
		case 'delete':
		
			$pages = $checked;
						
			###
			# Delegate: Delete
			# Description: Prior to deletion. Provided with an array of pages for deletion that can be modified.
			$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('pages' => &$pages));			
			
			$pagesList = join (', ', array_map ('intval', $pages));
				
			// 1. Fetch page details
			$query = 'SELECT `id`, `sortorder`, `handle` FROM tbl_pages WHERE `id` IN (' . $pagesList .')';
			$details = $DB->fetch($query);
				
			$DB->delete("tbl_pages","WHERE `id` IN('".implode("','",$checked)."')");
			$DB->delete("tbl_metadata", "WHERE `relation_id` IN ('".$fieldsList."') AND `class` = 'page'"); 	  
		
			foreach($details as $r){
				$DB->query("UPDATE tbl_pages SET `sortorder` = (`sortorder` + 1) WHERE `sortorder` < '".$r['sortorder']."'");     
				@unlink(WORKSPACE . "/pages/" . $r['handle'] . ".xsl");
			}
			
			$Admin->rebuildWorkspaceConfig();
			$Admin->flush_cache(array("pages"));
														  							
			General::redirect($Admin->getCurrentPageURL() . "&_f=deleted");	
			break;  	
    	
  }
	
?>
