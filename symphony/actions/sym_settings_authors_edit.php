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

	include_once(TOOLKIT . "/class.authormanager.php");
    $authorManager = new AuthorManager($Admin);

	$author_id = $_REQUEST['id'];

	if(@array_key_exists("save", $_POST['action']) || @array_key_exists("done", $_POST['action'])) {

		$fields = $_POST['fields'];

		$required = array('firstname', 'lastname', 'username', 'email');

		for($i=0;$i<count($required);$i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}

		if(is_array($errors))
			define("__SYM_ENTRY_MISSINGFIELDS__", true);

		elseif($fields['new_password'] != $fields['confirm_password'])
			$Admin->pageAlert("password-mismatch", NULL, false, 'error');

		elseif((trim($fields['password']) != "") && (md5($fields['password']) != $DB->fetchVar('password', 0, "SELECT `password` FROM tbl_authors WHERE `id` = '".$_REQUEST['id']."' LIMIT 1")))
			 $Admin->pageAlert("password-incorrect", NULL, false, 'error');


		else{

			$current_username = $DB->fetchVar('username', 0, "SELECT `username` FROM `tbl_authors` WHERE `id` = " . $_REQUEST['id']);

       		if((strtolower($current_username) != strtolower($fields['username'])) && $authorManager->fetchByUsername($fields['username'])){
 				$Admin->pageAlert("duplicate", array("An Author", "username"), false, 'error');

 			}else{

			    $author =& $authorManager->create();

				$author->set('id', $_REQUEST['id']);
				$author->set('textformat', $fields['textformat']);

				if(isset($fields['superuser']))
					$author->set('superuser', $fields['superuser']);

				$author->set('email', $fields['email']);
				$author->set('firstname', General::sanitize($fields['firstname']));
				$author->set('lastname', General::sanitize($fields['lastname']));

				if(isset($fields['allow_sections'])) $author->set('allow_sections', @implode(",", $fields['allow_sections']));

				$author->set('auth_token_active', ($fields['auth_token_active'] ? $fields['auth_token_active'] : 'no'));

				if($current_username != $fields['username'])
					$author->set('username', $fields['username']);


				$password_changed = false;

				if(trim($fields['password']) != "" && trim($fields['new_password']) != ""){
					$author->set('password', md5($fields['new_password']));
					$password_changed = true;
				}

				if($author->commit()){

					if($_REQUEST['id'] == $Admin->getAuthorID()){
						$args = unserialize($_COOKIE[__SYM_COOKIE__]);
						$Admin->login($args['username'], (!$password_changed ? $args['password'] : md5($fields['new_password'])), true, true);
					}

					###
					# Delegate: Edit
					# Description: After editing an author. ID of the author is provided.
					$CampfireManager->notifyMembers('Edit', CURRENTPAGE, array("author_id" => $_REQUEST['id']));

	  		    	if(@array_key_exists("save", $_POST['action']))
			         	General::redirect(URL."/symphony/?page=/settings/authors/edit/&id=". $_REQUEST['id']."&_f=saved");

			   	 	General::redirect(URL."/symphony/?page=/settings/authors/&_f=saved");

				}
			}
		}

	}

	if(@array_key_exists("delete", $_POST['action'])){

		###
		# Delegate: Delete
		# Description: Prior to deleting an author. ID is provided.
		$CampfireManager->notifyMembers('Delete', CURRENTPAGE, array("author_id" => $author_id));

		$authorManager->delete($author_id);

		$Admin->flush_cache(array("entries", "authors", "comments"));

		General::redirect(URL . "/symphony/?page=/settings/authors/&_f=complete");
	}

?>
