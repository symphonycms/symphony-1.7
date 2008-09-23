<?php	
		
	Class ds<!-- CLASS NAME --> Extends DataSource{			
		<!-- DEFINES LIST -->
	
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
			return '<!-- SECTION NAME -->';
		}
					
		function about(){		
			return array(
						 'name' => '<!-- NAME -->',
						 'description' => '<!-- DESCRIPTION -->',
						 'author' => array('name' => '<!-- AUTHOR-NAME -->',
										   'website' => '<!-- AUTHOR-WEBSITE -->',
										   'email' => '<!-- AUTHOR-EMAIL -->'),
						 'version' => '<!-- VERSION -->',
						 'release-date' => '<!-- RELEASE DATE -->');			 
		}

		function grab($param=array()){

			## Decide if we return an emtpy set or not
			if($this->__forceEmptySet()) {
				
				##Create the XML container
				$xml = new XMLElement("<!-- ROOT-ELEMENT -->");
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
				
			$sql = "SELECT t1.id, t1.publish_date_gmt "
				 . "FROM `tbl_entries` AS t1 "
				 . "LEFT JOIN `tbl_metadata` AS t2 ON t1.`id` = t2.`relation_id` "
				 . "AND t2.`class` = 'entry' "
				 . "LEFT JOIN `tbl_authors` AS t4 ON t1.`author_id` = t4.`id` "
				 . $joins
				 . "LEFT JOIN `tbl_entries2sections` AS t8 ON t1.id = t8.entry_id "
				 . "WHERE t8.section_id = '$section_id' " . $where
				 . "GROUP BY t1.`id` "
				 . "ORDER BY t1.`publish_date_gmt` " . $sort;		

			##Check the cache 			 
			$hash_id = md5(get_class($this) . serialize($env_url));

			if($param['caching'] && $cache = $this->check_cache($hash_id)){
				return $cache;
				exit();
			}

			##------------------------------

			##Create the XML container
			$xml = new XMLElement("<!-- ROOT-ELEMENT -->");
			$xml->setAttribute("section", $this->getType());

			##Grab the records
			$entries = $this->_db->fetch($sql);		

			$current_month = date("m", $obDate->get(true, false));
			$current_year = date("Y", $obDate->get(true, false));

			##Populate the XML
			if(empty($entries) || !is_array($entries)){
				$xml->addChild(new XMLElement("error", "No Records Found."));
				return $xml;
				
			}else{

				$bin = array();

				foreach($entries as $e){

					list($dYear, $dMonth, $dDay) = explode("-", date("Y-m-d", $obDate->get(true, false, strtotime($e['publish_date_gmt']))));

					$bin[$dYear][intval($dMonth)]++;
				}

				$years = @array_keys($bin);

				if($sort && $sort == 'DESC'){
					$end_year = $current_year;
					
					$bin_years = array_keys($bin);
					rsort($bin_years);
					
					for($ii = ($bin_years[0]+1); $ii <= $current_year; $ii++){
						$bin[$ii] = array();
					}
					
					$bin = array_reverse($bin, true);
					
				}else
					$start_year = $years[0];
				
				foreach($bin as $year => $months){

					$xYear = new XMLElement("year");
					$xYear->setAttribute("value", $year);

					#foreach($months as $month => $count){
					if($sort && $sort == 'DESC'){
						for($month = 12; $month > 0; $month--){	
							if($current_year > $year || ($current_year == $year && $current_month >= $month)){
								$xMonth = new XMLElement("month");
								$xMonth->setAttribute("value", ($month < 10 ? "0$month" : $month));
								$xMonth->setAttribute("entry-count", "" . max(0, intval($months[$month])) . "");
								$xYear->addChild($xMonth);

							}
						}	

					}else{
						for($month = 1; $month <= 12; $month++){	
							if($current_year > $year || ($current_year == $year && $current_month >= $month)){
								$xMonth = new XMLElement("month");
								$xMonth->setAttribute("value", ($month < 10 ? "0$month" : $month));
								$xMonth->setAttribute("entry-count", "" . max(0, intval($months[$month])) . "");
								$xYear->addChild($xMonth);

							}
						}
					}

					$xml->addChild($xYear);

					if($sort && $sort == 'DESC')
						$start_year = $year;	

					else
						$end_year = $year;				

			    }

				$xml->setAttribute("year-start", $start_year);
				$xml->setAttribute("year-end", $end_year);

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