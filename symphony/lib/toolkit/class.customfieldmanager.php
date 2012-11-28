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

	Class CustomFieldManager extends Object{

		var $_db;
		var $_parent;

		function __construct(&$parent){
			include_once(TOOLKIT . "/class.customfield.php");
			$this->_parent =& $parent;
			$this->_db =& $this->_parent->_db;
		}

		function &create(){
			$obj = new CustomField($this);
			return $obj;
		}

		function add($fields){

			$select_options = $fields['select_options'];
			unset($fields['select_options']);

			if(!$this->_db->insert($fields, "tbl_customfields")) return false;
			$field_id = $this->_db->getInsertID();

			if(isset($select_options) && ($fields['type'] == "select" || $fields['type'] == "multiselect"))
                $this->_db->insert(array("field_id" => $field_id, "values" => $select_options), "tbl_customfields_selectoptions");

			$this->_parent->updateMetadata("customfield", $field_id);
			$this->_parent->flush_cache(array("entries", "authors", "comments"));

			return $field_id;
		}

		function edit($id, $fields){

			$select_options = $fields['select_options'];
			unset($fields['select_options']);

			if(!$this->_db->update($fields, "tbl_customfields", "WHERE `id` = '$id'")) return false;

			if(isset($select_options) && ($fields['type'] == "select" || $fields['type'] == "multiselect")){
				$this->_db->delete("tbl_customfields_selectoptions", "WHERE `field_id` = '$id'");
                $this->_db->insert(array("field_id" => $id, "values" => $select_options), "tbl_customfields_selectoptions");
			}

			$this->_parent->updateMetadata("customfield", $id);
			$this->_parent->flush_cache(array("entries", "authors", "comments"));
			return true;
		}

		function delete($id){

			$query = "SELECT `id`, `sortorder` FROM tbl_customfields WHERE `id` = '$id' LIMIT 1";
			$details = $this->_db->fetchRow(0, $query);

			$this->_db->delete("tbl_customfields", "WHERE `id` = '$id'");
			$this->_db->delete("tbl_sections_visible_columns", "WHERE `field_id` = '$id'");
			$this->_db->delete("tbl_entries2customfields", "WHERE `field_id` = '$id'");
			$this->_db->delete("tbl_entries2customfields_list", "WHERE `field_id` = '$id'");
			$this->_db->delete("tbl_entries2customfields_upload", "WHERE `field_id` = '$id'");
			$this->_db->delete("tbl_customfields_selectoptions", "WHERE `field_id` = '$id'");
			$this->_db->delete("tbl_metadata", "WHERE `relation_id` = '$id' AND `class` = 'customfield'");

			$this->_db->query("UPDATE tbl_customfields SET `sortorder` = (`sortorder` - 1) WHERE `sortorder` > '".$details['sortorder']."'");

			return true;
		}
	}

?>