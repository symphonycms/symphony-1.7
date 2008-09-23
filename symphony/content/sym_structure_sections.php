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

	$GLOBALS['pageTitle'] = "Sections";

	$sections = $DB->fetch("SELECT tbl_sections.*, 
								   count(tbl_entries2sections.entry_id) as `entry_count`,
								   tbl_customfields.name as `primary_field_name`	 
							FROM `tbl_sections` 
							LEFT JOIN `tbl_entries2sections` ON `tbl_sections`.id = `tbl_entries2sections`.section_id
							LEFT JOIN `tbl_customfields` ON `tbl_customfields`.id = `tbl_sections`.primary_field
							GROUP BY `tbl_sections`.id
							ORDER BY `sortorder` ASC");

	if(isset($_GET['_f'])){
		switch($_GET['_f']){		

			case "complete":
				$Admin->pageAlert("selected-success", array("Section(s)", "deleted"));
				break;		

		}
	}

?>

<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
  	<h2><!-- PAGE TITLE --> <a class="create button"  href="<?php print $Admin->getCurrentPageURL(); ?>new/" title="Create a new section">Create New</a></h2>
	<table class="ordered">
		<thead>
			<tr>
				<th scope="col">Name</th>
				<th scope="col">Entries</th>
				<th scope="col">Primary field</th>
				<th scope="col">Comments</th>
			</tr>
		</thead>
		<tbody>
			
<?php
	
	if(!is_array($sections) || empty($sections)){
		print '		<tr><td colspan="4" class="inactive">None found.</td></tr>' . CRLF;

	}else{
		$bEven = false;
		foreach($sections as $s){
		
			if($s['entry_count'] > 0){
				$entry_link = '<a href="'.URL . '/symphony/?page=/publish/section/&_sid=' . $s['id'] . '">' . $s['entry_count'] . "</a>";
			}else
				$entry_link = "None";
			
		
?>

			<tr<?php print ($bEven ? ' class="even"' : ""); ?>>
				<td><a href="<?php print $Admin->getCurrentPageURL() . "edit/&amp;id=" . $s['id']; ?>" class="content"><?php print $s['name']; ?></a></td>
				<td<?php print ($entry_link != 'None' ? '' : ' class="inactive"'); ?>><?php print $entry_link; ?></td>
				<td><a href="<?php print URL . '/symphony/?page=/structure/customfields/edit/&id=' . $s['primary_field']; ?>"><?php print $s['primary_field_name']; ?></a></td>
				<td<?php print ($s['commenting'] == 'on' ? '' : ' class="inactive"'); ?>><?php print ($s['commenting'] == 'on' ? '<a href="?page=/publish/comments/&amp;filter=section-'.$s['id'].'">Enabled</a>' : 'Disabled'); ?> <input name="items[<?php print $s['id']; ?>]" type="checkbox" /></td>
			</tr>

<?php 
			$bEven = !$bEven;
		}
	}
?>

		</tbody>
	</table>
</form>
