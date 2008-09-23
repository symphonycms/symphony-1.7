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
	
	Class AuthorManager extends Object{
		
		var $_db;
		var $_parent;
		
		function __construct(&$parent){
			$this->_parent =& $parent;
			$this->_db =& $this->_parent->_db;
		}
		
		function &create(){	
			$obj = new Author($this);
			return $obj;
		}
		
		function add($fields){
			
			if(!$this->_db->insert($fields, "tbl_authors")) return false;
			$author_id = $this->_db->getInsertID();
						
			$this->_parent->updateMetadata("author", $author_id);
			$this->_parent->flush_cache(array("entries", "authors", "comments"));
			return $author_id;
		}

		function edit($id, $fields){
			
			if(!$this->_db->update($fields, "tbl_authors", "WHERE `id` = '$id'")) return false;

			$this->_parent->updateMetadata("author", $author_id);
			$this->_parent->flush_cache(array("entries", "authors", "comments"));
			return true;			
		}
		
		function delete($id){		

			$entries = $this->_db->fetchCol("id", "SELECT `id` FROM `tbl_entries` WHERE `author_id` = '".$id."'");  
			
			$this->_db->delete("tbl_authors", "WHERE `id` = '$id'");
			$this->_db->delete("tbl_entries", "WHERE `author_id` = '$id'");
			$this->_db->delete("tbl_entries2sections", "WHERE `entry_id` IN ('".@implode("', '", $entries)."')");
			$this->_db->delete("tbl_entries2customfields", "WHERE `entry_id` IN ('".@implode("', '", $entries)."')");
			$this->_db->delete("tbl_metadata", "WHERE `relation_id` IN ('".@implode("', '", $entries)."') AND `class` = 'entry'"); 			
				
			return true;
		}
		
		function fetch(){
		
	    	$sql = "SELECT tbl_authors.*, count(tbl_entries.id) as `entries` "
				     . "FROM tbl_authors "
				     . "LEFT JOIN `tbl_entries` ON `tbl_entries`.author_id = `tbl_authors`.id "
				     . "GROUP BY tbl_authors.id "
				     . "ORDER BY `id` DESC ";
					     
			$rec = $this->_db->fetch($sql);
						
			if(!is_array($rec) || empty($rec)) return NULL;
			
			$authors = array();

			foreach($rec as $row){
				$author = $this->create();
			
				foreach($row as $field => $val)
					$author->set($field, $val);
				
				$authors[] = $author;
			}
			
			return $authors;		
		}
		
		function fetchByID($id){
			
			$rec = $this->_db->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `id` = '$id' LIMIT 1");
			
			if(!is_array($rec) || empty($rec)) return NULL;
			
			$author = $this->create();
			
			foreach($rec as $field => $val)
				$author->set($field, $val);
				
			return $author;
			
		}
		
		function fetchByUsername($username){
			$rec = $this->_db->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `username` = '$username' LIMIT 1");
			
			if(!is_array($rec) || empty($rec)) return NULL;
			
			$author = $this->create();
			
			foreach($rec as $field => $val)
				$author->set($field, $val);
				
			return $author;		
		}
		
		function deactivateAuthToken($author_id){
			return $this->_db->query("UPDATE `tbl_authors` SET `auth_token_active` = 'no' WHERE `id` = '$author_id' LIMIT 1");
		}
		
		function activateAuthToken($author_id){
			return $this->_db->query("UPDATE `tbl_authors` SET `auth_token_active` = 'yes' WHERE `id` = '$author_id' LIMIT 1");
		}
	}

?>
