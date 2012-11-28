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

	function createMarkupForLocation($location, $fields, $field_schema){

		global $DB;

		if(!is_array($field_schema) || empty($field_schema)) return NULL;

		$code = NULL;

		foreach($field_schema as $row) {

			if($location == $row['location']){

				if($row['type'] != 'upload' && $row['type'] != 'checkbox'){
					$code .= "<label>".$row['name'];
					if(trim($row['description']) != "") $code .= "<small>".$row['description']."</small>" . CRLF;
				}

				switch($row['type']) {
					case 'textarea':

						$row['size'] = min(120, $row['size']);
						$row['size'] = max(6, $row['size']);

						$value = $fields["custom"][$row['handle']];

						if(!empty($_POST['fields']))
							$value = General::sanitize($value);

						$code .= '<textarea name="fields[custom]['.$row['handle'].']" rows="'.$row['size'].'" cols="75">'.General::fieldValue("textarea", $value, $row['value']).'</textarea></label>' . CRLF;
						break;

					case 'input':

						$value = $fields["custom"][$row['handle']];

						if(!empty($_POST['fields']))
							$value = General::sanitize($value);

						$code .= '<input name="fields[custom]['.$row['handle'].']" '.General::fieldValue("value", $value, $row['value']).' /></label>' . CRLF;
					break;

					case 'list':
						if(is_array($fields["custom"][$row['handle']]))
							$fields["custom"][$row['handle']] = @implode(", ", $fields["custom"][$row['handle']]);

						$value = $fields["custom"][$row['handle']];

						if(!empty($_POST['fields']))
							$value = General::sanitize($value);

						$code .= '<input name="fields[custom]['.$row['handle'].']" '.General::fieldValue("value", $fields["custom"][$row['handle']], $row['value']).' /></label>' . CRLF;
						break;

					case 'foreign':

						if($row['foreign_select_multiple'] == 'yes')
							$code .= '<select multiple="multiple" name="fields[custom]['.$row['handle'].'][]">' . CRLF;

						else{
							$code .= '<select name="fields[custom]['.$row['handle'].']">' . CRLF;
							if($row['required'] == 'no') $code .= '<option value=""></option>' . CRLF;
						}

						$sql = "SELECT * FROM `tbl_sections` WHERE `id` = '" . $row['foreign_section']. "'";
						$section = $DB->fetchRow(0, $sql);

						$sql = "SELECT * FROM `tbl_entries2customfields` WHERE `field_id` = '".$section['primary_field']."' ORDER BY `value_raw` ASC";
						$values = $DB->fetch($sql);

						foreach($values as $option){
							$o = NULL;
							$o = General::limitWords($option['value'], 100, true, true);
							$h = $option['handle'];

							if($row['foreign_select_multiple'] == 'yes')
								$code .= '<option '.(@in_array($h, $fields["custom"][$row['handle']]) ? 'selected="selected"' : '').' value="' . $h . '">' . $o . "</option>" . CRLF;

							else
								$code .=  '<option '.General::fieldValue("select", $fields["custom"][$row['handle']], "", $h).' value="' . $h . '">' . $o . "</option>" . CRLF;
						}

						$code .=  "</select></label>" . CRLF;
						break;

					case 'multiselect':

						$code .= '<select multiple="multiple" name="fields[custom]['.$row['handle'].'][]">' . CRLF;

						$options = preg_split('/,/', $row['values'], -1, PREG_SPLIT_NO_EMPTY);
						$options = array_map("trim", $options);

						foreach($options as $o){
							$o = General::sanitize($o);
							$code .= '<option '.(@in_array($o, $fields["custom"][$row['handle']]) ? 'selected="selected"' : '').' value="' . $o . '">' . $o . "</option>" . CRLF;
						}

						$code .= "</select></label>" . CRLF;
						break;

					case 'checkbox':

						$code .= '
					<label><input name="fields[custom]['.$row['handle'].']" type="checkbox" '.General::fieldValue("checkbox", $fields['custom'][$row['handle']], "", "yes").' /> '.($row['description'] != '' ? $row['description'] : $row['name']).'</label>' . CRLF;

						break;

					case 'upload':

						$code .= '							<div class="attachment">'.$row['name'].' '.(trim($row['description']) != "" ? '<small>'.$row['description'].'</small>' : '').'
							<div><input name="fields[custom]['.$row['handle'].'][]" type="file" /></div>' . CRLF;


						$tmp_files = $fields["custom"][$row['handle']];

						if(is_array($tmp_files) && !empty($tmp_files)){

							$code .= '							<ul>' . CRLF;

							unset($tmp_files['upload_directory']);
							unset($tmp_files['deleted_files']);

							foreach($tmp_files as $f){
									$code .= '			<li><a href="'.URL . $f['path'].'">'.$f['path'].'</a></li>' . CRLF;
							}

							$code .= '							</ul>' . CRLF;

						}

						$code .= '							<input name="fields[custom]['.$row['handle'].'][upload_directory]" type="hidden" value="'.$row['destination_folder'].'" />
							<input name="fields[custom]['.$row['handle'].'][deleted_files]" type="hidden" value="" />
						</div>' . CRLF;

						break;

					case 'select':

						$code .= '<select name="fields[custom]['.$row['handle'].']">' . CRLF;

						if($row['required'] == 'no') $code .= '<option value=""></option>' . CRLF;

						$options = preg_split('/,/', $row['values'], -1, PREG_SPLIT_NO_EMPTY);
						$options = array_map("trim", $options);

						foreach($options as $o){
							$o = General::sanitize($o);
							$code .= '<option '.General::fieldValue("select", $fields["custom"][$row['handle']], "", $o).' value="' . $o . '">' . $o . "</option>" . CRLF;
						}

						$code .= '</select></label>' . CRLF;
						break;
				}
				$code .= CRLF;
			}
		}

		return $code;

	}

	include_once(TOOLKIT . "/class.entrymanager.php");
	$entryManager = new EntryManager($Admin);

	$section_id = intval($_REQUEST['_sid']);
	$entry_id = intval($_REQUEST['id']);

	if(!$section = $DB->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `id` = '$section_id' LIMIT 1"))
		$Admin->fatalError("Unknown Section", "<p>The Section you are looking for could not be found.</p>", true, true);

	$date = $Admin->getDateObj();

	if($entry_id) {

		if($fields = $entryManager->fetchEntriesByID($entry_id, false, true)) {

			$can_edit = true;

			$GLOBALS['pageTitle'] = $section ['name'] . ' > ' . strip_tags($fields['fields'][$fields['primary_field']]['value']);

			foreach($fields['fields'] as $key => $f){
				$fields['custom'][$key] = $f['value_raw'];
			}

			if($fields['type'] != "")
				$fields['custom'][$fields['primary_field']] = "[" . $fields['type'] . "] " . $fields['custom'][$fields['primary_field']];

		} else {
			General::redirect(URL . "/symphony/?page=/publish/section/new/&_sid=" . $section_id);
		}

	} else {
		General::redirect(URL . "/symphony/?page=/publish/section/new/&_sid=" . $section_id);
	}

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$Admin->pageAlert("saved-time", array("Entry", date("h:i:sa", $date->get(true, false))));
				break;
		}
	}

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $entryManager->fetchEntryRequiredFields($section_id, true))), false, 'error');

	}elseif(defined("__SYM_ENTRY_VALIDATION_ERROR__")){
		$Admin->pageAlert("validation", __SYM_ENTRY_VALIDATION_ERROR__, false, 'error');

	}elseif(defined("__SYM_ENTRY_FIELD_XSLT_ERROR__")){
		$Admin->pageAlert("xslt-validation", __SYM_ENTRY_FIELD_XSLT_ERROR__, false, 'error');

	}else{

		$TFM = new TextformatterManager(array('parent' => &$Admin));

		$about = $TFM->about($fields['formatter']);
		$entry_formatter = $about['name'];

		$about = $TFM->about($Admin->getAuthorTextFormatter());
		$author_formatter = $about['name'];

		unset($TFM);

		if($entry_formatter != $author_formatter){
			$Admin->pageAlert("diff-formatter", array(($entry_formatter ? $entry_formatter : "raw XHTML"), ($author_formatter ? $author_formatter : "raw XHTML")), false, 'error');
		}
	}

	if(!empty($_POST['fields'])){
		$fields = $_POST['fields'];
		$date->set(strtotime($fields["time"] . " " . $fields['publish_date']));
	}else{
		$date->set($fields['timestamp_gmt'], false);
	}

	$upload_fields = $entryManager->fetchEntryFieldSchema($_REQUEST['_sid'], array('upload'));

	foreach($upload_fields as $row) {
		if($row['type'] == 'upload' && !@is_writable(DOCROOT . "/" . $row['destination_folder'])){
			$Admin->fatalError("Upload Path Not Writable", "<p>The upload path <code>".$row['destination_folder']."</code>, used by your '".$row['name']."' upload custom field, is not writable and needs to be corrected before you can publish to this section.</p>", true, true);
			exit();
		}
	}

	$field_schema = $entryManager->fetchEntryFieldSchema($section_id);

	$drawer = createMarkupForLocation("drawer", $fields, $field_schema);
	$sidebar = createMarkupForLocation("sidebar", $fields, $field_schema);
	$main_content = createMarkupForLocation("main", $fields, $field_schema);

