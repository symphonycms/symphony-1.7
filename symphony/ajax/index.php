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

	/*
	# This is the switching script for all AJAX related stuff in symphony.
	# The javascript makes a call to this file, specifying the 'action' and also
	# providing post data in the form of an array. The action corresponds
	# directly to the name of the scriptlet to use i.e ajax.ACTION.php
	#
	# A authentication details (username, password & serial) in the form of a session cookie
	# MUST be sent with the post data. This is how the AJAX authenticates.
	*/

	error_reporting(E_ALL);

	##We dont need to instanicate the entire Symphony engine so set boot to minimal
	define("__SYMPHONY_MINIMAL_BOOT__", true);

	##Initalize some variables
	$extras = null;
	$xml 	= null;

	##Include some parts of the engine
	require_once('../../manifest/config.php');
	require_once(LIBRARY . "/core/class.utilities.php");
	require_once(LIBRARY . "/boot/class.object.php");
	require_once(LIBRARY . "/core/class.general.php");
	require_once(LIBRARY . "/core/class.log.php");
	require_once(LIBRARY . "/core/class.mysql.php");
	require_once(LIBRARY . "/core/class.symdate.php");
	require_once(LIBRARY . "/core/class.configuration.php");
	require_once(LIBRARY . "/core/class.xmlelement.php");
	require_once(LIBRARY . "/core/class.filter.php");
	require_once(LIBRARY . "/core/class.author.php");

	Class ParentShell Extends Object{

		var $_db;
		var $_config;

		function __construct(&$db, &$config){
			$this->_db = $db;
			$this->_config = $config;
		}

	}

	$config =& new Configuration(true);
	$config->setArray($settings);

	##Establish connetion to database
	$dbDriver = $config->get("driver", "database");

	if (!class_exists($dbDriver)) {
		$dbDriver = "MySQL";
	}

	$db = new $dbDriver($config->get("database"));

	$Parent = new ParentShell($db, $config);

	function authenticateViaToken($token){
		global $db;

		$sql  = "SELECT *\n";
		$sql .= "FROM `tbl_authors`\n";
		$sql .= "WHERE SUBSTRING(MD5(CONCAT(`username`, `password`)), 1, 8) = '".addslashes($token)."'\n";
		$sql .= "AND `auth_token_active` = 'yes'\n";

		$row = $db->fetchRow(0, $sql);

		if(is_array($row) && !empty($row)) return $row['id'];

		return false;
	}

	function authenticate($username, $password, $already_md5=false){

		global $db;

		$sql  = "SELECT *\n";
		$sql .= "FROM `tbl_authors`\n";
		$sql .= "WHERE `username` = '".addslashes($username)."'\n";
		$sql .= "AND `password` = '".(!$already_md5 ? md5($password) : $password)."'\n";

		$row = $db->fetchRow(0, $sql);

		if(is_array($row) && !empty($row)) return $row['id'];

		return false;
	}

	##PARSE THE COOKIE
    $cookie = $_REQUEST['cookie'];

    $cookie = urldecode($cookie);

    $cookie = @explode(";", trim($cookie));

    foreach($cookie as $x){

        list($name, $value) = @explode("=", trim($x));

        $data[trim(@urldecode($name))] = trim($value);

    }

    ##HACK!!
    $data[__SYM_COOKIE_SAFE__] = str_replace("%3D", "=", $data[__SYM_COOKIE_SAFE__]);

    $auth = @unserialize(@base64_decode($data[__SYM_COOKIE_SAFE__]));

	$settings['auth'] = $auth;

	########

	if(isset($_REQUEST['token']) && $author_id = authenticateViaToken($_REQUEST['token'])):
		$Author = new Author($Parent, $author_id);

	elseif($author_id = authenticate($auth['username'], $auth['password'], true)):
		$Author = new Author($Parent, $author_id);

	else:
		$xml = new XMLElement('error', 'You do not have permission to access this page');

	endif;

	if(is_object($Author)):

		##Run requested script, returning an error if the action was not found
		if(@is_file(AJAX . "/ajax." . $_REQUEST['action'] . ".php")){
			$xml = new XMLElement($_REQUEST['action']);
			include_once(AJAX . "/ajax." . $_REQUEST['action'] . ".php");

		}else{

			$action_parts = preg_split('/\//', $_REQUEST['action'], -1, PREG_SPLIT_NO_EMPTY);

			$action_path = str_replace(end($action_parts).'/', '', $_REQUEST['action']);
			$action_name = rtrim(str_replace($action_path, '', $_REQUEST['action']), '/');

			$file_path = CAMPFIRE . $action_path . "/ajax/ajax." . $action_name . ".php";

			if(@is_file($file_path)){
				$xml = new XMLElement($action_name);
				include_once($file_path);

			}else
				$xml = new XMLElement("error", "Ajax action '".$_REQUEST['action']."' does not exist.");
		}

	endif;

	#Close the database connections
	@$db->close();

	#Record the render time
	$rendertime = precision_timer("stop", STARTTIME);

	##XML is returned, make sure the browser knows it
	header ("Content-Type: text/xml");

	$xml->setIncludeHeader(true);
	print $xml->generate(true);

	exit();

?>