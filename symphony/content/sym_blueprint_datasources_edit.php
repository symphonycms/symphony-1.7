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

	$fields = array();

	$dsPath = DATASOURCES . "/data." .  $_REQUEST['file'] . ".php";

	if(!@is_file($dsPath)) General::redirect(URL."/symphony/?page=/blueprint/datasources/new/");

	$sections = $DB->fetch("SELECT * FROM `tbl_sections`");

	$xml_fields = array();

	##Entries
	$xml_fields['entries'] = array(
				"date",
				"time",
				"rfc822-date",
				"pagination-info",
				"author::first-name",
				"author::last-name",
				"author::email",
				"author::username"
			);

	##Authors
	$xml_fields['authors'] = array(
				"entry-count",
				"first-name",
				"last-name",
				"email",
				"username",
				"status",
				"auth-token",
				"email-hash"
			);

	##Comments
	$xml_fields['comments'] = array(
				"spam",
				"author",
				"date",
				"time",
				"rfc822-date",
				"pagination-info",
				"authorised",
				"message",
				"url",
				"email",
				"email-hash"
			);



	$DSM = new DatasourceManager(array('parent' => &$Admin));

	$oDataSource = $DSM->create($_REQUEST['file']);

	$about = $oDataSource->about();

	$GLOBALS['pageTitle'] = 'Data Sources > ' . $about['name'];

	$allow_parse = $oDataSource->allowEditorToParse();
	$type = $oDataSource->getType();

	if(!$allow_parse)
		$Admin->pageAlert("cannot-edit-data-source", NULL, false, 'error');

	else{

		if(!empty($_POST)) {
			$fields = $_POST['fields'];
			$fields['static_xml'] = General::sanitize($fields['static_xml']);

		}else{

			$fields = array();
			$context = array();

			$fields['format_type'] = 'list';

			$vars = @get_class_vars(@get_class($oDataSource));

			$fields['source'] = $type;

			$constants = array(
					array("SHOW_SPAM", "show_spam", "on"),
					array("HANDLE", "handle"),
					array("YEAR", "year"),
					array("MONTH", "month"),
					array("DAY", "day"),
					array("ENCODE", "html_encode", "on"),
					array("LIMIT", "max_records"),
					array("LIMIT_MONTHS", "max_months"),
					array("SORT", "sort"),
					array("INCLUDEPOSTDATED", "includepostdated", "on"),
					array("FORCEEMPTYSET", "force-empty-set", "on"),
					array("USERNAME", "username"),
					array("ENTRY", "entry"),
					array("SHOWENTRIES", "showentries", "on"),
					array("PAGE", "handle"),
					array("PAGENUMBER", "page_number"),
					array("EXCLUDEHIDDEN", "excludehidden", "on"),
					array("MAX_DEPTH", "max_depth"),
					array("CUSTOM", "custom"),
					array("FORMAT_TYPE", "format_type"),
					array("XMLFIELDS", "xml-fields"),
					array("SECTION", "comments"),
					array("STATUS", "status"),
					array("CUSTOMFIELD", "customfield"),
					array("PARENTSECTION", "parentsection")

			);

			foreach($constants as $c){
				list($name, $index, $value) = $c;
				if(array_key_exists("_dsFilter$name", $vars)) $context[$index] = ($value ? $value : $oDataSource->{"_dsFilter$name"});
			}


			if(is_array($context['xml-fields']) && !empty($context['xml-fields'])):
				$tmp = array();
				foreach($context['xml-fields'] as $id => $f){

					if(is_array($f) && !empty($f)){
						foreach($f as $child){
							$tmp[] = "$id::$child";
						}

					}else{
						$tmp[] = $f;
					}

					$fields['xml-fields'][$type] = $tmp;

				}
			endif;

			unset($context['xml-fields']);

			switch($type){

				case "comments":
					break;

				case "authors":
					break;

				case "options":
					$context['customfield'] = $context['parentsection'] . '::' . $context['customfield'];
					break;

				case "navigation":
					$fields['navigation']['handle'] = $context['handle'];
					unset($context['handle']);
					break;

				case "static_xml":
					$fields['static_xml'] = General::sanitize($oDataSource->grab());
					break;

				default:

					if(is_array($context['custom']) && !empty($context['custom'])){
						$fields['custom'][$type] = $context['custom'];
						unset($context['custom']);
					}

					break;

			}

			$fields = array_merge($fields, $context);

		}

	}

	$fields["name"] = General::sanitize($about['name']);

	$date = $Admin->getDateObj();

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(ucwords(@implode(", ", $required))), false, 'error');
	}

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$Admin->pageAlert("saved-time", array("Data source", date("h:i:sa", $date->get(true, false))));
				break;
		}
	}

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(ucwords(@implode(", ", $required))), false, 'error');
	}

	include_once(TOOLKIT . "/class.entrymanager.php");
	$EM = new EntryManager($Admin);

	$can_use_customfield_source = false;

	foreach($sections as $s){
		$schema = $EM->fetchEntryFieldSchema($s['id'], array("select", "multiselect"));

		if(is_array($schema) && !empty($schema)){
			$can_use_customfield_source = true;
			break(1);
		}
	}

