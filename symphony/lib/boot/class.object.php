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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

	Class Object{

	    function Object () {
	        $args = func_get_args();
	        call_user_func_array(array(&$this, '__construct'), $args);
	        @register_shutdown_function(array(&$this, '__destruct'), $args);
	    }

		//Abstract Constructor
		function __construct(){
		}

		//Abstract Destructor
		function __destruct(){
		}

	}

?>