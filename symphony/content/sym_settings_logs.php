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

	$GLOBALS['pageTitle'] = "Activity Logs";
	
	$date = $Admin->getDateObj();
			
	$logs = array();
	$logs = General::listStructure(LOGS, array("log"), false, 'desc');
	
?>

	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2><!-- PAGE TITLE --></h2>
		<table>
			<thead>
				<tr>
					<th scope="col">Date Commenced</th>
					<th scope="col">System Status</th>
				</tr>
			</thead>

			<tbody>
<?php

	// Now make the rows
	if(!empty($logs['filelist'])){
		$bEven = false;
		foreach($logs['filelist'] as $f){
			
			$info = array();
			
			if($log_data = @file_get_contents(LOGS . "/" . $f)){
				 strallpos($log_data, "> SYM_LOG_ERROR:", $info[0]);
				 strallpos($log_data, "> SYM_LOG_WARNING:", $info[1]);
				 strallpos($log_data, "> SYM_LOG_NOTICE:", $info[2]);
			}
			
			if($info[0] == 0) unset($info[0]); else $info[0] = $info[0] . General::strPlural($info[0], " error", " errors");
			if($info[1] == 0) unset($info[1]); else $info[1] = $info[1] . General::strPlural($info[1], " warning", " warnings");
			if($info[2] == 0) unset($info[2]); else $info[2] = $info[2] . General::strPlural($info[2], " notice", " notices");
			
			$alerts = @implode(", ", $info);
						
			$name = basename($f, ".log");

?>
				<tr<?php print ($bEven ? ' class="even"' : ""); ?>>
					<td><a href="?page=/settings/logs/view/&amp;_l=<?php print $name; ?>" class="content"><?php print $date->get(true, true, strtotime($name)); ?></a></td>
					<td class="status <?php print (isset($info[0]) || isset($info[1]) ? "" : "maximum"); ?>"><strike>&bull; &bull; &bull; &bull; &bull;</strike></td>
				</tr>

<?php			
			$bEven = !$bEven;
		}
		
	} else {
		print '<tr><td colspan="2" class="inactive">None found.</td></tr>';
	
	}
?>				

			</tbody>
		</table>
	</form>
