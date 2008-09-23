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

	if(array_key_exists("save", $_POST['action'])) {
	
		$fields = $_POST['fields'];	
		
		$required = array('author_name', 'body');
		
		for($i=0;$i<count($required);$i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}
		
		if(is_array($errors))
			define("__SYM_ENTRY_MISSINGFIELDS__", true);

		else{
			
			$fields['author_name']    = General::sanitize($fields['author_name']);
			$fields['author_url']     = General::validateURL($fields['author_url']);
			$fields['author_email']	  = General::sanitize($fields['author_email']);
			$fields['body']			  = $fields['body'];
			$fields['spam']           = ($fields['spam'] ? "yes" : "no");		
	
			if($fields['blacklist'] && $fields['author_ip'] != ''){
				$ip = trim($fields['author_ip']);
				$current_blacklist = $Admin->getConfigVar("ip-blacklist", "commenting");
				$current_blacklist = preg_split('/,/', $current_blacklist, -1, PREG_SPLIT_NO_EMPTY);			
				$current_blacklist = @array_map("trim", $current_blacklist);
				
				if(!@in_array($ip, $current_blacklist)) $current_blacklist[] = $ip;

				$Admin->setConfigVar("ip-blacklist", @implode(', ', $current_blacklist), "commenting");								
				$Admin->saveConfig();
				$Admin->flush_cache(array("comments", "entries", "authors"));	

			}elseif(!$fields['blacklist'] && $fields['author_ip'] != ''){
			
				$ip = trim($fields['author_ip']);
				$current_blacklist = $Admin->getConfigVar("ip-blacklist", "commenting");
				$current_blacklist = preg_split('/,/', $current_blacklist, -1, PREG_SPLIT_NO_EMPTY);			
				$current_blacklist = @array_map("trim", $current_blacklist);
				
				$new_blacklist = array();
				
				if(@in_array($ip, $current_blacklist) && is_array($current_blacklist) && !empty($current_blacklist)){
				
					foreach($current_blacklist as $a){
						if($a != $ip) $new_blacklist[] = $a;
					}
					
					$Admin->setConfigVar("ip-blacklist", @implode(', ', $new_blacklist), "commenting");								
					$Admin->saveConfig();
					$Admin->flush_cache(array("comments", "entries", "authors"));						
				}			
			}
			
			$DB->query("UPDATE `tbl_metadata` SET `creator_ip` = '".$fields['author_ip']."' 
						WHERE `class` = 'comment' AND `relation_id` = '".$_REQUEST['id']."' LIMIT 1");
			
			unset($fields['author_ip']);
			unset($fields['blacklist']);
			
			if($DB->update($fields, "tbl_comments", "WHERE `id` = '".$_REQUEST['id']."'")){
				$Admin->updateMetadata("comment", $_REQUEST['id'], false);
				$Admin->flush_cache(array("comments", "entries", "authors"));
				
				###
				# Delegate: Edit
				# Description: Saving of a comment. Comment ID is provided
				$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array('comment_id' => $_REQUEST['id']));						
				
				General::redirect(URL . "/symphony/?page=/publish/comments/&_f=saved&id=".$_REQUEST["id"]);			
			}
		}

	}elseif(array_key_exists("delete", $_POST['action'])) {
		    
		$comment_id = $_REQUEST['id'];
		   
		###
		# Delegate: Delete
		# Description: Prior to deletion of a comment. Comment ID is provided, this can be manipulated
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('comment_id' => &$comment_id));
		
        $DB->delete("tbl_comments", "WHERE `id` = '$comment_id' LIMIT 1");
        $DB->delete("tbl_metadata", "WHERE `relation_id` = '$comment_id' AND `class` = 'comment' LIMIT 1");
        
        $Admin->flush_cache(array("comments", "entries", "authors"));
            
        General::redirect(URL . "/symphony/?page=/publish/comments/&_f=complete");	
		
	}
?>
