<?php	
		
	Class dsArchive_entry_list Extends DataSource{			
		
		var $_dsFilterYEAR = '$year';
		var $_dsFilterMONTH = '$month';
		var $_dsFilterLIMIT_MONTHS = '1';
		var $_dsFilterSORT = 'desc';
		var $_dsFilterFORMAT_TYPE = 'archive';
		var $_dsFilterCUSTOM = array(
						'publish' => 'yes',
				);
		var $_dsFilterXMLFIELDS = array(
						'date',
						'rfc822-date',
						'title',
				);
	
		function __construct($args = array()){
			parent::__construct($args);
			$this->_cache_sections = array('comments', 'entries', 'authors', 'customfields');			
		}
		
		## This function is required in order to edit it in the data source editor page. 
		## If this file is in any way edited manually, you must set the return value of this
		## function to 'false'. Failure to do so may result in your Datasource becoming 
		## accidently messed up
		function allowEditorToParse(){
			return true;
		}
				
		## This function is required in order to identify what type of data source this is for
		## use in the data source editor. It must remain intact. Do not include this function into
		## custom data sources
		function getType(){
			return 'entries';
		}
					
		function about(){		
			return array(
						 'name' => 'Archive Entry List',
						 'description' => 'NULL',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://symphony.local:8888',
										   'email' => 'alistair@21degrees.com.au'),
						 'version' => '1.0',
						 'release-date' => '2007-03-21 23:55:00');			 
		}
		
		function grab($param=array()){
			
			## Decide if we return an emtpy set or not
			if($this->__forceEmptySet()) {
				
				##Create the XML container
				$xml = new XMLElement("archive-entry-list");
				$xml->setAttribute("section", $this->getType());				
				$xml->addChild(new XMLElement("error", "No Records Found."));

				return $xml;
			}
			
			$obDate = $this->_parent->getDateObj(); 

			extract($this->_env, EXTR_PREFIX_ALL, 'env');
			
			$where = $sort = $joins = NULL;
				
			include_once(TOOLKIT . '/class.entrymanager.php');
			$entryManager = new EntryManager($this->_parent);
			
			$section_id = $entryManager->fetchSectionIDFromHandle($this->getType());			
						
			##Prepare the Query			
			if($handle = $this->__resolveDefine("dsFilterHANDLE")){
				$entries = $entryManager->fetchEntryIDFromPrimaryFieldHandle($section_id, $handle);						
				$where .= " AND t1.`id`".($this->__isDefineNotClause("dsFilterHANDLE") ? ' NOT' : '')." IN ('" . @implode("', '", $entries) . "') ";

			}
			
			if($date = $this->__resolveDefine("dsFilterDAY"))	
				$where .= " AND DATE_FORMAT(t1.publish_date, '%d') ".($this->__isDefineNotClause("dsFilterDAY") ? '!' : '')."= '" . $date . "' ";			

			if($month = $this->__resolveDefine("dsFilterMONTH"))	
				$where .= " AND DATE_FORMAT(t1.publish_date, '%m') ".($this->__isDefineNotClause("dsFilterMONTH") ? '!' : '')."= '" . $month . "' ";
				
			if($year = $this->__resolveDefine("dsFilterYEAR"))	
				$where .= " AND DATE_FORMAT(t1.publish_date, '%Y') ".($this->__isDefineNotClause("dsFilterYEAR") ? '!' : '')."= '" . $year . "' ";	
			
			if($this->_dsFilterINCLUDEPOSTDATED != 'yes')
				$where .= " AND UNIX_TIMESTAMP(t1.publish_date_gmt) <= '" . $obDate->get(false, false) . "' ";
					
			if(is_array($this->_dsFilterCUSTOM) && !empty($this->_dsFilterCUSTOM)){

				$table_id = 15;

				foreach($this->_dsFilterCUSTOM as $handle => $value){

					$field = $this->_db->fetchRow(0, "SELECT `id`, `type`, `foreign_select_multiple` FROM `tbl_customfields` WHERE `parent_section` = '$section_id' AND `handle` = '$handle' LIMIT 1");

					$value_handle = Lang::createHandle($value, $this->_parent->getConfigVar('handle_length', 'admin'));

					if($field['type'] == 'multiselect' || ($field['type'] == 'foreign' && $field['foreign_select_multiple'] == 'yes')){
						$joins .= " LEFT JOIN `tbl_entries2customfields_list` AS t$table_id ON t1.`id` = t$table_id.`entry_id` AND t$table_id.field_id = ".$field['id']." ";
						$where .= " AND (t$table_id.value_raw = '$value' OR t$table_id.handle = '$value_handle') ";
					}
					
					else{
						$joins .= " LEFT JOIN `tbl_entries2customfields` AS t$table_id ON t1.`id` = t$table_id.`entry_id` AND t$table_id.field_id = ".$field['id']." ";
						$where .= " AND (t$table_id.value_raw = '$value' OR t$table_id.handle = '$value_handle') ";

					}

					$table_id++;

				}
			}
								
			if($this->_dsFilterSORT != '')
				$sort = strtoupper($this->_dsFilterSORT);
			
			if($max_months = $this->__resolveDefine("dsFilterLIMIT_MONTHS")){

				$sql = "SELECT UNIX_TIMESTAMP(t1.publish_date) AS publish_timestamp "
					 . "FROM `tbl_entries` AS t1 "
					 . "LEFT JOIN `tbl_metadata` AS t2 ON t1.`id` = t2.`relation_id` "
					 . "AND t2.`class` = 'entry' "
					 . "LEFT JOIN `tbl_authors` AS t4 ON t1.`author_id` = t4.`id` "
					 . $joins
					 . "LEFT JOIN `tbl_entries2sections` AS t8 ON t1.id = t8.entry_id "
					 . "WHERE t8.section_id = '$section_id' " . $where
					 . "GROUP BY t1.`id` "
					 . "ORDER BY t1.`publish_date` $sort "
					 . "LIMIT 1";
						
				$relative_start = $this->_db->fetchVar('publish_timestamp', 0, $sql); 
				
				switch($sort){
				
					case "DESC":					
						$end = mktime(0, 0, 0, date('m', $relative_start)-$max_months+1, 1, date('Y', $relative_start));	
						$where .= " AND (UNIX_TIMESTAMP(t1.publish_date) <= '$relative_start' AND UNIX_TIMESTAMP(t1.publish_date) >= '$end')";
						
						break;
						
					case "ASC":
						## Since this is assending, we need to start from 0. The DS editor will give us 1+
						$max_months--;
										
						$last_day = date('d', mktime(0, 0, 0, date('m', $relative_start)+1, 0, date('Y', $relative_start)));
						$end = mktime(23, 59, 59, date('m', $relative_start)+$max_months, $last_day, date('Y', $relative_start));					
												
						$where .= " AND (UNIX_TIMESTAMP(t1.publish_date) >= '$relative_start' AND UNIX_TIMESTAMP(t1.publish_date) <= '$end')";
						break;
						
				}	
			
			}else{				
							
				##We are trying to preview
				if(isset($param['limit']))
					$limit = " LIMIT 0, " . $param['limit'];
				
				elseif($this->_dsFilterLIMIT != '')
					$limit = " LIMIT 0, " . $this->_dsFilterLIMIT;
					
				##Prevent things from getting to big	
				elseif($where == NULL) 
					$limit = " LIMIT 0, 50";
			}				
							            											
			$sql = "SELECT t1.id "
				 . "FROM `tbl_entries` AS t1 "
				 . "LEFT JOIN `tbl_metadata` AS t2 ON t1.`id` = t2.`relation_id` "
				 . "AND t2.`class` = 'entry' "
				 . "LEFT JOIN `tbl_authors` AS t4 ON t1.`author_id` = t4.`id` "
				 . $joins
				 . "LEFT JOIN `tbl_entries2sections` AS t8 ON t1.id = t8.entry_id "
				 . "WHERE t8.section_id = '$section_id' " . $where
				 . "GROUP BY t1.`id` "
				 . "ORDER BY t1.`publish_date_gmt` " . $sort . $limit;	

			##Check the cache 			 
			$hash_id = md5(get_class($this) . serialize($env_url));
				 
			if($param['caching'] && $cache = $this->check_cache($hash_id)){
				return $cache;
				exit();
			}
			
			##------------------------------
			
			##Create the XML container
			$xml = new XMLElement("archive-entry-list");
			$xml->setAttribute("section", $this->getType());
            $xml->setAttribute("section-id", $section_id);
			
			##Grab the records
			$entries = $this->_db->fetchCol("id", $sql);		

			##Populate the XML
			if(empty($entries) || !is_array($entries)){
				$xml->addChild(new XMLElement("error", "No Records Found."));
				return $xml;
				
			}else{
			
				$bin = array();
				
				foreach($entries as $id){
					
					$row = $entryManager->fetchEntriesByID($id, false, true);
					
					list($dYear, $dMonth, $dDay) = explode("-", date("Y-m-d", $obDate->get(true, false, strtotime($row['publish_date_gmt']))));
					
					$bin[$dYear][$dMonth][$dDay][] = $row;
				}
				
				foreach($bin as $year => $months){
					
					$xYear = new XMLElement("year");
					$xYear->setAttribute("value", $year);
					
					foreach($months as $month => $days){
						
						$xMonth = new XMLElement("month");
						$xMonth->setAttribute("value", $month);
												
						foreach($days as $day => $entries){	
							
							$xDay = new XMLElement("day");
							$xDay->setAttribute("value", $day);		
										
							foreach($entries as $row){						
								
					            $entry = new XMLElement("entry");
					            $entry->setAttribute("id", $row['id']);
					            $entry->setAttribute("handle", trim($row['fields'][$row['primary_field']]['handle']));
								$entry->setAttribute('linked-count', ''.count($row['linked_entries']).''); 
					
								$date_local = $obDate->get(true, false, $row['timestamp_gmt']);

					            $entry_fields = array(
									"date" => General::createXMLDateObject($date_local),
									"time" => General::createXMLTimeObject($date_local),
									"rfc822-date" => date("D, d M Y H:i:s \G\M\T", $obDate->get(false, false, $row['timestamp_gmt']))
					            );
					            
					            $this->__addChildFieldsToXML($entry_fields, $entry);
					            
					            ##Author Details
					            $author_rec = $this->_db->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `id` = '".$row['author_id']."' LIMIT 1");
					            
					            $author = new XMLElement("author");
					            
					            $author_fields = array(
						            "first-name" => $author_rec['firstname'],
						            "last-name" => $author_rec['lastname'],
						           	"email" => $author_rec['email'],
						            "username" => $author_rec['username']            
				                );
				                
				                $this->__addChildFieldsToXML($author_fields, $author, "author");
					            $entry->addChild($author);				
								
								##Custom Fields
			
								$fields = $row['fields'];
								
								if(is_array($fields) && !empty($fields)):
								
									$customFields = new XMLElement("fields");
									
									foreach($fields as $f){
									
										if(@in_array($f['field_handle'], $this->_dsFilterXMLFIELDS)){
											$newField = new XMLElement($f['field_handle']);	
												
											if($f['type'] == 'list' || $f['type'] == 'multiselect'){
												foreach($f['value_raw'] as $val){
													$item = new XMLElement("item", $val);
													$item->setAttribute("handle", Lang::createHandle($val, $this->_parent->getConfigVar('handle_length', 'admin')));
													$newField->addChild($item);
												}
												
											}
								
											elseif($f['type'] == 'foreign'){

												$sid = $f['foreign_section'];
												$section_handle = $this->_db->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_sections` WHERE `id` = '$sid ' LIMIT 1");
												$newField->setAttribute("handle", $f['handle']);
												$newField->setAttribute("type", 'foreign');
												$newField->setAttribute("section-id", $sid);
												$newField->setAttribute("section-handle", $sid);
																				
												if(!is_array($f['value_raw'])) $f['value_raw'] = array($f['value_raw']);
								
												foreach($f['value_raw'] as $h){
													$entry_id = $entryManager->fetchEntryIDFromPrimaryFieldHandle($sid, $h);
													$e = $entryManager->fetchEntriesByID($entry_id, false, true);
										
													$item = new XMLElement("item", trim($e['fields'][$e['primary_field']]['value']));
													$item->setAttribute("entry-id", $entry_id[0]);
													$item->setAttribute("entry-handle", $e['fields'][$e['primary_field']]['handle']);	
													$newField->addChild($item);											
												}
		
											}
																			
											elseif($f['type'] == 'upload'){	
				
												foreach($f['value_raw'] as $val){
													$item = new XMLElement("item");
													$item->addChild(new XMLElement("path", trim($val['path'], '/')));
													$item->addChild(new XMLElement("type", $val['type']));
													$item->addChild(new XMLElement("size", General::formatFilesize($val['size'])));
													$newField->addChild($item);	
												}	
												
											}
											
											elseif($f['type'] == 'checkbox'){
												$newField->setValue($f['value_raw']);
												
											}
											
											elseif($f['type'] == 'select'){
												$newField->setValue($f['value_raw']);
												$newField->setAttribute("handle", $f['handle']);
													
											}
											
											else{
												$key = 'value';
												if($f['format'] != 1) $key = 'value_raw';

												$f[$key] = trim($f[$key]);
												$value = $f[$key];

												if($this->_dsFilterENCODE == "yes")
													$value = trim(General::sanitize($f[$key]));

												if($f['type'] == 'textarea'){

													$newField->setValue($value);
													$newField->setAttribute("word-count", General::countWords(strip_tags($f['value'])));

												}elseif($f['type'] == 'input' && $f['field_id'] != $row['primary_field']){
													$newField->setAttribute("handle", $f['handle']);
													$newField->setValue($value);
												}
													
											}
											
											$customFields->addChild($newField);
										}
											
									}
									
									$entry->addChild($customFields);	
									
								endif;								
								
								##Comments
								$commenting = $this->_db->fetchVar('commenting', 0, "SELECT `commenting` FROM `tbl_sections` WHERE `id` = '$section_id' LIMIT 1");

								if($commenting == 'on'){
									$comments = new XMLElement("comments");

									$sql = "SELECT  count(*) as `count` "
										 . "FROM `tbl_comments` "
										 . "WHERE `entry_id` = '". $row['id'] ."'";							 		

									$comment_count = max(0, @intval($this->_db->fetchVar("count", 0, $sql . " AND `spam` = 'no'")));
									$spam_count = max(0, @intval($this->_db->fetchVar("count", 0, $sql . " AND `spam` = 'yes'")));

									$comments->setAttribute("count", "" .$comment_count. "");
									$comments->setAttribute("spam", "" .$spam_count. "");	

									$entry->addChild($comments);	
								}	
								
					            $xDay->addChild($entry);	
					            
							}
							
							$xMonth->addChild($xDay);	
						}
						
						$xYear->addChild($xMonth);	
					}	
					
					$xml->addChild($xYear);
							
			    }
			    
		    }
			
			##------------------------------		
					
			##Write To Cache
			if($param['caching']){
				$result = $xml->generate($param['indent'], $param['indent-depth']);
				$this->write_to_cache($hash_id, $result, $this->_cache_sections);
				return $result;
			}
				
			return $xml;
				
		}	
		
	}

?>