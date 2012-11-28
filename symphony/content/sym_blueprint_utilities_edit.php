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

	$Admin->addScriptToHead('assets/editor.js');

	if(!isset($_REQUEST['id']))	General::redirect(URL . "/symphony/?page=/blueprint/utilities/new/");

	$fields = array();

	if(isset($_REQUEST['id'])){

		$sql = "SELECT t1.*, t2.* "
			. "FROM `tbl_utilities` as t1 "
			. "LEFT JOIN `tbl_metadata` as t2 ON t2.relation_id = t1.id AND t2.class = 'transformation' "
			. "WHERE t1.id = '".$_REQUEST['id']."' "
			. "GROUP BY t1.id "
			. "LIMIT 1";

		$fields = $DB->fetchRow(0, $sql);

		$GLOBALS['pageTitle'] = 'Utilities > ' . $fields['name'];

		$fields['data_source'] = $DB->fetchCol('data_source', "SELECT `data_source` FROM `tbl_utilities2datasources` WHERE utility_id = '".$_REQUEST['id']."'");
		$fields['events'] = $DB->fetchCol('event', "SELECT `event` FROM `tbl_utilities2events` WHERE utility_id = '".$_REQUEST['id']."'");

		$fields["body"] = @file_get_contents(WORKSPACE . "/utilities/" . $fields['handle'] . ".xsl");
	}

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	$date = $Admin->getDateObj();

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$Admin->pageAlert("saved-time", array("Utility", date("h:i:sa", $date->get(true, false))));
				break;
		}
	}

	if(!empty($_POST)) $fields = $_POST['fields'];

	$fields['body'] = General::sanitize($fields['body']);

	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$datasources = $DSM->listAll();

	$EM = new EventManager(array('parent' => &$Admin));
	$events = $EM->listAll();
?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>&amp;id=<?php print $_GET['id']; ?>" method="post">
  	<h2><?php print $fields['name']; ?></h2>
		<fieldset>
			<label>Name <input name="fields[name]" <?php print General::fieldValue("value", $fields['name']);?> /></label>
			<fieldset>
				<label>Associate with Data Source
					<select multiple="multiple" name="fields[data_source][]">
<?php

				if(is_array($datasources) && !empty($datasources)){

					foreach($datasources as $d){

						print '<option value="'.$d['handle'].'" '.(@in_array($d['handle'], $fields['data_source']) ? ' selected="selected"' : '') . '>'.$d['name'].'</option>' . "\n";


					}

				}

?>
					</select>
				</label>
				<label>Associate with Event
					<select name="fields[events][]" multiple="multiple">
<?php

				if(is_array($events) && !empty($events)){
					foreach($events as $name => $about)
						print '<option value="'.$name.'" '.(@in_array($name, $fields['events']) ? ' selected="selected"' : '').'>'.$about['name'].'</option>' . "\n";

				}

?>
					</select>
				</label>
			</fieldset>
			<label>Body <textarea id="code-editor" name="fields[body]" cols="75" rows="25"><?php print $fields['body'];?></textarea></label>
			<input name="action[save]" type="submit" value="Save" accesskey="s" />
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this utility" />
		</fieldset>
	</form>