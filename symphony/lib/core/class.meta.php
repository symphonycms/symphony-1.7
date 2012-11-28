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

	Class Meta{

		var $_exclude = array();
		var $_data = array();
		var $_data_indexed = array();
		var $_previous = -1;

		function Meta($db=null, $relation_id=null, $class=null){

			if($db){
				$this->_data = $db->fetchRow(0, "SELECT * FROM `tbl_metadata` WHERE `relation_id` = '$relation_id' AND `class` = '$class' LIMIT 1");
				$this->index();
			}

			return true;
		}

		function index(){

			$this->_data_indexed = array();

			foreach($this->_data as $key => $val){
				if($val != null)
					$this->_data_indexed[] = array('field' => $key, 'value' => $val);
			}
		}

		function set($name, $val, $index=false){
			$this->_data[$name] = $val;
			if($index) $this->index();
		}

		function fetch($field=null){

			if($field)
				return $this->_data[$field];

			else{
				$this->_previous++;

				while(in_array($this->_data_indexed[$this->_previous]['field'], $this->_exclude))
					$this->_previous++;

				if($this->_previous >= count($this->_data_indexed))
					return false;

				return $this->_data_indexed[$this->_previous];
			}

		}

		function exclude($field, $clear=false){

			if($clear) $this->_exclude = array();

			if(is_array($field))
				$this->_exclude = array_merge($this->_exclude, $field);
			else
				$this->_exclude[] = $field;

		}

	}


?>