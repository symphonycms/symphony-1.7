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

	$GLOBALS['pageTitle'] = "Data Sources > Untitled";

	$Admin->addScriptToHead('assets/editor.js');

	$sections = $DB->fetch("SELECT * FROM `tbl_sections`");

	$xml_fields = array();

	##Entries
	$xml_fields['entries'] = array(
				"date" => 1,
				"time" => 1,
				"rfc822-date" => 0,
				"pagination-info" => 0,
				"author::first-name" => 1,
				"author::last-name" => 1,
				"author::email" => 0,
				"author::username" => 1
			);

	##Authors
	$xml_fields['authors'] = array(
				"entry-count" => 0,
				"first-name" => 1,
				"last-name" => 1,
				"email" => 1,
				"username" => 1,
				"status" => 1,
				"auth-token" => 1,
				"email-hash" => 0
			);

	##Comments
	$xml_fields['comments'] = array(
				"spam" => 0,
				"author" => 1,
				"date" => 1,
				"time" => 1,
				"rfc822-date" => 0,
				"pagination-info" => 0,
				"authorised" => 1,
				"message" => 1,
				"url" => 1,
				"email" => 0,
				"email-hash" => 0
			);



	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(ucwords(@implode(", ", $required))), false, 'error');
	}

	if(!empty($_POST)) {
		$fields = $_POST['fields'];
		$fields['static_xml'] = General::sanitize($fields['static_xml']);

	}else{
		$fields = array();
		$fields['format_type'] = 'list';
		$fields['max_records'] = 50;
		$fields['page_number'] = 1;
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
	  	<h2>Untitled</h2>

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
					<label>Username <input name="fields[username]" <?php print General::fieldValue("value", $fields['authors']['username']);?> /></label>
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
				$included_fields[$ss['handle']] = 1;
			}

?>
							<optgroup label="<?php print $s['handle']; ?>">

<?php		foreach($included_fields as $name => $state){ ?>
								<option value="[<?php print $s['handle']; ?>][<?php print $name; ?>]" <?php print General::fieldValue("select", $state, "", "1");?>><?php print $name; ?></option>
<?php } ?>

							</optgroup>
<?php
		}
?>
							<optgroup label="authors">
<?php		foreach($xml_fields['authors'] as $name => $state){ ?>
								<option value="[authors][<?php print $name; ?>]" <?php print General::fieldValue("select", $state, "", "1");?>><?php print $name; ?></option>
<?php } ?>
							</optgroup>
							<optgroup label="comments">
<?php		foreach($xml_fields['comments'] as $name => $state){ ?>
								<option value="[comments][<?php print $name; ?>]" <?php print General::fieldValue("select", $state, "", "1");?>><?php print $name; ?></option>
<?php } ?>
							</optgroup>
						</select>
					</label>
					<label class="sections comments">Sort Results by
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
		</fieldset>
	</form>