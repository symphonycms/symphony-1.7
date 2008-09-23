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

	$GLOBALS['pageTitle'] = "Utilities > Untitled";
	
	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}
	
	$fields = array();
	
	if(!empty($_POST)) {
		$fields = $_POST['fields']; $fields['body'] = General::sanitize($fields['body']);
		
	}else{ 
		$fields['body'] = '&lt;xsl:template name=""&gt;
	
&lt;/xsl:template&gt;'; 
	
	}

	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$datasources = $DSM->listAll();
	
	$EM = new EventManager(array('parent' => &$Admin));
	$events = $EM->listAll();	
		
	$fields['name'] = strtolower($fields['name']);
	
?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>&amp;id=<?php print $_GET['id']; ?>" method="post">
  	<h2>Untitled</h2>
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
		</fieldset>
	</form>	