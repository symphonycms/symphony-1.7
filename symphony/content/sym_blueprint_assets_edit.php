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

	if(!@is_file(WORKSPACE . $_REQUEST['file'])) General::redirect(URL."/symphony/?page=/blueprint/assets/new/");

	$Admin->addScriptToHead('assets/editor.js');

    $type = General::getExtension($_REQUEST['file']);

	$GLOBALS['pageTitle'] = 'Assets > ' . basename($_REQUEST['file']);
	$fields = General::getFileMeta(WORKSPACE . $_REQUEST['file']);

  	$fields["name"] = basename($_REQUEST['file']);

	$ignore = array("events", "data-sources", "text-formatters", "pages", "masters", "utilities");

  	$fields["location"] = "/workspace" . dirname($_REQUEST['file']) . "/";

  	$fields["type"] = $type;
	$fields["body"] = @file_get_contents(WORKSPACE . $_REQUEST['file']);

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	$date = $Admin->getDateObj();

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$Admin->pageAlert("saved-time", array("Asset", date("h:i:sa", $date->get(true, false))));
				break;
		}
	}

	if(!empty($_POST)) $fields = $_POST['fields'];

	$fields['body'] = General::sanitize($fields['body']);

	$directories = General::listDirStructure(WORKSPACE, true, "asc", DOCROOT);

?>

  	<form action="<?php print $Admin->getCurrentPageURL(); ?>&amp;file=<?php print $_REQUEST['file']; ?>" method="post">
		<h2><?php print basename($_REQUEST['file']); ?></h2>
		<fieldset>
			<label>Name <input name="fields[name]" type="text" <?php print General::fieldValue("value", $fields['name']);?> /></label>
			<fieldset>
				<label>Put in folder
					<select name="fields[location]">
				<option>workspace/</option>
<?php
			foreach($directories as $d){
				if(!in_array($d, $ignore)){
?>
                		<option value="<?php print ltrim($d, '/'); ?>"<?php print ($fields["location"] == $d ? ' selected="selected"' : ""); ?>><?php print ltrim($d, '/'); ?></option>
<?php
				}
			}
?>

					</select>
				</label>
			</fieldset>
			<label>Body <textarea id="code-editor" name="fields[body]" cols="75" rows="30"><?php print General::fieldValue("textarea", $fields['body']);?></textarea></label>
			<input name="action[save]" type="submit" value="Save" accesskey="s" />
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this asset" />
		</fieldset>
	</form>