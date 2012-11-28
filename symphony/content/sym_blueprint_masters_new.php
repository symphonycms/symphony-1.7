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

	$GLOBALS['pageTitle'] = 'Masters > Untitled';

	$Admin->addScriptToHead('assets/editor.js');

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	if(!empty($_POST)) {
		$fields = $_POST['fields']; $fields['body'] = General::sanitize($fields['body']);

	}else{
		$fields['body'] = '<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output
	method="xml"
	doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
	doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<html>
		<head>
			<title><xsl:value-of select="$page-title"/></title>
		</head>
		<body>
			<xsl:apply-templates/>
		</body>
	</html>
</xsl:template>

</xsl:stylesheet>';

		$fields['body'] = str_replace("<", "&lt;", $fields['body']);

	}

	$utilities = $DB->fetch("SELECT DISTINCT t1.*
							 FROM `tbl_utilities` as t1
							 LEFT JOIN `tbl_utilities2datasources` as t2 ON t1.id = t2.utility_id
							 LEFT JOIN `tbl_utilities2events` as t3 ON t1.id = t3.utility_id
							 WHERE (t2.`data_source` IS NULL AND t3.`event` IS NULL)
							 OR (t2.`data_source` IN ('".@implode("', '", $fields['data_sources'])."')
							 OR t3.`event` IN ('".@implode("', '", $fields['events'])."'))");

	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$datasources = $DSM->listAll();

	$EM = new EventManager(array('parent' => &$Admin));
	$events = $EM->listAll();

?>

  	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
  	<h2>Untitled <a class="button configure" href="#config" title="Configure master settings">Configure</a></h2>
		<fieldset>
			<fieldset>
				<fieldset id="utilities">
					<legend>Available Utilities</legend>
					<ul>
<?php
		if(empty($utilities) || !is_array($utilities)){
			print "<li></li>";

		}else{

			foreach($utilities as $u){
?>
						<li><a href="<?php print URL; ?>/symphony/?page=/blueprint/utilities/edit/&amp;id=<?php print $u['id']; ?>"><?php print $u['name']; ?></a></li>
<?php
			}
		}
?>
					</ul>
				</fieldset>
			</fieldset>
			<label>Name <input name="fields[name]" type="text" <?php print General::fieldValue("value", $fields['name']);?> /></label>
			<label>Body <textarea id="code-editor" cols="75" rows="25" name="fields[body]"><?php print General::fieldValue("textarea", $fields['body']);?></textarea></label>
			<input name="action[save]" type="submit" value="Save" accesskey="s" />

		</fieldset>
		<div id="config">
			<h3>Master Settings</h3>
			<fieldset>
				<legend>Master Environment</legend>
				<div class="group">
					<label>Data Source <acronym title="eXtensible Markup Language">XML</acronym>
						<select name="fields[data_sources][]" multiple="multiple">
<?php

				if(is_array($datasources) && !empty($datasources)){
					foreach($datasources as $name => $about){
						print '<option value="'.$name.'" '.(@in_array($name, $fields['data_sources']) ? ' selected="selected"' : '').'>'.$about['name'].'</option>' . "\n";
					}
				}

?>
						</select>
					</label>
					<label>Attach Event
						<select name="fields[events][]" multiple="multiple">
<?php

				if(is_array($events) && !empty($events)){
					foreach($events as $name => $about)
						print '<option value="'.$name.'" '.(@in_array($name, $fields['events']) ? ' selected="selected"' : '').'>'.$about['name'].'</option>' . "\n";

				}

?>
						</select>
					</label>
				</div>
			<fieldset>
		</div>
	</form>