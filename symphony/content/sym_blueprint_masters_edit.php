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

	if(!@is_file(WORKSPACE . "/masters/" . $_REQUEST['file'] . ".xsl")) 
		General::redirect(URL."/symphony/?page=/blueprint/masters/new/");

	$Admin->addScriptToHead('assets/editor.js');

	$GLOBALS['pageTitle'] = 'Masters > ' . $_REQUEST['file'];

	$fields = array();

	$sql = "SELECT t1.*, t2.* "
		. "FROM `tbl_masters` as t1 "
		. "LEFT JOIN `tbl_metadata` as t2 ON t2.relation_id = t1.id AND t2.class = 'master' "
		. "WHERE t1.name = '".$_REQUEST['file']."' "
		. "GROUP BY t1.id "
		. "LIMIT 1";
	
	$fields = $DB->fetchRow(0, $sql);	

  	$fields["name"] = $_REQUEST['file']; 
	$fields["body"] = @file_get_contents(WORKSPACE . "/masters/" . $_REQUEST['file'] . ".xsl");

	$fields['data_sources'] = @explode(",", $fields['data_sources']);
	$fields['events'] = @explode(",", $fields['events']);	

	$utilities = $DB->fetch("SELECT DISTINCT t1.* 
							 FROM `tbl_utilities` as t1
							 LEFT JOIN `tbl_utilities2datasources` as t2 ON t1.id = t2.utility_id
							 LEFT JOIN `tbl_utilities2events` as t3 ON t1.id = t3.utility_id
							 WHERE (t2.`data_source` IS NULL AND t3.`event` IS NULL)
							 OR (t2.`data_source` IN ('".@implode("', '", $fields['data_sources'])."') 
							 OR t3.`event` IN ('".@implode("', '", $fields['events'])."'))");
	
	$date = $Admin->getDateObj();
	
	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}
	
	if(isset($_GET['_f'])){
		switch($_GET['_f']){
		
			case "saved":
				$Admin->pageAlert("saved-time", array("Master", date("h:i:sa", $date->get(true, false))));
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
	
  	<form action="<?php print $Admin->getCurrentPageURL(); ?>&amp;file=<?php print $_REQUEST['file']; ?>" method="post">
  	<h2><?php print $_REQUEST['file']; ?> <a class="button configure" href="#config" title="Configure master settings">Configure</a></h2>
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
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this template" />

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