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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Fatal Error</h2><p>You cannot directly access this file</p>");

	##Interface for page filter objects
	Class Filter Extends Object{

		var $_global_settings;
		var $_parent;
		var $_result;
		var $_type;
		var $_page;
		var $_db;
		var $_total_pages;
		var $_total_records;

		function __construct($args){
			$this->_result = array();
			$this->_global_settings = $args['global_settings'];
			$this->_type = ($args['type'] ? $args['type'] : "all");
			$this->_page = $args['page'];
			$this->_limit = $args['limit'];
			$this->_db = $args['db'];
		}

		function process(){
		}

		function fetch(){
		}

		function getTotalPages(){
			return $this->_total_pages;
		}

		function getTotalRecords(){
			return $this->_total_records;
		}

	}

?>