?>
	<form id="settings" action="" method="post">
	  	<h2><?php print $about['name']; ?></h2>

		<fieldset>
			<fieldset>
				<legend>Essentials</legend>

				<div class="group">
					<label>Name <input name="fields[name]" <?php print General::fieldValue("value", $fields['name']);?> /></label>
					<label>Source
						<select name="fields[source]">
							<optgroup label="Sections">

<?php
		foreach($sections as $s){
?>
						<option value="<?php print $s['handle']; ?>" <?php print General::fieldValue("select", $fields['source'], "", $s['handle']);?>><?php print $s['name']; ?></option>
<?php
		}
?>

							</optgroup>
							<optgroup label="Other">
								<option value="authors" <?php print General::fieldValue("select", $fields['source'], "", "authors");?>>Authors</option>
								<option value="comments" <?php print General::fieldValue("select", $fields['source'], "", "comments");?>>Comments</option>
								<option value="navigation" <?php print General::fieldValue("select", $fields['source'], "", "navigation");?>>Navigation</option>
<?php if($can_use_customfield_source){ ?>
								<option value="options" <?php print General::fieldValue("select", $fields['source'], "", "options");?>>Custom Field</option>
<?php } ?>
								<option value="static_xml" <?php print General::fieldValue("select", $fields['source'], "", "static_xml");?>>Static XML</option>

							</optgroup>
						</select>
					</label>
				</div>
				<label class="options">Which custom field?
					<select name="fields[customfield]">
<?php
		foreach($sections as $s){

			$schema = $EM->fetchEntryFieldSchema($s['id'], array("select", "multiselect"));

			if(is_array($schema) && !empty($schema)){
?>
						<optgroup label="<?php print $s['name']; ?>">
<?php
					foreach($schema as $k){
						$value = $s['handle'].'::'.$k['handle'];
?>
							<option value="<?php print $value; ?>" <?php print General::fieldValue("select", $fields['customfield'], "", $value);?>><?php print $k['name']; ?></option>
<?php
					}
?>
						</optgroup>

<?php
			}
		}
?>
					</select>
				</label>
			</fieldset>

			<fieldset class="sections comments navigation authors">
				<legend>Filter Options</legend>
				<p>Use <code>$param</code> to filter results by a given <abbr>URL</abbr> parameter.</p>

				<label class="sections comments">Entry Handle <input name="fields[handle]"  <?php print General::fieldValue("value", $fields['handle']);?> /></label>
				<div class="sections comments date group">
					<label>Year <input name="fields[year]" <?php print General::fieldValue("value", $fields['year']);?> /></label>
					<label>Month <input name="fields[month]" <?php print General::fieldValue("value", $fields['month']);?> /></label>
					<label>Day <input name="fields[day]" <?php print General::fieldValue("value", $fields['day']);?> /></label>
				</div>

				<label class="sections"><input type="checkbox" name="fields[includepostdated]" <?php print General::fieldValue("checkbox", $fields['includepostdated'], "", "on");?> /> Include post-dated entries</label>

				<div class="sections comments">
					<label class="sections comments"><input type="checkbox" name="fields[force-empty-set]" <?php print General::fieldValue("checkbox", $fields['force-empty-set'], "", "on");?> /> Return empty result set when no URL parameter values are present <small>Only effects pages with a URL schema.</small></label>
				</div>

				<label class="comments">Comment in Section
					<select name="fields[comments]">
						<option></option>

<?php
		foreach($sections as $s){
?>
						<option value="<?php print $s['handle']; ?>" <?php print General::fieldValue("select", $fields['comments'], "", $s['handle']);?>><?php print $s['name']; ?></option>
<?php
		}
