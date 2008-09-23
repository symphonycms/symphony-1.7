<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

	Class formatterSimpleHtml Extends TextFormatter{
		
		function __construct($args = array()){
			parent::__construct($args);
		}
		
		function about(){		
			return array("handle" => "simplehtml",
						 "name" => "Simple HTML",
						 "description" => "Symphony Simple HTML text formatter for Entries and Comments",
						 "author" => "Alistair Kearney",
						 "version" => "1.0",
						 "release-date" => "2005-11-26 13:55:00");						 
		}	
			
		function run($string){
			require_once(TOOLKIT . "/class.simplehtml.php");
			$simplehtml = new SimpleHTML;						
			return $simplehtml->process($string, NULL, false);						
		}		
		
	}

?>