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

	$GLOBALS['pageTitle'] = "Authors > Untitled";
	$fields = $_POST['fields'];
	
	if(!$Admin->authorIsSuper()) 
		$Admin->fatalError("Access Denied", "<p>Access denied. You are not authorised to access this page.</p>", true, true);
	
	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');		
	}
	
	$TFM = new TextformatterManager(array('parent' => &$Admin));
	$formatters = $TFM->listAll();
	
	$sections = $DB->fetch("SELECT * FROM `tbl_sections`");
	$authors = $DB->fetch("SELECT * FROM `tbl_authors` WHERE `superuser` != '1' && `id` != '".addslashes($_GET['id'])."'");
		
?>
	<form id="settings" action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2>Untitled</h2>
		<fieldset>
			<fieldset id="login-details">
				<legend>Login Details</legend>
				<label>Username <input name="fields[username]" value="<?php print $fields["username"]; ?>" /></label>
				<div class="group">
					<label>Password <input name="fields[password]" type="password" /></label>
					<label>Confirm Password <input name="fields[password_confirm]" type="password" /></label>
				</div>
			</fieldset>

			<fieldset>
				<legend>Personal Information</legend>
				<div class="group">
					<label>First Name <input name="fields[firstname]" value="<?php print $fields["firstname"]; ?>" /></label>
					<label>Last Name <input name="fields[lastname]" value="<?php print $fields["lastname"]; ?>" /></label>
				</div>
				<label>Email Address <input name="fields[email]" value="<?php print $fields["email"]; ?>" /></label>
			</fieldset>

			<fieldset>
				<legend>Status</legend>
				<label>Access privileges
					<select name="fields[superuser]">
						<option value="0"<?php print ($fields['superuser'] == "0" ? ' selected="selected"' : ""); ?>>Author</option>
						<option value="1"<?php print ($fields['superuser'] == "1" ? ' selected="selected"' : ""); ?>>Administrator</option>
					</select>
				</label>
				<p>Administrators have access to all sections.</p>
<?php
		if(is_array($sections) && !empty($sections)){
?>
				<label>Allow access to following sections
					<select name="fields[allow_sections][]" multiple="multiple">
<?php					
					foreach($sections as $s){						
?>
						<option value="<?php print $s['id']; ?>" <?php print (@in_array($s['id'], $fields['allow_sections']) ? ' selected="selected"' : ""); ?>><?php print $s['name']; ?></option>						
<?php						

					}
?>
					</select>
				</label>
<?php 			
		} 

?>
				
			</fieldset>

			<fieldset>
				<legend>Miscellaneous</legend>
				<label>Formatting Preference
					<select name="fields[textformat]">
						<option value="">None</option>
<?php
					foreach($formatters as $name => $about){
?>	
						<option value="<?php print $name; ?>" <?php print General::fieldValue("select", ($fields['textformat'] == $name)); ?>><?php print $about['name']; ?></option>

<?php
					}
?>	
					</select>
				</label>
				<label><input name="fields[auth_token_active]" type="checkbox" value='yes' <?php print ($fields['auth_token_active'] == 'yes' ? 'checked="checked" ' : ''); ?>/> Enable author token</label>
			</fieldset>
			<input name="action[save]" type="submit" accesskey="s" value="Create Author" />

		</fieldset>
	</form>