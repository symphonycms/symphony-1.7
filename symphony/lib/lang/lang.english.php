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

	##Defines all error messages in the system
	$errors = array();	
	$errors['default'] 				= array('There was a problem rendering this page. Please check Symphony\'s <a href=\'%1\'>activity logs</a> for specific messages.');
	$errors['required'] 			= array('Please fill out the following fields: %1');
	$errors['validation'] 			= array('The following fields contain invalid data: %1');
	$errors['xslt-validation'] 		= array('The following fields contain invalid markup: %1');	
	$errors['saved'] 				= array('%1 saved successfully.');
	$errors['saved-time'] 			= array('%1 saved at %2.');
	$errors['deleted'] 				= array('%1 deleted.');
	$errors['selected-success'] 	= array('Selected %1 have been %2.');
	$errors['action-1-2-success'] 	= array('%1 have been %2.');	
	$errors['upload-success'] 		= array('Upload complete.');
	$errors['upload-fail'] 			= array('Could not upload file. Check permissions on the destination directory.');
	$errors['config-not-writable'] 	= array('Symphony\'s configuration file is not writable. Please check permissions on /manifest/config.php, ensuring that it is PHP writable.');
	$errors['lodged'] 				= array('%1 has been lodged.');
	$errors['server-problem'] 		= array('There was a problem processing your request. Please try again in a few minutes.');
	$errors['write-failed'] 		= array('The %1 cannot be saved. Please check permissions on your /workspace directory, ensuring that all files and folders within it are writable.');
	$errors['duplicate']			= array('%1 with that %2 already exists. Please choose a different %2.');
	$errors['reserved-section-name'] = array('That Section name cannot be used. It clashes with a Symphony reserved word (Authors, Navigation, Comments, Options).');	
	$errors['diff-formatter']		= array('This entry is formatted with <em>\'%1\'</em>. You must use <em>\'%1\'</em> when editing this entry.');
	$errors['password-mismatch']	= array('New passwords did not match.');
	$errors['password-incorrect']	= array('Password entered was incorrect.');
	$errors['author-token-deactivated'] = array('Your authentication token has been deactivated.');
	$errors['author-token-activated'] = array('Your authentication token has been deactivated.');
	$errors['locked-entry']			= array('The author of this entry has not given you permission to edit it. You cannot delete it or save any changes you make.');
	$errors['campfire-incompatible-upgrade']   = array('This Campfire service is not compatible with the version of Symphony you are using. You must update first before you can install it.');
	$errors['campfire-incompatible-downgrade'] = array('The author of this Campfire service may not have updated it to be compatible with the version of Symphony you are using. It may not function as intended.');	
	$errors['campfire-installed']	= array('This Campfire service has already been installed. If you wish to reinstall, please uninstall the existing copy.');
	$errors['campfire-update-available']	= array('This Campfire service is newer than the version you have installed. You can update below.');
	$errors['campfire-install-error'] = array('There was a problem installing this service. Please check Symphony\'s activity logs for specific messages.');		
	$errors['cannot-edit-data-source'] = array('This Data Source is not compatible with the Symphony Data Source editor. If you wish to edit this file you must do so manually.');
	$errors['cannot-edit-event'] = array('This Event is not compatible with the Symphony Event editor. If you wish to edit this file you must do so manually.');
	$errors['workspace-sync-complete'] = array('Workspace folder has been successfully synchronised.');
	$errors['workspace-sync-failed'] = array('Workspace folder failed to synchronise. Please check Symphony\'s activity logs for specific messages.');
	$errors['uninstall-failed'] = array('There was a problem while attempting to uninstall. Please check Symphony\'s activity logs for specific messages.');	
?>
