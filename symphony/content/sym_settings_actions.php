<?php 

	$GLOBALS['pageTitle'] = 'File Actions';

	if(isset($_GET['sync'])){

		if($_GET['sync'] == 'complete'):
			$Admin->pageAlert("workspace-sync-complete");					

		elseif($_GET['sync'] == 'failed'):
			$Admin->pageAlert("workspace-sync-failed", NULL, false, 'error');
			
		endif;
	}
	
	if(isset($_GET['uninstall']) && $_GET['uninstall'] == 'failed')
		$Admin->pageAlert('uninstall-failed', NULL, false, 'error');	

?>

<form id="settings" action="" method="post">
	<h2>File Actions</h2>
	<fieldset>
		<fieldset>
			<legend>Synchronise Workspace</legend>
			<p>This will make sure that Symphony has up-to-date information about the workspace. Use this if you manually replace or significantly update the <code>/workspace</code> directory.</p>
			<input name="action[sync]" type="submit" value="Synchronise" />
		</fieldset>
		<fieldset>
			<legend>Uninstall Symphony</legend>
			<p>Uninstalling will remove all files written by Symphony. This includes your workspace, so if you have important files make sure you have backup before you perform this action.</p>
			<input name="action[uninstall]" type="submit" value="Uninstall" />
		</fieldset>
	</fieldset>
</form>