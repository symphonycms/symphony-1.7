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

	$GLOBALS['pageTitle'] = "Assets > Untitled";

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	if(!empty($_POST)) $fields = $_POST['fields'];

	$fields['body'] = General::sanitize($fields['body']);

	$ignore = array("events", "data-sources", "text-formatters", "pages", "masters", "utilities");
	$directories = General::listDirStructure(WORKSPACE, true, "asc", DOCROOT);

	$Admin->addScriptToHead('assets/editor.js');

?>

	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2>Untitled</h2>
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
                		<option value="<?php print ltrim($d, '/'); ?>"<?php print ($path == $d ? ' selected="selected"' : ""); ?>><?php print ltrim($d, '/'); ?></option>
<?php
					}
				}
?>

					</select>
				</label>
			</fieldset>
			<label>Body <textarea id="code-editor" name="fields[body]" cols="75" rows="30"><?php print General::fieldValue("textarea", $fields['body']);?></textarea></label>
			<input name="action[save]" type="submit" value="Save" accesskey="s" />
		</fieldset>
	</form>