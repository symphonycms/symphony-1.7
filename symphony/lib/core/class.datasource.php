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
	
	##Interface for datasouce objects
	Class DataSource Extends Cacheable{
		
		var $_parent;
		var $_env;
		var $_db;
		var $_cache_sections;
		
		function __construct($args){
			$this->_env = $args['env'];	
			parent::__construct($args);		
			$this->__processXMLFields();
			$this->__processCustom();
			$this->__isUrlParamValuesPresent();
		}
		
		function __findHandle(){
			$classname = get_class($this);
			return strtolower(substr($classname, 2));
		}
		
		## This function is required in order to edit it in the data source editor page. 
		## Do not overload this function if you are creating a custom data source. It is only
		## used by the data source editor
		function allowEditorToParse(){
			return false;
		}
				
		## This function is required in order to identify what type of data source this is for
		## use in the data source editor. It must remain intact. Do not overload this function into
		## custom data sources.
		function getType(){
			return NULL;
		}
				
		##Static function
		function about(){		
		}
		
		function preview($param, $limit = 5){
			$param += array("limit" => $limit);			
			return $this->grab($param);			
		}
		
		function grab($param=array()){
		}
		
		function __forceEmptySet(){
			return (!isset($this->_urlParamPresent) || $this->_urlParamPresent || $this->_dsFilterFORCEEMPTYSET != 'yes' ? false : true);
		}
		
		function __isUrlParamValuesPresent(){
			
			## _urlParamPresent is set to true if there is a URL schema present, and 
			## there is at least one value set or a GET variable is present. if there 
			## is no URL schema, _urlParamPresent is not created. False means there 
			## is a URL schema, but no values present
			
			if(!is_array($this->_env['get'])) $this->_env['get'] = array();
			if(!is_array($this->_env['url'])) $this->_env['url'] = array();
			
			$vars = array_merge($this->_env['url'], $this->_env['get']);
							
			if(is_array($vars) && !empty($vars)){
				
				$this->_urlParamPresent = false;
	
				foreach($vars as $name => $val)
					if($val != ''){ $this->_urlParamPresent = true; return; }
				
			}
			
			return;
		}
		
		function __addChildFieldsToXML($fields, &$obXML, $fieldindex=NULL){
			foreach($fields as $key => $data){
				
				$attribute = false;
				$type = NULL;
				
				if(is_array($data))
					list($value, $type) = $data;
				else
					$value = $data;
									
				$XMLFields = $this->_dsFilterXMLFIELDS;				
				if($fieldindex)	$XMLFields = $this->_dsFilterXMLFIELDS[$fieldindex];	
					
				if(@in_array($key, $XMLFields)){
					if($type == "attr")
						$obXML->setAttribute($key, $value);
					
					elseif(is_object($value))
						$obXML->addChild($value);
							
					else	
						$obXML->addChild(new XMLElement($key, $value));	
				}
			}			
		}
		
		function __processXMLFields(){
			
			if(!is_array($this->_dsFilterXMLFIELDS) || empty($this->_dsFilterXMLFIELDS))
				return;
				
			$tmp = array();

			foreach($this->_dsFilterXMLFIELDS as $f){				
				if(strpos($f, "::") !== false){
					$bits = preg_split('/::/', $f, -1, PREG_SPLIT_NO_EMPTY);
					
					if(!isset($tmp[$bits[0]])) $tmp[$bits[0]] = array();
					
					$tmp[$bits[0]][] = $bits[1];
					
				}else
					$tmp[] = $f;
			}
			
			$this->_dsFilterXMLFIELDS = $tmp;
		}
		
		function __isDefineNotClause($define){
			return (General::substr_f($this->{"_$define"}) == '!');
		}

		function __processCustom(){

			$context = $this->_env['url'];
			$tmp = array();
			
			if(!is_array($context) || empty($context)) return;
			
			if(isset($this->_dsFilterCUSTOM) && is_array($this->_dsFilterCUSTOM) && !empty($this->_dsFilterCUSTOM)){

				foreach($this->_dsFilterCUSTOM as $id => $value){
					$result = (General::substr_f($value) != '$' ? $value : $context[str_replace('$', '', $value)]);
					if($result != '') $tmp[$id] = $result;
				}
				
				$this->_dsFilterCUSTOM = $tmp;
				
			}
			
		}		
						
		function __resolveDefine($define, $isList = false){
			$result = NULL;
			
			if($define != "" && isset($this->{"_$define"})){
				$context = $this->_env['url'];
				
				if($this->__isDefineNotClause($define))
					$define_val = substr($this->{"_$define"}, 1);
				else
					$define_val = $this->{"_$define"};				
			
				if(General::substr_f($define_val) == '$' && $this->_parent->_param[str_replace('$', '', $define_val)]){
					return $this->_parent->_param[str_replace('$', '', $define_val)];
				}
			
				if($isList){
					
					$bits = preg_split('/[\\s]{0,},[\\s]{0,}/', trim($define_val), -1, PREG_SPLIT_NO_EMPTY);
					$bits = array_map("trim", $bits);
					
					if(is_array($bits) && !empty($bits)){
						$result = array();
						foreach($bits as $bit){
							$result[] = (General::substr_f($bit) != '$' ? $bit : $context[str_replace('$', '', $bit)]);
						}
						
						$result = @array_flip($result);
						$result = @array_flip($result);
					}
					
				}else{
					$result = (General::substr_f($define_val) != '$' ? $define_val : $context[str_replace('$', '', $define_val)]);
					
				}
			}
			
			return $result;
		}
		
	}
	
?>