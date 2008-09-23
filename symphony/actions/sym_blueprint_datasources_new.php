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

	if(array_key_exists("save", $_POST['action']) || array_key_exists("done", $_POST['action'])) {	
		
		$fields = $_POST['fields'];
		
		$date = new SymDate($Admin->getConfigVar("time_zone", "region"), $Admin->getConfigVar("date_format", "region"));
		
		##Make sure all required fields are filled
		$required = array('name', 'source');
	
		for($i = 0; $i < count($required); $i++) {
			if(trim($fields[$required[$i]]) == "") {
				$errors[$required[$i]] = true;
			}
		}
		
		if(is_array($errors)){			
			define("__SYM_ENTRY_MISSINGFIELDS__", true);
			
		}else{	
				
			$defines = array();
			
			$fields['name'] = str_replace(array('\'', '"'), "", $fields['name']);
			
			$handle = General::createFileName($fields['name'], $Admin->getConfigVar('handle_length', 'admin'), '_');
			$rootelement = General::createFileName($fields['name'], $Admin->getConfigVar('handle_length', 'admin'), '-');
			
			$classname = ucfirst($handle);
			$source = $fields['source'];
			
			$var = array(
						'HANDLE' => $handle,
						'ROOT-ELEMENT' => $rootelement,
						'CLASS NAME' => $classname,
						'NAME' => $fields['name'],
						'DESCRIPTION' => "",
						'AUTHOR-NAME' => $Admin->getAuthorName(),
						'AUTHOR-WEBSITE' => General::validateURL(URL),
						'AUTHOR-EMAIL' => $Admin->getAuthorEmail(),
						'VERSION' => "1.0",
						'RELEASE DATE' => date("Y-m-d H:i:s", $date->get(true, false))
					);

			$xml_elements = array();		
					
			if(is_array($fields['xml-elements']) && !empty($fields['xml-elements'])){		
				foreach($fields['xml-elements'] as $f){
					
					$f = trim($f, '[]');
					$bits = preg_split('/\]\[/i', $f, -1, PREG_SPLIT_NO_EMPTY);
					
					list($group, $element) = $bits;
					
					$xml_elements[$group][] = $element;
					
				}
			}
			
			switch($source){	
									
				case "authors":
					$defines['status'] = $fields['status'];
					$defines['username'] = $fields['username'];				
					break;
					
				case "comments":			
					$defines['handle'] = $fields['handle'];
					$defines['year'] = $fields['year'];
					$defines['month'] = $fields['month'];
					$defines['day'] = $fields['day'];
					$defines['encode'] = ($fields['html_encode'] ? 'yes' : '');
					$defines['limit'] = $fields['max_records'];
					$defines['limit_months'] = $fields['max_months'];
					$defines['sort'] = $fields['sort'];	
					$defines['show_spam'] =  ($fields['show_spam'] ? 'yes' : '');
					$defines['section'] = $fields['comments'];
					$defines['forceemptyset'] = ($fields['force-empty-set'] ? 'yes' : '');	
					$defines['pagenumber'] = $fields['page_number'];					
					break;
					
				case "navigation":
					$defines['page'] = $fields['navigation']['handle'];
					$defines['max_depth'] = $fields['max_depth'];		
					break;

				case "options":
					$fields['source'] = 'customfield';
					list($section, $customfield) = explode('::', $fields['customfield']);
					$defines['customfield'] = $customfield;
					$defines['parentsection'] = $section;
					break;
					
				case "static_xml":
					$var['STATIC-XML'] = $fields['static_xml'];
					break;
					
				 ##Must be a custom section
				 default:	
				
					$defines['forceemptyset'] = ($fields['force-empty-set'] ? 'yes' : '');			
					$defines['handle'] = $fields['handle'];
					$defines['year'] = $fields['year'];
					$defines['month'] = $fields['month'];
					$defines['day'] = $fields['day'];
					$defines['encode'] = ($fields['html_encode'] ? 'yes' : '');
					$defines['limit'] = $fields['max_records'];
					$defines['limit_months'] = $fields['max_months'];
					$defines['sort'] = $fields['sort'];	
					$defines['includepostdated'] = ($fields['includepostdated'] ? 'yes' : '');
					$defines['format_type'] = $fields['format_type'];
					
					if(is_array($fields['custom'][$source]) && !empty($fields['custom'][$source])){

						foreach($fields['custom'][$source] as $key => $value){
							$defines['custom'][$key] = $value;	
						}

					}
					
					$var['SECTION NAME'] = $source;
					
					if($defines['format_type'] == 'list'){
						$fields['source'] = 'section_entries';
						$defines['pagenumber'] = $fields['page_number'];
						
					}elseif($defines['format_type'] == 'archive')
						$fields['source'] = 'section_entries_archive';
						
					else
						$fields['source'] = 'section_entries_archive_overview';					
					
					break;
				
			}

			$defines['xml-fields'] = $xml_elements[$source];

			$fields['name'] = $handle;
			$fields['body'] = file_get_contents(DOCROOT . "/symphony/template/data." . $fields['source'] . ".tpl");
			
			$defines_list = null;
			
			foreach($defines as $key => $val){
				if($key == 'custom'){

					if(is_array($val) && !empty($val)){

						$defines_list .= "\n\t\tvar \$_dsFilterCUSTOM = array(";

						foreach($val as $k => $v){
							if($v != '') $defines_list .= "\n\t\t\t\t\t\t'$k' => '$v',";
						}

						$defines_list .= "\n\t\t\t\t);";

					}
				}elseif($key == 'xml-fields'){
					if(is_array($val) && !empty($val)){

						$defines_list .= "\n\t\tvar \$_dsFilterXMLFIELDS = array(";

						foreach($val as $k => $v){
							if($v != '') $defines_list .= "\n\t\t\t\t\t\t'$v',";
						}

						$defines_list .= "\n\t\t\t\t);";

					}					
				
				}else{
						if(trim($val) != "")
							$defines_list .= "\n\t\tvar \$_dsFilter".strtoupper($key)." = '" . addslashes($val) . "';";
				}
					
			}
			
			if($defines_list == NULL) $var['DEFINES LIST'] = "## No Defines Specified" . CRLF;
			else $var['DEFINES LIST'] = $defines_list;
			
			foreach($var as $key => $val){
				if(trim($val) == '') $val = 'NULL';
				$fields['body']	= str_replace("<!-- $key -->", $val, $fields['body']);	
			}			

      		$file = DATASOURCES . "/data." . $handle . ".php";
      		       		
			##Duplicate
			if(@is_file($file)){
 				$Admin->pageAlert("duplicate", array("An Data Source", "name"), false, 'error'); 
			
			##Write the file
			}elseif(!is_writable(dirname($file)) || !$write = General::writeFile($file, $fields['body'], $Admin->getConfigVar("write_mode", "file")))
				$Admin->pageAlert("write-failed", array("Data Source"), false, 'error'); 			
				
			##Write Successful, add record to the database
			else{
			
				##Clean out the cache
				$Admin->flush_cache("ALL");	
							
				###
				# Delegate: Create
				# Description: After saving the datasource, the file path is provided and an array 
				#              of variables set by the editor
				$CampfireManager->notifyMembers('Create', CURRENTPAGE, array('file' => $file, 'defines' => $defines, 'var' => $var));			
								
				if(@array_key_exists("save", $_POST['action']))
			        General::redirect(URL . "/symphony/?page=/blueprint/datasources/edit/&file=$handle&_f=saved");
			
			    General::redirect(URL . "/symphony/?page=/blueprint/controllers/");	
															
			}
			
		}		
	}
?>
