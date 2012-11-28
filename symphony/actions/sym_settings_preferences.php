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

	##Do not proceed if the config file is read only
    if(!is_writable(CONFIG))
        General::redirect($Admin->getCurrentPageURL());

	$action = @array_keys($_POST['action']);

	if(isset($_POST['action']['save']) || isset($_POST['action']['done'])){

		$settings = $_POST['settings'];

		$required = array("general"  => array("sitename"));

		foreach($required as $key => $fields){
			foreach($fields as $f){
				if(trim($settings[$key][$f]) == "") {
					$errors[$key][$f] = true;
				}
			}
		}

		if(is_array($errors))
			define("__SYM_MISSINGFIELDS__", true);

		else{

			$settings['region']['dst'] = ($settings['region']['dst'] ? 'yes' : 'no');
			$settings['symphony']['allow_workspace_synchronisation'] = ($settings['symphony']['allow_workspace_synchronisation'] ? '1' : '0');

			if($settings['region']['dst'] != $Admin->getConfigVar('dst', 'region')
				|| $settings['region']['time_zone'] != $Admin->getConfigVar('time_zone', 'region')){

				$repairEntries = true;

			}

			foreach($settings as $set => $values) {
				foreach($values as $key => $val) {
					$Admin->setConfigVar($key, $val, $set);
				}
			}

			if($repairEntries){
				require_once(TOOLKIT . '/class.entrymanager.php');
				$em = new EntryManager($Admin);
				$em->repairEntryLocalPublishDates();
			}

			$Admin->saveConfig();

			if(!$errors){
				$Admin->flush_cache("ALL");

				###
				# Delegate: Save
				# Description: Saving of system preferences.
				$CampfireManager->notifyMembers('Save', CURRENTPAGE);

				General::redirect($Admin->getCurrentPageURL() . "&_f=saved");
			}

		}
	}
?>
