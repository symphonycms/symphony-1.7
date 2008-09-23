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

	if($_GET['id']) {
		$sql = "SELECT t1.*, tbl_customfields_selectoptions.values as select_options "
			 . "FROM tbl_customfields as t1 "
			 . "LEFT JOIN tbl_customfields_selectoptions ON t1.id = tbl_customfields_selectoptions.field_id "
			 . "LEFT JOIN `tbl_metadata` as t2 ON t2.relation_id = t1.id AND t2.class = 'customfield' "		 
			 . "WHERE t1.`id` = '".addslashes($_GET['id'])."' ";
		
		$fields = $DB->fetchRow(0,$sql);

		if($fields) {
			$GLOBALS['pageTitle'] = 'Custom Fields > ' . $fields['name'];
					
		} else {
			General::redirect(URL . "/symphony/?page=/structure/customfields/new/");
		}
		
	} else {
		General::redirect(URL . "/symphony/?page=/structure/customfields/new/");	
	}

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');	
	}

	$section = $DB->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `primary_field` = '".$fields['id']."' LIMIT 1");

	$bIsSectionIDField = (isset($section['id']));

	$date = $Admin->getDateObj();
	
	if(isset($_GET['_f'])){
		switch($_GET['_f']){
		
			case "saved":
				$Admin->pageAlert("saved-time", array("Custom Field", date("h:i:sa", $date->get(true, false))));
				break;			
		}
	}	
		
	if(isset($_POST['fields'])){	
		$fields = array();  
	  	$handle = $fields['handle'];
		$fields = $_POST['fields'];
		$fields['format'] = (isset($fields['format'])? 'on' : 'off');
    	$fields['handle'] = $handle;
		$fields['select_options'] = $_POST['fields']['select_options'];    

	}else{		
		
		if($fields['type'] == 'list'){
			$fields['type'] = 'input';
			$fields['create_input_as_list'] = 'on';
			
		}elseif($fields['type'] == 'multiselect'){
			$fields['type'] = 'select';
			$fields['select_multiple'] = 'on';
		}
		
		$fields['format'] = ($fields['format'] == '1' ? 'on' : 'off');		
		$fields['required'] = ($fields['required'] == 'yes' ? 'on' : 'off');
		$fields['foreign_select_multiple'] = ($fields['foreign_select_multiple'] == 'yes' ? 'on' : 'off');
					
		$select_options = $fields['values'];
	}
	
	$sql  = "SELECT * "
		  . "FROM tbl_sections "
		  . "ORDER BY `sortorder`";
		  
	$sections = $DB->fetch($sql);
	
	include(TOOLKIT . '/util.validators.php');
	
?>		
	<form action="<?php print $Admin->getCurrentPageURL(); ?>&amp;id=<?php print $_GET['id']; ?>" method="post">
  		<h2><?php print $fields['name']; ?></h2>
 		<fieldset>
			<label>Name <input name="fields[name]" <?php print General::fieldValue("value", $fields['name']); ?> /></label>
			<label>Description <small>Optional</small> <input name="fields[description]" <?php print General::fieldValue("value", $fields['description']); ?> /></label>
			
			<label>Field Type
				<select name="fields[type]">
					<option value="input"  <?php print General::fieldValue("select", $fields['type'], "", "input"); ?> >Text Input (single line)</option>
					<option value="textarea"  <?php print General::fieldValue("select", $fields['type'], "", "textarea"); ?> >Text Area (multiple lines)</option>
<?php if(!$bIsSectionIDField){ ?>
					<option value="select"  <?php print General::fieldValue("select", $fields['type'], "", "select"); ?> >Select Box</option>
					<option value="checkbox"  <?php print General::fieldValue("select", $fields['type'], "", "checkbox"); ?> >Checkbox</option>
					<option value="upload" <?php print General::fieldValue("select", $fields['type'], "", "upload"); ?> >File Attachment</option>
					<option value="foreign" <?php print General::fieldValue("select", $fields['type'], "", "foreign"); ?> >Section Link</option>					
<?php } ?>				
				</select>
			</label>


			<div id="context">

