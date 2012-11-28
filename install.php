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

	##Show PHP Info
	if(isset($_REQUEST['info'])){
		phpinfo();
		exit();
	}

    @error_reporting(E_ALL ^ E_NOTICE);
    @ini_set("allow_call_time_pass_reference", 1);

	define('kVERSION', '1.7.01');
	define('kBUILD', '1701');
	define('kSUPPORT_SERVER', 'http://status.symphony21.com');
    define('kINSTALL_ASSET_LOCATION', kSUPPORT_SERVER . '/install/assets/4.0');

	## Need these for using particular Symphony files.
	define('__SYMPHONY_MINIMAL_BOOT__', true);

	## Include the existing Symphony configuration. If it exists
	## this will be an update, instead of installation.
	if(is_file('manifest/config.php')){

		require_once('manifest/config.php');
		require_once('symphony/lib/core/class.configuration.php');

		if(isset($settings) && is_array($settings)){

			$SymphonyConfiguration =& new Configuration(true);
			$SymphonyConfiguration->setArray($settings);

			$build = $SymphonyConfiguration->get('build', 'symphony');

			define('kCURRENT_BUILD', $build);
			define('kCURRENT_VERSION', $build{0} . '.' . $build{1} . ($build{2} != 0 || $build{3} != 0 ? '.' . $build{2} . $build{3} : ''));

			if($build < kBUILD) define('__IS_UPDATE__', true);
			else define('__ALREADY_UP_TO_DATE__', true);

		}

	}

	## 1.6.02 or lower
	elseif(is_file('conf/config.php')){

		require_once('conf/config.php');
		require_once('symphony/lib/core/class.configuration.php');

		if(isset($settings) && is_array($settings)){

			$SymphonyConfiguration =& new Configuration(true);
			$SymphonyConfiguration->setArray($settings);

			$build = $SymphonyConfiguration->get('build', 'symphony');

			define('kCURRENT_BUILD', $build);
			define('kCURRENT_VERSION', $build{0} . '.' . $build{1} . ($build{2} != 0 || $build{3} != 0 ? '.' . $build{2} . $build{3} : ''));

			if($build < kBUILD) define('__IS_UPDATE__', true);

		}
	}

	## If its not an update, we need to set a couple of important constants.
	if(!defined('__IS_UPDATE__')){
		define('__IN_SYMPHONY__', true);
		define('CRLF', "\r\n");
	}

	## Include some parts of the Symphony engine
	require_once('symphony/lib/boot/class.object.php');
	require_once('symphony/lib/core/class.mysql.php');
	require_once('symphony/lib/core/class.xmlelement.php');
	require_once('symphony/lib/core/class.general.php');
	require_once('symphony/lib/core/class.log.php');

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');

    $clean_path = $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
    $clean_path = rtrim($clean_path, '/\\');
    $clean_path = preg_replace('/\/{2,}/i', '/', $clean_path);

    define('_INSTALL_DOMAIN_', $clean_path);
	define('_INSTALL_URL_', 'http://' . $clean_path);

	define('CRLF', "\r\n");

    define('SYM_LOG_NOTICE', 0);
    define('SYM_LOG_WARNING', 1);
    define('SYM_LOG_ERROR', 2);
    define('SYM_LOG_ALL', 3);

	define('BAD_BROWSER', 0);
	define('MISSING_MYSQL', 3);
	define('MISSING_ZLIB', 5);
	define('MISSING_XSL', 6);
	define('MISSING_XML', 7);
	define('MISSING_PHP', 8);
	define('MISSING_MOD_REWRITE', 9);

	$header = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><!-- TITLE --></title>
		<link rel="stylesheet" type="text/css" href="'.kINSTALL_ASSET_LOCATION.'/main.css"/>
		<script type="text/javascript" src="'.kINSTALL_ASSET_LOCATION.'/main.js"></script>
	</head>' . CRLF;

	define('kHEADER', $header);

	$footer = '
