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
    
    function Librarian($dir, $exclude=array(), $priority=array(), $recurse=true) {
        
        if(is_array($priority) && !empty($priority)){
            foreach($priority as $p){
    
                if(@is_file($dir . "/" . $p))
                    require_once($dir . "/" . $p);
            
            }
        }
        
        if($handle = @opendir($dir)) {
            
            while(false !== ($file = readdir($handle))) {
                
                $parts = explode(".", $file);
                
                if(end($parts) == "php" && !@in_array($file, $exclude) && !@in_array($file, $priority)){
                    require_once($dir . "/" . $file);
                    
                }elseif(!preg_match('/_vti/i', $file) && @is_dir($dir . "/" . $file) && !in_array($file, array(".", "..")) && $recurse == true)
                    Librarian($dir . "/" . $file);
                    
            }
            
            closedir($handle);
            
        }
    }
    
?>