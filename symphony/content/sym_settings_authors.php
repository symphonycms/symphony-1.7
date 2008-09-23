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

	$GLOBALS['pageTitle'] = "Authors";
	
		if(isset($_GET['_f'])){
			switch($_GET['_f']){
							
				case "complete":
					$Admin->pageAlert("selected-success", array("Author(s)", "deleted"));
					break;
					
			}
		}	
    
    include_once(TOOLKIT . "/class.authormanager.php");
    $authorManager = new AuthorManager($Admin);
    $authors = $authorManager->fetch();
    
    $date = new SymDate($Admin->getConfigVar("time_zone", "region"), $Admin->getConfigVar("date_format", "region"));	
    
    $new_button = ($Admin->authorIsSuper() ? '<a class="create button" href="'.$Admin->getCurrentPageURL().'new/" title="Add an author">Create New</a>' : "");
    
?>
	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2><!-- PAGE TITLE --> <?php print $new_button; ?></h2>
		<table>
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Entries</th>
					<th scope="col">Email Address</th>
					<th scope="col">Last Login</th>
				</tr>
			</thead>
			<tbody>
<?php

	if(@count($authors) > 0):
		$bEven = false;
		foreach($authors as $a){ 
		
			$class = "";
			if(isset($_REQUEST['_f']) && $_REQUEST['id'] == $a->get('id')) $class = "active ";

			$entries_count = ($a->get('entries') <= 0 ? 'None' : $a->get('entries'));
			
	        if($bEven) $class .= "even";
	        $class = trim($class);		
		        
			if(intval($a->get('superuser')) == 1) $group = "admin"; else $group = "author";    				
?>
				<tr<?php print ($class ? ' class="'.$class.'"' : ""); ?>>
					<td><a href="<?php print $Admin->getCurrentPageURL(); ?>edit/&amp;id=<?php print $a->get('id'); ?>" class="<?php print $group; ?>" title="<?php print $a->get('username'); ?>"><?php print $a->get('firstname'); ?> <?php print $a->get('lastname'); ?> </a></td>
					<td<?php print ($entries_count == 'None' ? ' class="inactive"' : ''); ?>><?php print $entries_count; ?></td>
					<td><a href="mailto:<?php print $a->get('email'); ?>" title="Email this author"><?php print $a->get('email'); ?></a></td>
					<td<?php print ($a->get('last_session') == NULL ? ' class="inactive"' : ''); ?>><?php print ($a->get('last_session') == NULL ? 'Unknown' : $date->get(true, true, strtotime($a->get('last_session')))); ?></td>
				</tr>
	
<?php
	
						$bEven = !$bEven;
		}
	else:
		print '<tr><td colspan="4" class="inactive">None found.</td></tr>';	

	endif;

?>
				
			</tbody>
		</table>
	</form>	