?>

	<form id="entry" action="<?php print $Admin->getCurrentPageURL() . "&amp;id=".$entry_id."&amp;_sid=" . $section_id; ?>" method="post" enctype="multipart/form-data">
		<h2><?php print strip_tags($fields['fields'][$fields['primary_field']]['value']); ?> <?php if($drawer) print '<a class="button configure" href="#config" title="More Options">More</a>'; ?></h2>
		<fieldset>

			<input name="MAX_FILE_SIZE" type="hidden" value="<?php print $Admin->getConfigVar('max_upload_size', 'admin'); ?>" />

			<fieldset>

<?php

		if($section['calendar_show'] == 'show'){
?>

				<fieldset>
					<legend>Date and Time</legend>
                    <label>Date <input name="fields[publish_date]" type="text" value="<?php print date("j F Y", $date->get()); ?>" /></label>
					<label>Time
						<select name="fields[time]">
							<option>Automatic</option>
<?php

							if(!isset($fields['time'])){
								$fields['time'] = date("g:ia", $date->get());
							}

              				print "							<option selected=\"selected\" value=\"".$fields['time']."\">".$fields['time']."</option>\n";


							$suffix = "am";

							for($hh = 0, $mm = "00", $hour = 12; $hh<24;){

								$time = "$hour:$mm$suffix";

								if($time != $fields['time'])
								    print "							<option value=\"$time\">$time</option>\n";

								if($mm == "00") $mm = "30";
								else{
									$mm = "00";
									$hh++;
									$hour++;
									if($hour > 12) $hour = 1;
									if($hh >= 12) $suffix = "pm";
								}

							}
?>
						</select>
					</label>
				</fieldset>
<?php
			}

 print $sidebar;

?>

			</fieldset>

<?php print $main_content;

		if($can_edit){

?>
			<input name="action[save]" type="submit" value="Save" accesskey="s" />
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this entry" />

<?php
		}else{
?>
			<input name="action[save]" disabled="disabled" type="submit" value="Save" accesskey="s" />
<?php
		}
?>
		</fieldset>
<?php


	if($drawer){

?>

		<div id="config">
			<fieldset>
				<h3>More Options</h3>

<?php print $drawer; ?>

			</fieldset>
		</div>
<?php } ?>
	</form>