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

	Class Service Extends Object{
	
		var $_parent;
		
		function __construct($args=array()){
			$this->_parent =& $args['parent'];
		}
		
		function __destruct(){
		}
		
		function update(){
		}
		
		function enable(){		
		}
		
		function disable(){
		}
		
		function uninstall(){
		}
		
		function install(){
		}
		
		function about(){	
		}
		
		function getSubscribedDelegates(){
			return NULL;
		}
	}

?>