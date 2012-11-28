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

	$GLOBALS['pageTitle'] = "System Preferences";

    $date = $Admin->getDateObj();

    $bIsWritable = true;

    if(!is_writable(CONFIG)){
        $Admin->pageAlert("config-not-writable", NULL, false, 'error');
        $bIsWritable = false;
    }

	if(isset($_GET['_f'])){
		switch($_GET['_f']){

			case "saved":
				$Admin->pageAlert("saved-time", array("Preferences", date("h:i:sa", $date->get(true, false))));
				break;

		}
	}

	if(defined("__SYM_MISSINGFIELDS__")){
		$Admin->pageAlert("required", array(@implode(", ", $required)), false, 'error');
	}

	$date->setFormat("l g:i a");

?>
	<form id="settings" action="<?php print $Admin->getCurrentPageURL(); ?>" method="post">
		<h2><!-- PAGE TITLE --></h2>
		<fieldset>
			<fieldset>
				<legend>Website Status</legend>
				<label>Website Name <input name="settings[general][sitename]" value="<?php print htmlspecialchars( stripslashes( $Admin->getConfigVar('sitename', 'general'))); ?>" /></label>
				<div class="related">Online Status
					<label><input name="settings[public][status]" type="radio" value="online" <?php print ($Admin->getConfigVar("status", "public") == "online" ? ' checked="checked"' : ""); ?> /> Live</label>
					<label><input name="settings[public][status]" type="radio" value="offline" <?php print ($Admin->getConfigVar("status", "public") != "online" ? ' checked="checked"' : ""); ?> /> Maintenance Mode</label>
				</div>
				<label><input name="settings[symphony][allow_workspace_synchronisation]" <?php print ($Admin->getConfigVar('allow_workspace_synchronisation', 'symphony') == '1' ? ' checked="checked"' : ''); ?> type="checkbox" /> Allow automatic Workspace synchronisation</label>
			</fieldset>
			<fieldset>
				<legend>Regional Settings</legend>
				<label>Timezone <small><?php print $date->get(true, true); ?></small>
					<select name="settings[region][time_zone]">

<?php

								$zones[-12]  = "(GMT -12:00 hrs) Eniwetok, Kwajalein";
								$zones[-11]  = "(GMT -11:00 hrs) Midway Island, Samoa";
								$zones[-10]  = "(GMT -10:00 hrs) Hawaii";
								$zones[-9]   = "(GMT -9:00 hrs) Alaska";
								$zones[-8]   = "(GMT -8:00 hrs) Pacific Time (US &amp; Canada)";
								$zones[-7]   = "(GMT -7:00 hrs) Mountain Time (US &amp; Canada)";
								$zones[-6]   = "(GMT -6:00 hrs) Central Time (US &amp; Canada), Mexico City";
								$zones[-5]   = "(GMT -5:00 hrs) Eastern Time (US &amp; Canada), Bogota, Lima, Quito";
								$zones[-4]   = "(GMT -4:00 hrs) Atlantic Time (Canada), Caracas, La Paz";
								$zones[-3.5] = "(GMT -3:30 hrs) Newfoundland";
								$zones[-3]   = "(GMT -3:00 hrs) Brazil, Buenos Aires, Georgetown";
								$zones[-2]   = "(GMT -2:00 hrs) Mid-Atlantic";
								$zones[-1]   = "(GMT -1:00 hrs) Azores, Cape Verde Islands";
								$zones[0]    = "(GMT) Western Europe Time, London, Lisbon, Casablanca, Monrovia";
								$zones[1]    = "(GMT +1:00 hrs) Brussels, Copenhagen, Madrid, Paris";
								$zones[2]    = "(GMT +2:00 hrs) Kaliningrad, South Africa";
								$zones[3]    = "(GMT +3:00 hrs) Baghdad, Kuwait, Riyadh, Moscow, St. Petersburg, Volgograd, Nairobi";
								$zones[3.5]  = "(GMT +3:30 hrs) Tehran";
								$zones[4]    = "(GMT +4:00 hrs) Abu Dhabi, Muscat, Baku, Tbilisi";
								$zones[4.5]  = "(GMT +4:30 hrs) Kabul";
								$zones[5]    = "(GMT +5:00 hrs) Ekaterinburg, Islamabad, Karachi, Tashkent";
								$zones[5.5]  = "(GMT +5:30 hrs) Bombay, Calcutta, Madras, New Delhi";
								$zones[6]    = "(GMT +6:00 hrs) Almaty, Dhaka, Colombo";
								$zones[7]    = "(GMT +7:00 hrs) Bangkok, Hanoi, Jakarta";
								$zones[8]    = "(GMT +8:00 hrs) Beijing, Perth, Singapore, Hong Kong, Chongqing, Urumqi, Taipei";
								$zones[9]    = "(GMT +9:00 hrs) Tokyo, Seoul, Osaka, Sapporo, Yakutsk";
								$zones[9.5]  = "(GMT +9:30 hrs) Adelaide, Darwin";
								$zones[10]   = "(GMT +10:00 hrs) Brisbane, Sydney, Papua New Guinea, Vladivostok";
								$zones[11]   = "(GMT +11:00 hrs) Magadan, Solomon Islands, New Caledonia";
								$zones[12]   = "(GMT +12:00 hrs) Auckland, Wellington, Fiji, Kamchatka, Marshall Island";

								foreach($zones as $key => $val) {
									print '          <option value="'.$key.'" title="'. date("l g:i a", ($date->get(false, false) + ($key * 3600))). '"';
									if($Admin->getConfigVar('time_zone', 'region') == $key) print ' selected="selected"';
									print ">".$val."</option>\n";
								}

?>
					</select>
				</label>
				<label><input name="settings[region][dst]" <?php print ($Admin->getConfigVar("dst", "region") == "yes" ? ' checked="checked"' : ""); ?> type="checkbox" /> Use daylight savings time</label>
				<div class="group">
					<label>Time Format
						<select name="settings[region][time_format]">
<?php
							$timeFormats= array( "H:i:s", "H:i", "g:i:s a", "g:i a");

							foreach($timeFormats as $f) {
								print "<option value=\"".$f."\"";
								if($Admin->getConfigVar('time_format', 'region') == $f) print " selected=\"selected\"";
								print ">".date($f, mktime(4, 20, 0, 9, 21, 1981))."</option>\n";
							}
?>
						</select>
				    </label>
					<label>Date Format
						<select name="settings[region][date_format]">
<?php
						$dateFormats = array( "jS \\o\\f F Y",
											  "jS F Y",
											  "j F Y",
											  "j M y",
											  "F jS Y",
											  "F j Y",
											  "Y/m/d",
											  "m/d/Y",
											  "m/d/y",
											  "d/m/Y",
											  "d/m/y");


						foreach($dateFormats as $f) {
							print "<option value=\"".$f."\"";
							if($Admin->getConfigVar('date_format', 'region') == $f) print " selected=\"selected\"";
							print ">".date($f, mktime(4,20,0, 9, 21, 1981))."</option>\n";
						}
?>
						</select>

					</label>
				</div>
			</fieldset>
			<input name="action[done]" type="submit" value="Save Changes" <?php print (!$bIsWritable ? 'disabled="disabled"' : ''); ?>/>
			<a class="logs button" href="<?php print URL . "/symphony/?page=/settings/logs/"; ?>" title="View activity logs">Activity Logs</a>
<?php
			# Some example text stuck in by Scott :D
?>
			<a class="file-actions button" href="<?php print URL . "/symphony/?page=/settings/actions/"; ?>" title="File actions">File Actions</a>
		</fieldset>
	</form>
