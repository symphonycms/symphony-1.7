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

	$GLOBALS['pageTitle'] = "Version 1.1.00 Update Migration Tool";

	if(defined("__SYM_MIGRATION_ERRORS__")){
		define("__SYM_ERROR_MESSAGE__", '<strong>Migration Failed. Please check Symphony\'s <a href="'.URL."/symphony/?page=/settings/logs/view/&_l=".date("Ymd", $Admin->_date->get(false)).'">activity logs</a> for specific messages.</strong>');
	}

	if(defined("__SYM_MIGRATION_RESTORE_ERRORS__")){
		define("__SYM_ERROR_MESSAGE__", '<strong>Migration Restore Failed. Please check Symphony\'s <a href="'.URL."/symphony/?page=/settings/logs/view/&_l=".date("Ymd", $Admin->_date->get(false)).'">activity logs</a> for specific messages.</strong>');
	}

?>

	<form action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2><!-- PAGE TITLE --></h2>
		<fieldset>
			<p>This is the v1.1.00 Migration tool. Since there were a large number of changed in Symphony to do with <strong>muli-language support</strong>, the way Entries, Comments, Categories & Custom Fields are store in the database has changed. As a result, it is necessary to migrate them to the new format, otherwise your front end may not work as expected.</p>
			<p>Use the button below to begin the migration process. A <strong>backup of your database</strong> will be created in the <code>/symphony/tmp/</code> directory should something bad happen. Detailed information of this process can also be seen in your activity logs.</p>
			<p><strong>Note: Your Entries table data will be ALTERED DRAMATICALLY. You should do a full database backup of your own prior to using this tool. In the event that something horrible happens, and the Migration backup created is also corrupt, you wont have lost anything.</strong></p>
			<input name="action[begin]" type="submit" value="Migrate" />

		</fieldset>
<?php if(@is_file(TMP . "/migration-backup.sql")){ ?>
		<fieldset>
			<legend>Restore Previous Migration Backup</legend>
			<p>It appears you have a backed up database file from a previous Migration attempt. Use the button below to revert to that version. <strong>Note: Your Entries and Comments tables will be EMPTIED prior to this import. You should do a full database backup of your own prior to using this tool.</strong></p>
			<input name="action[restore]" type="submit" value="Import Previous Migration Backup" />
		</fieldset>

<?php } ?>


	</form>