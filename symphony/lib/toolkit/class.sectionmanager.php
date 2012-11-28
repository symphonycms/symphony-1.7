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

	Class SectionManager extends Object{

		var $_db;
		var $_parent;

		function __construct(&$parent){
			include_once(TOOLKIT . "/class.section.php");
			include_once(TOOLKIT . "/class.customfield.php");
			include_once(TOOLKIT . "/class.customfieldmanager.php");

			$this->_parent =& $parent;
			$this->_db =& $this->_parent->_db;
		}

		function &create(){
			$obj = new Section($this);
			return $obj;
		}

		function add($fields){

			if(!$this->_db->insert($fields, "tbl_section")) return false;
			$section_id = $this->_db->getInsertID();

			$this->_parent->updateMetadata("section", $section_id);
			$this->_parent->flush_cache(array("entries", "authors", "comments", "customfields", "sections"));
			return $section_id;
		}

		function edit($id, $fields){

			if(!$this->_db->update($fields, "tbl_section", "WHERE `id` = '$id'")) return false;

			$this->_parent->updateMetadata("section", $author_id);
			$this->_parent->flush_cache(array("entries", "authors", "comments", "customfields", "sections"));
			return true;
		}

		function delete($id){

			$section_id = $id;

			// 1. Fetch entry details
			$query = 'SELECT `id`, `sortorder` FROM tbl_sections WHERE `id` = \''.$section_id.'\'';
			$details = $this->_db->fetchRow(0, $query);

			$entries = $this->_db->fetchCol("entry_id", "SELECT `entry_id` FROM `tbl_entries2sections` WHERE `section_id` = '".$section_id."'");

			$customfields = $this->_db->fetchCol("id", "SELECT `id` FROM `tbl_customfields` WHERE `parent_section` = '".$section_id."'");

			$this->_db->delete("tbl_sections", "WHERE `id` = '".$section_id."'");
			$this->_db->delete("tbl_entries", "WHERE `id` IN ('".@implode("', '", $entries)."')");
			$this->_db->delete("tbl_entries2sections", "WHERE `section_id` = '".$section_id."'");
			$this->_db->delete("tbl_customfields", "WHERE `parent_section` = '".$section_id."'");
			$this->_db->delete("tbl_entries2customfields", "WHERE `entry_id` IN ('".@implode("', '", $entries)."')");
			$this->_db->delete("tbl_sections_column", "WHERE `section_id` = '".$section_id."'");

			// Section Meta Data
			$this->_db->delete("tbl_metadata", "WHERE `relation_id` = '".$section_id."' AND `class` = 'section'");

			// Custom Field Meta Data
			$this->_db->delete("tbl_metadata", "WHERE `relation_id` IN ('" . @implode("', '", $customfields) . "')  AND `class` = 'customfield'");

			// Entry Meta Data
			$this->_db->delete("tbl_metadata", "WHERE `relation_id` IN ('" . @implode("', '", $entries) . "')  AND `class` = 'entry'");

			// 4. Update the sort orders
			$this->_db->query("UPDATE tbl_sections SET `sortorder` = (`sortorder` - 1) WHERE `sortorder` > '".$details['sortorder']."'");

			return true;
		}
	}

?>