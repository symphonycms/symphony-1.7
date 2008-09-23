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

	$Admin->addScriptToHead('assets/editor.js');

	$GLOBALS['pageTitle'] = "Pages > Untitled";
	
	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}


	if(!empty($_POST)) {
		$fields = $_POST['fields']; $fields['body'] = General::sanitize($fields['body']);
	
	}else{ 
		$fields['show_in_nav'] = "yes";
		$fields['body'] = 
'&lt;xsl:template match="data">
	
&lt;/xsl:template>'; 
	
	}

	$utilities = $DB->fetch("SELECT DISTINCT t1.* 
							 FROM `tbl_utilities` as t1
							 LEFT JOIN `tbl_utilities2datasources` as t2 ON t1.id = t2.utility_id
							 LEFT JOIN `tbl_utilities2events` as t3 ON t1.id = t3.utility_id
							 WHERE (t2.`data_source` IS NULL AND t3.`event` IS NULL)
							 OR (t2.`data_source` IN ('".@implode("', '", $fields['data_sources'])."') 
							 OR t3.`event` IN ('".@implode("', '", $fields['events'])."'))");
		
	$masters = General::listStructure(WORKSPACE . "/masters", array("xsl"));
	$masters = $masters['filelist'];	

	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$datasources = $DSM->listAll();	
	
	$EM = new EventManager(array('parent' => &$Admin));
	$events = $EM->listAll();
	
	$pages = $DB->fetch("SELECT * FROM `tbl_pages` ORDER BY `sortorder` ASC");
	
	$page_types = array("default" => 'Default', 'xml' => 'XML');
	
	if(!$DB->fetchVar('count', 0, "SELECT count(*) as `count` FROM `tbl_pages` WHERE `type` = 'error'")) $page_types['error'] = "Page Not Found";

	if(!$DB->fetchVar('count', 0, "SELECT count(*) as `count` FROM `tbl_pages` WHERE `type` = 'index'")) $page_types['index'] = "Index";	

	if(!$DB->fetchVar('count', 0, "SELECT count(*) as `count` FROM `tbl_pages` WHERE `type` = 'maintenance'")) $page_types['maintenance'] = "Maintenance";
	
	if(isset($fields['type']) && !in_array($fields['type'], array_keys($page_types)))
		$page_types[$fields['type']] = $fields['type'];
	
?>
  	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
        <h2>Untitled <a class="button configure" href="#config" title="Configure page settings">Configure</a></h2>
		<fieldset>
			<label>Title <input name="fields[title]" <?php print General::fieldValue("value", $fields['title']);?> /></label>
			<fieldset>
				<label>Master
					<select name="fields[master]">
						<option>None</option>
<?php
						
			if(is_array($masters) && !empty($masters)){
				foreach($masters as $m){
					print "<option value=\"". $m ."\" ". General::fieldValue("select", $fields['master'], "", $m) .">". $m ."</option>";
				}	
			}	
					
?>
					</select>
				</label>
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
			<label>Body <textarea id="code-editor" name="fields[body]" cols="75" rows="35"><?php print General::fieldValue("textarea", $fields['body']);?></textarea></label>
			<input name="action[save]" type="submit" value="Save" accesskey="s" />
		</fieldset>
		
		<div id="config">
			<h3>Page Settings</h3>
			<fieldset>
				<legend><acronym title="Universal Resource Locator">URL</acronym> Attributes</legend>
				<div class="group">
					<label><acronym title="Universal Resource Locator">URL</acronym> Handle <small>Auto-generated if blank</small><input name="fields[handle]" <?php print General::fieldValue("value", $fields['handle']);?> /></label>
					<label><acronym title="Universal Resource Locator">URL</acronym> Schema <input name="fields[url_schema]" <?php print General::fieldValue("value", $fields['url_schema']);?> /></label>
				</div>
				<label>Parent Page
					<select name="fields[parent]">
						<option>None</option>
<?php				
					if(is_array($pages) && !empty($pages)){		
						foreach($pages as $page)
							if($page['id'] != $_REQUEST['id'])
								print "							<option value=\"".$page['id']."\" ". General::fieldValue("select", $fields['parent'], "", $page['id']) .">".$page['title']."</option>";
					}
?>	
					</select>
				</label>

				<label>Page Type
					<select name="fields[type]">
<?php
	
			foreach($page_types as $key => $val){

?>						
						<option value="<?php print $key; ?>" <?php print General::fieldValue("select", $fields['type'], "", $key); ?>><?php print $val; ?></option>
						
<?php
			}
?>							

					</select>
				</label>

				<label><input name="fields[show_in_nav]" type="checkbox"<?php print ($fields['show_in_nav'] == "no" ? ' checked="checked"' : ""); ?> /> Hide this page from my navigation</label>
			</fieldset>
			<fieldset>
				<legend>Caching Options</legend>				
				<label><input name="fields[full_caching]" type="checkbox"<?php print ($fields['full_caching'] == "yes" ? ' checked="checked"' : ""); ?> /> Enable full page caching for this page</label>
				<label>Refresh the cache every <input name="fields[cache_refresh_rate]" <?php print General::fieldValue("value", $fields['cache_refresh_rate'], ' value="59"');?> size="3" /> minutes.</label>
			</fieldset>				
			<fieldset>
				<legend>Page Environment</legend>
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
