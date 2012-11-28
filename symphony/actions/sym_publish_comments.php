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

	$checked  = @array_keys($_POST['items']);

	if(isset($_POST['action']['apply']) && !empty($checked)){

		switch($_POST["with-selected"]) {

			case 'blacklist':
				$IP_list = $DB->fetchCol('creator_ip', "SELECT `creator_ip` FROM `tbl_metadata` WHERE `relation_id` IN('".implode("','",$checked)."') AND `class` = 'comment' ");

				if(is_array($IP_list) && !empty($IP_list)){

					$current_blacklist = $Admin->getConfigVar("ip-blacklist", "commenting");
					$current_blacklist = preg_split('/,/', $current_blacklist, -1, PREG_SPLIT_NO_EMPTY);

					foreach($IP_list as $ip){
						if(!@in_array($ip, $current_blacklist)) $current_blacklist[] = $ip;
					}

					$Admin->setConfigVar("ip-blacklist", @implode(', ', $current_blacklist), "commenting");
					$Admin->saveConfig();
					$Admin->flush_cache(array("comments", "entries", "authors"));

				}

				###
				# Delegate: Blacklist
				# Description: After blacklisting some IP addresses. An array of selected comments are provided
				$CampfireManager->notifyMembers('Blacklist', CURRENTPAGE, array("comments" => $checked));

				General::redirect($Admin->getCurrentPageURL() . "&_f=complete-blacklist");
				break;

			case 'spam':
				$fields['spam'] = 'yes';
				$DB->update($fields, "tbl_comments", "WHERE `id` IN('".implode("','",$checked)."')");
				$Admin->flush_cache(array("comments", "entries", "authors"));

				###
				# Delegate: Spam
				# Description: After flagging some comments as spam. An array of selected comments are provided
				$CampfireManager->notifyMembers('Spam', CURRENTPAGE, array("comments" => $checked));

				General::redirect($Admin->getCurrentPageURL() . "&_f=complete-flag");
			break;

			case 'clean':
				$fields['spam'] = 'no';
				$DB->update($fields, "tbl_comments", "WHERE `id` IN('".implode("','", $checked)."')");
				$Admin->flush_cache(array("comments", "entries", "authors"));

				###
				# Delegate: NotSpam
				# Description: After flagging some comments as not spam. An array of selected comments are provided
				$CampfireManager->notifyMembers('NotSpam', CURRENTPAGE, array("comments" => $checked));

				General::redirect($Admin->getCurrentPageURL() . "&_f=complete-unflag");
			break;

			case 'delete':

				###
				# Delegate: Delete
				# Description: Prior to deleting comments. An array of selected comments are provided. This can be modified.
				$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array("comments" => &$checked));

				$DB->delete("tbl_comments", "WHERE `id` IN('".implode("','", $checked)."')");
				$DB->delete("tbl_metadata", "WHERE `relation_id` IN('".implode("','", $checked)."') AND `class` = 'comment'");
				$Admin->flush_cache(array("comments", "entries", "authors"));
				General::redirect($Admin->getCurrentPageURL() . "&_f=complete-delete");
			break;

		}


	}

?>
