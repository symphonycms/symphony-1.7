<?php	
		
	Class dsCategories_list Extends DataSource{			
		
		var $_dsFilterCUSTOMFIELD = 'categories';
		var $_dsFilterPARENTSECTION = 'entries';
	
		function __construct($args = array()){
			parent::__construct($args);
			$this->_cache_sections = array('customfields');			
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
			return 'options';
		}
					
		function about(){		
			return array(
						 'name' => 'Categories List',
						 'description' => 'NULL',
						 'author' => array('name' => 'Allen Chang',
										   'website' => 'http://symphony.local:8888',
										   'email' => 'allen@21degrees.com.au'),
						 'version' => '1.0',
						 'release-date' => '2006-10-04 00:57:27');			 
		}
		
		function grab($param=array()){

			extract($this->_env, EXTR_PREFIX_ALL, 'env');
							
			include_once(TOOLKIT . '/class.entrymanager.php');
			$entryManager = new EntryManager($this->_parent);
			
			$section_id = $entryManager->fetchSectionIDFromHandle($this->__resolveDefine("dsFilterPARENTSECTION"));			
						
			$schema = $entryManager->fetchEntryFieldSchema($section_id, NULL, $this->_dsFilterCUSTOMFIELD);		
			$schema = $schema[0];
			
			##Check the cache 			 
			$hash_id = md5(get_class($this));
				 
			if($param['caching'] && $cache = $this->check_cache($hash_id)){
				return $cache;
				exit();
			}
			
			##------------------------------
			
			##Create the XML container
			$xml = new XMLElement("categories-list");
			$xml->setAttribute("section", "customfield");	

			##Populate the XML
			if(empty($schema) || !is_array($schema)){
				$xml->addChild(new XMLElement("error", "No Records Found."));
				return $xml;
				
			}else{

        		$ops = preg_split('/,/', $schema['values'], -1, PREG_SPLIT_NO_EMPTY);
        		$ops = array_map("trim", $ops);

        		$xml->addChild(new XMLElement("name", $schema['name']));
        		$xml->setAttribute("handle", $schema['handle']);

        		$options = new XMLElement("options");
        		
        		foreach($ops as $o){     
	      	
					if($schema['type'] == 'multiselect')
						$table = 'tbl_entries2customfields_list';
						
					else
						$table = 'tbl_entries2customfields';
						
	        		$count = $this->_db->fetchVar('count', 0, "SELECT count(id) AS `count` FROM `$table` WHERE `field_id` = '".$schema['id']."' AND value_raw = '$o' ");
	        		
	        		$xO = new XMLElement("option", $o);
	        		$xO->setAttribute('entry-count', $count);
	        		$xO->setAttribute('handle', Lang::createHandle($o, $this->_parent->getConfigVar('handle_length', 'admin')));
            		$options->addChild($xO);
        		}
        		
        		$xml->addChild($options);
            							
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