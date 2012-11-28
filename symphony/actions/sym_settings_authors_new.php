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

	if(@array_key_exists("save", $_POST['action']) || @array_key_exists("done", $_POST['action'])) {

		$fields = $_POST['fields'];

	    include_once(TOOLKIT . "/class.authormanager.php");
	    $authorManager = new AuthorManager($Admin);

		$required = array('firstname', 'lastname', 'username', 'email', 'password');

		for($i = 0; $i < count($required); $i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}

		if(is_array($errors))
			define("__SYM_ENTRY_MISSINGFIELDS__", true);

		elseif($fields['password'] != $fields['password_confirm'])
			$Admin->pageAlert("password-mismatch", NULL, false, 'error');

		elseif($authorManager->fetchByUsername($fields['username']))
 			$Admin->pageAlert("duplicate", array("An Author", "username"), false, 'error');

		else{


		    $author =& $authorManager->create();

			$author->set('textformat', $fields['textformat']);
			$author->set('superuser', $fields['superuser']);
			$author->set('owner', '0');
			$author->set('email', $fields['email']);
			$author->set('username', $fields['username']);
			$author->set('firstname', General::sanitize($fields['firstname']));
			$author->set('lastname', General::sanitize($fields['lastname']));
			$author->set('last_refresh', NULL);
			$author->set('last_session', NULL);
			$author->set('password', md5($fields['password']));
			$author->set('allow_sections', @implode(",", $fields['allow_sections']));
			$author->set('auth_token_active', ($fields['auth_token_active'] ? $fields['auth_token_active'] : 'no'));

			if($author_id = $author->commit()){

				###
				# Delegate: Create
				# Description: Creation of a new Author. The ID of the author is provided.
				$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('author_id' => $author_id));

	  		    if(@array_key_exists("save", $_POST['action']))
			    	General::redirect(URL."/symphony/?page=/settings/authors/edit/&id=$author_id&_f=saved");

			    General::redirect(URL."/symphony/?page=/settings/authors/&_f=saved");


			}

		}

	}
?>