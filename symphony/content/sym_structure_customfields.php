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

	$GLOBALS['pageTitle'] = "Custom Fields";
	
	$sql = "SELECT * "
		 . "FROM tbl_customfields "
		 . "ORDER BY `sortorder` ASC";
		 
	$fields = $DB->fetch($sql);	 
	
	if(isset($_GET['_f'])){
		switch($_GET['_f']){		
		
			case "complete":
				$Admin->pageAlert("selected-success", array("Custom Field(s)", "deleted"));
				break;		
											
		}
	}
?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2><!-- PAGE TITLE --> <a class="create button" href="<?php print $Admin->getCurrentPageURL(); ?>new/" title="Create a new custom field">Create New</a></h2>
		<table class="ordered">
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Description</th>
					<th scope="col">Associated Section</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
						<select name="with-selected">
							<option>With Selected...</option>					
							<option value="delete">Delete</option>
						</select>
						<input name="action[apply]" type="submit" value="Apply" />
					</td>
				</tr>
			</tfoot>			
			<tbody>
<?php
			
				if(empty($fields) || !is_array($fields)){
?>					
					<tr><td colspan="3" class="inactive">None found.</td></tr>	
				
<?php					
				}else{
					$bEven = false;
					
					foreach($fields as $c) {
						
						$id = $c['id'];
						
						$sql = "SELECT * FROM `tbl_sections` WHERE `id` = '".$c['parent_section']."' LIMIT 1";
						$parent = $DB->fetchRow(0, $sql);

						$links = '<a href="?page=/structure/sections/edit/&amp;id=' . $parent['id'].'">'.$parent['name'].'</a>';						
			
?>
											
				<tr<?php print ($bEven ? ' class="even"' : ""); ?>>
					<td><a class="framework" href="<?php print $Admin->getCurrentPageURL(); ?>edit/&amp;id=<?php print $c['id']; ?>" title="<?php print $c['handle']; ?>"><?php print General::limitWords($c['name'], 42, true); ?></a></td>
					<td<?php print ($c['description'] ? '' : ' class="inactive"'); ?>><?php print ($c['description'] ? General::limitWords($c['description'], 85, true) : "None"); ?></td>
					<td<?php print ($links ? "" : ' class="inactive"'); ?>><?php print ($links ? $links : "None"); ?> <input name="items[<?php print $id; ?>]" type="checkbox" /></td>
				</tr>
				
<?php $bEven = !$bEven; } } ?>	
				
			</tbody>
		</table>
	</form>