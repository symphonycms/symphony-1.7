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

	Class EntryManager extends Object{

		var $_db;
		var $_TFM;

		function __construct(&$parent){
			$this->_parent =& $parent;
			$this->_db =& $this->_parent->_db;
			$this->_TFM = new TextformatterManager(array('parent' => &$this->_parent));
		}


		function __resolveFieldNames($sid, $fields){

			$updated = array();

			foreach($fields as $handle => $val){
				$sql = "SELECT `id`
						FROM `tbl_customfields`
						WHERE `handle` = '".$handle."' AND `parent_section` = '$sid'
						LIMIT 1";

				if($id = $this->_db->fetchVar('id', 0, $sql)){
					$updated[$id] = $val;
				}
			}

			return $updated;

		}

		function __findDefaultValues($sid, $fields, $mode="real"){
			$schema = $this->fetchEntryFieldSchema($sid, array("checkbox", "select", "multiselect", "foreign"));

			foreach($schema as $s){

				if($s['type'] == 'checkbox'){

					if($mode == 'actual')
						$value = ($s['default_state'] == "checked" ? 'yes' : 'no');

					else
						$value = 'no';

				}elseif($s['type'] == 'multiselect' || $s['type'] == 'foreign'){

					$value = NULL;

				}else{

					$options = @explode(',', $s['values']);
					$value = $options[0];
				}

				if(!array_key_exists($s['id'], $fields)) $fields[$s['id']] = $value;

				if($s['type'] == 'checkbox'){
					if($fields[$s['id']] == 'on') $fields[$s['id']] = "yes";
					elseif($fields[$s['id']] == 'off') $fields[$s['id']] = "no";
				}
			}


			return $fields;
		}

		function __applyFormattingToString($formatter, $string){

			if($formatter = $this->_TFM->create($formatter))
				return $formatter->run($string);

			return $string;

		}

		function __fetchPrimaryField($sid){
			$row = $this->_db->fetchRow(0, "SELECT t1.primary_field, t2.name
								  FROM tbl_sections as t1
								  LEFT JOIN `tbl_customfields` AS t2 ON t1.primary_field = t2.id
								  WHERE t1.id = '" . $sid . "' LIMIT 1");

			return array($row['primary_field'], $row['title']);
		}

		function __fetchSection($entry_id){
			return $this->_db->fetchVar('section_id', 0, "SELECT `section_id` FROM `tbl_entries2sections` WHERE `entry_id` = '$entry_id' LIMIT 1");
		}

		function repairEntryLocalPublishDates(){
			$date = $this->_parent->getDateObj();
			$date->setFormat("Y-m-d H:i:s");

			$sql = "SELECT `id`, `publish_date_gmt`, `publish_date` FROM `tbl_entries`";
			$entries = $this->_db->fetch($sql);

			if(!is_array($entries) || empty($entries)) return true;

			foreach($entries as $row){
				$publish_date = $date->get(true, true, strtotime($row['publish_date_gmt']));

				$this->_db->query("UPDATE `tbl_entries`
									SET `publish_date` = '$publish_date'
									WHERE `id` = '".$row['id']."' LIMIT 1");

			}

			return true;
		}

		function updateEntryPublishDate($entry_id, $local_timestamp){

			$date = $this->_parent->getDateObj();
			$date->setFormat("Y-m-d H:i:s");

			$date->set($local_timestamp);

			$publish_date 	= $date->get(true, true);
			$publish_date_gmt = $date->get(false, true);

			$this->_db->query("UPDATE `tbl_entries` SET `publish_date` = '$publish_date', `publish_date_gmt` = '$publish_date_gmt' WHERE `id` = '$entry_id' LIMIT 1");
		}

		function updateField($field_id, $entry_id, $value_raw, $formatter=NULL, $create_if_nonexistant=true, $handle=NULL){

			$sql = "SELECT `type` FROM `tbl_customfields` WHERE `id` = '$field_id' LIMIT 1";
			$field_type = $this->_db->fetchVar('type', 0, $sql);

			$existing_field = $this->_db->fetchRow(0, "SELECT * FROM `tbl_entries2customfields` WHERE `field_id` = '$field_id' AND `entry_id` = '$entry_id' LIMIT 1");

			if(!$create_if_nonexistant) return false;

			if($field_type == 'upload'){

				$sql = "SELECT * FROM `tbl_customfields` WHERE `id` = '$field_id' LIMIT 1";

				$field = $this->_db->fetchRow(0, $sql);
				$field['destination_folder'] = trim($field['destination_folder'], '/');
				$field['destination_folder'] = $field['destination_folder'] . "/";

				$value_raw['deleted_files'] = preg_split('/,/', $value_raw['deleted_files'], -1, PREG_SPLIT_NO_EMPTY);
				$value_raw['deleted_files'] = array_map("trim", $value_raw['deleted_files']);

				if(is_array($value_raw['deleted_files']) && !empty($value_raw['deleted_files'])){
					foreach($value_raw['deleted_files'] as $file){

						if(!@is_file(DOCROOT . $file) || General::deleteFile(DOCROOT . $file))
							$this->_db->query("DELETE FROM `tbl_entries2customfields_upload` WHERE `file` = '$file' AND `entry_id` = '$entry_id' AND `field_id` = '$field_id'");

						else
							$this->_parent->log->pushToLog("Could not delete file '".DOCROOT . $file."' from entry. Check permissions.", SYM_LOG_ERROR, true);

					}
				}

				if(is_array($value_raw['files']) && !empty($value_raw['files'])){
					foreach($value_raw['files'] as $file){

	                	if($file['error'] == 0 && $file['size'] != 0){

		                	$filepath = "/" . $field['destination_folder'] . $file['name'];

							$retVal = General::uploadFile(DOCROOT . "/" . $field['destination_folder'], $file['name'], $file['tmp_name'], $this->_parent->getConfigVar("write_mode", "file"));

		                	if(!$retVal) return false;

		                	$array = array();
		                	$array['entry_id'] = $entry_id;
		                	$array['field_id'] = $field_id;
		                	$array['file'] = $filepath;
		                	$array['type'] = $file['type'];
		                	$array['size'] = $file['size'];

		                	$this->_db->insert($array, 'tbl_entries2customfields_upload');
		                }
					}
				}

				if(is_array($existing_field) && !empty($existing_field))
					return true;

				$field = array();
				$field['value'] = NULL;
				$field['value_raw'] = NULL;
				$field['entry_id'] = $entry_id;
				$field['field_id'] = $field_id;
				$field['handle'] = NULL;

				return $this->_db->insert($field, 'tbl_entries2customfields');

			}elseif($field_type == 'foreign'){

				$this->_db->query("DELETE FROM `tbl_entries2customfields_list` WHERE `entry_id` = '$entry_id' AND `field_id` = '$field_id'");

				$field = array();

				if(is_array($value_raw) && !empty($value_raw)){

					$field['value'] = NULL;
					$field['value_raw'] = NULL;

					foreach($value_raw as $v){

						$item = array();
						$item['value'] = $v;
						$item['value_raw'] = General::sanitize($v);
						$item['entry_id'] = $entry_id;
						$item['field_id'] = $field_id;

						$item['handle'] = $v;

						$this->_db->insert($item, 'tbl_entries2customfields_list');

					}
				}else{

					$field['value'] = $value_raw;
					$field['value_raw'] = $value_raw;

				}

				$field['handle'] = NULL;
				$field['entry_id'] = $entry_id;
				$field['field_id'] = $field_id;

				if(is_array($existing_field) && !empty($existing_field)){

					$sql = "UPDATE `tbl_entries2customfields`
							SET `value_raw` = '". mysql_escape_string($field['value_raw'])."',
								`value` = '". mysql_escape_string($field['value'])."'
							WHERE `id` = '".$existing_field['id']."' LIMIT 1";

					return $this->_db->query($sql);

				}

				return $this->_db->insert($field, 'tbl_entries2customfields');

			}elseif($field_type == 'multiselect'){

				$this->_db->query("DELETE FROM `tbl_entries2customfields_list` WHERE `entry_id` = '$entry_id' AND `field_id` = '$field_id'");

				if(is_array($value_raw) && !empty($value_raw)){

					foreach($value_raw as $item){

						$field = array();

						$field['value'] = $this->__applyFormattingToString($formatter, $item);
						$field['value_raw'] = General::sanitize($item);
						$field['entry_id'] = $entry_id;
						$field['field_id'] = $field_id;

						$field['handle'] = Lang::createHandle($item, $this->_parent->getConfigVar('handle_length', 'admin'));

						$this->_db->insert($field, 'tbl_entries2customfields_list');

					}
				}

				if(is_array($existing_field) && !empty($existing_field))
					return true;

				$field = array();
				$field['value'] = NULL;
				$field['value_raw'] = NULL;
				$field['entry_id'] = $entry_id;
				$field['field_id'] = $field_id;
				$field['handle'] = NULL;

				return $this->_db->insert($field, 'tbl_entries2customfields');

			}elseif($field_type == 'list'){

				$list_items = preg_split('/,/', $value_raw, -1, PREG_SPLIT_NO_EMPTY);
				$list_items = array_map("trim", $list_items);

				$this->_db->query("DELETE FROM `tbl_entries2customfields_list` WHERE `entry_id` = '$entry_id' AND `field_id` = '$field_id'");

				if(is_array($list_items) && !empty($list_items)){

					foreach($list_items as $item){

						$field = array();

						$field['value'] = $this->__applyFormattingToString($formatter, $item);

						$field['value_raw'] = General::sanitize($item);
						$field['entry_id'] = $entry_id;
						$field['field_id'] = $field_id;

						$field['handle'] = Lang::createHandle($item, $this->_parent->getConfigVar('handle_length', 'admin'));

						$this->_db->insert($field, 'tbl_entries2customfields_list');

					}
				}

				$field = array();
				$field['value'] = trim($this->__applyFormattingToString($formatter, $value_raw));

				$field['value_raw'] = General::sanitize($value_raw);

				if(is_array($existing_field) && !empty($existing_field)){

					$sql = "UPDATE `tbl_entries2customfields`
							SET `value_raw` = '". mysql_escape_string($field['value_raw'])."',
								`value` = '". mysql_escape_string($field['value'])."'
								".($handle ? ", `handle` = '$handle' " : "")."
							WHERE `field_id` = '$field_id' AND `entry_id` = '$entry_id' LIMIT 1";

					return $this->_db->query($sql);

				}

				$field['entry_id'] = $entry_id;
				$field['field_id'] = $field_id;
				$field['handle'] = NULL;

				return $this->_db->insert($field, 'tbl_entries2customfields');

			}else{

				$value_raw = trim($value_raw);

				if($field_type == 'checkbox' && !in_array(strtolower($value_raw), array('yes', 'no'))){
					if($value_raw == 'on') $value_raw = 'yes';
					else $value_raw = 'no';
				}

				$field = array();
				$field['value'] = $this->__applyFormattingToString($formatter, $value_raw);
				$field['value_raw'] = General::sanitize($value_raw);

				if(is_array($existing_field) && !empty($existing_field)){

					$sql = "UPDATE `tbl_entries2customfields`
							SET `value_raw` = '". mysql_escape_string($field['value_raw'])."',
								`value` = '". mysql_escape_string($field['value'])."'
								".($handle ? ", `handle` = '$handle' " : "")."
							WHERE `field_id` = '$field_id' AND `entry_id` = '$entry_id' LIMIT 1";

					return $this->_db->query($sql);

				}

				$field['entry_id'] = $entry_id;
				$field['field_id'] = $field_id;

				if($handle) $field['handle'] = $handle;

				return $this->_db->insert($field, 'tbl_entries2customfields');

			}

		}

		function validateFieldsXSLT($sid, $fields, $entry_id=NULL){

			$errors = $this->validateFields($sid, $fields);

			if(is_array($errors) && !empty($errors))
				return $errors;

			if(!defined('__SYM_ENTRY_VALIDATION_ERROR__')){

				$fields = $this->__resolveFieldNames($sid, $fields);

				if($entry_id) $formatter = $this->fetchEntryFormatter($entry_id);
				else $formatter = $this->_parent->getAuthorTextFormatter();

				##Validate the fields
				$fieldSchema = $this->fetchEntryFieldSchema($sid);

				$xsltProc =& new XsltProcess;

				foreach($fieldSchema as $f){

					$string = trim($fields[$f['id']]);

					if($formatter)
						$string = $this->__applyFormattingToString($formatter, $string);

					## Perform XSLT Validation as well
					if(!General::validateXML($string, $errors, false, $xsltProc)){
						define("__SYM_ENTRY_FIELD_XSLT_ERROR__", $f['name']);
						return false;
					}

				}
			}

			return NULL;

		}

		function validateFields($sid, $fields){

			$errors = array();

			$fields = $this->__resolveFieldNames($sid, $fields);

			$row = $this->_db->fetchRow(0, "SELECT t1.primary_field, t2.name
								  FROM tbl_sections as t1
								  LEFT JOIN `tbl_customfields` AS t2 ON t1.primary_field = t2.id
								  WHERE t1.id = '" . $sid . "' LIMIT 1");

			$primary_field_id = $row['primary_field'];
			$primary_field_name = $row['title'];

			$required = $this->fetchEntryRequiredFields($sid);

			##Make sure required fields are filled
			for($i = 0; $i < count($required); $i++) {
				if(trim($fields[$required[$i]]) == "") {
					$errors[$required[$i]] = true;
				}
			}

			##Make sure the primary field is also filled
			if(trim($fields[$primary_field_id]) == "") {
				$errors[$primary_field_id] = true;
			}

			if(!empty($errors)) return $errors;

			##Validate the fields
			$fieldSchema = $this->fetchEntryFieldSchema($sid);

			foreach($fieldSchema as $f){

				$string = trim($fields[$f['id']]);

				if($string != "" && $f['validator'] != NULL && !defined('__SYM_ENTRY_VALIDATION_ERROR__')){

					if ($f['validator'] == 'custom'){
						$rule = $f['validation_rule'];

					}elseif($f['validator'] != NULL){
						include(TOOLKIT . "/util.validators.php");
						$rule = $validators[$f['validator']][1];
					}

					if($f['type'] == 'list'){
						$string = preg_split('/,/', $string, -1, PREG_SPLIT_NO_EMPTY);
						$string = array_map("trim", $string);
					}

					if(!General::validateString($string, $rule)){
						define("__SYM_ENTRY_VALIDATION_ERROR__", $f['name']);
						return false;
					}

				}
			}

			return NULL;

		}

		function fetchEntryHandleFromEntryID($entry_id){

			$section_id = $this->_db->fetchVar('section_id', 0, "SELECT `section_id` FROM `tbl_entries2sections` WHERE `entry_id` = '$entry_id' LIMIT 1");

			if($section_id == '') return null;

			$field_id = $this->fetchSectionPrimaryFieldID($section_id);

			return $this->_db->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_entries2customfields` WHERE `field_id` = '$field_id' AND `entry_id` = '$entry_id' LIMIT 1");

		}

		function entryFindLinkingEntries($entry_id){

			$entry_handle = $this->fetchEntryHandleFromEntryID($entry_id);

			$sql = "SELECT DISTINCT `entry_id` FROM `tbl_entries2customfields` WHERE `value_raw` = '$entry_handle' AND `entry_id` != '$entry_id'";

			$entry_list_1 = $this->_db->fetchCol('entry_id', $sql);

			$sql = "SELECT DISTINCT `entry_id` FROM `tbl_entries2customfields_list` WHERE `handle` = '$entry_handle' AND `entry_id` != '$entry_id'";

			$entry_list_2 = $this->_db->fetchCol('entry_id', $sql);

			if(!is_array($entry_list_1)) $entry_list_1 = array();
			if(!is_array($entry_list_2)) $entry_list_2 = array();

			$entry_list = @array_merge($entry_list_1, $entry_list_2);
			$entry_list = @array_unique($entry_list);

			return $entry_list;

		}

		function add($sid, $fields, $publish_timestamp=NULL, $find_defaults_mode='real'){

			list($primary_field_id, $primary_field_handle) = $this->__fetchPrimaryField($sid);

			##Do some processing to ensure all the text is in the correct format
			$date = $this->_parent->getDateObj();
			$date->setFormat("Y-m-d H:i:s");

			##User has specified the time, grab it as GMT
			if($fields['time'] == "Automatic" || $fields['time'] == "")
				$fields['time'] = date("h:ia", $date->get(true, false));

			if(isset($fields['publish_date']) && isset($fields['time']))
				$date->set(strtotime($fields['time'] . " " . $fields['publish_date']));

			elseif($publish_timestamp != NULL)
				$date->set($publish_timestamp);

			$data['author_id'] = $this->_parent->getAuthorID();
			$data['publish_date'] 	= $date->get(true, true);
			$data['publish_date_gmt'] = $date->get(false, true);
			$data['formatter'] = $this->_parent->getAuthorTextFormatter();

			unset($fields['time']);
			unset($fields['publish_date']);

			$this->_db->insert($data, 'tbl_entries');
			$entry_id = $this->_db->getInsertID();

			$this->_db->insert(array('entry_id' => $entry_id, 'section_id' => $sid), 'tbl_entries2sections');

			$fields = $this->__resolveFieldNames($sid, $fields);
			$fields = $this->__findDefaultValues($sid, $fields, $find_defaults_mode);

			foreach($fields as $field_id => $val){

				$handle = NULL;

				if(!is_array($val)){
					$handle = Lang::createHandle($val, $this->_parent->getConfigVar('handle_length', 'admin'));

					if($field_id == $primary_field_id){

						##Duplicate
						if($count = $this->_db->fetchVar("count", 0, "SELECT count(*) as `count` FROM `tbl_entries2customfields` WHERE `field_id` = '$primary_field_id' AND `handle` LIKE '" . $handle . "%' LIMIT 1")){
							$handle .= '-' . $count;
						}
					}
				}

				$this->updateField($field_id, $entry_id, $val, $data['formatter'], true, $handle);

			}

			$this->_parent->updateMetadata("entry", $entry_id);

			return $entry_id;
		}

		function edit($id, $fields, $publish_timestamp=NULL, $find_defaults_mode='real', $allow_primary_field_handle_to_change=false){

			$sid = $this->__fetchSection($id);

			list($primary_field_id, $primary_field_handle) = $this->__fetchPrimaryField($sid);
			$original_formatter = $this->fetchEntryFormatter($id);

			if($publish_timestamp)
				$this->updateEntryPublishDate($id, $publish_timestamp);

			$fields = $this->__resolveFieldNames($sid, $fields);
			$fields = $this->__findDefaultValues($sid, $fields, $find_defaults_mode);

			foreach($fields as $field_id => $val){

				$handle = NULL;

				if(!is_array($val)){

					if(!$allow_primary_field_handle_to_change && $field_id == $primary_field_id){
						$handle = NULL;

					}else{

						$handle = Lang::createHandle($val, $this->_parent->getConfigVar('handle_length', 'admin'));

						if($field_id == $primary_field_id){
							##Duplicate
							$count = $this->_db->fetchVar("count", 0, "SELECT count(*) as `count`
																	   FROM `tbl_entries2customfields`
																	   WHERE `field_id` = '$primary_field_id'
																	   AND `entry_id` < '$id'
																	   AND `handle` LIKE '" . $handle . "%' LIMIT 1");
							if($count > 0)
								$handle .= '-' . ($count+1);
						}

					}
				}

				$this->updateField($field_id, $id, $val, $original_formatter, true, $handle);

			}

			$this->_parent->updateMetadata("entry", $id, false);

			return true;
		}

		function delete($id){

			if(!is_array($id)) $id = array($id);

			$this->_db->delete("tbl_entries","WHERE `id` IN('".@implode("','",$id)."')");
            $this->_db->delete("tbl_entries2customfields","WHERE `entry_id` IN('".@implode("','",$id)."')");
            $this->_db->delete("tbl_entries2customfields_list","WHERE `entry_id` IN('".@implode("','",$id)."')");
            $this->_db->delete("tbl_entries2customfields_upload","WHERE `entry_id` IN('".@implode("','",$id)."')");
            $this->_db->delete("tbl_entries2sections","WHERE `entry_id` IN('".@implode("','",$id)."')");
            $this->_db->delete("tbl_metadata", "WHERE `relation_id` IN('".@implode("','",$id)."') AND `class` = 'entry'");

            $comments = $this->_db->fetchCol('id', "SELECT `id` FROM tbl_comments WHERE entry_id IN('".@implode("','",$id)."')");

         	$this->_db->delete("tbl_comments", "WHERE `entry_id` IN ('".@implode("','",$id)."')");
         	$this->_db->delete("tbl_metadata", "WHERE `relation_id` IN('".@implode("','",$comments)."') AND `class` = 'comment'");

			return true;
		}

		function fetchEntriesByID($id, $organise_by_id=false, $organise_by_handle=false, $pad_array=false, $order="DESC"){

			if(!is_array($id)) $id = array($id);

			$sql = "SELECT DISTINCT e.id,"
				 . "       e.author_id,"
				 . "       e.valid_xml,"
				 . "       e.formatter,"
				 . "       a.firstname as `author_firstname`,"
				 . "       a.lastname as `author_lastname`,"
				 . "       e.publish_date_gmt,"
				 . "	   s.primary_field,"
				 . "       s.id as `section_id`,"
				 . "       UNIX_TIMESTAMP(e.publish_date_gmt) as `timestamp_gmt`,"
				 . "       UNIX_TIMESTAMP(e.publish_date) as `timestamp_local` "
				 . "FROM tbl_entries as `e` "
				 . "LEFT JOIN tbl_authors as `a` ON e.author_id = a.id "
				 . "LEFT JOIN tbl_entries2sections ON e.id = tbl_entries2sections.entry_id "
				 . "LEFT JOIN tbl_sections as `s` ON tbl_entries2sections.section_id = s.id "
				 . "WHERE e.id IN ('".@implode("', '", $id)."') "
				 . "GROUP BY e.id "
				 . "ORDER BY e.publish_date_gmt $order, e.id $order ";
				 //. "LIMIT 1";

			$entries = $this->_db->fetch($sql);

			$result = array();

			if(is_array($entries) && !empty($entries)){
				foreach($entries as $entry){
					$entry['fields'] = $this->fetchEntryFields($entry['id'], $organise_by_id, $organise_by_handle);

					$entry['linked_entries'] = $this->entryFindLinkingEntries($entry['id']);

					if($organise_by_handle)
						$entry['primary_field'] = $this->fetchSectionPrimaryFieldHandle($entry['section_id']);

					$result[] = $entry;
				}
			}else
				return false;

			if(count($result) == 1 && !$pad_array) return $result[0];

			return $result;
		}

		function fetchSectionPrimaryFieldHandle($section){
			$id = $this->fetchSectionPrimaryFieldID($section);
			$sql = "SELECT `handle` FROM `tbl_customfields` WHERE `id` = '$id'";
			return $this->_db->fetchVar('handle', 0, $sql);
		}

		function fetchSectionPrimaryFieldID($section){
			$sql = "SELECT `primary_field` FROM `tbl_sections` WHERE `id` = '$section'";
			return $this->_db->fetchVar('primary_field', 0, $sql);
		}

		function fetchSectionIDFromHandle($handle){
			##Ensure the handle is formatted correctly
			$handle = Lang::createHandle($handle);

			return $this->_db->fetchVar('id', 0, "SELECT `id` FROM `tbl_sections` WHERE `handle` = '".$handle."' LIMIT 1");
		}

		function fetchEntryFormatter($id){
			return $this->_db->fetchVar("formatter", 0, "SELECT `formatter` FROM `tbl_entries` WHERE `id` = '".$id."' LIMIT 1");
		}

		function fetchEntryIDFromPrimaryFieldHandle($section, $handle){

			$primary_field = $this->fetchSectionPrimaryFieldID($section);

			if(empty($handle))
				$sql = "SELECT `entry_id` FROM `tbl_entries2customfields` WHERE `field_id` = '$primary_field'";

			elseif(is_array($handle))
				$sql = "SELECT `entry_id` FROM `tbl_entries2customfields`
						WHERE `field_id` = '$primary_field'
						AND (`handle` IN ('".@implode("', '", $handle)."') OR `handle` IN ('".@implode("', '", array_map('urlencode', $handle))."'))
						ORDER BY `entry_id` DESC";

			else
				$sql = "SELECT `entry_id` FROM `tbl_entries2customfields` WHERE `field_id` = '$primary_field' AND (`handle` = '$handle' OR `handle` = '".urlencode($handle)."') LIMIT 1";

			return $this->_db->fetchCol('entry_id', $sql);
		}

		function fetchEntryRequiredFields($section, $displayNames=false){

			$sql = "SELECT " . ($displayNames ? 'name' : 'id') . "
					FROM `tbl_customfields`
					WHERE `parent_section` = '$section' AND `required` = 'yes'
					ORDER BY `sortorder` ASC";

		    return $this->_db->fetchCol(($displayNames ? 'name' : 'id'), $sql);

		}

		function fetchEntryFieldSchema($section, $types=NULL, $handle=NULL){

			if($types != NULL && !is_array($types))
				$types = array($types);

			if($handle != NULL && !is_array($handle))
				$handle = array($handle);

			$sql = "SELECT t1.*, t2.values
					FROM `tbl_customfields` as t1
					LEFT JOIN `tbl_customfields_selectoptions` as t2 ON t1.id = t2.field_id
					WHERE `parent_section` = '$section'
					" . ($types ? "AND t1.type IN ('".implode("', '", $types)."')" : "") . "
					" . ($handle ? "AND t1.handle IN ('".implode("', '", $handle)."')" : "") . "
					ORDER BY `sortorder` ASC";

		    return $this->_db->fetch($sql);

		}

		function fetchEntryFields($id, $organise_by_id=false, $organise_by_handle=false){

			$fields = array();

			$rows = $this->_db->fetch(
				"SELECT t1.*, t2.name, t2.handle as `field_handle`, t2.description, t2.type, t2.format, t2.foreign_section, t2.foreign_select_multiple
				FROM `tbl_entries2customfields` as t1
				LEFT JOIN `tbl_customfields` as t2 ON t1.field_id = t2.id
				WHERE t1.entry_id = " . $id ." AND t2.id > 0
				ORDER BY t2.sortorder ASC"
			);

			if(!is_array($rows) || empty($rows)) return $fields;

			$section_id = $this->_db->fetchVar('section_id', 0, "SELECT `section_id` FROM `tbl_entries2sections` WHERE `entry_id` = '$id' LIMIT 1");

			$schema = $this->fetchEntryFieldSchema($section_id, array('checkbox'));

			if(is_array($schema) && !empty($schema)){
				$tmp = array();
				for($ii = 0; $ii < count($rows); $ii++){
					$tmp[$rows[$ii]['field_id']] = $rows[$ii];
				}

				foreach($schema as $s){
					if(!isset($tmp[$s['id']])){

						$rows[] = array
						       (
						           'entry_id' => $id,
						           'field_id' => $s['id'],
					               'handle' => 'no',
					               'value' => '<p>no</p>',
					               'value_raw' => 'no',
						           'name' => $s['name'],
						           'field_handle' => $s['handle'],
						           'description' => $s['description'],
						           'type' => $s['type'],
						           'format' => 1
						       );
					}
				}
			}

			for($ii = 0; $ii < count($rows); $ii++){

				if($rows[$ii]['type'] == 'multiselect' || $rows[$ii]['type'] == 'foreign' || $rows[$ii]['type'] == 'upload' || trim($rows[$ii]['value_raw']) != ''):

					if($rows[$ii]['type'] == 'list' || $rows[$ii]['type'] == 'multiselect' || ($rows[$ii]['foreign_select_multiple'] == 'yes' && $rows[$ii]['type'] == 'foreign')){

						$sql = "SELECT value, value_raw FROM `tbl_entries2customfields_list` WHERE `entry_id` = '$id' AND `field_id` = '".$rows[$ii]['field_id']."' ORDER BY `id` ASC";

						$rows[$ii]['value'] = $this->_db->fetchCol('value', $sql);
						$rows[$ii]['value_raw'] = $this->_db->fetchCol('value_raw', $sql);

						$rows[$ii]['value'] = array_map("trim", $rows[$ii]['value']);

					}elseif($rows[$ii]['type'] == 'upload'){
						$sql = "SELECT `file`, `type`, `size` FROM `tbl_entries2customfields_upload` WHERE `entry_id` = '$id' AND `field_id` = '".$rows[$ii]['field_id']."' ORDER BY `id` ASC";
						$rec = $this->_db->fetch($sql);

						$files = array();

						if(is_array($rec) && !empty($rec)){
							foreach($rec as $r){

								$files[] = array("path" => trim($r['file']),
											   "type" => $r['type'],
											   "size" => $r['size'],
											);

							}
						}

						$rows[$ii]['value'] = $files;
						$rows[$ii]['value_raw'] = $files;

					}elseif($rows[$ii]['type'] != 'textarea'){
						$rows[$ii]['value'] = str_replace(array('<p>', '</p>'), '', $rows[$ii]['value']);
					}

					if($organise_by_id)
						$fields[$rows[$ii]['field_id']] = $rows[$ii];

					elseif($organise_by_handle)
						$fields[$rows[$ii]['field_handle']] = $rows[$ii];

					else
						$fields[$rows[$ii]['name']] = $rows[$ii];

				endif;
			}

			return $fields;

		}

		function fetchEntries($limit=NULL, $organise_fields_by_id=false, $extras=NULL, $order='DESC', $start=0){

			$sql = "SELECT DISTINCT e.id,"
				 . "       e.author_id,"
				 . "       e.valid_xml,"
				 . "       e.formatter,"
				 . "       a.firstname as `author_firstname`,"
				 . "       a.lastname as `author_lastname`,"
				 . "       a.email as `author_email`,"
				 . "       e.publish_date_gmt,"
				 . "	   s.primary_field,"
				 . "       s.id as `section_id`,"
				 . "       UNIX_TIMESTAMP(e.publish_date_gmt) as `timestamp_gmt`,"
				 . "       UNIX_TIMESTAMP(e.publish_date) as `timestamp_local` "
				 . "FROM tbl_entries as `e` "
				 . "LEFT JOIN tbl_authors as `a` ON e.author_id = a.id "
				 . "LEFT JOIN tbl_entries2sections ON e.id = tbl_entries2sections.entry_id "
				 . "LEFT JOIN tbl_sections as `s` ON tbl_entries2sections.section_id = s.id "
				 . "WHERE 1 " . ($extras ? "AND $extras " : "")
				 . "GROUP BY e.id "
				 . "ORDER BY e.publish_date_gmt $order, e.id $order "
				 . ($limit ? "LIMIT $start, $limit" : "");

				$entries = $this->_db->fetch($sql);

				for($ii = 0; $ii < count($entries); $ii++){
					$entries[$ii]['fields'] = $this->fetchEntryFields($entries[$ii]['id'], $organise_fields_by_id);

					if($organise_by_handle)
						$entries[$ii]['primary_field'] = $this->fetchSectionPrimaryFieldHandle($entries[$ii]['section_id']);
				}

				return $entries;
		}

		function fetchEntriesByAuthor($authors, $limit=NULL, $start=0, $order='DESC', $extras=NULL, $organise_by_handle=false, $organise_fields_by_id=false, $section=NULL, $return_count_only=false){

			if($authors == '') return false;
			elseif(!is_array($authors)) $authors = array($authors);

			if($return_count_only){
				$sql = "SELECT count( e.id ) AS `count`
						FROM tbl_entries AS `e`
						LEFT JOIN tbl_authors AS `a` ON e.author_id = a.id
						LEFT JOIN tbl_entries2sections ON e.id = tbl_entries2sections.entry_id
						WHERE 1 AND " . ($section ? "tbl_entries2sections.section_id = '$section' " : "") . " $extras
						AND a.id IN ('".@implode("', '", $authors)."')
						ORDER BY e.publish_date_gmt DESC "
						. ($limit ? "LIMIT $start, " . $limit : '');

			}else{

				if($section != NULL)
					$entry_order = $this->_db->fetchVar('entry_order', 0, "SELECT `entry_order` FROM `tbl_sections` WHERE `id` = '$section' LIMIT 1");

				else $entry_order = 'date';

				$cf_id = NULL;

				if($entry_order == 'date' || $entry_order =='author')
					$order_clause = "`timestamp_gmt` $order, e.id $order ";

				elseif(is_numeric($entry_order)){
					$cf_id = intval($entry_order);
					$order_clause = "`cf`.handle ASC, `timestamp_gmt` $order ";
				}

				$sql = "SELECT DISTINCT e.id,"
					 . "       e.author_id,"
					 . "       e.valid_xml,"
					 . "       e.formatter,"
					 . "       a.firstname as `author_firstname`,"
					 . "       a.lastname as `author_lastname`,"
				 	 . "       a.email as `author_email`,"
					 . "       e.publish_date_gmt,"
					 . "	   s.primary_field,"
					 . "       s.id as `section_id`,"
					 . "       UNIX_TIMESTAMP(e.publish_date_gmt) as `timestamp_gmt`,"
					 . "       UNIX_TIMESTAMP(e.publish_date) as `timestamp_local` "
			 		 . "FROM tbl_entries as `e` "
					 . "LEFT JOIN tbl_authors as `a` ON e.author_id = a.id "
					 . "LEFT JOIN tbl_entries2sections ON e.id = tbl_entries2sections.entry_id "
					 . "LEFT JOIN tbl_sections as `s` ON tbl_entries2sections.section_id = s.id "
				 	 . (!$cf_id ? '' : "LEFT JOIN `tbl_entries2customfields` as `cf` ON e.id = cf.entry_id AND cf.field_id = '$cf_id' ")
					 . "WHERE 1 AND " . ($section ? "tbl_entries2sections.section_id = '$section' " : "") . " $extras "
					 . " AND a.id IN ('".@implode("', '", $authors)."') "
					 . "GROUP BY e.id "
					 . "ORDER BY $order_clause "
					 . ($limit ? "LIMIT $start, " . $limit : '');
			}

			if($return_count_only) return $this->_db->fetchVar('count', 0, $sql);

			$entries = $this->_db->fetch($sql);

			for($ii = 0; $ii < count($entries); $ii++){
				$entries[$ii]['fields'] = $this->fetchEntryFields($entries[$ii]['id'], $organise_fields_by_id, $organise_by_handle);

				if($organise_by_handle)
					$entries[$ii]['primary_field'] = $this->fetchSectionPrimaryFieldHandle($entries[$ii]['section_id']);
			}

			return $entries;
		}



		function fetchEntriesBySection($section, $limit=NULL, $order='DESC', $extras=NULL, $organise_by_handle=false, $organise_fields_by_id=false, $start=0){

			$entry_order = $this->_db->fetchVar('entry_order', 0, "SELECT `entry_order` FROM `tbl_sections` WHERE `id` = '$section' LIMIT 1");

			$cf_id = NULL;

			if($entry_order == 'date')
				$order_clause = "`timestamp_gmt` $order, e.id $order ";

			elseif($entry_order == 'author')
				$order_clause = "a.firstname ASC, `timestamp_gmt` $order ";

			elseif(is_numeric($entry_order)){
				$cf_id = intval($entry_order);
				$order_clause = "`cf`.handle ASC, `timestamp_gmt` $order ";
			}

			$sql = "SELECT DISTINCT e.id,"
				 . "       e.author_id,"
				 . "       e.valid_xml,"
				 . "       e.formatter,"
				 . "       a.firstname as `author_firstname`,"
				 . "       a.lastname as `author_lastname`,"
				 . "       a.email as `author_email`,"
				 . "       e.publish_date_gmt,"
				 . "	   s.primary_field,"
				 . "       s.id as `section_id`,"
				 . "       UNIX_TIMESTAMP(e.publish_date_gmt) as `timestamp_gmt`,"
				 . "       UNIX_TIMESTAMP(e.publish_date) as `timestamp_local` "
				 . "FROM tbl_entries as `e` "
				 . "LEFT JOIN tbl_authors as `a` ON e.author_id = a.id "
				 . "LEFT JOIN tbl_entries2sections ON e.id = tbl_entries2sections.entry_id "
				 . "LEFT JOIN tbl_sections as `s` ON tbl_entries2sections.section_id = s.id "
				 . (!$cf_id ? '' : "LEFT JOIN `tbl_entries2customfields` as `cf` ON e.id = cf.entry_id AND cf.field_id = $cf_id ")
				 . "WHERE tbl_entries2sections.section_id = '$section' $extras "
				 . "GROUP BY e.id "
				 . "ORDER BY $order_clause "
				 . ($limit ? "LIMIT $start, " . $limit : '');

				$entries = $this->_db->fetch($sql);

				for($ii = 0; $ii < count($entries); $ii++){
					$entries[$ii]['fields'] = $this->fetchEntryFields($entries[$ii]['id'], $organise_fields_by_id, $organise_by_handle);

					if($organise_by_handle)
						$entries[$ii]['primary_field'] = $this->fetchSectionPrimaryFieldHandle($entries[$ii]['section_id']);
				}

				return $entries;
		}

		function fetchEntriesByPage($section, $page, $limit, $filter=NULL, $extras=NULL){

			if($filter){
				$filter = explode("-", $filter);

				list($type, $id) = $filter;

				switch($type){

					case 'author':
						$entries = $this->fetchEntriesByAuthor($id, $limit, (($page - 1) * $limit), 'DESC', NULL, false, false, $section);
						$entry_count_total = $this->fetchEntriesByAuthor($id, NULL, 0, 'DESC', NULL, false, false, $section, true);
						break;

				}

			}else{
				$entries = $this->fetchEntriesBySection($section, $limit, 'DESC', $extras, false, false, (($page - 1) * $limit));
				$entry_count_total = $this->countAll($section);
			}

			$entry_count_remaining = ($entry_count_total - (($page - 1) * $limit));

			return array($entry_count_total, $entry_count_remaining, $entries);

		}

		function countAll($section){
			return $this->_db->fetchVar("count", 0, "SELECT count(*) as `count`
													 FROM `tbl_entries2sections`
													 WHERE `section_id` = '$section'");
		}
	}

?>