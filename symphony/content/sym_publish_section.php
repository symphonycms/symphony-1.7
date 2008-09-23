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

	include_once(TOOLKIT . "/class.entrymanager.php");
	$entryManager = new EntryManager($Admin);
	
	$current_page = 1;
	
	if(isset($_REQUEST['pg']) && is_numeric($_REQUEST['pg']))
		$current_page = intval($_REQUEST['pg']);

	$filter = NULL;

	if(isset($_REQUEST['filter']) && $_REQUEST['filter'] != '')
		$filter = $_REQUEST['filter'];

	if(!$section = $DB->fetchRow(0, "SELECT * FROM `tbl_sections` WHERE `id` = '" . intval($_REQUEST['_sid']) . "' LIMIT 1"))
		$Admin->fatalError("Unknown Section", "<p>The Section you are looking for could not be found.</p>", true, true);

	$section_id = intval($section['id']);
	$section_primary_field = $section['primary_field'];

	$GLOBALS['pageTitle'] = $section['name'];
	
	$sql  = "SELECT * "
		  . "FROM tbl_authors "
		  . "ORDER BY username ASC";
		  
	$authors = $DB->fetch($sql);
		
	list($entry_count_total, $entry_count_remaining, $entries) =
			$entryManager->fetchEntriesByPage(
								$section_id, 
								$current_page, 
								$Admin->getConfigVar('pagination_maximum_rows', 'symphony'), 
								$filter
							);
		
	$sql = "SELECT t2.*
			FROM `tbl_sections_visible_columns` as t1
			LEFT JOIN `tbl_customfields` as t2 ON t1.field_id = t2.id
			WHERE t1.section_id = $section_id
			ORDER BY t2.sortorder ASC
			";
	
	$result = $DB->fetch($sql);

	$columns = array();
	
	if(is_array($result) && !empty($result)){
		
		foreach($result as $r)
			$columns[] = array("title" => $r['name'], "field_id" => $r['id']);
		
	}
		
	if($section['author_column'] == 'show') $columns[] = array("title" => 'Author', "field_id" => NULL);
	if($section['date_column'] == 'show') $columns[] = array("title" => 'Date Published', "field_id" => NULL);
	if($section['commenting'] == 'on') $columns[] = array("title" => 'Comments', "field_id" => NULL);

		
	if(isset($_GET['_f'])){
		switch($_GET['_f']){
			     		
			case "complete":
				$Admin->pageAlert("selected-success", array("Entries(s)", "deleted"));
				break;
				
		}
	}	
	
?>
	<form action="<?php print $Admin->getCurrentPageURL() . '&_sid=' . $_REQUEST['_sid']; ?>" method="post">
		<h2><!-- PAGE TITLE --> <a class="create button" href="<?php print $Admin->getCurrentPageURL(); ?>new/&amp;_sid=<?php print $_REQUEST['_sid']; ?>" title="Create a new entry">Create New</a>
			<select name="filter">
				<option value="">All Entries</option>
				
<?php if(count($authors > 1)): ?>	
			
				<optgroup label="Authors"> 
				<?php foreach($authors as $a){ ?> 
				
					<option value="author-<?php print $a['id']; ?>"<?php print ($_REQUEST['filter'] == ("author-" . $a['id']) ? ' selected="selected"' : ""); ?>><?php print $a['firstname'] . " " . $a['lastname']; ?></option>
					
				<?php } ?>
				</optgroup>
				
<?php endif; ?>
			</select>
		</h2>
		<table>
			<thead>
				<tr>
<?php
	
	foreach($columns as $c){
		
		print '					<th scope="col">'.$c['title'].'</th>' . "\n";
		
	}
	
