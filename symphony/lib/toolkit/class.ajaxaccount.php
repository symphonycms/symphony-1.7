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

	require_once(LIBRARY . "/core/class.gateway.php");
	require_once(LIBRARY . "/core/class.xmldoc.php");
	
	Class ajaxAccount extends Gateway{

	    var $_settings;
	    
        function ajaxAccount($settings){	        
            $this->_settings = $settings;
            $this->setopt("URL", rtrim($this->_settings["symphony"]["acct_server"], '/') . '/');
            $this->setopt("POST", 1);        
        }
        
        ##Abstract the curler function so we can add some better error handling
        function grab($action){

            $mandatory_fields = array("_a" => strtoupper($action),
                                      "_DOMAIN" => "symphony.local",
                                      "_b" =>$this->_settings["symphony"]["build"]
                                );
                        
            foreach($mandatory_fields as $key => $val)
                $d[] = $key . "=" . urlencode($val);
              
            $mandatory_fields = implode("&", $d);
                        
            if(trim($this->_postfields) != "")
                $mandatory_fields .= "&" . $this->_postfields;
            
            $this->setopt("POSTFIELDS", $mandatory_fields);
            
            $data = $this->exec();   

            $this->flush();
            
            return $data;
        }
            
        function processServerData($data, $return_array=true, $is_gzip=true){
                                    
            if(empty($data))
                return false;
            
            if($return_array){
                $xml = new XmlDoc();
                $xml->parseString($data);
                
                return($xml->getArray());
            }
            
            return true;
            
        }
    }
    
?>