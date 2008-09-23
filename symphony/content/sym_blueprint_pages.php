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

	$GLOBALS['pageTitle'] = "Pages";
	
	$pages = $DB->fetch("SELECT * FROM `tbl_pages` WHERE `sortorder` > -1 ORDER BY `sortorder` ASC");
	$pages = array_merge($pages, $DB->fetch("SELECT * FROM `tbl_pages` WHERE `sortorder` = -1 ORDER BY `sortorder` ASC"));

	$assets = General::listStructureFlat(WORKSPACE, array("css", "js", "xml", "txt", "html"), true, "asc", WORKSPACE);
	$utilities = $DB->fetch("SELECT * FROM `tbl_utilities` ORDER BY `name` ASC");
	$masters = General::listStructureFlat(WORKSPACE . "/masters", array("xsl"), true, "asc", WORKSPACE);
	
	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$datasources = $DSM->listAll();
	
	if(isset($_GET['_f'])){
		switch($_GET['_f']){		
		
			case "complete":
				$Admin->pageAlert("selected-success", array("Page(s)", "deleted"));
				break;		
											
		}
	}
	
?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2><!-- PAGE TITLE --> <a class="create button"  href="<?php print $Admin->getCurrentPageURL(); ?>new/" title="Create a new page">Create New</a>
</h2>
		<table class="ordered">
			<thead>
				<tr>
					<th scope="col">Title</th>
					<th scope="col"><acronym title="Univeral Resource Locator">URL</acronym></th>
					<th scope="col">Master</th>
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

                if(empty($pages) || !is_array($pages)):
?>   
                 <tr><td colspan="3" class="inactive">None found.</td></tr>
 
<?php  
                else:
				$bEven = false;
				foreach($pages as $p){
				
				  $class = "";  
				
				  if(trim($p['master']) != "")
				    $p['master'] = '<a href="'.URL . "/symphony/?page=/blueprint/masters/edit/&amp;file=" . basename($p['master'], ".xsl") . '">'.$p['master'].'</a>';
				  else
				    $p['master'] = "None";
				    
				    		        
		        if($bEven) $class .= "even";
		        $class = trim($class);
?>
				<tr<?php print ($class != "" ? ' class="'.$class.'"' : ""); ?>>
					<td><a href="<?php print $Admin->getCurrentPageURL(); ?>edit/&amp;id=<?php print $p['id']; ?>" class="content" title="<?php print $p['handle']; ?>"><?php print General::limitWords($p['title'], 75, true); ?></a></td>
					<td><a href="<?php print URL . "/" . $Admin->resolvePagePath($p['id']) . "/"; ?>"><?php print URL . "/" . $Admin->resolvePagePath($p['id']) . "/"; ?></a></td>
					<td<?php print (trim($p['master']) == "None" ? ' class="inactive"' : ""); ?>><?php print $p['master']; ?> <input name="items[<?php print $p['id']; ?>]" type="checkbox" /></td>
				</tr>					
<?php		

					$bEven = !$bEven;			
				}
				
				endif;
?>									
			</tbody>
		</table>
	</form>