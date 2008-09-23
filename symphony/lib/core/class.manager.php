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

    Class Manager extends Object{
	    var $_parent;
	    var $_pool;
	    var $_log;
		var $_db;
	    
        function __construct(&$args){

			if(is_array($args) && isset($args['parent'])){	
	        	$this->_parent =& $args['parent'];
			}	
			
			## This next part is to retain support for some of the older
			## campfire services, where they would pass their parent object 
			## reference directly to this superclass. Naughty Naughty.
			elseif(is_object($args)){
				$object_type = strtolower(get_class($args));

				## Site object passed directly
				if($object_type == 'site'):
					$this->_parent =& $args;
				
				## Admin object passed directly
				elseif($object_type == 'admin'):
					$this->_parent =& $args;
					
				## Unknown Type
				else:
					die("<strong>Fatal error:</strong> Unsupported class type '$object_type' passed to Manager. Please disable any Campfire services you might be using as they could be using out of date code.");
				
				endif;
				
			}
					
	        $this->_db = $this->_parent->_db;
			
	        if(!@is_object($this->_parent->log))
	        	$this->_log =& new SymphonyLog(array_merge($this->_parent->_config->_vars, array("log_path" => LOGS . "/" . date("Ymd") . ".log")));
	        
	        else
	        	$this->_log =& $this->_parent->log;
	        	
	        $this->_pool = array();
        }
        
        function __destruct(){
			
			if(is_array($this->_pool) && !empty($this->_pool)){	    
		        foreach($this->_pool as $o){
			     	unset($o);   
		        }
			}
			
        }
        
        function flush(){
	        $this->_pool = array();	        
        }  
        
        ##Returns the about details of a service
        function about($name, $owner=NULL, $path=NULL){

	        $classname = $this->__getClassName($name, $owner);   
	        $path = $this->__getDriverPath($name, $owner);

			if(!@file_exists($path)) return false;

			require_once($path);

			$handle = $this->__getHandleFromFilename(basename($path));

	        if($about = @call_user_func(array(&$classname, "about")))			
				return array_merge($about, array('handle' => $handle));	
			
			return false;
									        
        } 
                     
        function __getClassName($name, $owner = NULL){
        }
        
        function __getClassPath($name, $owner = NULL){
        }
        
        function __getDriverPath($name, $owner = NULL){
        }        
        
		function __getHandleFromFilename($filename){
		}

        function listAll(){
        }
               
        function &create($name, $owner=NULL, $param=array(), $slient=false){
        }       
        
    }
    
?>