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

    Class DatasourceManager extends Manager{
	    
	    var $_pageDatasources;
	    
        function __construct($parent){
			parent::__construct($parent);			
			$this->_pageDatasources = array();
        }
        
	    function __find($name){
		 
		    if(@is_file(DATASOURCES . "/data.$name.php")) return DATASOURCES;
		    else{	      
				$structure = General::listStructure(CAMPFIRE, array(), false, "asc", CAMPFIRE);
				$owners = $structure['dirlist'];
				
				if(is_array($owners) && !empty($owners)){
					foreach($owners as $o){
						$services = General::listStructure(CAMPFIRE . "/$o", array(), false, "asc", CAMPFIRE . "/$o");
						
						if(is_array($services['dirlist']) && !empty($services['dirlist'])){
							foreach($services['dirlist'] as $s)
								if(@is_file(CAMPFIRE . "/$o/$s/data-sources/data.$name.php")) return CAMPFIRE . "/$o/$s/data-sources";	
						}
					}	
				}		    
	    	}	
	    		    
		    return false;
	    }
        
		function __getHandleFromFilename($filename){
			return str_replace(array('data.', '.php'), '', $filename);
		}

        function __getClassName($name, $owner = NULL){
	        return "ds" . $name;
        }
        
        function __getClassPath($name, $owner = NULL){
	        return $this->__find($name);
        }
        
        function __getDriverPath($name, $owner = NULL){	        
	        return $this->__getClassPath($name) . "/data.$name.php";
        }   

        function addDatasource($name, $param=array()){
	        $this->_pageDatasources[] = array($name, $param);
        }
        
        function flush(){
	        parent::flush();
	        $this->_pageDatasources = array();	        
        }   
            
        function renderData(&$xml, $param=array()){
	        
			$called_data_sources = array();

			if(@is_array($this->_pageDatasources) && !empty($this->_pageDatasources)){	
				foreach($this->_pageDatasources as $d){
					
					$dsName = $dsParam = NULL;
					list($dsName, $dsParam) = $d;

					if(!@is_file($this->__getDriverPath($dsName)))
						$this->_parent->fatalError("Specified datasource '".$dsName."' could not be loaded");
						
					$classname = $this->__getClassName($dsName);
					
					if(!@in_array($classname, $called_data_sources)){					
												
						$data =& $this->create($dsName, $param);

						if($dsParam['preview'])
							$result = $data->preview($dsParam);
							
						else
							$result = $data->grab($dsParam);
														
						if(@is_object($result))
							$xml->addChild($result);							
					
						else
							$xml->setValue($xml->_value . CRLF . $result);	
						
						$called_data_sources[] = $classname;
						
						unset($data);
					}					
				}
			}      
        }     
               
        function listAll(){
	        
			$result = array();
			$people = array();
			
	        $structure = General::listStructure(DATASOURCES, '/data.[\\w-]+.php/', false, "asc", DATASOURCES);
	        
	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){		        
	        	foreach($structure['filelist'] as $f){
		        	$f = str_replace(array("data.", ".php"), "", $f);					        	
					if($about = $this->about($f)){

					  	$classname = $this->__getClassName($f);   
				    	$path = $this->__getDriverPath($f);

						$can_parse = false;
						$type = NULL;
						
				    	if(is_callable(array($classname,'allowEditorToParse')))
							$can_parse = @call_user_func(array(&$classname, "allowEditorToParse"));

						if(is_callable(array($classname,'getType')))	
							$type = @call_user_func(array(&$classname, "getType"));
						
						$about['can_parse'] = $can_parse;
						$about['type'] = $type;
						$result[$f] = $about;		

					}
				}
			}
			
			$structure = General::listStructure(CAMPFIRE, array(), false, "asc", CAMPFIRE);
			$owners = $structure['dirlist'];
			
			if(is_array($owners) && !empty($owners)){
				foreach($owners as $o){										
					$services = General::listStructure(CAMPFIRE . "/$o", array(), false, "asc", CAMPFIRE . "/$o");
					
					if(is_array($services['dirlist']) && !empty($services['dirlist'])){
						foreach($services['dirlist'] as $s){		
							
							$tmp = General::listStructure(CAMPFIRE . "/$o/$s/data-sources", '/data.[\\w-]+.php/', false, "asc", CAMPFIRE . "/$o/$s/data-sources");
											
					    	if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){		        
					        	foreach($tmp['filelist'] as $f){					        	
						        	$f = str_replace(array("data.", ".php"), "", $f);			
						        		        	
										if($about = $this->about($f, NULL, CAMPFIRE . "/$o/$s/data-sources")){
					
											$can_parse = false;
											$type = NULL;
											
											$about['can_parse'] = $can_parse;
											$about['type'] = $type;
											$result[$f] = $about;	
											

										}
								}
							}
						}
					}					
				}	
			}

			ksort($result);
			return $result;	        
        }
               
        ##Creates a new campfire object and returns a pointer to it
        function &create($name, $param=array(), $slient=false){
	        
	        $classname = $this->__getClassName($name);	        
	        $path = $this->__getDriverPath($name);
	        
	        if(!@is_file($path)){
		        if(!$slient && @is_object($this->_log))
		        	$this->_log->pushToLog("DATASOURCE: Could not find data-source at location '$path'", SYM_LOG_ERROR, true);
		        	
		        return false;
	        }
	        
			if(!class_exists($classname))									
				require_once($path);
			
			if(!@is_array($param)) $param = array();	
				
			if(!isset($param['parent'])) $param['parent'] = $this->_parent;	
			
			##Create the object
			$this->_pool[] =& new $classname($param);	
								
			return end($this->_pool);
	        
        }        
         
    }
    
?>