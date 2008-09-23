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

	$author_id = $_REQUEST['id'];

    if($author_id) {
		
			$sql = "SELECT *  "
			  	. "FROM `tbl_authors` "
		      . "WHERE `id` = '".addslashes($_GET['id'])."' ";
		      
		if($fields = $DB->fetchRow(0, $sql)) {
			$fields['allow_sections'] = @explode(",", $fields['allow_sections']);
			
		} else {
			General::redirect(URL . "/symphony/?page=/settings/authors/new/");
		}
		
	}else{
		General::redirect(URL . "/symphony/?page=/settings/authors/new/");
	}
	
	$GLOBALS['pageTitle'] = $fields['firstname'] . " " . $fields['lastname'];
	
	$status = ($fields['superuser'] == '1' ? 'Administrator' : 'Author');
	$status = ($fields['owner'] == '1' ? 'Owner' : $status);
	
?>

	<form id="settings" action="" method="post">
		<h2><!-- PAGE TITLE --></h2>
		<div class="summary">
			<p>You are not permitted to make changes to this author. Below is a summary of this author's details.</p>
			<dl>

				<dt>Username</dt>
				<dd><?php print $fields['username']; ?></dd>
				<dt>First Name</dt>
				<dd><?php print $fields['firstname']; ?></dd>
				<dt>Last Name</dt>
				<dd><?php print $fields['lastname']; ?></dd>
				<dt>Email Address</dt>
				<dd><a href="mailto:<?php print $fields['email']; ?>"><?php print $fields['email']; ?></a></dd>
				<dt>Status</dt>
				<dd><?php print $status; ?></dd>

			</dl>
		</div>
	</form>