<?php

	if($fields['type'] == 'input'){ 
?>

				<label>Validation
					<select name="fields[validator]">

<?php	
			if(!empty($validators) && is_array($validators)){
				foreach($validators as $id => $v) {
					list($name, $rule) = $v;

					print "						<option value=\"".$id."\" ".General::fieldValue("select", $fields['validator'], "", $id).">".$name."</option>\n";
				}	
			}
?>						

						<option value="custom" <?php print General::fieldValue("select", $fields['validator'], "", 'custom'); ?>>Custom...</option>

					</select>
				</label>

<?php 	
		if($fields['validator'] == 'custom'){ 
?>
				<label>Custom Validation Rule <input name="fields[validation_rule]" <?php print General::fieldValue("value", $fields['validation_rule']); ?> /></label>
				
<?php
		}
?>

				<label><input name="fields[create_input_as_list]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['create_input_as_list'], "", "on"); ?> /> Split by commas <small>Validation will apply to each segment</small></label>
				
				<label><input name="fields[format]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['format'], "", "on"); ?> /> Apply formatting</label>				

<?php
		
	}elseif($fields['type'] == 'textarea'){ 
?>
			 <label>Make text area <input name="fields[size]" <?php print General::fieldValue("value", General::sanitize($fields['size'])); ?> size="2" maxlength="2" /> rows tall.</label>
			
			<label><input name="fields[format]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['format'], "", "on"); ?> /> Apply formatting</label>			
<?php 

	}elseif($fields['type'] == 'foreign'){ 
?>
			<label>Section
				<select name="fields[foreign_section]">
					
<?php	
			if(!empty($sections) && is_array($sections)){
				foreach($sections as $s) {
						print "<option value=\"".$s['id']."\" ".General::fieldValue("select", $fields['foreign_section'], "", $s['id']).">".$s['name']."</option>\n";
				}	
			}
?>					
					
				</select>
			</label>
			
			<label><input name="fields[foreign_select_multiple]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['foreign_select_multiple'], "", "on"); ?> /> Allow selection of multiple items</label>
			
<?php
	}elseif($fields['type'] == 'select'){ 

?>

			 <label>Options<small>Separate by commas</small><input name="fields[select_options]" value="<?php print General::sanitize($fields['select_options']); ?>" /></label>
			<label><input name="fields[select_multiple]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['select_multiple'], "", "on"); ?> /> Allow selection of multiple items</label>

<?php

	}elseif($fields['type'] == 'checkbox'){ 

?>
			 <label><input name="fields[default_state]" type="checkbox" <?php print General::fieldValue("checkbox", $fields['default_state'], "", "checked"); ?> /> Check this Custom field by default.</label>
<?php 			

	}elseif($fields['type'] == 'upload'){
		
		$directories = General::listDirStructure(WORKSPACE, true, "asc", DOCROOT);
    	$ignore = array("events", "data-sources", "text-formatters", "pages", "masters", "utilities");
	
?>
			<label>Destination Folder <select name="fields[destination_folder]">
				<option value="workspace/" <?php print ($fields['destination_folder'] == "workspace/" ? ' selected="selected"' : ""); ?>>workspace/</option>
<?php
			foreach($directories as $d){
				if(!in_array($d, $ignore)){ 
?>						

						<option value="<?php print ltrim($d, '/'); ?>"<?php print ($fields['destination_folder'] == ltrim($d, '/') ? ' selected="selected"' : ""); ?>><?php print ltrim($d, '/'); ?></option>
<?php
				}
			}
?>	

			</select>
<?php
	}
?>
			</div>
			
<?php
	if(!$bIsSectionIDField){
?>
			<fieldset>
				<label>Associate with Section
  					<select name="fields[parent_section]">

<?php	
			if(!empty($sections) && is_array($sections)){
				foreach($sections as $s) {
						print "<option value=\"".$s['id']."\" ".General::fieldValue("select", $fields['parent_section'], "", $s['id']).">".$s['name']."</option>\n";
				}	
			}
?>
					</select>
				</label>			

				<label>Location
					<select name="fields[location]">
						<option value="main" <?php print General::fieldValue("select", $fields['location'], "", "main"); ?>>Main Content</option>
						<option value="sidebar" <?php print General::fieldValue("select", $fields['location'], "", "sidebar"); ?>>Sidebar</option>
						<option value="drawer" <?php print General::fieldValue("select", $fields['location'], "", "drawer"); ?>>Drawer</option>
					</select>
				</label>

				<label><input name="fields[required]" type="checkbox" <?php print (!in_array($fields['type'], array('upload', 'checkbox')) && $fields['location'] != 'drawer' ? General::fieldValue("checkbox", $fields['required'], "", "on") : 'disabled="disabled"'); ?> /> Required Field</label>			
			</fieldset>
<?php 

	}else{
		
		$message = 'This custom field is linked to the <a href="'. URL . '/symphony/?page=/blueprint/sections/edit/&amp;id=' . $section['id'] .'">'.$section['name'].'</a> section, so you cannot delete or re-associate it, and it must use either a text input or text area field type.';
		if($section['id'] != 1) $message .= ' It will automatically be removed if you delete the '.$section['name'].' section.';
?>	
				<p class="fixed"><?php print $message; ?></p>
<?php 
	}
?>		

 			<input name="action[save]" type="submit" value="Save" accesskey="s" />
<?php
 	if(!$bIsSectionIDField){

?>				
 			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" value="Delete" title="Delete" />
<?php } ?> 			
		</fieldset>
	</form>