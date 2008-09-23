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

	$GLOBALS['pageTitle'] = "Controllers";	


	$EM = new EventManager(array('parent' => &$Admin));
	$events = $EM->listAll();
		
	$DSM = new DatasourceManager(array('parent' => &$Admin));
	$datasources = $DSM->listAll();
	
	if(isset($_GET['_f'])){
		switch($_GET['_f']){		
		
			case "complete":
				$Admin->pageAlert("selected-success", array("Component", "deleted"));
				break;		
											
		}
	}
	
?>
	<form id="controllers" action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">

		<h2><!-- PAGE TITLE --></h2>
		<ul>
			<li>
				<h3>Data Sources <a href="<?php print URL; ?>/symphony/?page=/blueprint/datasources/new/" class="create button" title="Create a new data source">Create new</a></h3>
				<ul>
<?php

		if(is_array($datasources) && !empty($datasources)){
			foreach($datasources as $ds){
				if($ds['can_parse']){				
?>
					<li><a href="<?php print URL; ?>/symphony/?page=/blueprint/datasources/edit/&amp;file=<?php print strtolower($ds['handle']); ?>" title="data.<?php print $ds['handle']; ?>.php"><?php print $ds['name']; ?></a></li>
<?php

				}else{
?>

					<li class="external"><a href="<?php print URL; ?>/symphony/?page=/blueprint/datasources/info/&amp;file=<?php print strtolower($ds['handle']); ?>" title="data.<?php print $ds['handle']; ?>.php"><?php print $ds['name']; ?></a></li>

<?php				
				}
	
			}
		}
?>				
				</ul>
			</li>
			<li>
				<h3>Events</h3>
				<ul>
<?php

		if(is_array($events) && !empty($events)){
			foreach($events as $e){

?>				
					<li class="external"><a href="<?php print URL; ?>/symphony/?page=/blueprint/events/info/&amp;file=<?php print strtolower($e['handle']); ?>" title="event.<?php print $e['handle']; ?>.php"><?php print $e['name']; ?></a></li>
					
<?php		
			}
		}
?>				
				</ul>
			</li>			
		</ul>
	</form>
