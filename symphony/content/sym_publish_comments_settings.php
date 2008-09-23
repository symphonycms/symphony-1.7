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

	$GLOBALS['pageTitle'] = "Comments > Settings";
	$date = $Admin->getDateObj();
	
	$bIsWritable = true;

    if(!is_writable(CONFIG)){
        $Admin->pageAlert("config-not-writable", NULL, false, 'error');
        $bIsWritable = false;
    }

	if(defined("__SYM_ENTRY_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
		
	}elseif(isset($_GET['_f'])){
		
		switch($_GET['_f']){
		
			case "saved":
				$Admin->pageAlert("saved-time", array("Comments preferences", date("h:i:sa", $date->get(true, false))));					
				break;
				
			case "complete-delete":
				$Admin->pageAlert("action-1-2-success", array("All spam comments", "deleted"));
				break;
			
		}
	}	
	if(isset($_POST['fields'])) $fields = $_POST['fields'];
	
	$TFM = new TextformatterManager(array('parent' => &$Admin));
	$formatters = $TFM->listAll();
	
?>
	<form id="settings" action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2>Comment Settings</h2>
		<fieldset>
			<fieldset>
				<legend>General Preferences</legend>
				<div class="group">
					
					<label><input name="fields[allow-duplicates]" type="checkbox" <?php print ($Admin->getConfigVar("allow-duplicates", "commenting") == 'off' ? ' checked="checked"' : ""); ?> /> Do not allow duplicate comments</label>
				</div>
				
				<label><input name="fields[email-notify]" type="checkbox" <?php print ($Admin->getConfigVar("email-notify", "commenting") == 'on' ? ' checked="checked"' : ""); ?> /> Email me when new comments are posted</label>
				<label><input name="fields[nuke-spam]" type="checkbox" <?php print ($Admin->getConfigVar("nuke-spam", "commenting") == 'on' ? ' checked="checked"' : ""); ?> /> Disallow comments identified as spam from being stored</label>				
			</fieldset>
			<fieldset>

				<legend>Spam Settings</legend>
				<label>Flag comment as spam if it contains <input name="fields[maximum-allowed-links]" <?php print General::fieldValue("value", $Admin->getConfigVar("maximum-allowed-links", "commenting")); ?> size="2" maxlength="2" /> or more links.</label>
				<div class="group">
					<label>Banned Words <small>Separate by commas</small> <textarea name="fields[banned-words]" cols="75" rows="6"><?php print $Admin->getConfigVar("banned-words", "commenting"); ?></textarea></label>
					<label>Replace banned words with <input name="fields[banned-words-replacement]" <?php print General::fieldValue("value", $Admin->getConfigVar("banned-words-replacement", "commenting")); ?> /></label>			
				</div>
				
				<label><acronym title="Internet Protocol">IP</acronym> Address Blacklist <small>Use regular expressions for wildcard matching</small> <textarea name="fields[ip-blacklist]" cols="75" rows="6"><?php print $Admin->getConfigVar("ip-blacklist", "commenting"); ?></textarea></label>
				
				<label><input name="fields[hide-spam-flagged]" type="checkbox" <?php print ($Admin->getConfigVar("hide-spam-flagged", "commenting") == 'on' ? ' checked="checked"' : ""); ?> /> Hide comments flagged as spam</label>
				<label><input name="fields[check-referer]" type="checkbox" <?php print ($Admin->getConfigVar("check-referer", "commenting") == 'on' ? ' checked="checked"' : ""); ?> /> Check referer when comments are posted</label>				
				
				<div id="delete-spam">Delete all spam comments
				    <input name="action[delete_spam]" type="submit" value="Delete Spam" />
				</div>			
			
			</fieldset>
			<fieldset>
				<legend>Formatting Options</legend>
				<label>Comment Formatting Preference
					<select name="fields[formatting-type]">
						<option>None</option>
<?php
					foreach($formatters as $name => $about){				
						
?>	
						<option value="<?php print $name; ?>" <?php print General::fieldValue("select", ($Admin->getConfigVar("formatting-type", "commenting") == $name)); ?>><?php print $about['name']; ?></option>
						
<?php
					}
?>					
					</select>
				</label>

			</fieldset>
			<input name="action[done]" type="submit" value="Save Changes" <?php print (!$bIsWritable ? 'disabled="disabled"' : ''); ?>/>
		</fieldset>
	</form>
