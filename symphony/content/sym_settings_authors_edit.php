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

	$author_id = $_REQUEST['id'];

    if(isset($_POST['fields'])){
	    $fields = $_POST['fields'];

	}elseif($author_id) {

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

	if(!$Admin->authorIsOwner() && !$isOwner = ($author_id == $Admin->getAuthorID())){
		if(!$Admin->authorIsSuper()) General::redirect(URL . "/symphony/?page=/settings/authors/summary/&id=$author_id");
		elseif($fields['superuser'] == '1' || $fields['owner'] == '1') General::redirect(URL . "/symphony/?page=/settings/authors/summary/&id=$author_id");
	}

	$date = $Admin->getDateObj();

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$Admin->pageAlert("saved-time", array("Author profile", date("h:i:sa", $date->get(true, false))));
				break;

		}
	}

	$GLOBALS['pageTitle'] = 'Authors > ' . $fields['firstname'] . ' ' . $fields['lastname'];

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	$TFM = new TextformatterManager(array('parent' => &$Admin));
	$formatters = $TFM->listAll();

	$sections = $DB->fetch("SELECT * FROM `tbl_sections`");
	$authors = $DB->fetch("SELECT * FROM `tbl_authors` WHERE `superuser` != '1' && `id` != '".addslashes($_GET['id'])."'");

?>
	<form id="settings" action="<?php print $Admin->getCurrentPageURL(); ?>&amp;id=<?php print $_GET['id']; ?>" method="post">
		<h2><?php print $fields['firstname'] . ' ' . $fields['lastname']; ?></h2>
		<fieldset>
			<fieldset id="login-details">
				<legend>Login Details</legend>
				<label>Username <input name="fields[username]" value="<?php print $fields["username"]; ?>" /></label>
			</fieldset>

			<fieldset>
				<legend>Personal Information</legend>
				<div class="group">
					<label>First Name <input name="fields[firstname]" value="<?php print $fields["firstname"]; ?>" /></label>
					<label>Last Name <input name="fields[lastname]" value="<?php print $fields["lastname"]; ?>" /></label>
				</div>
				<label>Email Address <input name="fields[email]" value="<?php print $fields["email"]; ?>" /></label>
			</fieldset>

<?php if(!$isOwner || ($isOwner && $fields['owner'] == "1")){ ?>
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
<?php } ?>
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
<?php
		if($Admin->getAuthorID() == $author_id || $isOwner || $Admin->authorIsSuper()){
?>
 				<label><input name="fields[auth_token_active]" value="yes" type="checkbox" <?php print ($fields['auth_token_active'] == 'yes' ? ' checked="checked"' : ''); ?> /> Enable author token</label>
<?php
			if($fields['auth_token_active'] == 'yes'){
?>
				<div class="token"><?php print $Admin->authorGenerateAuthToken($author_id); ?></div>
<?php
			}
		}
?>

			</fieldset>

			<input name="action[save]" type="submit" value="Save" accesskey="s"<?php print (!$isOwner && !$Admin->authorIsSuper() ? ' disabled="disabled"' : ""); ?> />

<?php
		if(!$isOwner && $Admin->authorIsSuper()){
?>
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this author" />
<?php
		}
?>
		</fieldset>
	</form>
