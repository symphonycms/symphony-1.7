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

	if(isset($_POST['with-selected']) && !empty($checked)){

		$action = $_POST['with-selected'];

		switch($action) {

			case 'enable':

				###
				# Delegate: Enable
				# Description: Notifies of enabling Campfire services. Array of selected services is provided.
				#              This can not be modified.
				$CampfireManager->notifyMembers('Enable', CURRENTPAGE, array('services' => $checked));

				if(is_array($checked) && !empty($checked)){
					foreach($checked as $ii){
						list($owner, $name) = explode('/', $ii);
						$CampfireManager->enable($name, $owner);
					}
				}

				break;

			case 'disable':

				###
				# Delegate: Disable
				# Description: Notifies of disabling Campfire services. Array of selected services is provided.
				#              This can be modified.
				$CampfireManager->notifyMembers('Disable', CURRENTPAGE, array('services' => &$checked));

				if(is_array($checked) && !empty($checked)){
					foreach($checked as $ii){
						list($owner, $name) = explode('/', $ii);
						$CampfireManager->disable($name, $owner);
					}
				}

				break;

			case 'hide-from-menu':

				###
				# Delegate: HideFromMenu
				# Description: Notifies of hiding any Campfire services from the menu. Array of selected services is provided.
				#              This can be modified.
				$CampfireManager->notifyMembers('HideFromMenu', CURRENTPAGE, array('services' => &$checked));

				$menu = @array_flip($Admin->_config->_vars['campfire-menu']);

				for($ii = 0; $ii < count($checked); $ii++)
					unset($menu[$checked[$ii]]);

				$Admin->_config->_vars['campfire-menu'] = @array_flip($menu);
				$Admin->saveConfig();

				break;

			case 'show-in-menu':

				###
				# Delegate: ShowInMenu
				# Description: Notifies of showing any Campfire services in the menu. Array of selected services is provided.
				#              This can be modified.
				$CampfireManager->notifyMembers('ShowInMenu', CURRENTPAGE, array('services' => &$checked));

				unset($Admin->_config->_vars['campfire-menu']);

				for($ii = 0; $ii < count($checked); $ii++){
					$Admin->setConfigVar($ii, $checked[$ii], 'campfire-menu');
				}

				$Admin->saveConfig();

				break;
		}

		General::redirect($Admin->getCurrentPageURL() . "&_f=complete-$action");
	}

?>