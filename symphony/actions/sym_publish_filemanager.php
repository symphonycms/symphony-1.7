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
	
	function logAttempt(&$Admin, $file, $dest, $blocked=false, $reason=NULL){
		if($Admin->getConfigVar('log_all_upload_attempts', 'filemanager') == 'yes'){
			$LOG = new Log;
			$LOG->setLogPath(LOGS . '/filemanager');
			$LOG->open();
			$LOG->writetolog(date('Y/m/d @ H:i:s') . ' > ' . addslashes($dest) . addslashes($file) . ' :: ' . ($blocked == true ? 'BLOCKED - ' . $reason : 'ALLOWED'));
		}
	}
	
	## 1. Check that we are in Symphony
	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");
	
	if(@array_key_exists("upload", $_POST['action'])) {
			
		$file = $_FILES["file"];	
			
		if($_POST['destination'] == "") $dest_path = "workspace/";
		else $dest_path = $_POST['destination'];
		$dest_path = "/" . trim($dest_path, "/") . "/";
						
		## 2. Make sure the filemanager is enabled to begin with
		if($Admin->getConfigVar('enabled', 'filemanager') != 'yes'){
			logAttempt($Admin, $file['name'], $dest_path, true, 'File manager disabled');
			$Admin->fatalError('Access Denied', '<p>Access denied. The file manager for this site has been disabled.</p>', true, true);
			die();		
		}
		
		## 3. Check the referer
		if(!preg_match('/\?page=\/publish\/filemanager\//i', $_SERVER['HTTP_REFERER']) || !preg_match('@^'.URL.'\/symphony\/@i', $_SERVER['HTTP_REFERER'])){
			logAttempt($Admin, $file['name'], $dest_path, true, 'Invalid referrer => ' . $_SERVER['HTTP_REFERER']);
			$Admin->fatalError('Access Denied', '<p>Access denied. You are not authorised to access this page.</p>', true, true);
			die();
		}
	
		## 4. Check the user has both cookies and there is a valid author object
		if(!isset($_COOKIE['sym_auth']) || !isset($_COOKIE['sym_auth_safe']) || !isset($Admin->Author) || !is_object($Admin->Author)){
			logAttempt($Admin, $file['name'], $dest_path, true, 'Invalid cookie and/or author object');
			$Admin->fatalError('Access Denied', '<p>Access denied. You are not authorised to access this page.</p>', true, true);
			die();		
		}
	
		## 5. Check the author for validity
		$id = $Admin->Author->get('id');
		$username = $Admin->Author->get('username');
		$password = $Admin->Author->get('password');
		$email = $Admin->Author->get('email');
		$firstname = $Admin->Author->get('firstname');
		$lastname = $Admin->Author->get('lastname');
	
		$sql = "SELECT * FROM `tbl_authors` 
				WHERE `id` = '$id' 
					AND `username` = '$username' 
					AND `password` = '$password' 
					AND `email` = '$email' 
					AND `firstname` = '$firstname' 
					AND `lastname` = '$lastname' 
				LIMIT 1";
	
		$record = $Admin->_db->fetchRow(0, $sql);
		if(!is_array($record) || empty($record) || !$Admin->login($username, $password, true, false)){
			logAttempt($Admin, $file['name'], $dest_path, true, 'Invalid author credentials');
			$Admin->fatalError('Access Denied', '<p>Access denied. You are not authorised to access this page.</p>', true, true);
			die();		
		}
				

		## 6. Check the file extension is safe (whitelist)
		$safe = preg_split('@\s*,\s*@i', $Admin->getConfigVar('filetype_restriction', 'filemanager'), -1, PREG_SPLIT_NO_EMPTY);
		
		$found = false;
		foreach($safe as $ext){
			if(preg_match("/\.$ext/i", $file['name'])){
				$found = true;
				break;
			}
		}
		
		if(!$found){
			logAttempt($Admin, $file['name'], $dest_path, true, 'Invalid file type');
			General::redirect($Admin->getCurrentPageURL() . "&_f=upload-fail&filter=" . $_REQUEST['filter']);	
		}
							
		##Upload the file
		if(@is_uploaded_file($file['tmp_name'])) {
			
			logAttempt($Admin, $file['name'], $dest_path);			
			
			$rel_path = $dest_path;
			$dest_path = DOCROOT . $dest_path;
			
			$temp = $file['tmp_name'];
			$dest = $dest_path . $file['name'];	

			###
			# Delegate: UploadSetDestination
			# Description: File about to be uploaded. Destination is provided and can be modified.
			$CampfireManager->notifyMembers('UploadSetDestination', CURRENTPAGE, array('file' => &$dest));
			
			##Try place the file in the correction location	
			if(@move_uploaded_file($temp, $dest)) {				
				@chmod($dest, intval($Admin->getConfigVar("write_mode", "file"), 8));
				
				###
				# Delegate: Upload
				# Description: File successfully uploaded. Path to it is provided.
				$CampfireManager->notifyMembers('Upload', CURRENTPAGE, array('file' => $dest));   
						  				
				General::redirect($Admin->getCurrentPageURL() . "&_f=upload-success&filter=" . str_replace("workspace", "", $_POST['destination']));			
			
			##Moving Failed
			} else {
				General::redirect($Admin->getCurrentPageURL() . "&_f=upload-fail&filter=" . $_REQUEST['filter']);			
			}
			
		##Could not move the file
		} else {
			General::redirect($Admin->getCurrentPageURL() . "&_f=upload-fail&filter=" . $_REQUEST['filter']);		
		}
	}
	
	$checked  = @array_keys($_POST['items']);
	
	switch($_POST["with-selected"]) {
		
		case 'delete':
		
			###
			# Delegate: Delete
			# Description: Prior to deletion of files. Array of files selected is provided. This can be manipulated.
			$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array('files' => &$checked));

			foreach($checked as $f){

				if(!preg_match('/\/workspace\//i', $f)) break;
					@unlink($f);
			}  		      
		      
            General::redirect($Admin->getCurrentPageURL() . "&_f=deleted&filter=" . $_REQUEST['filter']);	
            break;  
    	
  	}
?>
