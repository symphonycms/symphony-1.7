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

	error_reporting(E_ALL ^ E_NOTICE);

	## We dont need to instanicate the entire Symphony engine so set boot to minimal
	define("__SYMPHONY_MINIMAL_BOOT__", true);

	##Initalize some variables
	$extras = null;
	$xml 	= null;

	##Include some parts of the engine
	require_once(dirname(__FILE__) . "/manifest/config.php");
	require_once(TOOLKIT . "/class.profiler.php");

	$profiler =& new Profiler;

	require_once(LIBRARY . "/boot/class.object.php");
	include_once(LIBRARY . "/core/class.service.php");
	require_once(LIBRARY . "/core/class.cacheable.php");
	require_once(LIBRARY . "/core/class.datasource.php");
	require_once(LIBRARY . "/core/class.event.php");
	require_once(LIBRARY . "/core/class.textformatter.php");
	require_once(LIBRARY . "/core/class.general.php");
	require_once(LIBRARY . "/core/class.lang.php");
	require_once(LIBRARY . "/core/class.utilities.php");

	##To prevent users that are logged in from getting maintenance pages, ensure the URL matches
	##the one speficied in the config file.
	$url_bits = parse_url(URL);

	if($_SERVER['HTTP_HOST'] != $url_bits['host'] && $_SERVER['HTTP_HOST'] != ($url_bits['host'] . ':' . $url_bits['port'])){
		##Clean up the query string
		$query = str_replace("page=" . $_REQUEST['page'], "", $_SERVER['QUERY_STRING']);
		$query = ltrim($query, "&");

		##Reconstruct the correct URL and redirect them there
		$destination = URL . "/" . $_REQUEST['page'] . "/" . ($query != "" ? "?$query" : "");
		$destination = rtrim($destination, "/") . "/";

		##Lets the browser know its a 301 page
		header("HTTP/1.1 301 Moved Permanently");
		General::redirect($destination);
		exit();
	}
	##

	require_once(LIBRARY . "/core/class.xsltprocess.php");
	require_once(LIBRARY . "/core/class.symphonylog.php");
	require_once(LIBRARY . "/core/class.mysql.php");
	require_once(LIBRARY . "/core/class.symdate.php");
	require_once(LIBRARY . "/core/class.configuration.php");
	require_once(LIBRARY . "/core/class.xmlelement.php");
	require_once(LIBRARY . "/core/class.gateway.php");
	require_once(TOOLKIT . "/class.xmlrepair.php");

	require_once(LIBRARY . "/core/class.manager.php");
	require_once(LIBRARY . "/core/class.eventmanager.php");
	require_once(LIBRARY . "/core/class.datasourcemanager.php");
	require_once(LIBRARY . "/core/class.textformattermanager.php");
	require_once(LIBRARY . "/core/class.campfiremanager.php");

	require_once(LIBRARY . "/class.site.php");

	$profiler->sample("Engine Overhead");

	$config =& new Configuration(true);
	$config->setArray($settings);

	##Establish connetion to database
	$dbDriver = $config->get("driver", "database");

	if (!class_exists($dbDriver)) {
		$dbDriver = "MySQL";
	}

	$db = new $dbDriver($config->get("database"));

	$current_page = (isset($_GET['page']) ? $_GET['page'] : NULL);

    ##Make sure the table encoding settings are right
    if($config->get("runtime_character_set_alter", "database")){
    	$db->setCharacterSet($config->get("character_encoding", "database"));
    	$db->setCharacterEncoding($config->get("character_set", "database"));
	}

	$Site = new Site($current_page, $db, $config, $profiler);

	## Determine if we have requested debug mode
	define('__IN_DEBUG_MODE__', ($Site->isLoggedIn() && isset($_GET['debug'])
												? true
												: false));

	$profiler->sample("Page Initialization", PROFILE_LAP);

	## Set a default content header
	$Site->addHeaderToPage("Content-Type", "text/html; charset=UTF-8");

	## We dont want to see error messages if in debug mode
	$Site->setVerbose((__IN_DEBUG_MODE__ ? false : true));

	#Render the page
	$Site->display();

	if(!__IN_DEBUG_MODE__) print $Site->getTransformed();
	else{

		$active = 'xml';

		$xml = General::sanitize($Site->getXML());

		$xsl = str_replace("\r\n", "\n", trim($Site->getXSL()));
		$xsl = General::sanitize($xsl);

		$output = General::sanitize($Site->getTransformed());

		$xml = ($xml != "" ? General::str2array($xml, false) : NULL);
		$xsl = ($xsl != "" ? General::str2array($xsl) : NULL);
		$output = ($output != "" ? General::str2array($output, false) : NULL);

		$page_name = $db->fetchVar("title", 0, "SELECT `title` FROM `tbl_pages` WHERE `handle` = '".$Site->_page."'");

		$page_id = $db->fetchVar("id", 0, "SELECT `id` FROM `tbl_pages` WHERE `handle` = '".$Site->_page."'");

		$type = ($_GET['line_numbers'] == 'false' ? 'ul' : 'ol');

$result =
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

	<head>
		<title>Symphony | Debug > '.$page_name.'</title>
		<link rel="stylesheet" type="text/css" media="screen" href="'.URL.'/symphony/assets/debug.css" />
		<script type="text/javascript" src="'.URL.'/symphony/assets/main.js"></script>
	</head>

	<body id="view">
		<h1><a href="'.URL.'/symphony/?page=/blueprint/pages/edit/&amp;id='.$page_id.'" title="Edit this page.">'.$page_name.'</a></h1>

		<ul>
			<li'.($active == "xml" ? ' id="active"' : '').'><a href="#xml"><acronym title="eXtensible Markup Language">XML</acronym></a></li>
			<li'.($active == "xslt" ? ' id="active"' : '').'><a href="#xslt"><acronym title="eXtensible Stylesheet Language Transformation">XSLT</acronym></a></li>
			<li'.($active == "output" ? ' id="active"' : '').'><a href="#output">Output</a></li>
		</ul>


		<'.$type.' id="xml">
';


			if(is_array($xml) && !empty($xml)){
				foreach($xml as $line){
					$result .= "			<li><code>$line</code></li>\n";
				}
			}

			$result .= '		</'.$type.'><'.$type.' id="xslt">';

			if(is_array($xsl) && !empty($xsl)){
				foreach($xsl as $line){
					$result .= "			<li><code>$line</code></li>\n";
				}
			}

			$result .= '		</'.$type.'><'.$type.' id="output">';


			if(is_array($output) && !empty($output)){
				foreach($output as $line){
					$result .= "			<li><code>$line</code></li>\n";
				}
			}

			$result .= '
		</'.$type.'>

		<div><a id="toggle-line-numbers" href="?debug'.($_GET['line_numbers'] == 'false' ? '' : '&amp;line_numbers=false').'">Line numbers?</a></div>

	</body>
</html>';

		print $result;

	}

	#Close the database connections
	$db->close();

	exit();

?>