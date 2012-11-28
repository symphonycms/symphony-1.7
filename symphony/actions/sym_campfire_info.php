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

	if(isset($_POST['action']['uninstall'])){

		$service = $_REQUEST['name'];

		###
		# Delegate: Uninstall
		# Description: Triggered prior to any uninstallation. Array of selected services is provided.
		#              This cannot be modified.
		$CampfireManager->notifyMembers('Uninstall', CURRENTPAGE, array('service' => $service));

		list($owner, $name) = explode('/', $service);
		$CampfireManager->uninstall($name, $owner);

		General::redirect(URL . '/symphony/?page=/campfire/&_f=complete-uninstall');
	}

	elseif(isset($_POST['action']['install'])){

		$service = $_REQUEST['name'];

		###
		# Delegate: Install
		# Description: Notifies of installing a Campfire services. service name is provided.
		#              This cannot be modified.
		$CampfireManager->notifyMembers('Install', CURRENTPAGE, array('service' => $service));

		list($owner, $name) = explode('/', $service);
		$CampfireManager->install($name, $owner);

		General::redirect(URL . '/symphony/?page=/campfire/&_f=complete-install');

	}

	elseif(isset($_POST['action']['update'])){

		$service = $_REQUEST['name'];

		###
		# Delegate: Update
		# Description: Notifies of updating a Campfire services. service name is provided.
		#              This cannot be modified.
		$CampfireManager->notifyMembers('Update', CURRENTPAGE, array('service' => $service));

		list($owner, $name) = explode('/', $service);
		$CampfireManager->update($name, $owner);

		General::redirect(URL . '/symphony/?page=/campfire/&_f=complete-update');

	}

?>