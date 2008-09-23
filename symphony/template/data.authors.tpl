<?php	
		
	Class ds<!-- CLASS NAME --> Extends DataSource{			
		<!-- DEFINES LIST -->
	
		function __construct($args = array()){
			parent::__construct($args);
			$this->_cache_sections = array('entries', 'authors');			
		}
		
		## This function is required in order to edit it in the data source editor page. 
		## If this file is in any way edited manually, you must set the return value of this
		## function to 'false'. Failure to do so may result in you losing any manual changes
		function allowEditorToParse(){
			return true;
		}
				
		## This function is required in order to identify what type of data source this 
		## is for use in the data source editor. It must remain intact. Do not include
		## this function into custom data sources
		function getType(){
			return 'authors';
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

			$date = $this->_parent->getDateObj(); 
			
			extract($this->_env, EXTR_PREFIX_ALL, 'env');
			
			$where = NULL;
			
			##Prepare the Query				
			if($usernames = $this->__resolveDefine('dsFilterUSERNAME', true))				
				$where .= " AND `tbl_authors`.username ".($this->__isDefineNotClause("dsFilterUSERNAME") ? 'NOT' : '')." IN ('" . @implode("', '", $usernames) . "') ";			
				
			if($account_type = $this->__resolveDefine("dsFilterSTATUS")){
				switch($account_type){
					
					case "author":
						$where .= " AND `tbl_authors`.owner = '0' AND `tbl_authors`.superuser = '0' ";
						break;
						
					case "owner":
						$where .= " AND `tbl_authors`.owner = '1' ";
						break;
						
					case "administrator":
						$where .= " AND `tbl_authors`.superuser = '1' ";
						break;
					
				}
			}
					
			##We are trying to preview
			if(isset($param['limit']))
				$limit = " LIMIT 0, " . $param['limit'];
				
			##Prevent things from getting to big	
			elseif($where == NULL) 
				$limit = " LIMIT 0, 50";	
										            											
			$sql = "SELECT tbl_authors.*, count(tbl_entries.id) as `entry_count` FROM `tbl_authors` "
				 . "LEFT JOIN `tbl_entries` ON `tbl_entries`.`author_id` = `tbl_authors`.`id` "
				 . "WHERE 1 " . $where
				 . "GROUP BY `tbl_authors`.id "
				 . "ORDER BY `tbl_authors`.username ASC " . $sort . $limit;

			##Check Cache	  
			$hash_id = md5(get_class($this) . $sql);
				 
			if($param['caching'] && $cache = $this->check_cache($hash_id)){
				return $cache;
				exit();
			}							 

			##------------------------------
			
			##Create the XML container			
			$xml = new XMLElement("<!-- ROOT-ELEMENT -->");
			
			##Grab the records			
			$authors = $this->_db->fetch($sql);	
					
			##Populate the XML		
			if(empty($authors) || !is_array($authors)){
				$xml->addChild(new XMLElement("error", "No Records Found."));
				return $xml;
				
			}else{
					
				foreach($authors as $row){
		            
					$status = 'Author';
					
					if($row['owner'] == 1) $status = 'Owner';
					elseif($row['superuser'] == 1) $status = 'Adminstrator';
		
		            ##Author Details
		            $fields = array();
		            $fields["entry-count"] = $row['entry_count'];
		            $fields["first-name"] = $row['firstname'];
		            $fields["last-name"] = $row['lastname'];
		            $fields["email"] = $row['email'];
		            $fields["username"] = array($row['username'], "attr");
		            $fields["status"] = $status;
		            $fields["auth-token"] = substr(md5($row['username'].$row['password']), 0 ,8);	
		
					$author = new XMLElement("author");
					$this->__addChildFieldsToXML($fields, $author);
		            	
					$xml->addChild($author);					
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
