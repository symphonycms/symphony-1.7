<?php	
		
	Class dsComments Extends DataSource{			
		
		var $_dsFilterHANDLE = '$entry';
		var $_dsFilterLIMIT = '50';
		var $_dsFilterSORT = 'asc';
		var $_dsFilterSECTION = 'entries';
		var $_dsFilterFORCEEMPTYSET = 'yes';
		var $_dsFilterPAGENUMBER = '$page';
		var $_dsFilterXMLFIELDS = array(
						'author',
						'date',
						'time',
						'pagination-info',
						'authorised',
						'message',
						'url',
				);
	
		function __construct($args = array()){
			parent::__construct($args);
			$this->_cache_sections = array('comments', 'entries');		
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
			return 'comments';
		}
		
		function about(){		
			return array(
						 'name' => 'Comments',
						 'description' => 'NULL',
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://symphony.local:8888',
										   'email' => 'alistair@21degrees.com.au'),
						 'version' => '1.0',
						 'release-date' => '2007-03-08 14:17:15');			 
		}
		
		function grab($param=array()){
			
			## Decide if we return an emtpy set or not
			if($this->__forceEmptySet()) {
				
				##Create the XML container
				$xml = new XMLElement("comments");			
				$xml->addChild(new XMLElement("error", "No Records Found."));

				return $xml;
			}

			
			$obDate = $this->_parent->getDateObj(); 

			extract($this->_env, EXTR_PREFIX_ALL, 'env');

			$where = NULL;
				
			include_once(TOOLKIT . "/class.entrymanager.php");
			$entryManager = new EntryManager($this->_parent);
			
			##Prepare the Query	
			
			if($section_id = $entryManager->fetchSectionIDFromHandle($this->_dsFilterSECTION)){

				$comment_where .= " AND t4.`section_id` = '$section_id' ";
			
				if($entries = $this->__resolveDefine("dsFilterHANDLE", true)){			
					$entry_ids = $entryManager->fetchEntryIDFromPrimaryFieldHandle($section_id, $entries);						
					$comment_where .= " AND t3.`id`".($this->__isDefineNotClause("dsFilterHANDLE") ? ' NOT' : '')." IN ('" . @implode("', '", $entry_ids) . "') ";
				}
			}

			if($date = $this->__resolveDefine("dsFilterDAY"))
				$comment_where .= " AND DATE_FORMAT(t2.creation_date, '%d') ".($this->__isDefineNotClause("dsFilterDAY") ? '!' : '')."= '" . $date . "' ";	

			if($month = $this->__resolveDefine("dsFilterMONTH"))	
				$comment_where .= " AND DATE_FORMAT(t2.creation_date, '%m') ".($this->__isDefineNotClause("dsFilterMONTH") ? '!' : '')."= '" . $month . "' ";				

			if($year = $this->__resolveDefine("dsFilterYEAR"))	
				$comment_where .= " AND DATE_FORMAT(t2.creation_date, '%Y') ".($this->__isDefineNotClause("dsFilterYEAR") ? '!' : '')."= '" . $year . "' ";	
							
			$sort = "DESC";
					
			if($this->_dsFilterSORT != '')
				$sort = strtoupper($this->_dsFilterSORT);
				
			if(!isset($this->_dsFilterSHOWSPAM) || $this->_dsFilterSHOWSPAM != 'yes')
				$comment_where .= " AND `t1`.`spam` = 'no' ";
												
			if($max_months = $this->__resolveDefine("dsFilterLIMIT_MONTHS")){

				$sql = "SELECT UNIX_TIMESTAMP(t2.creation_date_gmt) as `creation_timestamp_gmt` "
					 . "FROM `tbl_comments` as t1 "
					 . "LEFT JOIN `tbl_metadata` AS t2 ON t1.`id` = t2.`relation_id` AND t2.`class` = 'comment' "
					 . "INNER JOIN `tbl_entries` as t3 ON t1.`entry_id` = t3.`id` "	
					 . "LEFT JOIN `tbl_entries2sections` AS t4 ON t3.`id` = t4.`entry_id` "	 
					 . "WHERE 1 " . $comment_where
					 . "GROUP BY t1.`id` "
					 . "ORDER BY `creation_timestamp_gmt` $sort "
					 . "LIMIT 1";
						
				$relative_start = $this->_db->fetchVar('creation_timestamp_gmt', 0, $sql); 
				
				switch($sort){
				
					case "DESC":					
						$end = mktime(0, 0, 0, date('m', $relative_start)-$max_months+1, 1, date('Y', $relative_start));	
						$comment_where .= " AND (UNIX_TIMESTAMP(t2.creation_date_gmt) <= '$relative_start' AND UNIX_TIMESTAMP(t2.creation_date_gmt) >= '$end')";
						
						break;
						
					case "ASC":
						## Since this is assending, we need to start from 0. The DS editor will give us 1+
						$max_months--;
						
						$last_day = date('d', mktime(0, 0, 0, date('m', $relative_start)+1, 0, date('Y', $relative_start)));
						$end = mktime(23, 59, 59, date('m', $relative_start)+$max_months, $last_day, date('Y', $relative_start));					
												
						$comment_where .= " AND (UNIX_TIMESTAMP(t2.creation_date_gmt) >= '$relative_start' AND UNIX_TIMESTAMP(t2.creation_date_gmt) <= '$end')";
						break;
						
				}	
			
			}else{

				##We are trying to preview
				if(isset($param['limit'])){
					$limit = $param['limit'];
	
				}elseif($this->_dsFilterLIMIT != ''){
					$limit = intval($this->_dsFilterLIMIT);
	
				##Prevent things from getting too big	
				}else{
					$limit = 50;
				}
			}

			$start = 0;
			
			$sql = "SELECT count(t1.id) AS `total-comments` "
				 . "FROM `tbl_comments` AS t1 "
				 . "LEFT JOIN `tbl_metadata` AS t2 ON t1.`id` = t2.`relation_id` AND t2.`class` = 'comment' "
				 . "INNER JOIN `tbl_entries` as t3 ON t1.`entry_id` = t3.`id` "	
				 . "LEFT JOIN `tbl_entries2sections` AS t4 ON t3.`id` = t4.`entry_id` "	 	 
				 . "WHERE 1 " . $comment_where;

			$kTotalCommentCount = $this->_db->fetchVar('total-comments', 0, $sql);

			if(isset($this->_dsFilterPAGENUMBER)){

				$pagenumber = $this->__resolveDefine("dsFilterPAGENUMBER");
				$kPageNumber = max(1, intval($pagenumber));

				if(!$limit) $limit = 50;		

				$kTotalPages = ceil($kTotalCommentCount * (1 / $limit));

				$start = $limit * ($kPageNumber - 1);

			}

			$sql = "SELECT  t1.*, UNIX_TIMESTAMP(t2.creation_date_gmt) as `creation_timestamp_gmt` "
				 . "FROM `tbl_comments` as t1 "
				 . "LEFT JOIN `tbl_metadata` AS t2 ON t1.`id` = t2.`relation_id` AND t2.`class` = 'comment' "
				 . "INNER JOIN `tbl_entries` as t3 ON t1.`entry_id` = t3.`id` "	
				 . "LEFT JOIN `tbl_entries2sections` AS t4 ON t3.`id` = t4.`entry_id` "	 	 
				 . "WHERE 1 " . $comment_where
				 . "GROUP BY t1.`id` "
				 . "ORDER BY `creation_timestamp_gmt` $sort " . ($limit ? " LIMIT $start, $limit" : '');

			##Check Cache	  
			$hash_id = md5(get_class($this) . $sql);

			if($param['caching'] && $cache = $this->check_cache($hash_id)){
				return $cache;
				exit();
			}

			##------------------------------

			##Create the XML container
			$xml = new XMLElement("comments");

			##Grab the records				 
			$comments = $this->_db->fetch($sql);	

			##Populate the XML
			if(empty($comments) || !is_array($comments)){
				$xml->addChild(new XMLElement("error", "No Records Found."));
				return $xml;
				
			}else{

				$entries = array();

				foreach($comments as $c){					
					$entries[$c['entry_id']]['commenting'] = $c['commenting'];
					$entries[$c['entry_id']]['comments'][] = $c;
				}

				if(in_array("pagination-info", $this->_dsFilterXMLFIELDS)){

					$pageinfo = new XMLElement("pagination-info");
					$pageinfo->setAttribute("total-comments", $kTotalCommentCount);
					$pageinfo->setAttribute("total-pages", $kTotalPages);
					$pageinfo->setAttribute("comment-per-page", $limit);
					$pageinfo->setAttribute("current-page", $kPageNumber);
					$xml->addChild($pageinfo);

				}

				foreach($entries as $id => $row){

					$entry_data = $entryManager->fetchEntriesByID($id, false, true);

		            $entry = new XMLElement("entry");
		            $entry->setAttribute("id", $id);
					$entry->setAttribute('section-id', $entry_data['section_id']);
		            $entry->setAttribute("handle", trim($entry_data['fields'][$entry_data['primary_field']]['handle']));
					$entry->setAttribute("commenting", $row['commenting']);		

					$entry->addChild(new XMLElement("entry-title", trim($entry_data['fields'][$entry_data['primary_field']]['value'])));

					$fields = $row['comments'];

					$entry->setAttribute("count", $kTotalCommentCount);		

					if(is_array($fields) && !empty($fields)){
						foreach($fields as $c){
							$comment = new XMLElement("comment");
							$comment->setAttribute("id", $c['id']);
							
							if($c['author_id'] != NULL){
								$comment->setAttribute('authorised', 'yes');
								$comment->setAttribute('author_id', $c['author_id']);
							}
							
							if(@in_array('spam', $this->_dsFilterXMLFIELDS)) $comment->setAttribute("spam", $c['spam']);

							$date_local = $obDate->get(true, false, $c['creation_timestamp_gmt']);
														
							$comment_fields = array(
								"author" => $c['author_name'],
								"date" => General::createXMLDateObject($date_local),
								"time" => General::createXMLTimeObject($date_local),
								"rfc822-date" => date("D, d M Y H:i:s \G\M\T", $obDate->get(false, false, $row['creation_timestamp_gmt'])),
								"message" => ($this->_dsFilterENCODE != 'yes' ? $c['body'] : General::sanitize($c['body'])),
								"url" => $c['author_url'],
								"email" => $c['author_email'],
								"email-hash" => md5($c['author_email'])
							);
							
							$this->__addChildFieldsToXML($comment_fields, $comment);
							$entry->addChild($comment);

						}			
					}					

					$xml->addChild($entry);								
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