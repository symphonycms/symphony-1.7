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

	Class Section extends Object{

		var $_fields;
		var $_customfields;
		var $_customFieldManager

		function __construct(&$parent){
			$this->_parent =& $parent;
			$this->_fields = $this->_customfields = array();
			$this->_customFieldManager =& new CustomFieldManager($this->_parent);
		}

		function set($field, $value){
			$this->_fields[$field] = $value;
		}

		function get($field){
			return $this->_fields[$field];
		}

		function addCustomField(){
			$this->_customfields[] =& new CustomField($this->_customFieldManager);
		}

		function commit(){
			$fields = $this->_fields;
			$retVal = NULL;

			if(isset($fields['id'])){
				$id = $fields['id'];
				unset($fields['id']);
				$retVal = $this->_parent->edit($id, $fields);

				if($retVal) $retVal = $id;

			}else{
				$retVal = $this->_parent->add($fields);
			}

			if(is_numeric($retVal) && $retVal !== false){
				for($ii = 0; $ii < count($this->_customfields); $ii++){
					$this->_customfields[$ii]->set('parent_section', $retVal);
					$this->_customfields[$ii]->commit();
				}
			}

		}
	}
?>