?>
					</select>
				</label>
				<div class="authors group">
					<label>Username <input name="fields[username]" <?php print General::fieldValue("value", $fields['username']);?> /></label>
					<label>Status
						<select name="fields[status]">
							<option></option>
							<option value="author" <?php print ($fields['status'] == 'author' ? 'selected="selected"' : '');?>>Author</option>
							<option value="administrator" <?php print ($fields['status'] == 'administrator' ? 'selected="selected"' : '');?>>Administrator</option>
							<option value="owner" <?php print ($fields['status'] == 'owner' ? 'selected="selected"' : '');?>>Owner</option>
						</select>
					</label>
				</div>

				<label class="comments"><input name="fields[show_spam]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['show_spam'], "", "on");?> /> Show spam comments</label>
				<label class="navigation">Parent Page <input name="fields[navigation][handle]" <?php print General::fieldValue("value", $fields['navigation']['handle']);?> /></label>
			</fieldset>

<?php

		foreach($sections as $index => $s){
			$schema = $EM->fetchEntryFieldSchema($s['id'], array('checkbox', 'select', 'multiselect', 'foreign', 'input'));

			if(!empty($schema)) $schemas[] = array($index, $schema);
		}

		if(is_array($schemas) && !empty($schemas)){

?>

			<fieldset class="sections">
				<legend>Filter by Custom Field</legend>
				<p>Specify a value for any custom field to only show entries with that value.</p>

<?php

			foreach($schemas as $schema){

				$s = $sections[$schema[0]];
				$schema = $schema[1];

				$remainder = count($schema) % 2;

				$ii = 0;

				$match = false;

				if(count($schema) > 1){

					for($ii = 0; $ii < count($schema) - 1; $ii+=2){

						print '				<div class="group">' . CRLF;

						for($xx = $ii; $xx <= $ii+1; $xx++){


							print "					<label class=\"custom-filter\">" . $schema[$xx]['name'] . CRLF;

							if($schema[$xx]['type'] != 'input')
								print " 					<select name=\"fields[custom][".$s['handle']."][".$schema[$xx]['handle']."] \">" . CRLF .
								      "							<option></option>" . CRLF;


							if($schema[$xx]['type'] == 'checkbox'){
								print '							<option value="yes" '.General::fieldValue("select", $fields['custom'][$s['handle']][$schema[$xx]['handle']], "", "yes").'>Yes</option>'.CRLF.
									  '							<option value="no" '.General::fieldValue("select", $fields['custom'][$s['handle']][$schema[$xx]['handle']], "", "no").'>No</option>';

								$match = ($fields['custom'][$s['handle']][$schema[$xx]['handle']] == 'yes' || $fields['custom'][$s['handle']][$schema[$xx]['handle']] == 'no');

							}elseif($schema[$xx]['type'] == 'select'){
								$bits = preg_split('/,/', $schema[$xx]['values'], -1, PREG_SPLIT_NO_EMPTY);
								foreach($bits as $o){
									$o = trim($o);
									print "							<option value=\"$o\" ".General::fieldValue("select", $fields['custom'][$s['handle']][$schema[$xx]['handle']], "", $o).">$o</option>\n";
								}

								$match = @in_array($fields['custom'][$s['handle']][$schema[$xx]['handle']], $bits);

							}elseif($schema[$xx]['type'] == 'foreign'){
							    $row = $schema[$xx];

								$sql = "SELECT * FROM `tbl_sections` WHERE `id` = '" . $row['foreign_section']. "'";
								$section = $DB->fetchRow(0, $sql);

								$sql = "SELECT * FROM `tbl_entries2customfields` WHERE `field_id` = '".$section['primary_field']."' ORDER BY `value_raw` ASC";
								$values = $DB->fetch($sql);

								$match = false;

								foreach($values as $option){
									$o = NULL;
									$o = General::limitWords($option['value'], 100, true, true);
									$h = $option['handle'];

									if($h == $fields['custom'][$s['handle']][$schema[$xx]['handle']]) $match = true;

									print "						<option value=\"$h\" ".($h == $fields['custom'][$s['handle']][$schema[$xx]['handle']] ? ' selected="selected"' : '').">$o</option>\n";
								}

							}elseif($schema[$xx]['type'] == 'input'){
								print ' 					<input type="text" name="fields[custom]['.$s['handle'].']['.$schema[$xx]['handle'].']" value="'.$fields['custom'][$s['handle']][$schema[$xx]['handle']].'" />' . CRLF;


							}else{
								$bits = preg_split('/,/', $schema[$xx]['values'], -1, PREG_SPLIT_NO_EMPTY);
								foreach($bits as $o){
									$o = trim($o);
									print "						<option value=\"$o\" ".(@in_array($o, $fields['custom'][$s['handle']][$schema[$xx]['handle']]) ? ' selected="selected"' : '').">$o</option>\n";
								}

								$match = @in_array($fields['custom'][$s['handle']][$schema[$xx]['handle']], $bits);
							}

							if(!$match && $fields['custom'][$s['handle']][$schema[$xx]['handle']] != '' && $schema[$xx]['type'] != 'input')
								print '							<option value="'.$fields['custom'][$s['handle']][$schema[$xx]['handle']].'" selected="selected">'.$fields['custom'][$s['handle']][$schema[$xx]['handle']].'</option>';

							if($schema[$xx]['type'] != 'input')
								print '						</select>' . CRLF;

							print '					</label>' . CRLF;

						}

						print '				</div>' . CRLF;

					}
				}

				if($remainder){

					$f = end($schema);


					print "					<label class=\"custom-filter\">" . $f['name'] . CRLF;

					if($f['type'] != 'input')
						print " 					<select name=\"fields[custom][".$s['handle']."][".$f['handle']."] \">" . CRLF .
						      "							<option></option>" . CRLF;

					if($f['type'] == 'checkbox'){
						print '						<option value="yes" '.General::fieldValue("select", $fields['custom'][$s['handle']][$f['handle']], "", "yes").'>Yes</option>'.CRLF.
							  '						<option value="no" '.General::fieldValue("select", $fields['custom'][$s['handle']][$f['handle']], "", "no").'>No</option>';

						$match = ($fields['custom'][$s['handle']][$f['handle']] == 'yes' || $fields['custom'][$s['handle']][$f['handle']] == 'no');

					}elseif($f['type'] == 'select'){
						$bits = preg_split('/,/', $f['values'], -1, PREG_SPLIT_NO_EMPTY);
						foreach($bits as $o){
							$o = trim($o);
							print "						<option value=\"$o\" ".General::fieldValue("select", $fields['custom'][$s['handle']][$f['handle']], "", $o).">$o</option>\n";
						}

						$match = @in_array($fields['custom'][$s['handle']][$f['handle']], $bits);

					}elseif($f['type'] == 'foreign'){
					    $row = $f;

						$sql = "SELECT * FROM `tbl_sections` WHERE `id` = '" . $row['foreign_section']. "'";
						$section = $DB->fetchRow(0, $sql);

						$sql = "SELECT * FROM `tbl_entries2customfields` WHERE `field_id` = '".$section['primary_field']."' ORDER BY `value_raw` ASC";
						$values = $DB->fetch($sql);

						$match = false;

						foreach($values as $option){
							$o = NULL;
							$o = General::limitWords($option['value'], 100, true, true);
							$h = $option['handle'];

							if($h == $fields['custom'][$s['handle']][$f['handle']]) $match = true;

							print "						<option value=\"$h\" ".($h == $fields['custom'][$s['handle']][$f['handle']] ? ' selected="selected"' : '').">$o</option>\n";
						}

					}elseif($f['type'] == 'input'){
						print ' 					<input type="text" name="fields[custom]['.$s['handle'].']['.$f['handle'].']" value="'.$fields['custom'][$s['handle']][$f['handle']].'" />' . CRLF;

					}else{
						$bits = preg_split('/,/', $f['values'], -1, PREG_SPLIT_NO_EMPTY);
						foreach($bits as $o){
							$o = trim($o);
							print "						<option value=\"$o\" ".(@in_array($o, $fields['custom'][$s['handle']][$f['handle']]) ? ' selected="selected"' : '').">$o</option>\n";
						}

						$match = @in_array($fields['custom'][$s['handle']][$f['handle']], $bits);
					}

					if(!$match && $fields['custom'][$s['handle']][$f['handle']] != '' && $f['type'] != 'input')
						print '							<option value="'.$fields['custom'][$s['handle']][$f['handle']].'" selected="selected">'.$fields['custom'][$s['handle']][$f['handle']].'</option>';

					if($f['type'] != 'input')
						print '						</select>' . CRLF;

					print '					</label>' . CRLF;
				}

			}

			print '			</fieldset>';
		}

