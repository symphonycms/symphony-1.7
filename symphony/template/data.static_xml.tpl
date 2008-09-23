<?php

	Class ds<!-- CLASS NAME --> Extends DataSource{
		
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
			return 'static_xml';
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

			$xml = <<<XML
<!-- STATIC-XML -->
XML;
			
			return CRLF . trim($xml) . CRLF;
			
		}		
	}

?>