?>

				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="<?php print count($columns); ?>">
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
	if(!empty($entries)):
		$bEven = false;
		
		foreach ($entries as $row) {			

		    $class = "";
				
			if(isset($_REQUEST['_f']) && $_REQUEST['id'] == $row['id']) $class = "active ";
      
	        if($bEven) $class .= "even";
	        $class = trim($class);	
        		
            $sql = "SELECT count(*) as `comments_count` FROM `tbl_comments` WHERE `entry_id` = ".$row['id'];

            $row['comments_count'] = $DB->fetchVar("comments_count", 0, $sql);

			extract($row, EXTR_PREFIX_ALL, "ent");

            $comment_link = ($ent_comments_count <= 0 ? 'None' : '<a href="' . URL . '/symphony?page=/publish/comments/&filter=entry-' . $ent_id . '">'.$ent_comments_count.'</a>');
			
	        $locked = 'content';		
			
			$date = $Admin->getDateObj();
			$bIsFutureDated = ($date->get(false, false) < $ent_timestamp_gmt);						

			$tr = array("class" => $class);
			$tr['cells'] = array();
			
			foreach($columns as $index => $c){
				
				if(!$c['field_id']){
					switch($c['title']){
					
						case 'Author':
							$tr['cells'][] = array("value" => '<a href="'.URL . '/symphony?page=/settings/authors/edit/&amp;id=' . $ent_author_id . '">'.$ent_author_firstname . ' ' . $ent_author_lastname . '</a>');
							break;
												
						case 'Comments':
							$tr['cells'][] = array("value" => $comment_link,
							 						"class" => ($comment_link == 'None' ? 'inactive' : ''));
							break;
						
						case 'Date Published':

							$tr['cells'][] = array(
								"value" => $date->get(true, true, $ent_timestamp_gmt), 
								"class" => ($ent_status == "draft" || $bIsFutureDated ? 'inactive' : ''));
							break;
					}
					
				}else{
					
					$value = strip_tags(trim($row['fields'][$c['title']]['value']));
					$type = $row['fields'][$c['title']]['type'];

					if($index == 0){

						foreach($row['fields'] as $f){
							if($f['field_id'] == $row['primary_field']) $handle = $f['handle'];
						}
					
						$value = '<a title="'.$handle.'" class="'.$locked.'" href="' . $Admin->getCurrentPageURL() . 'edit/&amp;_sid=' . $section_id . '&amp;id=' . $row['id'] . '">' . $value . '</a>';						
					}
					
					if($type == 'checkbox'){
						$tmp = array("value" => ucwords($value));

					}elseif($type == 'foreign' && $value != ''){

						$value = '';
						
						$items = $row['fields'][$c['title']]['value'];
						
						if(!is_array($items)) $items = array($items);
						
						if(!empty($items)){
							
							foreach($items as $ii){
								
								$id =  $entryManager->fetchEntryIDFromPrimaryFieldHandle($row['fields'][$c['title']]['foreign_section'], $ii);
	
								if($link = $entryManager->fetchEntriesByID($id, true)){

									$value .= '<a href="' 
												. $Admin->getCurrentPageURL() 
												. 'edit/&amp;_sid=' . $row['fields'][$c['title']]['foreign_section'] 
												. '&amp;id=' . $id[0]
												. '">' . General::limitWords($link['fields'][$link['primary_field']]['value'], 50, true) . '</a>, ';
										
							
								}	
							}
							
							$value = rtrim($value, ', ');
						}
									
						$tmp = array("value" => ($value ? $value : 'None'));

					}elseif($type == 'upload'){
						
						$files = $row['fields'][$c['title']]['value'];
						
						if(is_array($files) && !empty($files)){
							$links = array();
							foreach($files as $f){
								$links[] = '<a href="'.URL . $f['path'].'" title="'.$f['path'].' ('.$f['size'].' bytes)">'.basename($f['path']).'</a>';
							}
							
							$tmp = array("value" => implode(', ', $links));
							
						}else{
							$tmp = array("value" => 'None');
							$tmp['class'] = 'inactive';
						}
						
					}elseif($type == 'multiselect'){
						
						$items = $row['fields'][$c['title']]['value_raw'];
						
						if(is_array($items) && !empty($items))
							$tmp = array("value" => implode(', ', $items));
							
						else{
							$tmp = array("value" => 'None');
							$tmp['class'] = 'inactive';
						}
						
						
					}else{
						$tmp = array("value" => ($value ? $value : 'None'));
					}
					
					if($value == '') $tmp['class'] = 'inactive';
						
					$tr['cells'][] = $tmp;
					
				}
				
			}
			
			$tr['cells'][count($tr['cells']) - 1]['value'] .= " <input name=\"items[$ent_id]\" type=\"checkbox\" />";
			
?>				
				<tr<?php print ($tr['class'] ? ' class="'.$tr['class'].'"' : ''); ?>>

<?php
				foreach($tr['cells'] as $cell){					
?>
					<td<?php print ($cell['class'] ? ' class="'.$cell['class'].'"' : ''); ?>><?php print $cell['value']; ?></td>
					
<?php					
				}
?>

				</tr>
						
<?php			$bEven = !$bEven;
			}
	    else:
?>   
                <tr><td colspan="<?php print count($columns); ?>" class="inactive">None found.</td></tr>
 
<?php  
		endif;
?>
			
			</tbody>
		</table>

<?php

	$pagination = array(
			'total_records' => $entry_count_total,
			'total_pages' => max(ceil($entry_count_total * (1 / $Admin->getConfigVar('pagination_maximum_rows', 'symphony'))), 1),
			'current_page' => $current_page,
			'viewing_start' => max(1, ($current_page - 1) * $Admin->getConfigVar('pagination_maximum_rows', 'symphony') + 1),
			'viewing_end' => min($current_page * $Admin->getConfigVar('pagination_maximum_rows', 'symphony'), $entry_count_total),
			'next_page' => ($entry_count_remaining > $Admin->getConfigVar('pagination_maximum_rows', 'symphony') ? ($current_page + 1) : NULL),
			'previous_page' => ($current_page > 1 ? ($current_page - 1) : NULL),
			'first_page' => ($current_page > 1 ? 1 : NULL),
			'last_page' => ($entry_count_remaining > $Admin->getConfigVar('pagination_maximum_rows', 'symphony') ? max(ceil($entry_count_total * (1 / $Admin->getConfigVar('pagination_maximum_rows', 'symphony'))), 1) : NULL)

		);

	extract($pagination);

	if($filter){
		$filter = '&amp;filter='.$filter;
	}

	if($total_pages > 1):

?>					

		<ul class="page">
			<li>
<?php 

	if($first_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;_sid='.$section_id.'&amp;pg=1">First</a>' . CRLF;

	else
		print '				First';
?>			
			</li>

			<li>
<?php 

	if($previous_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;_sid='.$section_id.'&amp;pg='.$previous_page.'">&larr; Previous</a>' . CRLF;

	else
		print '				&larr; Previous';
?>			
			</li>
			<li title="Viewing <?php print $viewing_start; ?> - <?php print $viewing_end; ?> of <?php print $total_records; ?> entries">Page <?php print $current_page; ?> of <?php print $total_pages; ?></li>

			<li>
<?php 

	if($next_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;_sid='.$section_id.'&amp;pg='.$next_page.'">Next &rarr;</a>' . CRLF;

	else
		print '				Next &rarr;';
?>			
			</li>			
			<li>
<?php 

	if($last_page != NULL)
		print '				<a href="'.$Admin->getCurrentPageURL().$filter.'&amp;_sid='.$section_id.'&amp;pg='.$last_page.'">Last</a>' . CRLF;

	else
		print '				Last';
?>			
			</li>
		</ul>	

<?php
	endif;
?>
		
	</form>