?>
			<fieldset class="sections comments navigation authors">
				<legend>Format Options</legend>

				<label class="sections">Format Style
					<select name="fields[format_type]">
						<option value="list" <?php print ($fields['format_type'] == 'list' ? ' selected="selected"' : ''); ?>>Entry List</option>
						<option value="archive" <?php print ($fields['format_type'] == 'archive' ? ' selected="selected"' : ''); ?>>Group by Date</option>
						<option value="archive-overview" <?php print ($fields['format_type'] == 'archive-overview' ? ' selected="selected"' : ''); ?>>Archive Overview</option>
					</select>
				</label>

				<div class="group">
					<label class="sections comments authors">Included Elements
						<select name="fields[xml-elements][]" multiple="multiple">
<?php

		foreach($sections as $s){

			$schema_all = $EM->fetchEntryFieldSchema($s['id']);
			$included_fields = $xml_fields['entries'];

			foreach($schema_all as $ss){
				$included_fields[] = $ss['handle'];
			}


?>
							<optgroup label="<?php print $s['handle']; ?>">

<?php		foreach($included_fields as $name){

				$state = 0;

				if(@in_array($name, $fields['xml-fields'][$s['handle']]))
					$state = 1;

				else
					$state = 0;

?>
								<option value="[<?php print $s['handle']; ?>][<?php print $name; ?>]" <?php print General::fieldValue("select", $state, "", "1");?>><?php print $name; ?></option>
<?php } ?>

							</optgroup>
<?php
		}
