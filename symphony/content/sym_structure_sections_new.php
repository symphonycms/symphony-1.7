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

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	$GLOBALS['pageTitle'] = "Sections > Untitled";
	
    $fields = $_POST['fields'];

    $fields['commenting'] = (isset($fields['commenting']) || empty($_POST) ? 1 : 0);
    $fields['calendar_show'] = (isset($fields['calendar_show']) || empty($_POST) ? 1 : 0);
    $fields['author_column'] = (isset($fields['author_column']) || empty($_POST) ? 1 : 0);
    $fields['date_column'] = (isset($fields['date_column']) || empty($_POST) ? 1 : 0);	

?>
		<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
  			<h2>Untitled</h2>
			<fieldset>
			
				<fieldset>
					<label>Sort Entries By
						<select name="fields[entry_order]">
							<option value="date">Date Published</option>
							<option value="author"<?php print ($fields['entry_order'] == 'author' ? ' selected="selected"' : ''); ?>>Author</option>							
						</select>
					</label>
				</fieldset>	
						
				<label>Name <input name="fields[name]" <?php print General::fieldValue("value", $fields['name']); ?> /></label>
				<label><input name="fields[commenting]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["commenting"], "", 1); ?> /> Enable comments for this section</label>
				<label><input name="fields[calendar_show]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["calendar_show"], "", 1); ?> /> Show the 'Published Date and Time' widget when creating and editing entries in this section</label>
				<div id="columns">Show the following columns
					<label><input name="fields[author_column]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["author_column"], "", 1); ?> /> Author</label>
					<label><input name="fields[date_column]" type="checkbox" <?php print General::fieldValue("checkbox", $fields["date_column"], "", 1); ?> /> Date Published</label>
				</div>
				<input name="action[save]" type="submit" value="Save" accesskey="s" />
				
			</fieldset>
		</form>