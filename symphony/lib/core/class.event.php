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

	define("kHIGH", 3);
	define("kNORMAL", 2);
	define("kLOW", 1);

	##Interface for page event objects
	Class Event Extends Object{

		var $_parent;
		var $_env;
		var $_db;

		function __construct($args){
			$this->_parent = $args['parent'];
			$this->_env = $args['env'];
			$this->_db = $this->_parent->_db;
		}

		## This function is required in order to edit it in the event editor page.
		## Do not overload this function if you are creating a custom event. It is only
		## used by the event editor
		function allowEditorToParse(){
			return false;
		}

		## This function is required in order to identify what type of event this is for
		## use in the event editor. It must remain intact. Do not overload this function into
		## custom events.
		function getType(){
			return NULL;
		}

		## Events have 3 priority levels. High, Normal and Low. Depending on what you set
		## the Event priority to, the order that your Events trigger will change. Event with
		## high priority will trigger first and so on. If you do not implement this function
		## in your Event, it will default to normal.
		function getPriority(){
			return kNORMAL;
		}

		##Static function
		function about(){
		}

		function load(){
		}

		function trigger(){
		}
	}

?>