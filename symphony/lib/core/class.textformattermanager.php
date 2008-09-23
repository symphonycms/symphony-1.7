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

    Class TextformatterManager extends Manager{
	    
        function __construct($parent){
			parent::__construct($parent);                
        }    
               
	    function __find($name){
		 
		    if(@is_file(TEXTFORMATTERS . "/formatter.$name.php")) return TEXTFORMATTERS;
		    else{	      
				$structure = General::listStructure(CAMPFIRE, array(), false, "asc", CAMPFIRE);
				$owners = $structure['dirlist'];
				
				if(is_array($owners) && !empty($owners)){
					foreach($owners as $o){
						$services = General::listStructure(CAMPFIRE . "/$o", array(), false, "asc", CAMPFIRE . "/$o");
						
						if(is_array($services['dirlist']) && !empty($services['dirlist'])){
							foreach($services['dirlist'] as $s)
								if(@is_file(CAMPFIRE . "/$o/$s/text-formatters/formatter.$name.php")) return CAMPFIRE . "/$o/$s/text-formatters";	
						}
					}	
				}		    
	    	}	
	    		    
		    return false;
	    }
	            
        function __getClassName($name, $owner = NULL){
	        return "formatter" . $name;
        }
        
        function __getClassPath($name, $owner = NULL){
	        return $this->__find($name);
        }
        
        function __getDriverPath($name, $owner = NULL){	        
	        return $this->__getClassPath($name) . "/formatter.$name.php";
        }          

		function __getHandleFromFilename($filename){
			return str_replace(array('formatter.', '.php'), '', $filename);
		}
        
        function listAll(){
	        
			$result = array();
			$people = array();
			
	        $structure = General::listStructure(TEXTFORMATTERS, '/formatter.[\\w-]+.php/', false, "asc", TEXTFORMATTERS);
	        
	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){		        
	        	foreach($structure['filelist'] as $f){
		        	$f = str_replace(array("formatter.", ".php"), "", $f);					        	
					if($about = $this->about($f))
						$result[$f] = $about;	

				}
			}
			
			$structure = General::listStructure(CAMPFIRE, array(), false, "asc", CAMPFIRE);
			$owners = $structure['dirlist'];
			
			if(is_array($owners) && !empty($owners)){
				foreach($owners as $o){										
					$services = General::listStructure(CAMPFIRE . "/$o", array(), false, "asc", CAMPFIRE . "/$o");
					
					if(is_array($services) && !empty($services)){
						foreach($services['dirlist'] as $s){		
							
							$tmp = General::listStructure(CAMPFIRE . "/$o/$s/text-formatters", '/formatter.[\\w-]+.php/', false, "asc", CAMPFIRE . "/$o/$s/text-formatters");
								
					        if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){		        
					        	foreach($tmp['filelist'] as $f){						        	
						        	$f = str_replace(array("formatter.", ".php"), "", $f);					        	
									if($about = $this->about($f, NULL, CAMPFIRE . "/$o/$s/text-formatters"))
										$result[$f] = $about;											
				
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
		        	$this->_log->pushToLog("FORMATTER: Could not find text formatter at location '$path'", SYM_LOG_ERROR, true);
		        	
		        return false;
	        }
	        
			if(!@class_exists($classname))									
				require_once($path);
			
			if(!@is_array($param)) $param = array();	
				
			if(!isset($param['parent'])) $param['parent'] = $this->_parent;	
			
			##Create the object
			$this->_pool[] =& new $classname($param);	
								
			return end($this->_pool);
	        
        }       
        
    }
    
?>