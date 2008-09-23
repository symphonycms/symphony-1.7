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

	$GLOBALS['pageTitle'] = "Components";	

	$assets = General::listStructureFlat(WORKSPACE, array("css", "js", "xml", "txt", "html"), true, "asc", WORKSPACE);
	$utilities = $DB->fetch("SELECT * FROM `tbl_utilities` ORDER BY `name` ASC");
	$masters = General::listStructureFlat(WORKSPACE . "/masters", array("xsl"), true, "asc", WORKSPACE);
	
	if(isset($_GET['_f'])){
		switch($_GET['_f']){		
		
			case "complete":
				$Admin->pageAlert("selected-success", array("Component", "deleted"));
				break;		
											
		}
	}
	
?>
	<form id="components" action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">

		<h2><!-- PAGE TITLE --></h2>
		<ul>
			<li>
				<h3>Utilities <a href="<?php print URL; ?>/symphony/?page=/blueprint/utilities/new/" class="create button" title="Create a new utility">Create new</a></h3>
				<ul>
<?php

		if(is_array($utilities) && !empty($utilities)){
			foreach($utilities as $u){
?>
					<li><a href="<?php print URL; ?>/symphony/?page=/blueprint/utilities/edit/&amp;id=<?php print $u['id']; ?>"><?php print $u['name']; ?></a></li>
<?php
				
			}
		}
?>

				</ul>
			</li>
			<li>
				<h3>Masters <a href="<?php print URL; ?>/symphony/?page=/blueprint/masters/new/" class="create button" title="Create a new master">Create new</a></h3>
				<ul>
<?php

		if(is_array($masters) && !empty($masters)){
			foreach($masters as $m){
?>
					<li><a href="<?php print URL; ?>/symphony/?page=/blueprint/masters/edit/&amp;file=<?php print basename($m['name'], ".xsl"); ?>"><?php print $m['name']; ?></a></li>
<?php
				
			}
		}
?>				
				</ul>
			</li>
			<li>
				<h3>Assets <a href="<?php print URL; ?>/symphony/?page=/blueprint/assets/new/" class="create button" title="Create a new asset">Create new</a></h3>
	
				<ul>
<?php

		if(is_array($assets) && !empty($assets)){
			foreach($assets as $a){
?>
					<li><a href="<?php print URL; ?>/symphony/?page=/blueprint/assets/edit/&amp;file=<?php print $a['path'] . "/" . $a['name']; ?>" title="workspace<?php print $a['path'] . "/" . $a['name']; ?>"><?php print $a['name']; ?></a></li>
<?php
				
			}
		}
?>	
				</ul>
			</li>
		</ul>
	</form>