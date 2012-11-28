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

	if(isset($_POST['action']['apply'])){
		$checked  = @array_keys($_POST['items']);

		if(!empty($checked) && is_array($checked)){
	        switch($_POST["with-selected"]) {

            	case 'delete':

					###
					# Delegate: Delete
					# Description: Prior to deletion of entries. Section ID and Array of Entries is provided.
					#              The array can be manipulated
					$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('section_id' => $_REQUEST['_sid'],
														  						 'entry_id' => &$checked));

					include_once(TOOLKIT . "/class.entrymanager.php");
					$entryManager = new EntryManager($Admin);

					$entryManager->delete($checked);

					$Admin->flush_cache(array("entries", "authors", "comments"));

				 	General::redirect($Admin->getCurrentPageURL() . "&_sid=".$_REQUEST['_sid']."&_f=complete");
			}
		}
	}

?>