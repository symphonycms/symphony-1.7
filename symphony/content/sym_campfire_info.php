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

	$date = $Admin->getDateObj();
		
	list($owner, $name) = explode("/", $_GET['name']);		
	
	if(!$info = $CampfireManager->about($name, $owner))
		$Admin->fatalError("Page Not Found", "The page you were looking for could not be found.");

	$link = $info['author']['name'];
	
	if(isset($info['author']['website']))
		$link = '<a href="' . General::validateURL($info['author']['website']) . '">' . $info['author']['name'] . '</a>';
		
	elseif(isset($info['author']['email']))
			$link = '<a href="mailto:' . $info['author']['email'] . '">' . $info['author']['name'] . '</a>';
		
	$GLOBALS['pageTitle'] = 'Your Campfire Services > ' . $info['name'];
?>
	<form id="campfire" action="<?php print $Admin->getCurrentPageURL(); ?>&amp;name=<?php print $_REQUEST['name']; ?>" method="post">
  	<h2><?php print $info['name']; ?></h2>
		<fieldset>
			<dl>
				<dt>Author</dt>
				<dd><?php print $link; ?></dd>
				<dt>Version</dt>
				<dd><?php print $info['version']; ?></dd>
				<dt>Release Date</dt>
				<dd><?php print $date->get(true, true, strtotime($info['release-date'])); ?></dd>		

			</dl>
			<?php print $info['description']; ?>
			<?php 

						
					if($CampfireManager->requiresUpdate($name, $owner) && $info['has-update-method']){
						
						print '<p><strong>Note: This Campfire Service is currently disabled as it is ready for updating. Use the button below to complete the update process.</strong></p>
						
						<input name="action[update]" type="submit" value="Update Service" />';						
						
					}	
							
					elseif($info['status'] == 'disabled' && $info['has-uninstall-method']){
						
						print '<p><strong>Note: This Campfire Service is currently disabled. You can enable it from your Campfire Services overview. Alternatively, you can uninstall this Campfire service, which will remove anything created by it, but will leave the original files intact. To fully remove it, you will need to manually delete the files.</strong></p>
						
						<input name="action[uninstall]" type="submit" value="Uninstall Service" />';
					}
					
					elseif($info['status'] == 'not installed'){
						
						include_once(TOOLKIT . '/class.account.php');
						$Account = new Account(&$Admin);
						$compatibility = $Account->checkCampfireServiceCompatiblity($name, $owner, floatval($info['version']) * 1000);

						switch($compatibility['status']){
							case 'incompatible':
								$Admin->pageAlert($err='This Campfire Service has not been updated since the last Symphony update. It has been deemed incompatible.', NULL, true, 'error');
								print '<p><strong>Note: You are not able to install this Campfire Service as it has been deemed incompatible with the version of Symphony you are using. You must update to the latest version first.</strong>';
								break;

							default:
								print '<p><strong>Note: This Campfire Service has not been installed. If you wish to install it, please use the button below.</strong></p><input name="action[install]" type="submit" value="Install Service" />';							
								break;	


						}
												

					}
					
					
			?>

		</fieldset>
	</form>