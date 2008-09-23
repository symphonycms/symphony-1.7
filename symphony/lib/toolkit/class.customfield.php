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
	
	Class CustomField extends Object{
		
		var $_fields;
		var $_parent;
				
		function __construct(&$parent){
			$this->_parent =& $parent;
			$this->_fields = array();
		}
		
		function set($field, $value){
			$this->_fields[$field] = $value;
		}

		function get($field){
			return $this->_fields[$field];
		}
		
		function commit(){
			$fields = $this->_fields;	
				
			if(isset($fields['id'])){
				$id = $fields['id'];
				unset($fields['id']);
				return $this->_parent->edit($id, $fields);
						
			}else{
				return $this->_parent->add($fields);	
			}		
			
		}

	}

?>