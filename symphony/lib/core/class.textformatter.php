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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");
	
	##Interface for page event objects
	Class TextFormatter Extends Object{
		
		var $_parent;
		var $_db;
		
		function __construct($args){
			$this->_parent = $args['parent'];
			$this->_db = $this->_parent->_db;
		}
		
		function about(){	
		}
				
		function run(){	
		}
		
	}
	
?>