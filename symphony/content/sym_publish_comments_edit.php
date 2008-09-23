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

	$sql = "SELECT t1.*, t4.creator_ip as `author_ip` "
		 . "FROM tbl_comments as t1 "
		 . "LEFT JOIN tbl_entries as t3 ON t1.entry_id = t3.id "
		 . "LEFT JOIN tbl_metadata as t4 ON t4.relation_id = t1.id AND t4.class = 'comment' "
		 . "WHERE t1.`id` = '" . $_REQUEST['id'] . "' "
		 . "GROUP BY t3.`id`";
		 
	$fields = $DB->fetchRow(0, $sql);
	
	$GLOBALS['pageTitle'] = "Edit Comment";

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}
	
	if(isset($_POST['fields'])) $fields = $_POST['fields'];

	$current_blacklist = $Admin->getConfigVar("ip-blacklist", "commenting");
	$current_blacklist = preg_split('/,/', $current_blacklist, -1, PREG_SPLIT_NO_EMPTY);
	$current_blacklist = array_map("trim", $current_blacklist);
	
	if(@in_array($fields["author_ip"], $current_blacklist)) $fields["blacklist"] = "yes";
		
?>

	<form action="<?php print $Admin->getCurrentPageURL(); ?>&amp;id=<?php print $_GET['id']; ?>" method="post">
		<h2><!-- PAGE TITLE --></h2>
		<fieldset>
			<label>Comment <textarea name="fields[body]" cols="75" rows="15"><?php echo General::sanitize($fields['body']); ?></textarea></label>
			<fieldset>
				<label>Name <input name="fields[author_name]" <?php print General::fieldValue("value", $fields["author_name"]); ?> /></label>
				<label>Email Address <input name="fields[author_email]" <?php print General::fieldValue("value", $fields["author_email"]); ?> /></label>
				<label>Website <input name="fields[author_url]" <?php print General::fieldValue("value", General::validateURL($fields["author_url"])); ?> /></label>
				<label><acronym title="Internet Protocol">IP</acronym> Address <input name="fields[author_ip]" <?php print General::fieldValue("value", $fields["author_ip"]); ?> /></label>
				<label><input name="fields[spam]" type="checkbox" <?php print ($fields['spam'] == "yes" ? ' checked="checked"' : ""); ?> /> Flag this comment as spam</label>
				<label><input name="fields[blacklist]" type="checkbox" <?php print ($fields['blacklist'] == "yes" ? ' checked="checked"' : ""); ?> /> Blacklist this users <acronym title="Internet Protocol">IP</acronym> Address</label>			
			</fieldset>
			<input name="action[save]" type="submit" value="Save" />
			<input name="action[delete]" type="image" src="assets/images/buttons/delete.png" title="Delete this comment" />
		</fieldset>
	</form>