</html>';

	define('kFOOTER', $footer);


    function installResult(&$Page, &$install_log, $start){

        if(!defined("_INSTALL_ERRORS_")){

            $install_log->writeToLog("============================================", true);
            $install_log->writeToLog("INSTALLATION COMPLETED: Execution Time - ".max(1, time() - $start)." sec (" . date("d.m.y H:i:s") . ")", true);
            $install_log->writeToLog("============================================" . CRLF . CRLF . CRLF, true);

        }else{

            $install_log->pushToLog(_INSTALL_ERRORS_, SYM_LOG_ERROR, true);
            $install_log->writeToLog("============================================", true);
            $install_log->writeToLog("INSTALLATION ABORTED: Execution Time - ".max(1, time() - $start)." sec (" . date("d.m.y H:i:s") . ")", true);
            $install_log->writeToLog("============================================" . CRLF . CRLF . CRLF, true);

			$Page->setPage('failure');
        }

    }

    function writeConfig($dest, $conf, $mode){

        $string  = "<?php\n";

        foreach($conf['define'] as $key => $val) {
                $string .= "define('". $key ."', '". addslashes($val) ."');\n";
        }

        $string .= '$settings = array();' . "\n\n";

        foreach($conf['settings'] as $set => $array) {
            foreach($array as $key => $val) {
                $string .= '$'."settings['".$set."']['".$key."'] = '".addslashes($val)."';\n";
            }
        }

        foreach($conf['require'] as $val) {
                $string .= "require_once('". addslashes($val) . "');\n";
        }

        $string .= "?>\n";

        return GeneralExtended::writeFile($dest . "/config.php", $string, $mode);

    }

    function fireSql(&$db, $data, &$error, $compatibility='NORMAL'){

		$compatibility = strtoupper($compatibility);

		if($compatibility == 'HIGH'){
			$data = str_replace('ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci', '', $data);
			$data = str_replace('collate utf8_unicode_ci', '', $data);
		}

		## Silently attempt to change the storage engine. This prevents INNOdb errors.
		$db->query('SET storage_engine=MYISAM', $e);

        $queries = preg_split('/;[\\r\\n]+/', $data, -1, PREG_SPLIT_NO_EMPTY);

        if(is_array($queries) && !empty($queries)){
            foreach($queries as $sql) {
                if(trim($sql) != "") $result = $db->query($sql, $error);
                if(!$result) return false;
            }
        }

        return true;

    }

	function getTableSchema(){
		return "
				CREATE TABLE `tbl_authors` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `username` varchar(20) collate utf8_unicode_ci NOT NULL default '',
				  `password` varchar(32) collate utf8_unicode_ci NOT NULL default '',
				  `firstname` varchar(100) collate utf8_unicode_ci default NULL,
				  `lastname` varchar(100) collate utf8_unicode_ci default NULL,
				  `email` varchar(255) collate utf8_unicode_ci default NULL,
				  `last_refresh` datetime default '0000-00-00 00:00:00',
				  `last_session` datetime default '0000-00-00 00:00:00',
				  `superuser` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
				  `textformat` varchar(50) collate utf8_unicode_ci default NULL,
				  `owner` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
				  `allow_sections` text collate utf8_unicode_ci,
				  `auth_token_active` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'no',
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `username` (`username`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_cache` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `hash` varchar(32) collate utf8_unicode_ci NOT NULL default '',
				  `section` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `creation` int(14) NOT NULL default '0',
				  `data` longtext collate utf8_unicode_ci NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `section` (`section`),
				  KEY `creation` (`creation`),
				  KEY `hash` (`hash`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_campfire` (
				  `id` varchar(32) collate utf8_unicode_ci NOT NULL default '',
				  `owner` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `status` enum('enabled','disabled') collate utf8_unicode_ci NOT NULL default 'enabled',
				  `version` double unsigned NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `owner` (`owner`),
				  KEY `handle` (`name`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_campfire2delegates` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `campfire_id` varchar(32) collate utf8_unicode_ci NOT NULL default '',
				  `page` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `delegate` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `callback` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  PRIMARY KEY  (`id`),
				  KEY `campfire_id` (`campfire_id`),
				  KEY `page` (`page`),
				  KEY `delegate` (`delegate`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_comments` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) NOT NULL default '0',
				  `author_id` int(11) unsigned default NULL,
				  `author_name` varchar(128) collate utf8_unicode_ci NOT NULL default '',
				  `author_email` varchar(255) collate utf8_unicode_ci default NULL,
				  `author_url` varchar(255) collate utf8_unicode_ci default NULL,
				  `body` text collate utf8_unicode_ci NOT NULL,
				  `spam` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'no',
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `author_id` (`author_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_customfields` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `handle` varchar(50) collate utf8_unicode_ci NOT NULL default '',
				  `description` varchar(255) collate utf8_unicode_ci default NULL,
				  `type` enum('checkbox','textarea','input','select','list','multiselect','upload','foreign') collate utf8_unicode_ci NOT NULL default 'input',
				  `parent_section` int(11) NOT NULL default '0',
				  `format` enum('0','1') collate utf8_unicode_ci NOT NULL default '1',
				  `required` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'yes',
				  `validator` varchar(50) collate utf8_unicode_ci default NULL,
				  `validation_rule` varchar(255) collate utf8_unicode_ci default NULL,
				  `default_state` enum('checked','unchecked','na') collate utf8_unicode_ci NOT NULL default 'na',
				  `destination_folder` varchar(255) collate utf8_unicode_ci default NULL,
				  `size` int(5) default '25',
				  `sortorder` int(11) NOT NULL default '1',
				  `location` enum('main','sidebar','drawer') collate utf8_unicode_ci NOT NULL default 'main',
				  `foreign_section` int(11) default NULL,
				  `foreign_select_multiple` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'no',
				  PRIMARY KEY  (`id`),
				  KEY `foreign_section` (`foreign_section`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_customfields_selectoptions` (
				  `field_id` int(11) NOT NULL default '0',
				  `values` text collate utf8_unicode_ci NOT NULL,
				  PRIMARY KEY  (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_entries` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `author_id` int(11) NOT NULL default '0',
				  `publish_date` datetime NOT NULL default '0000-00-00 00:00:00',
				  `publish_date_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
				  `type` int(11) default NULL,
				  `formatter` varchar(255) collate utf8_unicode_ci default NULL,
				  `valid_xml` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'yes',
				  PRIMARY KEY  (`id`),
				  KEY `author_id` (`author_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_entries2customfields` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) NOT NULL default '0',
				  `field_id` int(11) NOT NULL default '0',
				  `handle` varchar(255) collate utf8_unicode_ci default '',
				  `value` text collate utf8_unicode_ci,
				  `value_raw` text collate utf8_unicode_ci,
				  PRIMARY KEY  (`id`),
				  KEY `field_id` (`field_id`),
				  KEY `handle` (`handle`),
				  KEY `entry_id` (`entry_id`),
				  FULLTEXT KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_entries2customfields_list` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL default '0',
				  `field_id` int(11) unsigned NOT NULL default '0',
				  `value` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `value_raw` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `handle` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_entries2customfields_upload` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL default '0',
				  `field_id` int(11) unsigned NOT NULL default '0',
				  `file` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `type` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `size` int(11) unsigned NOT NULL default '0',
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`,`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_entries2sections` (
				  `entry_id` int(11) NOT NULL default '0',
				  `section_id` int(11) NOT NULL default '1',
				  PRIMARY KEY  (`entry_id`),
				  KEY `section_id` (`section_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_forgotpass` (
				  `author_id` int(11) NOT NULL default '0',
				  `token` varchar(32) collate utf8_unicode_ci NOT NULL default '',
				  PRIMARY KEY  (`author_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_masters` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `events` text collate utf8_unicode_ci,
				  `data_sources` text collate utf8_unicode_ci,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `filename` (`name`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_metadata` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `relation_id` int(11) NOT NULL default '0',
				  `class` varchar(50) collate utf8_unicode_ci NOT NULL default '',
				  `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
				  `creation_date_gmt` datetime NOT NULL default '0000-00-00 00:00:00',
				  `modified_date` datetime default NULL,
				  `modified_date_gmt` datetime default NULL,
				  `creator_ip` varchar(16) collate utf8_unicode_ci NOT NULL default '',
				  `modifier_ip` varchar(16) collate utf8_unicode_ci default NULL,
				  `modifier_id` int(11) default NULL,
				  `referrer` varchar(255) collate utf8_unicode_ci default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `relation_id` (`relation_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_pages` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `parent` int(11) default NULL,
				  `title` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `handle` varchar(255) collate utf8_unicode_ci default NULL,
				  `master` varchar(50) collate utf8_unicode_ci default NULL,
				  `url_schema` varchar(255) collate utf8_unicode_ci default '',
				  `data_sources` text collate utf8_unicode_ci,
				  `events` text collate utf8_unicode_ci,
				  `show_in_nav` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'yes',
				  `sortorder` int(11) NOT NULL default '0',
				  `cache_refresh_rate` int(5) unsigned NOT NULL default '60',
				  `full_caching` enum('yes','no') collate utf8_unicode_ci NOT NULL default 'no',
				  `type` varchar(255) collate utf8_unicode_ci NOT NULL default 'other',
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `handle` (`handle`),
				  KEY `parent` (`parent`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_sections` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `handle` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `commenting` enum('on','off') collate utf8_unicode_ci NOT NULL default 'on',
				  `primary_field` int(11) NOT NULL default '0',
				  `calendar_show` enum('show','hide') collate utf8_unicode_ci NOT NULL default 'show',
				  `author_column` enum('show','hide') collate utf8_unicode_ci NOT NULL default 'show',
				  `date_column` enum('show','hide') collate utf8_unicode_ci NOT NULL default 'show',
				  `sortorder` int(11) NOT NULL default '0',
				  `entry_order` varchar(7) collate utf8_unicode_ci NOT NULL default 'date',
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `handle` (`handle`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_sections_visible_columns` (
				  `field_id` int(11) NOT NULL default '0',
				  `section_id` int(11) NOT NULL default '0',
				  UNIQUE KEY `field_id` (`field_id`),
				  KEY `section_id` (`section_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_utilities` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `handle` varchar(255) collate utf8_unicode_ci NOT NULL default '',
				  `description` longtext collate utf8_unicode_ci NOT NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `name` (`name`),
				  UNIQUE KEY `handle` (`handle`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_utilities2datasources` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `utility_id` int(11) unsigned NOT NULL default '0',
				  `data_source` varchar(255) collate utf8_unicode_ci default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `utility_id` (`utility_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


				CREATE TABLE `tbl_utilities2events` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `utility_id` int(11) unsigned NOT NULL default '0',
				  `event` varchar(255) collate utf8_unicode_ci default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `utility_id` (`utility_id`,`event`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

		";
	}

	function getDefaultTableData(){

		return "

		INSERT INTO `tbl_entries` VALUES (1, 1, '2006-09-19 22:17:00', '2006-09-19 12:17:00', NULL, 'simplehtml', 'yes');
		INSERT INTO `tbl_entries` VALUES (3, 1, '2007-03-22 09:00:00', '2007-03-21 23:00:00', NULL, 'simplehtml', 'yes');
		INSERT INTO `tbl_entries` VALUES (4, 1, '2006-09-19 14:26:00', '2006-09-19 04:26:00', NULL, 'simplehtml', 'yes');
		INSERT INTO `tbl_entries` VALUES (5, 1, '2006-09-21 14:31:00', '2006-09-21 04:31:00', NULL, 'simplehtml', 'yes');

		INSERT INTO `tbl_entries2customfields` VALUES (1, 1, 57, 'check-out-the-symphony-showcase-for-ideas-and-insp', '<p>Check out the Symphony <a href=\"http://overture21.com/wiki/community/showcase\">showcase</a> for ideas and inspiration, or add your own site.</p>', 'Check out the Symphony &lt;a href=&quot;http://overture21.com/wiki/community/showcase&quot;&gt;showcase&lt;/a&gt; for ideas and inspiration, or add your own site.');
		INSERT INTO `tbl_entries2customfields` VALUES (7, 3, 4, 'welcome-to-symphony', '<p>Welcome to Symphony!</p>', 'Welcome to Symphony!');
		INSERT INTO `tbl_entries2customfields` VALUES (8, 3, 1, 'if-youre-reading-this-then-symphony-has-been-succ', '<p>If you''re reading this, then Symphony has been successfully installed on your server and is running smoothly. I''m sure you''d like to take some time to explore the system and see what Symphony has to offer, but if you don''t want to dive in too quickly, allow me a moment to introduce you to Symphony.</p>', 'If you''re reading this, then Symphony has been successfully installed on your server and is running smoothly. I''m sure you''d like to take some time to explore the system and see what Symphony has to offer, but if you don''t want to dive in too quickly, allow me a moment to introduce you to Symphony.');
		INSERT INTO `tbl_entries2customfields` VALUES (9, 3, 2, 'right-now-youre-viewing-the-default-theme-which', '<p>Right now, you''re viewing the default theme, which was designed and built by <a href=\"http://www.chaoticpattern.com/\">Allen</a>. If you''re new to XSLT, we highly recommend that you take a look under the hood and see how the theme works. Try making some changes and adding your own personal touch as an exercise before tackling projects on your own.</p>\n<p>You can <a href=\"/symphony/\">login to the Symphony admin</a> with the username and password you set up during installation. All your login and author details can be changed as you wish. You can add new authors and administrators to your website, and choose which sections they can publish to.</p>\n<p><a href=\"http://overture21.com/\">Overture</a> is Symphony''s resource website, with articles, tutorials and a flourishing community of Symphony developers. For the nitty-gritty on the finer points of using Symphony, the <a href=\"http://overture21.com/wiki/\">wiki</a> houses a growing collection of resources and documentation. If you have any questions or find any bugs in Symphony, please head over to the <a href=\"http://overture21.com/forum/\">Overture forum</a> since we''re often around to help out.</p>\n<p>From all the Symphony team, we hope you have fun using Symphony!</p>', 'Right now, you''re viewing the default theme, which was designed and built by &lt;a href=&quot;http://www.chaoticpattern.com/&quot;&gt;Allen&lt;/a&gt;. If you''re new to XSLT, we highly recommend that you take a look under the hood and see how the theme works. Try making some changes and adding your own personal touch as an exercise before tackling projects on your own.\r\n\r\nYou can &lt;a href=&quot;/symphony/&quot;&gt;login to the Symphony admin&lt;/a&gt; with the username and password you set up during installation. All your login and author details can be changed as you wish. You can add new authors and administrators to your website, and choose which sections they can publish to.\r\n\r\n&lt;a href=&quot;http://overture21.com/&quot;&gt;Overture&lt;/a&gt; is Symphony''s resource website, with articles, tutorials and a flourishing community of Symphony developers. For the nitty-gritty on the finer points of using Symphony, the &lt;a href=&quot;http://overture21.com/wiki/&quot;&gt;wiki&lt;/a&gt; houses a growing collection of resources and documentation. If you have any questions or find any bugs in Symphony, please head over to the &lt;a href=&quot;http://overture21.com/forum/&quot;&gt;Overture forum&lt;/a&gt; since we''re often around to help out.\r\n\r\nFrom all the Symphony team, we hope you have fun using Symphony!');
		INSERT INTO `tbl_entries2customfields` VALUES (10, 3, 63, NULL, NULL, NULL);
		INSERT INTO `tbl_entries2customfields` VALUES (11, 3, 11, 'yes', '<p>yes</p>', 'yes');
		INSERT INTO `tbl_entries2customfields` VALUES (13, 4, 57, 'you-can-use-your-symphony-account-username-and-pas', '<p>You can use your Symphony account username and password to sign in to Overture''s <a href=\"http://overture21.com/forum/\">forum</a> and <a href=\"http://overture21.com/wiki/\">wiki</a>.</p>', 'You can use your Symphony account username and password to sign in to Overture''s &lt;a href=&quot;http://overture21.com/forum/&quot;&gt;forum&lt;/a&gt; and &lt;a href=&quot;http://overture21.com/wiki/&quot;&gt;wiki&lt;/a&gt;.');
		INSERT INTO `tbl_entries2customfields` VALUES (14, 5, 57, 'while-your-website-is-in-maintenance-mode-you-can', '<p>While your website is in maintenance mode, you can append ?debug to the end of a page''s URL to view its XML and XSLT.</p>', 'While your website is in maintenance mode, you can append ?debug to the end of a page''s URL to view its XML and XSLT.');
		INSERT INTO `tbl_entries2customfields` VALUES (15, 3, 65, NULL, NULL, NULL);

		INSERT INTO `tbl_entries2customfields_list` VALUES (34, 3, 65, '<p>Life</p>', 'Life', 'life');
		INSERT INTO `tbl_entries2customfields_list` VALUES (35, 3, 65, '<p>Applications</p>', 'Applications', 'applications');

		INSERT INTO `tbl_entries2sections` VALUES (1, 2);
		INSERT INTO `tbl_entries2sections` VALUES (3, 1);
		INSERT INTO `tbl_entries2sections` VALUES (4, 2);
		INSERT INTO `tbl_entries2sections` VALUES (5, 2);

		INSERT INTO `tbl_metadata` VALUES (37, 1, 'entry', '2006-09-20 22:17:59', '2006-09-20 12:17:59', '2006-10-16 16:55:56', '2006-10-16 06:55:56', '127.0.0.1', '127.0.0.1', 1, 'http://www.yoursite.com');
		INSERT INTO `tbl_metadata` VALUES (41, 3, 'entry', '2006-09-20 22:46:05', '2006-09-20 12:46:05', '2007-03-22 17:44:42', '2007-03-22 07:44:42', '127.0.0.1', '127.0.0.1', 1, 'http://www.yoursite.com');
		INSERT INTO `tbl_metadata` VALUES (43, 4, 'entry', '2006-09-21 14:26:11', '2006-09-21 04:26:11', '2006-09-21 14:26:51', '2006-09-21 04:26:51', '127.0.0.1', '127.0.0.1', 1, 'http://www.yoursite.com');
		INSERT INTO `tbl_metadata` VALUES (44, 5, 'entry', '2006-09-21 14:31:25', '2006-09-21 04:31:25', '2006-09-21 15:59:07', '2006-09-21 05:59:07', '127.0.0.1', '127.0.0.1', 1, 'http://www.yoursite.com');

		";

	}

	function fetchSymphonyConfig(){
		global $SymphonyConfiguration;
		return $SymphonyConfiguration;
	}

	function fetchLastDBError(&$db){
		$errors = $db->debug();

		if(empty($errors) or !is_array($errors))
			return NULL;

		$e = end($errors);

		return $e['num'] . ': ' . $e['msg'] . ' in query ' . $e['query'];

	}

	Class GeneralExtended extends General{

        function realiseDirectory($path, $mode){

            if(!empty($path)){

                if(@file_exists($path) && !@is_dir($path)){
                    return false;

                }elseif(!@is_dir($path)){

			        @mkdir($path);

			        $oldmask = @umask(0);
			        @chmod($path, @intval($mode, 8));
			        @umask($oldmask);

				}
            }

            return true;

        }

	   	function redirect ($url){

			$url = str_replace("Location:", "", $url); //Just make sure.

			if(headers_sent($filename, $line)){
				print "<h1>Error: Cannot redirect to <a href=\"$url\">$url</a></h1><p>Output has already started in $filename on line $line</p>";
				exit();
			}

			header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');
	        header("Location: $url");
	        exit();
	    }

		function repeatStr($str, $xx){

			if($xx < 0) $xx = 0;

			$xx = ceil($xx);

			$result = NULL;

			for($ii = 0; $ii < $xx; $ii++)
				$result .= $str;

			return $result;

		}

	    function checkRequirement($item, $type, $expected){

	 		switch($type){

			    case "func":

			        $test = function_exists($item);
			        if($test != $expected) return false;
			        break;

			    case "setting":
			        $test = ini_get($item);
			        if(strtolower($test) != strtolower($expected)) return false;
			        break;

			    case "ext":
			        foreach(explode(":", $item) as $ext){
			            $test = extension_loaded($ext);
			            if($test == $expected) return true;
			        }

					return false;
			        break;

			     case "version":
			        if(version_compare($item, $expected, ">=") != 1) return false;
			        break;

			     case "permission":
			        if(!is_writable($item)) return false;
			        break;

			     case "remote":
			        $result = curler($item);
			        if(strpos(strtolower($result), "error") !== false) return false;
			        break;

			}

			return true;

	    }

	}

    Class SymphonyLog extends Log{

		function SymphonyLog($path){
			$this->setLogPath($path);

			if(@file_exists($this->getLogPath())){
				$this->open();

			}else{
				$this->open("OVERRIDE");
				$this->writeToLog("Symphony Installer Log", true);
				$this->writeToLog("Opened: ". date("d.m.Y G:i:s"), true);
				$this->writeToLog("Version: ". kVERSION, true);
				$this->writeToLog("Domain: "._INSTALL_URL_, true);
				$this->writeToLog("--------------------------------------------", true);
			}
		}
	}

	Class Action{

		function requirements(&$Page){

			$missing = array();

			if(!GeneralExtended::checkRequirement(phpversion(), "version", "4.3")){
				$Page->log->pushToLog("Requirement - PHP Version is not correct. ".phpversion()." detected." , SYM_LOG_ERROR, true);
				$missing[] = MISSING_PHP;
			}

			if(!GeneralExtended::checkRequirement('mysql_connect', "func", true)){
				$Page->log->pushToLog("Requirement - MySQL extension not present" , SYM_LOG_ERROR, true);
				$missing[] = MISSING_MYSQL;
			}

			elseif(!GeneralExtended::checkRequirement(mysql_get_client_info(), "version", '3.23')){
				$Page->log->pushToLog("Requirement - MySQL Version is not correct. ".mysql_get_client_info()." detected." , SYM_LOG_ERROR, true);
				$missing[] = MISSING_MYSQL;
			}

			if(!GeneralExtended::checkRequirement("zlib", "ext", true)){
				$Page->log->pushToLog("Requirement - ZLib extension not present" , SYM_LOG_ERROR, true);
				$missing[] = MISSING_ZLIB;
			}

			if(!GeneralExtended::checkRequirement("xml:libxml", "ext", true)){
				$Page->log->pushToLog("Requirement - No XML extension present" , SYM_LOG_ERROR, true);
				$missing[] = MISSING_XML;
			}

			if(!GeneralExtended::checkRequirement("xsl:xslt", "ext", true) && !GeneralExtended::checkRequirement("domxml_xslt_stylesheet", "func", true))	{
				$Page->log->pushToLog("Requirement - No XSL extension present" , SYM_LOG_ERROR, true);
				$missing[] = MISSING_XSL;
			}

			$Page->missing = $missing;

			return;

		}

		function update1700(&$Page){

			$config = fetchSymphonyConfig();

	        $install_log = $Page->log;

	        $start = time();

            $install_log->writeToLog(CRLF . '============================================', true);
            $install_log->writeToLog('UPDATE PROCESS STARTED (' . date("d.m.y H:i:s") . ')', true);
            $install_log->writeToLog('============================================', true);

			$config->set('build', '1701', 'symphony');
			$config->set('useragent', 'Symphony/1701', 'general');
		    $config->set('acct_server', kSUPPORT_SERVER, 'symphony');

			$string  = '<?php' . CRLF
					 . "define('DOCROOT','".DOCROOT."');" . CRLF
					 . "define('DOMAIN','". str_replace("http://", "", _INSTALL_DOMAIN_) . "');" . CRLF . CRLF
					 . '$settings = array();' . CRLF;

			$string .= $config->create("php");

			$string .= CRLF . "require_once(DOCROOT . '/symphony/lib/boot/bundle.php');" . CRLF . '?>';

	        $install_log->pushToLog("WRITING: Updates to Configuration File", SYM_LOG_NOTICE, true, true);
	        if(!GeneralExtended::writeFile(DOCROOT . '/manifest/config.php', $string, $config->get("write_mode", "file"))){
	            define("_INSTALL_ERRORS_", "Could not write config file. Check permission on /manifest.");
	            $install_log->pushToLog("ERROR: Writing Configuration File Failed", SYM_LOG_ERROR, true, true);
	            installResult($Page, $install_log, $start);
				return;
	        }

			if(!defined('_INSTALL_ERRORS_')){
		        $install_log->pushToLog("Installation Process Completed In ".max(1, time() - $start)." sec", SYM_LOG_NOTICE, true);
		        installResult($Page, $install_log, $start);
				GeneralExtended::redirect('http://' . rtrim(str_replace('http://', '', _INSTALL_DOMAIN_), '/') . '/symphony/');
			}

			return;
		}

		function update1602(&$Page){

			$config = fetchSymphonyConfig();

	        $install_log = $Page->log;

	        $start = time();

            $install_log->writeToLog(CRLF . '============================================', true);
            $install_log->writeToLog('UPDATE PROCESS STARTED (' . date("d.m.y H:i:s") . ')', true);
            $install_log->writeToLog('============================================', true);

			## Create Manifest directory structure
			#

	        $install_log->pushToLog("WRITING: Creating 'manifest' folder (/manifest)", SYM_LOG_NOTICE, true, true);
	        if(!GeneralExtended::realiseDirectory(DOCROOT . '/manifest', $config->get("write_mode", "directory"))){
	            define("_INSTALL_ERRORS_", "Could not create 'manifest' directory. Check permission on the root folder.");
	            $install_log->pushToLog("ERROR: Creation of 'manifest' folder failed.", SYM_LOG_ERROR, true, true);
	            installResult($Page, $install_log, $start);
				return;
	        }

	        $install_log->pushToLog("WRITING: Creating 'logs' folder (/manifest/logs)", SYM_LOG_NOTICE, true, true);
	        if(!GeneralExtended::realiseDirectory(DOCROOT . '/manifest/logs', $config->get("write_mode", "directory"))){
	            define("_INSTALL_ERRORS_", "Could not create 'logs' directory. Check permission on /manifest.");
	            $install_log->pushToLog("ERROR: Creation of 'logs' folder failed.", SYM_LOG_ERROR, true, true);
	            installResult($Page, $install_log, $start);
				return;
	        }

	        $install_log->pushToLog("WRITING: Creating 'cache' folder (/manifest/cache)", SYM_LOG_NOTICE, true, true);
	        if(!GeneralExtended::realiseDirectory(DOCROOT . '/manifest/cache', $config->get("write_mode", "directory"))){
	            define("_INSTALL_ERRORS_", "Could not create 'cache' directory. Check permission on /manifest.");
	            $install_log->pushToLog("ERROR: Creation of 'cache' folder failed.", SYM_LOG_ERROR, true, true);
	            installResult($Page, $install_log, $start);
				return;
	        }

	        $install_log->pushToLog("WRITING: Creating 'tmp' folder (/manifest/tmp)", SYM_LOG_NOTICE, true, true);
	        if(!GeneralExtended::realiseDirectory(DOCROOT . '/manifest/tmp', $config->get("write_mode", "directory"))){
	            define("_INSTALL_ERRORS_", "Could not create 'tmp' directory. Check permission on /manifest.");
	            $install_log->pushToLog("ERROR: Creation of 'tmp' folder failed.", SYM_LOG_ERROR, true, true);
	            installResult($Page, $install_log, $start);
				return;
	        }

			## Update the config
			$config->set('build', '1701', 'symphony');
			$config->set('useragent', 'Symphony/1701', 'general');
			$config->set('exclude-parameter-declarations', 'off', 'xsl');
			$config->set('cookie_prefix', 'sym_', 'symphony');
		    $config->set('acct_server', kSUPPORT_SERVER, 'symphony');

			if(!defined('DOMAIN')){

				$clean_path = $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
			    $clean_path = rtrim($clean_path, '/\\');
			    $clean_path = preg_replace('/\/{2,}/i', '/', $clean_path);

				define('DOMAIN', $clean_path);

			}

			$string  = '<?php' . CRLF
					 . "define('DOCROOT','".DOCROOT."');" . CRLF
					 . "define('DOMAIN','". str_replace("http://", "", _INSTALL_DOMAIN_) . "');" . CRLF . CRLF
					 . '$settings = array();' . CRLF;

			$string .= $config->create("php");

			$string .= CRLF . "require_once(DOCROOT . '/symphony/lib/boot/bundle.php');" . CRLF . '?>';

	        $install_log->pushToLog("WRITING: Updates to Configuration File", SYM_LOG_NOTICE, true, true);
	        if(!GeneralExtended::writeFile(DOCROOT . '/manifest/config.php', $string, $config->get("write_mode", "file"))){
	            define("_INSTALL_ERRORS_", "Could not write config file. Check permission on /manifest.");
	            $install_log->pushToLog("ERROR: Writing Configuration File Failed", SYM_LOG_ERROR, true, true);
	            installResult($Page, $install_log, $start);
				return;
	        }

			$install_log->pushToLog("MYSQL: Establishing Connection...", SYM_LOG_NOTICE, true, false);
	        if(!$db = new MySQL($config->get("database"))){
	            define("_INSTALL_ERRORS_", "There was a problem while trying to establish a connection to the MySQL server. Please check your settings.");
	            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
	            installResult($Page, $install_log, $start);
				return;

	        }else{
	            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
	        }

			## Make some updates to the tables
			$install_log->pushToLog("MYSQL: Executing Table Update Queries (1 of 4)...", SYM_LOG_NOTICE, true, false);
	        if(!$db->query('ALTER TABLE `tbl_comments` ADD `author_id` INT(11) UNSIGNED NULL AFTER `entry_id`')){
	            define('_INSTALL_ERRORS_', 'There was an error while trying to execute query. MySQL returned: ' . fetchLastDBError($db));
	            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
	            installResult($Page, $install_log, $start);
				return;

	        }else{
	            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
	        }

			$install_log->pushToLog("MYSQL: Executing Table Update Queries (2 of 4)...", SYM_LOG_NOTICE, true, false);
	        if(!$db->query('ALTER TABLE `tbl_comments` ADD INDEX (`author_id`)')){
	            define('_INSTALL_ERRORS_', 'There was an error while trying to execute query. MySQL returned: ' . fetchLastDBError($db));
	            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
	            installResult($Page, $install_log, $start);
				return;

	        }else{
	            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
	        }

			$install_log->pushToLog("MYSQL: Executing Table Update Queries (3 of 4)...", SYM_LOG_NOTICE, true, false);
	        if(!$db->query("ALTER TABLE  `tbl_customfields` CHANGE `type` `type` ENUM('checkbox', 'textarea', 'input', 'select', 'list', 'multiselect', 'upload', 'foreign') DEFAULT 'input' NOT NULL")){
	            define('_INSTALL_ERRORS_', 'There was an error while trying to execute query. MySQL returned: ' . fetchLastDBError($db));
	            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
	            installResult($Page, $install_log, $start);
				return;

	        }else{
	            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
	        }

			$install_log->pushToLog("MYSQL: Executing Table Update Queries (4 of 4)...", SYM_LOG_NOTICE, true, false);
	        if(!$db->query("ALTER TABLE `tbl_campfire` ADD `version` FLOAT(32) UNSIGNED NOT NULL;")){
	            define('_INSTALL_ERRORS_', 'There was an error while trying to execute query. MySQL returned: ' . fetchLastDBError($db));
	            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
	            installResult($Page, $install_log, $start);
				return;

	        }else{
	            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
	        }

			if(!defined('_INSTALL_ERRORS_')){
		        $install_log->pushToLog("Installation Process Completed In ".max(1, time() - $start)." sec", SYM_LOG_NOTICE, true);
		        installResult($Page, $install_log, $start);
				GeneralExtended::redirect('http://' . rtrim(str_replace('http://', '', _INSTALL_DOMAIN_), '/') . '/symphony/');
			}

			return;

		}

		function install(&$Page, $fields){

			$db = new MySQL;

			$db->connect($fields['database']['host'],
						 $fields['database']['username'],
						 $fields['database']['password'],
						 $fields['database']['port']);

			if($db->isConnected())
				$tables = $db->fetch("SHOW TABLES FROM `".$fields['database']['name']."` LIKE '".mysql_escape_string($fields['database']['prefix'])."%'");

			## Invalid path
			if(!@is_dir(rtrim($fields['docroot'], '/') . '/symphony')){
				$Page->log->pushToLog("Configuration - Bad Document Root Specified: " . $fields['docroot'], SYM_LOG_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-symphony-dir');
			}

			## Existing .htaccess
			elseif(is_file(rtrim($fields['docroot'], '/') . '/.htaccess')){
				$Page->log->pushToLog("Configuration - Existing '.htaccess' file found: " . $fields['docroot'] . '/.htaccess', SYM_LOG_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'existing-htaccess');
			}

			## Cannot write to workspace
			elseif(is_dir(rtrim($fields['docroot'], '/') . '/workspace') && !is_writable(rtrim($fields['docroot'], '/') . '/workspace')){
				$Page->log->pushToLog("Configuration - Workspace folder not writable: " . $fields['docroot'] . '/workspace', SYM_LOG_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-write-permission-workspace');
			}

			## Cannot write to root folder.
			elseif(!is_writable(rtrim($fields['docroot'], '/'))){
				$Page->log->pushToLog("Configuration - Root folder not writable: " . $fields['docroot'], SYM_LOG_NOTICE, true);
				define("kENVIRONMENT_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-write-permission-root');
			}

			## Failed to establish database connection
			elseif(!$db->isConnected()){
				$Page->log->pushToLog("Configuration - Could not establish database connection", SYM_LOG_NOTICE, true);
				define("kDATABASE_CONNECTION_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-database-connection');
			}

			## Failed to select database
			elseif(!$db->select($fields['database']['name'])){
				$Page->log->pushToLog("Configuration - Database '".$fields['database']['name']."' Not Found", SYM_LOG_NOTICE, true);
				define("kDATABASE_CONNECTION_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'no-database-connection');
			}

			## Failed to establish connection
			elseif(is_array($tables) && !empty($tables)){
				$Page->log->pushToLog("Configuration - Database table prefix clash with '".$fields['database']['name']."'", SYM_LOG_NOTICE, true);
				define("kDATABASE_PREFIX_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'database-table-clash');
			}

			## Username Not Entered
			elseif(trim($fields['user']['username']) == ''){
				$Page->log->pushToLog("Configuration - No username entered.", SYM_LOG_NOTICE, true);
				define("kUSER_USERNAME_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-no-username');
			}

			## Password Not Entered
			elseif(trim($fields['user']['password']) == ''){
				$Page->log->pushToLog("Configuration - No password entered.", SYM_LOG_NOTICE, true);
				define("kUSER_PASSWORD_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-no-password');
			}

			## Password mismatch
			elseif($fields['user']['password'] != $fields['user']['confirm-password']){
				$Page->log->pushToLog("Configuration - Passwords did not match.", SYM_LOG_NOTICE, true);
				define("kUSER_PASSWORD_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-password-mismatch');
			}

			## No Name entered
			elseif(trim($fields['user']['firstname']) == '' || trim($fields['user']['lastname']) == ''){
				$Page->log->pushToLog("Configuration - Did not enter First and Last names.", SYM_LOG_NOTICE, true);
				define("kUSER_NAME_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-no-name');
			}


			## Invalid Email
			elseif(!ereg('^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$', $fields['user']['email'])){
				$Page->log->pushToLog("Configuration - Invalid email address supplied.", SYM_LOG_NOTICE, true);
				define("kUSER_EMAIL_WARNING", true);
				if(!defined("ERROR")) define("ERROR", 'user-invalid-email');
			}

			## Otherwise there are no error, proceed with installation
			else{

				$config = $fields;

				$kDOCROOT = rtrim($config['docroot'], '/');

		        $database = array_map("trim", $fields['database']);

		        if(!isset($database['host']) || $database['host'] == "") $database['host'] = "localhost";
		        if(!isset($database['port']) || $database['port'] == "") $database['port'] = "3306";
		        if(!isset($database['prefix']) || $database['prefix'] == "") $database['prefix'] = "sym_";

		        $install_log = $Page->log;

		        $start = time();

	            $install_log->writeToLog(CRLF . '============================================', true);
	            $install_log->writeToLog('INSTALLATION PROCESS STARTED (' . date("d.m.y H:i:s") . ')', true);
	            $install_log->writeToLog('============================================', true);

		        $db = new MySQL;

		        $install_log->pushToLog("MYSQL: Establishing Connection...", SYM_LOG_NOTICE, true, false);
		        $db = new MySQL();

		        if(!$db->connect($database['host'], $database['username'], $database['password'], $database['port'])){
		            define("_INSTALL_ERRORS_", "There was a problem while trying to establish a connection to the MySQL server. Please check your settings.");
		            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
		            installResult($Page, $install_log, $start);
		        }else{
		            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
		        }

		        $install_log->pushToLog("MYSQL: Selecting Database '".$database['name']."'...", SYM_LOG_NOTICE, true, false);

		        if(!$db->select($database['name'])){
		            define("_INSTALL_ERRORS_", "Could not connect to specified database. Please check your settings.");
		            $install_log->pushToLog("Failed", SYM_LOG_NOTICE,true, true, true);
		            installResult($Page, $install_log, $start);
		        }else{
		            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
		        }

				$db->setPrefix($database['prefix']);

		        $install_log->pushToLog("MYSQL: Creating Tables...", SYM_LOG_NOTICE, true, false);
		        $error = NULL;
		        if(!fireSql($db, getTableSchema(), $error, ($config['database']['high-compatibility'] == 'yes' ? 'high' : 'normal'))){
		            define("_INSTALL_ERRORS_", "There was an error while trying to create tables in the database. MySQL returned: $error");
		            $install_log->pushToLog("Failed", SYM_LOG_ERROR,true, true, true);
		            installResult($Page, $install_log, $start);
		        }else{
		            $install_log->pushToLog("Done", SYM_LOG_NOTICE,true, true, true);
		        }

        		$author_sql = "INSERT INTO `tbl_authors` "
                   . "(username, password, firstname, lastname, email, superuser, owner, textformat) "
                   . "VALUES ("
                   . "'" . $config['user']['username'] . "', "
                   . "'" . md5($config['user']['password']) . "', "
                   . "'" . $config['user']['firstname'] . "', "
                   . "'" . $config['user']['lastname'] . "', "
                   . "'" . $config['user']['email'] . "', "
                   . "'1', '1', 'simplehtml' )";

		        $error = NULL;
		        $db->query($author_sql, $error);

		        if(!empty($error)){
		            define("_INSTALL_ERRORS_", "There was an error while trying create the default author. MySQL returned: $error");
		            $install_log->pushToLog("Failed", SYM_LOG_ERROR, true, true, true);
		            installResult($Page, $install_log, $start);

		        }else{
					$install_log->pushToLog("Done", SYM_LOG_NOTICE, true, true, true);
				}


				$conf = array();

  				$conf['define'] = array('DOCROOT' => $kDOCROOT,
                                		'DOMAIN' => str_replace("http://", "", _INSTALL_DOMAIN_));

		        $conf['require'] = array($kDOCROOT . '/symphony/lib/boot/bundle.php');

		        $conf['settings']['admin']['max_upload_size'] = '5242880';
				$conf['settings']['admin']['handle_length'] = '50';

				$conf['settings']['filemanager']['filetype_restriction'] = 'bmp, jpg, gif, png, doc, rtf, pdf, zip';
				$conf['settings']['filemanager']['enabled'] = 'yes';
				$conf['settings']['filemanager']['log_all_upload_attempts'] = 'yes';

		        $conf['settings']['symphony']['build'] = kBUILD;
		        $conf['settings']['symphony']['acct_server'] = kSUPPORT_SERVER;
		        $conf['settings']['symphony']['update'] = '5';
		        $conf['settings']['symphony']['lastupdatecheck'] = time();
		        $conf['settings']['symphony']['prune_logs'] = '1';
		        $conf['settings']['symphony']['allow_page_subscription'] = '1';
				$conf['settings']['symphony']['strict_section_field_validation'] = '1';
				$conf['settings']['symphony']['allow_primary_field_handles_to_change'] = '1';
				$conf['settings']['symphony']['pagination_maximum_rows'] = '17';
				$conf['settings']['symphony']['cookie_prefix'] = 'sym_';

				$conf['settings']['xsl']['exclude-parameter-declarations'] = 'off';

		        if($config['theme'] == 'yes')
		       		$conf['settings']['general']['sitename'] = 'Symphony Web Publishing System';

		        else
		        	$conf['settings']['general']['sitename'] = 'Website Name';

		        $conf['settings']['general']['useragent'] = 'Symphony/' . kBUILD;

				$conf['settings']['image']['cache'] = '1';
				$conf['settings']['image']['quality'] = '70';

		        $conf['settings']['file']['extend_timeout'] = 'off';
		        $conf['settings']['file']['write_mode'] = $config['permission']['file'];
		        $conf['settings']['directory']['write_mode'] = $config['permission']['directory'];

		        $conf['settings']['database']['driver'] = 'MySQL';
		        $conf['settings']['database']['host'] = $database['host'];
		        $conf['settings']['database']['port'] = $database['port'];
		        $conf['settings']['database']['user'] = $database['username'];
		        $conf['settings']['database']['password'] = $database['password'];
		        $conf['settings']['database']['db'] = $database['name'];
		        $conf['settings']['database']['tbl_prefix'] = $database['prefix'];
				$conf['settings']['database']['character_set'] = 'utf8';
				$conf['settings']['database']['character_encoding'] = 'utf8';
				$conf['settings']['database']['runtime_character_set_alter'] = '0';

				$conf['settings']['public']['status'] = 'online';
				$conf['settings']['public']['caching'] = 'on';
				$conf['settings']['public']['excerpt-length'] = '100';
				$conf['settings']['region']['time_zone'] = '10';
				$conf['settings']['region']['time_format'] = 'g:i a';
				$conf['settings']['region']['date_format'] = 'j F Y';
				$conf['settings']['region']['dst'] = 'no';

				$conf['settings']['workspace']['config_checksum'] = '';

				$conf['settings']['commenting']['allow-by-default'] = 'on';
				$conf['settings']['commenting']['email-notify'] = 'off';
				$conf['settings']['commenting']['allow-duplicates'] = 'off';
				$conf['settings']['commenting']['maximum-allowed-links'] = '3';
				$conf['settings']['commenting']['banned-words'] = '';
				$conf['settings']['commenting']['banned-words-replacement'] = '*****';
				$conf['settings']['commenting']['hide-spam-flagged'] = 'on';
				$conf['settings']['commenting']['formatting-type'] = 'simplehtml';
				$conf['settings']['commenting']['add-nofollow'] = 'off';
				$conf['settings']['commenting']['convert-urls'] = 'off';
				$conf['settings']['commenting']['check-referer'] = 'on';
				$conf['settings']['commenting']['nuke-spam'] = 'on';
				$conf['settings']['commenting']['ip-blacklist'] = '';


				## Create Manifest directory structure
				#

		        $install_log->pushToLog("WRITING: Creating 'manifest' folder (/manifest)", SYM_LOG_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest', $conf['settings']['directory']['write_mode'])){
		            define("_INSTALL_ERRORS_", "Could not create 'manifest' directory. Check permission on the root folder.");
		            $install_log->pushToLog("ERROR: Creation of 'manifest' folder failed.", SYM_LOG_ERROR, true, true);
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Creating 'logs' folder (/manifest/logs)", SYM_LOG_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest/logs', $conf['settings']['directory']['write_mode'])){
		            define("_INSTALL_ERRORS_", "Could not create 'logs' directory. Check permission on /manifest.");
		            $install_log->pushToLog("ERROR: Creation of 'logs' folder failed.", SYM_LOG_ERROR, true, true);
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Creating 'cache' folder (/manifest/cache)", SYM_LOG_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest/cache', $conf['settings']['directory']['write_mode'])){
		            define("_INSTALL_ERRORS_", "Could not create 'cache' directory. Check permission on /manifest.");
		            $install_log->pushToLog("ERROR: Creation of 'cache' folder failed.", SYM_LOG_ERROR, true, true);
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Creating 'tmp' folder (/manifest/tmp)", SYM_LOG_NOTICE, true, true);
		        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/manifest/tmp', $conf['settings']['directory']['write_mode'])){
		            define("_INSTALL_ERRORS_", "Could not create 'tmp' directory. Check permission on /manifest.");
		            $install_log->pushToLog("ERROR: Creation of 'tmp' folder failed.", SYM_LOG_ERROR, true, true);
		            installResult($Page, $install_log, $start);
					return;
		        }

		        $install_log->pushToLog("WRITING: Configuration File", SYM_LOG_NOTICE, true, true);
		        if(!writeConfig($kDOCROOT . "/manifest/", $conf, $conf['settings']['file']['write_mode'])){
		            define("_INSTALL_ERRORS_", "Could not write config file. Check permission on /manifest.");
		            $install_log->pushToLog("ERROR: Writing Configuration File Failed", SYM_LOG_ERROR, true, true);
		            installResult($Page, $install_log, $start);
		        }

		        $rewrite_base = dirname($_SERVER['PHP_SELF']);

		        $rewrite_base = trim($rewrite_base, '/');

		        if($rewrite_base != "") $rewrite_base .= '/';

		        $htaccess = '
### Symphony 1.7 - Do not edit ###

<IfModule mod_rewrite.c>
	RewriteEngine on
	RewriteBase /'.$rewrite_base.'

	### DO NOT APPLY RULES WHEN REQUESTING "favicon.ico"
	RewriteCond %{REQUEST_FILENAME} favicon.ico [NC]
	RewriteRule .* - [S=14]

	### IMAGE RULES
	RewriteRule ^image/([0-9]+)\/([0-9]+)\/(0|1)\/([a-fA-f0-9]{1,6})\/external/([\W\w]+)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=$1&height=$2&crop=$3&bg=$4&_f=$5.$6&external=true [L]
	RewriteRule ^image/external/([\W\w]+)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=0&height=0&crop=0&bg=0&_f=$1.$2&external=true [L]

	RewriteRule ^image/([0-9]+)\/([0-9]+)\/(0|1)\/([a-fA-f0-9]{1,6})\/external/(.*)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=$1&height=$2&crop=$3&bg=$4&_f=$5.$6&external=true [L]
	RewriteRule ^image/external/(.*)\.(jpg|gif|jpeg|png|bmp)$  /'.$rewrite_base.'symphony/image.php?width=0&height=0&crop=0&bg=0&_f=$1.$2&external=true [L]

	RewriteRule ^image/([0-9]+)\/([0-9]+)\/(0|1)\/([a-fA-f0-9]{1,6})\/([\W\w]+)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=$1&height=$2&crop=$3&bg=$4&_f=$5.$6 	[L]
	RewriteRule ^image/([\W\w]+)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=0&height=0&crop=0&bg=0&_f=$1.$2 	[L]

	RewriteRule ^image/([0-9]+)\/([0-9]+)\/(0|1)\/([a-fA-f0-9]{1,6})\/(.*)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=$1&height=$2&crop=$3&bg=$4&_f=$5.$6 	[L]
	RewriteRule ^image/(.*)\.(jpg|gif|jpeg|png|bmp)$   /'.$rewrite_base.'symphony/image.php?width=0&height=0&crop=0&bg=0&_f=$1.$2 	[L]

	### CHECK FOR TRAILING SLASH - Will ignore files
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_URI} !/'.trim($rewrite_base, '/').'$
	RewriteCond %{REQUEST_URI} !(.*)/$
	RewriteRule ^(.*)$ /'.$rewrite_base.'$1/ [L,R=301]

	### MAIN REWRITE - This will ignore directories
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.*)\/$ /'.$rewrite_base.'index.php?page=$1&%{QUERY_STRING}	[L]

</IfModule>

DirectoryIndex index.php
IndexIgnore *

######
';

		        $install_log->pushToLog("CONFIGURING: Frontend", SYM_LOG_NOTICE, true, true);
		        if(!GeneralExtended::writeFile($kDOCROOT . "/.htaccess", $htaccess, $conf['settings']['file']['write_mode'])){
		            define("_INSTALL_ERRORS_", "Could not write .htaccess file. Check permission on " . $kDOCROOT);
		            $install_log->pushToLog("ERROR: Writing .htaccess File Failed", SYM_LOG_ERROR, true, true);
		            installResult($Page, $install_log, $start);
		        }

				if(@is_file($fields['docroot'] . '/workspace/workspace.conf')){

			        $install_log->pushToLog("CONFIGURING: Importing Workspace", SYM_LOG_NOTICE, true, false);
			        if(!fireSql($db, file_get_contents($fields['docroot'] . '/workspace/workspace.conf') . getDefaultTableData(), $error, ($config['database']['high-compatibility'] == 'yes' ? 'high' : 'normal'))){
			            define("_INSTALL_ERRORS_", "There was an error while trying to import the workspace data. MySQL returned: $error");
			            $install_log->pushToLog("Failed", SYM_LOG_ERROR,true, true, true);
			            installResult($Page, $install_log, $start);

			        }else{
						$install_log->pushToLog("Done", SYM_LOG_NOTICE, true, true, true);
					}

				}elseif(@!is_dir($fields['docroot'] . '/workspace')){

					### Create the workspace folder structure
					#

			        $install_log->pushToLog("WRITING: Creating 'workspace' folder (/workspace)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'data-sources' folder (/workspace/data-sources)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/data-sources', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/data-sources' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/data-sources' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'events' folder (/workspace/events)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/events', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/events' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/events' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'masters' folder (/workspace/masters)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/masters', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/masters' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/masters' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'pages' folder (/workspace/pages)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/pages', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/pages' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/pages' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'text-formatters' folder (/workspace/text-formatters)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/text-formatters', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/text-formatters' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/text-formatters' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'upload' folder (/workspace/upload)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/upload', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/upload' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/upload' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

			        $install_log->pushToLog("WRITING: Creating 'utilities' folder (/workspace/utilities)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/workspace/utilities', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'workspace/utilities' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'workspace/utilities' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }

				}

				if(@!is_dir($fields['docroot'] . '/campfire')){
			        $install_log->pushToLog("WRITING: Creating 'campfire' folder (/campfire)", SYM_LOG_NOTICE, true, true);
			        if(!GeneralExtended::realiseDirectory($kDOCROOT . '/campfire', $conf['settings']['directory']['write_mode'])){
			            define("_INSTALL_ERRORS_", "Could not create 'campfire' directory. Check permission on the root folder.");
			            $install_log->pushToLog("ERROR: Creation of 'campfire' folder failed.", SYM_LOG_ERROR, true, true);
			            installResult($Page, $install_log, $start);
						return;
			        }
				}

		        $install_log->pushToLog("Installation Process Completed In ".max(1, time() - $start)." sec", SYM_LOG_NOTICE, true);

		        installResult($Page, $install_log, $start);

				GeneralExtended::redirect('http://' . rtrim(str_replace('http://', '', _INSTALL_DOMAIN_), '/') . '/symphony/');

		    }

		}

	}

	Class Page{

		var $_header;
		var $_footer;
		var $_content;
		var $_vars;
		var $_result;
		var $log;
		var $missing;
		var $_page;

		function Page(&$log){
			$this->_header = $this->_footer = $this->_content = NULL;
			$this->_result = NULL;
			$this->_vars = $this->missing = array();
			$this->log = $log;
		}

		function setPage($page){
			$this->_page = $page;
		}

		function getPage(){
			return $this->_page;
		}

		function setFooter($footer){
			$this->_footer = $footer;
		}

		function setHeader($header){
			$this->_header = $header;
		}

		function setContent($content){
			$this->_content = $content;
		}

		function setTemplateVar($name, $value){
			$this->_vars[$name] = $value;
		}

		function render(){
			$this->_result = $this->_header . $this->_content . $this->_footer;

			if(is_array($this->_vars) && !empty($this->_vars)){
				foreach($this->_vars as $name => $val){
					$this->_result = str_replace('<!-- ' . strtoupper($name) . ' -->', $val, $this->_result);
				}
			}

			return $this->_result;

		}

		function display(){
			return ($this->_result ? $this->_result : $this->render());
		}

	}

 	Class Widget{

		function createSimpleElement($name, $value=NULL, $attr=NULL){

			$obj = new XMLElement($name, $value);

			if(is_array($attr) && !empty($attr)){
				foreach($attr as $key => $val){
					$obj->setAttribute($key, $val);
				}
			}

			return $obj;

		}

		function note($value){
			return Widget::createSimpleElement('p', $value, array('class' => 'note'));
		}

		function warning($value){
			return Widget::createSimpleElement('p', $value, array('class' => 'warning'));
		}

		function input($name, $value=NULL, $attr=NULL, $type='text'){

			if(!is_array($attr) || empty($attr)) $attr = array();

			$attr = array_merge($attr, array('name' => $name));

			if($type) $attr = array_merge($attr, array('type' => $type));
			if($value) $attr = array_merge($attr, array('value' => $value));

			return Widget::createSimpleElement('input', NULL, $attr);
		}

		function definitionList($pairs, $class=NULL){
			$obj = new XMLElement('dl');

			if($class) $obj->setAttribute('class', $class);

			foreach($pairs as $p){
				list($dt, $dd) = $p;
				$obj->addChild(new XMLElement('dt', $dt));
				$obj->addChild(new XMLElement('dd', $dd));
			}

			return $obj;
		}

		function select($name, $options, $selected=NULL, $attr=NULL){

			if(!is_array($attr) || empty($attr)) $attr = array();

			$attr = array_merge($attr, array('name' => $name));

			$obj = Widget::createSimpleElement('select', NULL, $attr);

			foreach($options as $o){
				if(!is_array($o)) $o = array($o => $o);

				$key = array_keys($o);
				$key = $key[0];

				$val = $o[$key];

				$option = new XMLElement('option', $val);
				$option->setAttribute('value', $key);
				if($selected == $key) $option->setAttribute('selected', 'selected');

				$obj->addChild($option);
			}

			return $obj;

		}

		function label($text, $inputs, $class=NULL){
			$obj = new XMLElement('label', $text);
			if($class) $obj->setAttribute('class', $class);

			if(!is_array($inputs) && is_object($inputs)) $inputs = array($inputs);

			foreach($inputs as $ii) $obj->addChild($ii);

			return $obj;
		}

	}

	$fields = array();

	if(isset($_POST['fields'])) $fields = $_POST['fields'];
	else{

		$fields['docroot'] = rtrim($_SERVER["DOCUMENT_ROOT"] . '/' . dirname($_SERVER['PHP_SELF']), '/\\');
		$fields['docroot'] = preg_replace('/\/{2,}/i', '/', $fields['docroot']);
		$fields['database']['host'] = 'localhost';
		$fields['database']['port'] = '3306';
		$fields['database']['prefix'] = 'sym_';
		$fields['permission']['file'] = '0777';
		$fields['permission']['directory'] = '0755';

	}

	$warnings = array(

		'no-symphony-dir' => 'No <code>/symphony</code> directory was found at this location. Please upload the contents of Symphony\'s install package here.',
		'no-write-permission-workspace' => 'Symphony does not have write permission to the existing <code>/workspace</code> directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive <code>chmod -R</code> command.',
		'no-write-permission-manifest' => 'Symphony does not have write permission to the <code>/manifest</code> directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive <code>chmod -R</code> command.',
		'no-write-permission-root' => 'Symphony does not have write permission to the root directory. Please modify permission settings on this directory. This is necessary only if you are not including a workspace, and can be reverted once installation is complete.',
		'no-write-permission-htaccess' => 'Symphony does not have write permission to the temporary <code>htaccess</code> file. Please modify permission settings on this file so it can be written to, and renamed.',
		'existing-htaccess' => 'There appears to be an existing <code>.htaccess</code> file in the Symphony install location. To avoid name clashes, you will need to delete or rename this file.',
		'no-database-connection' => 'Symphony was unable to connect to the specified database. You may need to modify host or port settings.',
		'database-table-clash' => 'The table prefix <code><!-- TABLE-PREFIX --></code> is already in use. Please choose a different prefix to use with Symphony.',
		'user-password-mismatch' => 'The password and confirmation did not match. Please retype your password.',
		'user-invalid-email' => 'This is not a valid email address. You must provide an email address since you will need it if you forget your password.',
		'user-no-username' => 'You must enter a Username. This will be your Symphony login information.',
		'user-no-password' => 'You must enter a Password. This will be your Symphony login information.',
		'user-no-name' => 'You must enter your name.'

	);

	$notices = array(
		'existing-workspace' => 'An existing <code>/workspace</code> directory was found at this location. Symphony will use this workspace.'
	);

	Class Display{

		function index(&$Page, &$Contents, $fields){

			global $warnings;
			global $notices;

			$Form = new XMLElement('form');
			$Form->setAttribute('action', 'install.php');
			$Form->setAttribute('method', 'post');

			/**
			 *
			 * START ENVIRONMENT SETTINGS
			 *
			**/

/*
		<fieldset>
			<legend>Environment Settings</legend>
			<p>Symphony is ready to be installed at the following location.</p>
			<label class="warning">Root Path <input name="" value="/users/21degre/public_html/" /></label>
			<p class="warning">No <code>/symphony</code> directory was found at this location. Please upload the contents of Symphony's install package here.</p>
			<p class="note">An existing <code>/workspace</code> directory was found at this location. Symphony will use this workspace.</p>
			<p class="warning">Symphony does not have write permission to the existing <code>/workspace</code> directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive <code>chmod -R</code> command.</p>
			<p class="warning">Symphony does not have write permission to the <code>/manifest</code> directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive <code>chmod -R</code> command.</p>
		</fieldset>
*/

				$Environment = new XMLElement('fieldset');
				$Environment->addChild(new XMLElement('legend', 'Environment Settings'));
				$Environment->addChild(new XMLElement('p', 'Symphony is ready to be installed at the following location.'));

				$class = NULL;
				if(defined('kENVIRONMENT_WARNING') && kENVIRONMENT_WARNING == true) $class = 'warning';

				$Environment->addChild(Widget::label('Root Path', Widget::input('fields[docroot]', $fields['docroot']), $class));

				if(defined('ERROR') && defined('kENVIRONMENT_WARNING'))
					$Environment->addChild(Widget::warning($warnings[ERROR]));

				## CHECK FOR AN EXISTING WORKSPACE FOLDER
				if(!defined('ERROR') && @is_file($fields['docroot'] . '/workspace/workspace.conf')){
					$Environment->addChild(Widget::note($notices['existing-workspace']));
				}

				$Form->addChild($Environment);

			/** END ENVIRONMENT SETTINGS **/

			/**
			 *
			 * START DATABASE SETTINGS
			 *
			**/

/*
		<fieldset>
			<legend>Database Connection</legend>
			<p>Please provide Symphony with access to a database.</p>
			<label class="warning">Database <input name="" /></label>
			<div class="group warning">
				<label>Username <input name="" /></label>
				<label>Password <input name="" type="password" /></label>
			</div>
			<p class="warning">Symphony was unable to connect to the specified database. You may need to modify host or port settings.</p>

			...

		</fieldset>
*/

				$Database = new XMLElement('fieldset');
				$Database->addChild(new XMLElement('legend', 'Database Connection'));
				$Database->addChild(new XMLElement('p', 'Please provide Symphony with access to a database.'));

				$class = NULL;
				if(defined('kDATABASE_CONNECTION_WARNING') && kDATABASE_CONNECTION_WARNING == true) $class = ' warning';

				## fields[database][name]
				$Database->addChild(Widget::label('Database', Widget::input('fields[database][name]', $fields['database']['name'])));

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group' . $class);

				## fields[database][username]
				$Div->addChild(Widget::label('Username', Widget::input('fields[database][username]', $fields['database']['username'])));

				## fields[database][password]
				$Div->addChild(Widget::label('Password', Widget::input('fields[database][password]', $fields['database']['password'], NULL, 'password')));

				$Database->addChild($Div);

				if(defined('ERROR') && defined('kDATABASE_CONNECTION_WARNING'))
					$Database->addChild(Widget::warning($warnings[ERROR]));

/*

			...

			<fieldset>
				<legend>Advanced Configuration</legend>
				<p>Leave these fields unless you are sure they need to be changed.</p>
				<div class="group">
					<label>Host <input name="" value="localhost" /></label>
					<label>Port <input name="" value="3306" /></label>
				</div>
				<label class="warning">Table Prefix <input name="" value="sym_" /></label>
				<p class="warning">The table prefix <code>sym_</code> is already in use. Please choose a different prefix to use with Symphony.</p>
				<label class="option"><input name="" type="checkbox" /> Use compatibility mode</label>
				<p>Symphony normally specifies UTF-8 character encoding for database entries. With compatibility mode enabled, Symphony will instead use the default character encoding of your database.</p>
			</fieldset>
*/

				$Fieldset = new XMLElement('fieldset');
				$Fieldset->addChild(new XMLElement('legend', 'Advanced Configuration'));
				$Fieldset->addChild(new XMLElement('p', 'Leave these fields unless you are sure they need to be changed.'));

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group');

				## fields[database][host]
				$Div->addChild(Widget::label('Host', Widget::input('fields[database][host]', $fields['database']['host'])));

				## fields[database][port]
				$Div->addChild(Widget::label('Port', Widget::input('fields[database][port]', $fields['database']['port'])));

				$Fieldset->addChild($Div);

				$class = NULL;
				if(defined('kDATABASE_PREFIX_WARNING') && kDATABASE_PREFIX_WARNING == true) $class = 'warning';

				## fields[database][prefix]
				$Fieldset->addChild(Widget::label('Table Prefix', Widget::input('fields[database][prefix]', $fields['database']['prefix']), $class));

				if(defined('ERROR') && defined('kDATABASE_PREFIX_WARNING'))
					$Fieldset->addChild(Widget::warning($warnings[ERROR]));

				$Page->setTemplateVar('TABLE-PREFIX', $fields['database']['prefix']);

				## fields[database][high-compatibility]
				$Fieldset->addChild(Widget::label('Use compatibility mode', Widget::input('fields[database][high-compatibility]', 'yes', NULL, 'checkbox'), 'option'));

				$Fieldset->addChild(new XMLElement('p', 'Symphony normally specifies UTF-8 character encoding for database entries. With compatibility mode enabled, Symphony will instead use the default character encoding of your database.'));

				$Database->addChild($Fieldset);

				$Form->addChild($Database);

			/** END DATABASE SETTINGS **/

			/**
			 *
			 * START PERMISSION SETTINGS
			 *
			**/

/*
				<fieldset>
					<legend>Permission Settings</legend>
					<p>Symphony needs permission to read and write both files and directories.</p>
					<div class="group">
						<label>Files
							<select name="">
								<option value="0777">0777</option>
							</select>
						</label>
						<label>Directories
							<select name="">
								<option value="0777">0777</option>
							</select>
						</label>
					</div>
				</fieldset>
*/

				$Permissions = new XMLElement('fieldset');
				$Permissions->addChild(new XMLElement('legend', 'Permission Settings'));
				$Permissions->addChild(new XMLElement('p', 'Symphony needs permission to read and write both files and directories.'));

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group');

				## fields[permission][file]
				$Div->addChild(Widget::label('Files', Widget::select('fields[permission][file]', array('0777', '0775', '0755', '0666', '0644', '0444'), $fields['permission']['file'])));

				## fields[permission][directory]
				$Div->addChild(Widget::label('Directories', Widget::select('fields[permission][directory]', array('0777', '0775', '0755', '0666', '0644', '0444'), $fields['permission']['directory'])));

				$Permissions->addChild($Div);
				$Form->addChild($Permissions);

			/** END PERMISSION SETTINGS **/

			/**
			 *
			 * START USER SETTINGS
			 *
			**/

/*
		<fieldset>
			<legend>User Information</legend>
			<p>Once installed, you will be able to login to the Symphony admin with these user details.</p>
			<label>Username <input name="" /></label>
			<div class="group warning">
				<label>Password <input name="" type="password" /></label>
				<label>Confirm Password <input name="" type="password" /></label>
			</div>
			<p class="warning">The password and confirmation did not match. Please retype your password.</p>

			...

		</fieldset>
*/

				$User = new XMLElement('fieldset');
				$User->addChild(new XMLElement('legend', 'User Information'));
				$User->addChild(new XMLElement('p', 'Once installed, you will be able to login to the Symphony admin with these user details.'));

				$class = NULL;
				if(defined('kUSER_USERNAME_WARNING') && kUSER_PASSWORD_WARNING == true) $class = 'warning';

				## fields[user][username]
				$User->addChild(Widget::label('Username', Widget::input('fields[user][username]', $fields['user']['username']), $class));

				if(defined('ERROR') && defined('kUSER_USERNAME_WARNING'))
					$User->addChild(Widget::warning($warnings[ERROR]));

				$class = NULL;
				if(defined('kUSER_PASSWORD_WARNING') && kUSER_PASSWORD_WARNING == true) $class = ' warning';

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group' . $class);

				## fields[user][password]
				$Div->addChild(Widget::label('Password', Widget::input('fields[user][password]', $fields['user']['password'], NULL, 'password')));

				## fields[user][confirm-password]
				$Div->addChild(Widget::label('Confirm Password', Widget::input('fields[user][confirm-password]', $fields['user']['confirm-password'], NULL, 'password')));

				$User->addChild($Div);

				if(defined('ERROR') && defined('kUSER_PASSWORD_WARNING'))
					$User->addChild(Widget::warning($warnings[ERROR]));
/*

			...

			<fieldset>
				<legend>Personal Information</legend>
				<p>Please add the following personal details for this user.</p>
				<div class="group">
					<label>First Name <input name="" /></label>
					<label>Last Name <input name="" /></label>
				</div>
				<label class="warning">Email Address <input name="" /></label>
				<p class="warning">This is not a valid email address. You must provide an email address since you will need it if you forget your password.</p>
			</fieldset>
*/

				$Fieldset = new XMLElement('fieldset');
				$Fieldset->addChild(new XMLElement('legend', 'Personal Information'));
				$Fieldset->addChild(new XMLElement('p', 'Please add the following personal details for this user.'));

				$class = NULL;
				if(defined('kUSER_NAME_WARNING') && kUSER_EMAIL_WARNING == true) $class = ' warning';

				$Div = new XMLElement('div');
				$Div->setAttribute('class', 'group' . $class);

				## fields[database][host]
				$Div->addChild(Widget::label('First Name', Widget::input('fields[user][firstname]', $fields['user']['firstname'])));

				## fields[database][port]
				$Div->addChild(Widget::label('Last Name', Widget::input('fields[user][lastname]', $fields['user']['lastname'])));

				$Fieldset->addChild($Div);

				if(defined('ERROR') && defined('kUSER_NAME_WARNING'))
					$Fieldset->addChild(Widget::warning($warnings[ERROR]));

				$class = NULL;
				if(defined('kUSER_EMAIL_WARNING') && kUSER_EMAIL_WARNING == true) $class = 'warning';

				## fields[user][email]
				$Fieldset->addChild(Widget::label('Email Address', Widget::input('fields[user][email]', $fields['user']['email']), $class));

				if(defined('ERROR') && defined('kUSER_EMAIL_WARNING'))
					$Fieldset->addChild(Widget::warning($warnings[ERROR]));

				$User->addChild($Fieldset);

				$Form->addChild($User);

			/** END USER SETTINGS **/


			/**
			 *
			 * START FORM SUBMIT AREA
			 *
			**/

				$Form->addChild(new XMLElement('h2', 'Install Symphony'));
				$Form->addChild(new XMLElement('p', 'Make sure that you delete the <code>install.php</code> file after Symphony has installed successfully.'));

				$Submit = new XMLElement('div');
				$Submit->setAttribute('class', 'submit');

				### submit
				$Submit->addChild(Widget::input('submit', 'Install Symphony', NULL, 'submit'));

				### action[install]
				$Submit->addChild(Widget::input('action[install]', 'true', NULL, 'hidden'));

				$Form->addChild($Submit);
				$Contents->addChild($Form);

			/** END FORM SUBMIT AREA **/


			$Page->setTemplateVar('title', 'Install Symphony');
			$Page->setTemplateVar('tagline', 'Version ' . kVERSION);
		}

		function requirements(&$Page, &$Contents){

			$Contents->addChild(new XMLElement('h2', 'Outstanding Requirements'));
			$Contents->addChild(new XMLElement('p', 'Symphony needs the following requirements satisfied before installation can proceed.'));

			$messages = array();

			if(in_array(MISSING_PHP, $Page->missing))
				$messages[] = array('<abbr title="PHP: Hypertext Pre-processor">PHP</abbr> 4.3 or above',
							  		'Symphony needs a recent version of <abbr title="PHP: Hypertext Pre-processor">PHP</abbr>.');

			if(in_array(MISSING_MYSQL, $Page->missing))
				$messages[] = array('My<abbr title="Structured Query Language">SQL</abbr> 3.23 or above',
							  	'Symphony needs a recent version of My<abbr title="Structured Query Language">SQL</abbr>.');

			if(in_array(MISSING_ZLIB, $Page->missing))
				$messages[] = array('ZLib Compression Library',
							  		'Data retrieved from the Symphony support server is decompressed with the ZLib compression library.');

			if(in_array(MISSING_XSL, $Page->missing) || in_array(MISSING_XML, $Page->missing))
				$messages[] = array('<abbr title="eXtensible Stylesheet Language Transformation">XSLT</abbr> Processor',
							  		'Symphony needs an XSLT processor such as Lib<abbr title="eXtensible Stylesheet Language Transformation">XSLT</abbr> or Sablotron to build pages.');


			$Contents->addChild(Widget::definitionList($messages));

			$Page->setTemplateVar('title', 'Missing Requirements');
			$Page->setTemplateVar('tagline', 'Version ' . kVERSION);
		}

		function uptodate(&$Page, &$Contents){
			$Contents->addChild(new XMLElement('h2', 'Update Symphony'));
			$Contents->addChild(new XMLElement('p', 'You are already using the most recent version of Symphony. There is no need to run the installer, and can be safely deleted.'));

			$Page->setTemplateVar('title', 'Update Symphony');
			$Page->setTemplateVar('tagline', 'Version ' . kVERSION);
		}

		function incorrectVersion(&$Page, &$Contents){
			$Contents->addChild(new XMLElement('h2', 'Update Symphony'));
			$Contents->addChild(new XMLElement('p', 'You are not using the most recent version of Symphony. This update is only compatible with Symphony 1.6.02. You can still update to 1.6.02 using the internal update page.'));

			$Page->setTemplateVar('title', 'Update Symphony');
			$Page->setTemplateVar('tagline', 'Version ' . kVERSION);
		}

		function failure(&$Page, &$Contents){

			$Contents->addChild(new XMLElement('h2', 'Installation Failure'));
			$Contents->addChild(new XMLElement('p', 'An error occurred during installation. You can view you log <a href="install-log.txt">here</a> for more details.'));

			$Page->setTemplateVar('title', 'Installation Failure');
			$Page->setTemplateVar('tagline', 'Version ' . kVERSION);

		}

		function update(&$Page, &$Contents){
/*
		<form action="" method="post">
			<h2>Update Symphony</h2>
			<p>Symphony is ready to update from version 1.6.2 to version 1.6.3.</p>

			<div class="submit">
				<input name="action[update]" type="submit" value="Update Symphony" />
				<input name="action[update]" type="hidden" value="true" />
			</div>
		</form>
*/

			$Form = new XMLElement('form');
			$Form->setAttribute('action', 'install.php');
			$Form->setAttribute('method', 'post');

			$Form->addChild(new XMLElement('h2', 'Update Symphony'));
			$Form->addChild(new XMLElement('p', 'Symphony is ready to update from version '.kCURRENT_VERSION.' to version ' . kVERSION));

			$Submit = new XMLElement('div');
			$Submit->setAttribute('class', 'submit');

			### submit
			$Submit->addChild(Widget::input('submit', 'Update Symphony', NULL, 'submit'));

			### action[update]
			$Submit->addChild(Widget::input('action[update'.kCURRENT_BUILD.']', 'true', NULL, 'hidden'));

			$Form->addChild($Submit);
			$Contents->addChild($Form);

			$Page->setTemplateVar('title', 'Update Symphony');
			$Page->setTemplateVar('tagline', 'Version ' . kVERSION);
		}
	}

	$Log =& new SymphonyLog("install-log.txt");

	$Page =& new Page($Log);

	$Page->setHeader(kHEADER);
	$Page->setFooter(kFOOTER);

	$Contents =& new XMLElement('body');
	$Contents->addChild(new XMLElement('h1', '<!-- TITLE --> <em><!-- TAGLINE --></em>'));

	if(defined('__IS_UPDATE__') && __IS_UPDATE__)
		$Page->setPage((kCURRENT_BUILD < '1602' ? 'incorrectVersion' : 'update'));

	elseif(defined('__ALREADY_UP_TO_DATE__') && __ALREADY_UP_TO_DATE__)
		$Page->setPage('uptodate');

	else{
		$Page->setPage('index');
		Action::requirements($Page);
	}

	if(is_array($Page->missing) && !empty($Page->missing)) $Page->setPage('requirements');
	elseif(isset($_POST['action'])){

		$action = array_keys($_POST['action']);
		$action = $action[0];

		call_user_func_array(array('Action', $action), array(&$Page, $fields));
	}

	call_user_func_array(array('Display', $Page->getPage()), array(&$Page, &$Contents, $fields));

	$Page->setContent($Contents->generate(true, 2));

	header('Content-Type: text/html; charset=UTF-8');
	print $Page->display();
	exit;

?>