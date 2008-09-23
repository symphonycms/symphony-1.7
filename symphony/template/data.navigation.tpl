<?php

	Class ds<!-- CLASS NAME --> Extends DataSource{			
		<!-- DEFINES LIST -->
		
		function __construct($args = array()){				
			parent::__construct($args);
			$this->_cache_sections = array('pages');
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
			return 'navigation';
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

			$date = new SymDate($this->_parent->getConfigVar('time_zone', 'region'), $this->_parent->getConfigVar('date_format', 'region'));
			
			##Check Cache	  
			$hash_id = md5(get_class($this));
				 
			if($param['caching'] && $cache = $this->check_cache($hash_id)){
				return $cache;
				exit();
			}
			
			$start_node_id = NULL;
			$maximum_depth = NULL;
			$include_hidden = false;
			
			$xml =& new XMLElement("<!-- ROOT-ELEMENT -->");
			
			##Prepare the Query			
								
			if($page = $this->__resolveDefine("dsFilterPAGE")){
				$start_node_id = $this->_db->fetchVar("id", 0, "SELECT `id` FROM `tbl_pages` WHERE `handle` = '".$page."' LIMIT 1");
				
				if(empty($start_node_id) || $start_node_id == ''){
					$xml->addChild(new XMLElement("error", "No Records Found."));
					return $xml;
				}
			}
			
			if(isset($this->_dsFilterMAX_DEPTH))
				$maximum_depth = $this->_dsFilterMAX_DEPTH;

			
			##------------------------------			

			$this->__createChildrenNodes($start_node_id, $xml, $maximum_depth, $include_hidden);

			##------------------------------	
				
			##Write To Cache
			if($param['caching']){
				$result = $xml->generate($param['indent'], $param['indent-depth']);
				$this->write_to_cache($hash_id, $result, $this->_cache_sections);
				return $result;
			}				
			
			return $xml;
				
		}			

		function __createChildrenNodes($parent_id, &$parent_object, $max_depth=NULL, $include_hidden=false, $start_node_id=NULL){

			if($max_depth !== NULL){
				if(intval($max_depth) <= 0) return;
				else $max_depth--;
			}
			
			if($parent_id == NULL && $start_node_id != NULL){
				if(!is_array($start_node_id))
					$pages = $this->_db->fetch("SELECT * FROM `tbl_pages` WHERE `parent` IS NULL AND `id` = '$start_node_id' LIMIT 1");
				
				else
					$pages = $this->_db->fetch("SELECT * FROM `tbl_pages` WHERE `parent` IS NULL AND `id` IN ('" . @implode("', '", $start_node_id) . "') ORDER BY `sortorder` ASC");
				
			}elseif($parent_id == NULL)
				$pages = $this->_db->fetch("SELECT * FROM `tbl_pages` WHERE `parent` IS NULL AND `show_in_nav` = 'yes' ORDER BY `sortorder` ASC");
			
			else			
				$pages = $this->_db->fetch("SELECT * FROM `tbl_pages` WHERE `parent` = '$parent_id' AND `show_in_nav` = 'yes' ORDER BY `sortorder` ASC");
			
			
			if(is_array($pages) && !empty($pages)){
				foreach($pages as $item){
				
					$page =& new XMLElement("page");
					$page->setAttribute("handle", $item['handle']);
					$page->setAttribute("type", $item['type']);
						
					$page->addChild(new XMLElement("title", $item['title']));
									
					$children = $this->__createChildrenNodes($item['id'], $page, $max_depth, $include_hidden);
					
					if(is_object($children))
						$page->addChild($children);
					
					$parent_object->addChild($page);		
				}
			}			
		}		
	}

?>