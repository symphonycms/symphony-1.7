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

	if(isset($_POST['action'])){
		
		$actionParts = array_keys($_POST['action']);
		$action = end($actionParts);
		
		##Login Attempted
		if($action == "login"):
		
			if(empty($_POST['username']) || empty($_POST['password']) || !$Admin->login($_POST['username'], $_POST['password'])) {
				
				###
				# Delegate: LoginFailure
				# Description: Failed login attempt. Username is provided.
				$CampfireManager->notifyMembers('LoginFailure', CURRENTPAGE, array('username' => $_POST['username']));					
				
				General::redirect(URL."/symphony/?page=/login/&_f=error");
			}
		
			###
			# Delegate: LoginSuccess
			# Description: Successful login attempt. Username is provided.
			$CampfireManager->notifyMembers('LoginSuccess', CURRENTPAGE, array('username' => $_POST['username']));					
				
			General::redirect(URL . "/symphony/?page=" . str_replace('&amp;', '&', $Admin->_nav[0]['children'][0]['link']));
		
		##Reset of password requested	
		elseif($action == "reset"):
		
			$author = $DB->fetchRow(0, "SELECT `id`, `email`, `firstname` FROM `tbl_authors` WHERE `email` = '".$_POST['email']."'");	
			
			if(!empty($author)){
	
				if(!$token = $DB->fetchVar("token", 0, "SELECT `token` FROM `tbl_forgotpass` WHERE `author_id` = ".$author['id'])){
					$token = substr(md5(time()), 0, 8);
					$DB->insert(array("author_id" => $author['id'], "token" => $token), "tbl_forgotpass");					
				}					
				
				General::sendEmail($author['email'], 
							"DONOTREPLY@symphony21.com", 
							"Symphony Concierge", 
							"New Symphony Account Password", 
							"Hi " . $author['firstname']. ",\nA new password has been requested " . 
							"for your account. To change your password please click on the following " .
							"link: \n\n\t".URL."/symphony/?page=/login/&action=resetpass&_t=". $token ."\n\n" .
							"If you did not ask for a new password, please disregard this email.\n\nBest " .
							"Regards,\nThe Symphony Team");
			
				###
				# Delegate: PasswordResetSuccess
				# Description: A successful password reset has taken place. Author ID is provided
				$CampfireManager->notifyMembers('PasswordResetSuccess', CURRENTPAGE, array('author_id' => $author['id']));					 			
						
				$_f = "newpass";	
				$error = "You have been sent an email with instructions.";
				
			}else{
				
				###
				# Delegate: PasswordResetFailure
				# Description: A failed password reset has taken place. Author ID is provided
				$CampfireManager->notifyMembers('PasswordResetFailure', CURRENTPAGE, array('author_id' => $author['id']));		
													  				
				$error = "Symphony could not locate your account.";
				$_f = "forgot";
			}	
			
		endif;
	}
	
	
	
	if($_REQUEST['action'] == "resetpass" && isset($_REQUEST['_t'])){
		
		$sql = "SELECT t1.`id`, t1.`email`, t1.`firstname` "
			 . "FROM `tbl_authors` as t1, `tbl_forgotpass` as t2 "
			 . "WHERE t2.`token` = '".$_REQUEST['_t']."' AND t1.`id` = t2.`author_id` "
			 . "LIMIT 1";
		
		$author = $DB->fetchRow(0, $sql);	
		
		if(!empty($author)){
			
			$newpass = General::generatePassword();
			
			General::sendEmail($author['email'], 
						"DONOTREPLY@symphony21.com", 
						"Symphony Concierge", 
						"RE: New Symphony Account Password", 
						"Hi " . $author['firstname']. ",\nAs requested, here is your new Symphony Author Password for '". URL ."' \n\n\t$newpass\n\n".
						"\n\nBest Regards,\nThe Symphony Team");
		
			$DB->update(array("password" => md5($newpass)), "tbl_authors", "WHERE `id` = '".$author['id']."' LIMIT 1");			
			$DB->delete("tbl_forgotpass", "WHERE `author_id` = '".$author['id']."'");
			
			###
			# Delegate: PasswordResetRequest
			# Description: User has requested a password reset. Author ID is provided.
			$CampfireManager->notifyMembers('PasswordResetRequest', CURRENTPAGE, array('author_id' => $author['id']));				
			
			$error = "Password reset. Check your email";
			
		}
	}


?>
