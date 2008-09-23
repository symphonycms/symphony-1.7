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

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Fatal Error</h2><p>You cannot directly access this file</p>");
	
	Class Configuration {
		
		var $_vars = array();
		var $_forceLowerCase = false;
		
		function Configuration($forceLowerCase = false){
			$this->_forceLowerCase = $forceLowerCase;
		}
		
		function flush(){
			$this->_vars = array();	
		}
		
		function get($name, $index=null) {
			
			if($this->_forceLowerCase) { $name = strtolower($name); $index = strtolower($index); }
			
			
			if($index){
				return $this->_vars[$index][$name];
			}
				
			return $this->_vars[$name];
		}
		
		function set($name, $val, $index=null) {
			
			if($this->_forceLowerCase) { $name = strtolower($name); $index = strtolower($index); }
			
			if($index){
				$this->_vars[$index][$name] = $val;
				
			}else{
				$this->_vars[$name] = $val;
			}
		}
		
		function remove($name, $index=NULL){
			
			if($this->_forceLowerCase) { $name = strtolower($name); $index = strtolower($index); }
			
			if($index && isset($this->_vars[$index][$name]))
				unset($this->_vars[$index][$name]);
				
			elseif($this->_vars[$name])
				unset($this->_vars[$name]);
				
					
		}
				
		function setArray($arr){
			$this->_vars = array_merge($this->_vars, $arr);
		}
		
		function create($format="xml"){ //format can be xml or php
			
			$data = NULL;
			
			switch($format){
				
				case "xml":
					$data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>";
				
					foreach($this->_vars as $key => $attr){
					
						$data .= "\n\n\t<$key\n";
							
						foreach($attr as $a => $v){
						
							$data .= "\t\t".$a."=\"". trim($v) ."\"\n";
								
						}
						
						$data .= "\t/>\n\n";
					}
				
					$data .= "</configuration>";
					break;
					
				case "php":
				
					foreach($this->_vars as $set => $array) {
						
						if(is_array($array) && !empty($array)){
							foreach($array as $key => $val) {
								$data .= '$'."settings['".$set."']['".$key."'] = '".addslashes($val)."';\n";
							}
						}
					}
				
					break;
					
			}
			
			return (empty($data) ? false : $data);
		}
		
	}

?>