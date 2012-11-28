<?php

	/***
	 *
	 * Symphony web publishing system
	 *
	 * Copyright 2004–2006 Twenty One Degrees Pty. Ltd.
	 *
	 * @version 1.7
	 * @licence https://github.com/symphonycms/symphony-1.7/blob/master/LICENCE
	 *
	 ***/

	if(!$Admin->authorIsOwner()) $Admin->fatalError('Access Denied', '<p>Access denied. You are not authorised to access this page.</p>', true, true);

	if(isset($_POST['action']['sync'])):
		$retval = $Admin->synchroniseWorkspace();
		General::redirect($Admin->getCurrentPageURL() . '&' . ($retval ? 'sync=complete' : 'sync=failed'));

	elseif(isset($_POST['action']['uninstall'])):

		$Admin->uninstall();

        $Admin->fatalError('Uninstall Successful', '<p>Any Campfire Services have been left intact, along with the <code>symphony</code> folder, <code>index.php</code> and your database.</p><p>To complete the uninstall you will need to remove the aforementioned items manually.</p>', true);

	endif;

?>