?>
							<optgroup label="authors">
<?php		foreach($xml_fields['authors'] as $name){

				$state = 0;

				if(@in_array($name, $fields['xml-fields']['authors']))
					$state = 1;

				else
					$state = 0;
?>
								<option value="[authors][<?php print $name; ?>]" <?php print General::fieldValue("select", $state, "", "1");?>><?php print $name; ?></option>
<?php } ?>
							</optgroup>
							<optgroup label="comments">
<?php		foreach($xml_fields['comments'] as $name){

				$state = 0;

				if(@in_array($name, $fields['xml-fields']['comments']))
					$state = 1;

				else
					$state = 0;


?>
								<option value="[comments][<?php print $name; ?>]" <?php print General::fieldValue("select", $state, "", "1");?>><?php print $name; ?></option>
<?php } ?>
							</optgroup>
						</select>
					</label>
					<label label class="sections comments">Sort Results by
						<select name="fields[sort]">
							<option value="asc" <?php print General::fieldValue("select", $fields['sort'], "", "asc");?>>Ascending Date (earliest first)</option>
							<option value="desc" <?php print General::fieldValue("select", $fields['sort'], "", "desc");?>>Descending Date (latest first)</option>
						</select>
					</label>
				</div>
				<label class="sections comments"><input name="fields[html_encode]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['html_encode'], "", "on");?> /> HTML-encode text <small>Useful for <abbr>RSS</abbr> feeds</small></label>

				<label class="navigation">Limit to <input name="fields[max_depth]" size="2" maxlength="2" <?php print General::fieldValue("value", $fields['max_depth'], "");?> /> level(s) deep.</label>
			</fieldset>

			<fieldset class="static_xml">
				<legend>Static XML</legend>
				<label>Body <textarea id="code-editor" name="fields[static_xml]" rows="20" cols="75"><?php print $fields['static_xml']; ?></textarea></label>
			</fieldset>

			<fieldset class="sections comments">
				<legend>Limit Options</legend>
				<div class="group">
<?php if(isset($fields['max_months'])){ ?>
					<label>Show a maximum of <input name="fields[max_months]" size="3" maxlength="3" <?php print General::fieldValue("value", $fields['max_months'], "12");?>> month(s).</label>
<?php }else{ ?>
					<label>Show a maximum of <input name="fields[max_records]" size="3" maxlength="3" <?php print General::fieldValue("value", $fields['max_records'], "50");?> /> record(s).</label>

<?php } ?>
					<label>Show page <input name="fields[page_number]" size="3" <?php print General::fieldValue("value", $fields['page_number'], ""); ?> /> of results. <small>Accepts <code>$param</code> values</small></label>

				</div>
			</fieldset>

			<input name="action[save]" type="submit" value="Save" accesskey="s" />
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this data source" />
		</fieldset>
	</form>
