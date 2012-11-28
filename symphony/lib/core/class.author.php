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

	Class Author extends Object{

		var $_fields;
		var $_parent;
		var $_db;
		var $_accessSections;

		function __construct(&$parent, $author_id=NULL){
			$this->_parent =& $parent;
			$this->_db =& $parent->_db;
			$this->_fields = array();
			$this->_accessSections = NULL;

			if($author_id) $this->loadAuthor($author_id);
		}

		function loadAuthor($id){
			if(!$row = $this->_db->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `id` = '$id' LIMIT 1")) return false;

			foreach($row as $key => $val)
				$this->set($key, $val);

		}

		function canAccessSection($section_id){
			if(!$id = $this->get('id')) return false;

			if(intval($this->get('superuser')) == 1) return true;

			$sections = $this->get('allow_sections');

			if($this->_accessSections == NULL){
				$sections = preg_split('/,/', $sections, -1, PREG_SPLIT_NO_EMPTY);
				$this->_accessSections = $sections;
			}

			if(in_array($section_id, $this->_accessSections)) return true;

			return false;
		}

		function set($field, $value){
			$this->_fields[$field] = $value;
		}

		function get($field){
			if(!isset($this->_fields[$field]) || $this->_fields[$field] == '') return NULL;
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