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

	##Do not proceed if the config file is read only
    if(!is_writable(CONFIG)) General::redirect($Admin->getCurrentPageURL());

	if(isset($_POST['action']['delete_spam'])){
		
		$spam = $DB->fetchCol("id", "SELECT `id` FROM `tbl_comments` WHERE `spam` = 'yes'");

		###
		# Delegate: DeleteSpam
		# Description: Prior to deletion of spam comments. Array of comments to be deleted is provided.
		# 			   This can be manipulated.
		$CampfireManager->notifyMembers('DeleteSpam', CURRENTPAGE, array('comments' => &$spam));

		$DB->delete("tbl_comments", "WHERE `id` IN('".implode("','", $spam)."')");
		$DB->delete("tbl_metadata", "WHERE `relation_id` IN('".implode("','", $spam)."') AND `class` = 'comment'");
		$Admin->flush_cache(array("comments", "entries", "authors"));      
       
		General::redirect($Admin->getCurrentPageURL() . "&_f=complete-delete");
			
	}

	if(isset($_POST['action']['save']) || isset($_POST['action']['done'])){
		
		$settings = $_POST['fields'];

		if(is_array($errors))
			define("__SYM_MISSINGFIELDS__", true);
			
		else{		

			$settings['email-notify'] 		= ($settings['email-notify']	  == "on" ? "on" : "off");
			$settings['allow-duplicates'] 	= ($settings['allow-duplicates']  == "on" ? "off" : "on");
			$settings['hide-spam-flagged'] 	= ($settings['hide-spam-flagged'] == "on" ? "on" : "off");
			$settings['convert-urls'] 		= ($settings['convert-urls']	  == "on" ? "on" : "off");
			$settings['check-referer'] 		= ($settings['check-referer'] 	  == "on" ? "on" : "off");
			$settings['nuke-spam'] 			= ($settings['nuke-spam'] 	  	  == "on" ? "on" : "off");
						
			foreach($settings as $key => $val) {
				$Admin->setConfigVar($key, $val, "commenting");
			}			

			$Admin->saveConfig();

			if(!$errors){
				$Admin->flush_cache(array("comments", "entries", "authors"));
				
				###
				# Delegate: SaveConfiguration
				# Description: After saving comment settings
				$CampfireManager->notifyMembers('SaveConfiguration', CURRENTPAGE);    				
				
				General::redirect($Admin->getCurrentPageURL() . "&_f=saved");
			}
				
		}
	}
?>
