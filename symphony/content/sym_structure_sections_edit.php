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

	$fields = $_POST['fields'];

	if(isset($_REQUEST['id'])){

		$sql = "SELECT * FROM `tbl_sections` WHERE `id` = '" . $_REQUEST['id'] . "'";
		$fields = $DB->fetchRow(0, $sql);

		$customfields = $DB->fetch("SELECT * FROM `tbl_customfields`
									WHERE `parent_section` = '" . $_REQUEST['id'] . "'
									ORDER BY `sortorder` ASC");

		$columns = $DB->fetchCol('field_id', "SELECT * FROM `tbl_sections_visible_columns` WHERE `section_id` = '" . $_REQUEST['id'] . "'");

		for($ii = 0; $ii < count($columns); $ii++){
			$fields['columns'][$columns[$ii]] = '1';
		}


		$fields['allow_authors'] = @explode(",", $fields['allow_authors']);

	}

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$date = $Admin->getDateObj();
				$Admin->pageAlert("saved-time", array("Section", date("h:i:sa", $date->get(true, false))));
				break;
		}
	}

	$GLOBALS['pageTitle'] = 'Sections > ' . $fields['name'];

    $fields['commenting'] = ($fields['commenting'] == 'on' || isset($_POST['fields']['commenting']) ? 1 : 0);
    $fields['calendar_show'] = ($fields['calendar_show'] == 'show' || isset($_POST['fields']['calendar_show']) ? 1 : 0);
	$fields['author_column'] = ($fields['author_column'] == 'show' || isset($_POST['fields']['author_column']) ? 1 : 0);
    $fields['date_column'] = ($fields['date_column'] == 'show' || isset($_POST['fields']['date_column']) ? 1 : 0);

?>
		<form action="<?php print $Admin->getCurrentPageURL() . "&amp;id=" . $_REQUEST['id']; ?>" method="post">
  			<h2><?php print $fields['name']; ?></h2>
			<fieldset>

				<fieldset>
					<label>Sort Entries By
						<select name="fields[entry_order]">
							<option value="date">Date Published</option>
							<option value="author"<?php print ($fields['entry_order'] == 'author' ? ' selected="selected"' : ''); ?>>Author</option>
<?php
				if(is_array($customfields) && !empty($customfields)){
					foreach($customfields as $c){
						print '							<option value="'.$c['id'].'"'.($fields['entry_order'] == $c['id'] ? ' selected="selected"' : '').'>'.$c['name'].'</option>' . CRLF;
					}
				}
?>
						</select>
					</label>
				</fieldset>

				<label>Name <input name="fields[name]" <?php print General::fieldValue("value", $fields['name']); ?> /></label>
				<label><input name="fields[commenting]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["commenting"], "", 1); ?> /> Enable comments for this section</label>
				<label><input name="fields[calendar_show]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["calendar_show"], "", 1); ?> /> Show the 'Published Date and Time' widget when creating and editing entries in this section</label>
				<div id="columns">Show the following columns

<?php

	if(is_array($customfields) && !empty($customfields)){

		foreach($customfields as $c){
?>
					<label><input name="fields[columns][<?php print $c['id'];?>]"<?php print ($c['id'] == $fields['primary_field'] ? ' disabled="disabled"' : ''); ?> type="checkbox" <?php print General::fieldValue("checkbox", $fields["columns"][$c['id']], "", 1); ?> /> <?php print $c['name']; ?></label>

<?php

		}

	}

?>
					<label><input name="fields[author_column]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["author_column"], "", 1); ?> /> Author</label>
					<label><input name="fields[date_column]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["date_column"], "", 1); ?> /> Date Published</label>


				</div>
				<input name="action[save]" type="submit" value="Save" accesskey="s" />
				<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this section" />

			</fieldset>
		</form>
