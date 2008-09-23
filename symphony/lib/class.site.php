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
	
	Class Site Extends Cacheable{
		
		var $_config;
		var $_profiler;
		var $_db;
		var $_result;
		var $_page;
		var $_env;
		var $_parentPath;
		var $_xsl_final;
		var $_xml_final;
		var $_verbose;
		var $_events;
		var $_EventManager;
		var $_DatasourceManager;
		var $_TextFormatterManager;
		var $_CampfireManager;
		var $_preview;
		var $_date;
		var $_headers;
		
		function Site($page, $db, $config, &$profiler, $verbose=true){
			
			$this->_page_raw = $page;
			$this->_db = $db;
			$this->_config = $config;
			$this->_profiler = $profiler;
			$this->_result = NULL;
			$this->_page = NULL;
			$this->_xsl_final = NULL;
			$this->_xml_final = NULL;		
			$this->_events = NULL;	
			$this->_parentPath = NULL;
			$this->_verbose = $verbose;
			$this->_env = array();	
			$this->_preview = false;
			$this->_headers = array();
			$this->_CampfireManager = new CampfireManager(array('parent' => &$this));	

			$this->_date =& new SymDate($this->getConfigVar("time_zone", "region"), $this->getConfigVar("date_format", "region"), ($this->getConfigVar("dst", "region") == "yes" ? 1 : 0));
			
			if(!_XSLT_AVAILABLE_) $this->fatalError("<p>Your PHP Installation does not have an XSL processor available</p>");

		   	if(!$this->_db->isConnected())
		        $this->fatalError("<p>There was a problem establishing a connection to the MySQL server. Check that the details in your configuration file <code>/manifest/config.php</code> are correct.</p>");
		   
		    if(!$this->_db->getSelected())
		       $this->fatalError("<p>There was a problem establishing a connection to the specified database. Check that the details in your configuration file <code>/manifest/config.php</code> are correct.</p>");
			
			if($this->getConfigVar("status", "public") == "offline" && !$this->isLoggedIn()){
				if(!$maintenance = $this->_db->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_pages` WHERE `type` = 'maintenance' LIMIT 1"))
			 		$this->fatalError("<p>This site is currently in maintenance mode. Please check back later.</p>");
			 		
			 	else
			 		$page = $maintenance;
				
			}elseif(!$page){
				$index = $this->_db->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_pages` WHERE `type` = 'index' LIMIT 1");
				$page = $index;

			}
			
			$this->__resolovePage($page);
			
			if(!$this->_page){
				if(!$error_page = $this->_db->fetchVar('handle', 0, "SELECT `handle` FROM `tbl_pages` WHERE `type` = 'error' LIMIT 1")){
					##Make sure the browser gets the 404 header
					header ("HTTP/1.0 404 Not Found");
					$this->fatalError("<p>404 Page Not Found - The requested URL /".$page." was not found on this server.</p>");
				}
			 		
	 			$page = $error_page;
				$this->_page_type = 'error';
			 	$this->__resolovePage($page);	
			 		
		 	}
		 	
			$this->__initialisePageParam();
			$this->_EventManager =& new EventManager(array('parent' => &$this));
			$this->_DatasourceManager =& new DatasourceManager(array('parent' => &$this));
		 	
		}
		
		function setVerbose($verbose){
			$this->_verbose = $verbose;
		}
		
		function getDateObj($reset = true){
			if($reset) $this->_date->set();
			
			return $this->_date;
		}	
		
		function togglePreviewMode(){
			$this->_preview = !$this->_preview;
		}
		
		function fatalError($message=NULL, $heading=NULL, $show_footer=false){

			if(!$heading) $heading = "Symphony Error";
			if(!$message) $message = "An Unknown Error Has Occurred.";

			if(!$template = @file_get_contents(CORE . "/template/error"))
				die("<h1>$heading</h1><p>$message<hr /><em>".$_SERVER['SERVER_SIGNATURE']."</em></p>");	

			if($show_footer) 
				$message .= CRLF . '<p>If this problem continues, send us a support e-mail and we will endeavour to resolve the issue.</p>

				<a class="email" href="mailto:team@symphony21.com">team@symphony21.com</a>';	

			$pattern = array("<!-- URL -->", "<!-- HEADING -->", "<!-- MESSAGE -->");
			$replacements = array(URL, $heading, $message);
			$template = str_replace($pattern, $replacements, $template);
			die($template); 

	    }

		function addHeaderToPage($name, $value=NULL){
			$this->_headers[$name] = $value;
		}

		function renderHeaders(){
			
			if(headers_sent()) return;
			
			if(!is_array($this->_headers) || empty($this->_headers)) return;

			foreach($this->_headers as $name => $value){
				if($value) @header("$name: $value");
				else @header($name);
			}
		}
					
		function updateMetadata($class, $relation_id, $insert=true){
				
			$meta = array();
		
			$meta['class']	= $class;		
			
			$date = new SymDate($this->getConfigVar("time_zone", "region"));
			$date->set();
			$date->setFormat("Y-m-d H:i:s");
			
			if(!$insert){
				
				$meta['modified_date'] 		= $date->get(true, true);
				$meta['modified_date_gmt'] 	= $date->get(false, true);			
				$meta['modifier_id'] 		= $this->getAuthorID();
				$meta['modifier_ip']		= $_SERVER['REMOTE_ADDR'];
				$meta['referrer']			= $_SERVER['HTTP_REFERER'];		
				$this->_db->update($meta, "tbl_metadata", "WHERE `relation_id` = '".$relation_id."' AND `class` = '$class' LIMIT 1");
	
			}else{
				
				$meta['relation_id'] 		= $relation_id;
				$meta['creation_date'] 		= $date->get(true, true);
				$meta['creation_date_gmt'] 	= $date->get(false, true);			
				$meta['creator_ip']			= $_SERVER['REMOTE_ADDR'];
				$meta['referrer']			= $_SERVER['HTTP_REFERER'];
				$this->_db->insert($meta, "tbl_metadata");	
			}					
		}
		
		function __resolveParentPath($page_id){
			
			$path = array();
			
			$page = $this->_db->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `id` = '".$page_id."'");
			$this->_page_type = $page['type'];
			
			if($page['parent'] != NULL) {
			
				$next_parent = $page['parent'];
				
				while($parent = $this->_db->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `id` = '". $next_parent ."'")){
					
					array_unshift($path, $parent['handle']);
					$next_parent = $parent['parent'];
					
				}
			}
			
			return "/" . @implode("/", $path);
				
		}
			
		function isLoggedIn(){
			
			if(isset($_REQUEST['auth'])){
				
				$sql  = "SELECT * "
					  . "FROM `tbl_authors` "
				      . "WHERE `auth_token_active` = 'yes' 
								AND LEFT(MD5(CONCAT(`username`, `password`)), 8) = '".addslashes($_REQUEST['auth'])."'";
			}else{
				
				$args = unserialize(base64_decode($_COOKIE[__SYM_COOKIE_SAFE__]));
				
				$sql  = "SELECT * "
					  . "FROM `tbl_authors` "
				      . "WHERE `username` = '".addslashes($args['username'])."' "
				      . "AND `password` = '".addslashes($args['password'])."' ";		
			}

			$row = $this->_db->fetchRow(0, $sql);
					
			if(is_array($row) && !empty($row)) return $row['id'];
				
			return false;	
		}
		
		function __isValidPage($handle, $parent=NULL){
			
			$parent_id = $this->_db->fetchVar('id', 0, "SELECT `id` FROM `tbl_pages` WHERE `handle` = '$parent' LIMIT 1");
			$parent = ($parent_id ? "= '$parent_id'" : "IS NULL");

			return $this->_db->fetchRow(0, "SELECT `handle` FROM `tbl_pages` WHERE `handle` = '$handle' AND `parent` $parent LIMIT 1");
		}	
		
		function __isSchemaValid($page, $bits){
			
			$schema = $this->_db->fetchVar('url_schema', 0, "SELECT `url_schema` FROM `tbl_pages` WHERE `handle` = '".$page."' LIMIT 1");					
			$schema_arr = preg_split('/\//', $schema, -1, PREG_SPLIT_NO_EMPTY);		
			
			return (count($schema_arr) >= count($bits));
				
		}	
		
		function __resolovePage($path){
			
			$pathArr = preg_split('/\//', $path, -1, PREG_SPLIT_NO_EMPTY);				
			$prevPage = NULL;
			
			$validPage = true;
			$valid_page_path = array();
			$page_extra_bits = array();
			
			foreach($pathArr as $p){
				
				$x = false;
				
				if($validPage) $x = $this->__isValidPage($p, $prevPage);
					
				if($x) $valid_page_path[] = $p;
				else $page_extra_bits[] = $p;
				
				$prevPage = $p;
			}

			if(!$this->__isSchemaValid(end($valid_page_path), $page_extra_bits)) return;

			$row = $this->_db->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `handle` = '".end($valid_page_path)."' LIMIT 1");
			
			##Determine Final Page
			$this->_page = end($valid_page_path);
			$this->_pageTitle = $row['title'];	
						
			##Process the extra URL params
			$url_schema = preg_split('/\//', $row['url_schema'], -1, PREG_SPLIT_NO_EMPTY);
			
			foreach($url_schema as $var){
				$this->_env["url"][$var] = NULL;
			}
			
			for($ii = 0; $ii < count($page_extra_bits); $ii++){
			
				if($ii >= count($url_schema))
					$this->_env["extras"][] = $page_extra_bits[$ii];
				else
					$this->_env["url"][$url_schema[$ii]] = $page_extra_bits[$ii];
					
			}
			
			## Inject get information into the page env
			if(is_array($_GET) && !empty($_GET)){
			    foreach($_GET as $key => $val){				    
			        if($key != 'page') $this->_env['get'][$key] = $val;	
			    }
			}

			##Inject cookie information into the page env
			if(is_array($_COOKIE) && !empty($_COOKIE)){
				foreach($_COOKIE as $key => $val){
					$this->_env['cookie'][$key] = $val;
				}
			}
			
			$this->_parentPath = $this->__resolveParentPath($this->_db->fetchVar("id", 0, "SELECT `id` FROM `tbl_pages` WHERE `handle` = '".$this->_page."' LIMIT 1"));
			
			$expected_path = trim($this->_parentPath . "/" . $this->_page, "/");
			
			if(strpos(URL . "/" . $path, URL . "/" . $expected_path) === false) $this->_page = NULL;
			
		}
		
		function getCookieDomain(){
			$domain = parse_url_compat(URL, PHP_URL_PATH);
			$domain = '/' . trim($domain, '/');		
			return $domain;
		}

		function getConfigVar($name, $index=NULL){
			return $this->_config->get($name, $index);
		}	

		function removeConfigVar($name, $index=NULL){
			$this->_config->remove($name, $index);
		}

		function getXSL(){
			return $this->_xsl_final;
		}
		
		function getXML(){
			return $this->_xml_final;
		}
		
		function getTransformed(){
			return $this->_result;
		}		
		
		function __initialisePageParam(){
			
			$doctor = new XMLRepair;
			
			$cDate = new SymDate($this->getConfigVar("time_zone", "region"), "Y-m-d");
			
			$this->_param = array();
			$this->_param['root'] = URL;
			$this->_param['workspace'] = URL . "/workspace";
			$this->_param['current-page'] = $this->_page;
			$this->_param['page-title'] = $doctor->entities2hexadecimal($this->_pageTitle);
			$this->_param['parent-page'] = $this->_parentPath;
			$this->_param['today'] = $cDate->get(true, true, time() - date("Z"));
			$this->_param['website-name'] = $this->getConfigVar("sitename", "general");
			$this->_param['symphony-build'] = $this->getConfigVar("build", "symphony");
			
			if(is_array($_GET) && !empty($_GET)){
			    foreach($_GET as $key => $val){			    
			        if($key != 'page') $this->_param['url-' . $key] = $val;
			    }
			}			
			
		}
		
		function display($param=array(), $flag='TRANSFORMED', $registerHeaders=true){
			
			$row = $this->_db->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `handle` = '".$this->_page."' LIMIT 1");
				
			$full_caching = ($row['full_caching'] == 'yes' && $this->getConfigVar("caching", "public") == 'on');	
				 
			if($full_caching){
				
				$refresh_rate = max(1, intval($row['cache_refresh_rate']));
				
				## This modifier will allow the cache to update
				$modifier = ceil(ceil(time() * (1/60)) * (1 / $refresh_rate));
	
				## Create the hash id for cache lookup
				$hash_id = md5(get_class($this) . $_REQUEST['page'] . $modifier);
				
				## Check the cache		
				if($cache = $this->check_cache($hash_id)){
					$this->_result = $cache;
					return $cache;
				}

			}
						
			if($this->_result == NULL) $this->render($param);
			
			switch($flag){
			
				case 'XML':
				case 'xml':
					return $this->_xml_final;
					break;
					
				case 'XSL':
				case 'xsl':
					return $this->_xsl_final;
					break;
					
			}

			##Write To Cache
			if($full_caching){
				$this->write_to_cache($hash_id, $this->_result, array("pages"));
				return $this->_result;
			}			
				
			if($registerHeaders) $this->renderHeaders();		
			return $this->_result;
		}		
		
		function buildXML($page_handle=NULL, $utilities=NULL, $indent=false, $caching=true){
			
			$events = new XMLElement("events");
			$xml = new XMLElement("data");
			$xml->setIncludeHeader(true);
								
			$page_handle = ($page_handle ? $page_handle : $this->_page);
								
			$sql = "SELECT t1.*, 
						   t2.events as `master_events`, 
						   t2.data_sources as `master_data_sources`
						
					FROM `tbl_pages` AS `t1`
					LEFT JOIN `tbl_masters` AS `t2` ON t1.`master` = concat(t2.`name`, '.xsl')
					WHERE t1.`handle` = '".$page_handle."' LIMIT 1";

			if(!$page = $this->_db->fetchRow(0, $sql))
				$this->fatalError("Requested page '".$page_handle."' could not be found");			
			
			$page_data = preg_split('/,/', $page['data_sources'] . "," . $page['master_data_sources'], -1, PREG_SPLIT_NO_EMPTY);
			$page_events = preg_split('/,/', $page['events'] . "," . $page['master_events'], -1, PREG_SPLIT_NO_EMPTY);

			$page_data = General::array_remove_duplicates($page_data);
			$page_events = General::array_remove_duplicates($page_events);

			##EVENTS
			if(is_array($page_events) && !empty($page_events)){	
				foreach($page_events as $e){					
					$this->_EventManager->addEvent($e);	
				}
			}			
			
			$this->_EventManager->fireEvents($events, array('parent' => $this, 'env' => $this->_env));
			$this->_EventManager->flush();			
			
			$xml->addChild($events);
			$this->_events = $events;
			
			##DATASOURCES
			$dsParam = array("indent-depth" => 1, 
							 "caching" => $caching, 
							 "indent" => $indent, 
							 "preview" => $this->_preview,
							 "allow_optimise" => ($page['optimise_xml'] == "yes" ? 'on' : 'off'));
							 
			if(is_array($page_data) && !empty($page_data)){	
				foreach($page_data as $d){			
					$this->_DatasourceManager->addDatasource($d, $dsParam);	
				}
			}	

			$this->_DatasourceManager->renderData($xml, array('parent' => $this, 'env' => $this->_env));
			$this->_DatasourceManager->flush();			
			
			##Generate the final XML
			$this->_xml_final = $xml->generate($indent, 0);	
			
			$doctor = new XMLRepair;
			$doctor->repair($this->_xml_final);
			unset($doctor);
			
			$this->_xml_final = trim($this->_xml_final);
			
			return $this->_xml_final;
			
		}
		
		function buildXSL($page_handle=NULL, $utilities=NULL, $param=NULL){

			$xsl = array();			

			$sql = "SELECT t1.*, 
						   t2.events as `master_events`, 
						   t2.data_sources as `master_data_sources`
						
					FROM `tbl_pages` AS `t1`
					LEFT JOIN `tbl_masters` AS `t2` ON t1.`master` = concat(t2.`name`, '.xsl')
					WHERE t1.`handle` = '".$this->_page."' LIMIT 1";
			
			if(!$page = $this->_db->fetchRow(0, $sql))
				$this->fatalError("Requested page '".$this->_page."' could not be found");

			$page_data = preg_split('/,/', $page['data_sources'] . "," . $page['master_data_sources'], -1, PREG_SPLIT_NO_EMPTY);
			$page_data = General::array_remove_duplicates($page_data);

			$page_events = preg_split('/,/', $page['events'] . "," . $page['master_events'], -1, PREG_SPLIT_NO_EMPTY);
			$page_events = General::array_remove_duplicates($page_events);
			
			$master = $page['master'];
				
			if(!is_array($utilities) || empty($utilities)){
										
				$utilities = $this->_db->fetch("SELECT DISTINCT t1.* 
										 FROM `tbl_utilities` as t1
										 LEFT JOIN `tbl_utilities2datasources` as t2 ON t1.id = t2.utility_id
										 LEFT JOIN `tbl_utilities2events` as t3 ON t1.id = t3.utility_id
										 WHERE (t2.`data_source` IS NULL AND t3.`event` IS NULL)
										 OR (t2.`data_source` IN ('".@implode("', '", $page_data)."') 
										 OR t3.`event` IN ('".@implode("', '", $page_events)."'))");		
																						
				$utilities = General::array_remove_duplicates($utilities);
			}
			
			if(!$xsl['page'] = @file_get_contents(WORKSPACE . "/pages/" . $this->_page. ".xsl"))
				$this->fatalError("Specified page '".$this->_page."' could not be loaded");
			
			if(is_array($utilities) && !empty($utilities)){	
				foreach($utilities as $u){
					if(!@file_exists(WORKSPACE . "/utilities/" . $u['handle']. ".xsl"))
						$this->fatalError("Specified utility '".$u['name']."' could not be loaded");
						
					$xsl['utilities'][] = file_get_contents(WORKSPACE . "/utilities/" . $u['handle']. ".xsl");
				}
			}
			
			if($master != NULL){				
					
				if(!$xsl['master'] = @file_get_contents(WORKSPACE . "/masters/" . $master))
					$this->fatalError("Specified master '".$master."' could not be loaded");
				
				$xsl['master'] = trim($xsl['master']);
				$xsl['master'] = str_replace("</xsl:stylesheet>", "", trim($xsl['master']));
				
			}else{
				
				$xsl['page'] = str_replace("</xsl:stylesheet>", "", trim($xsl['page']));	
			}
				
			$this->_xsl_final = trim($xsl['master']) . CRLF . $xsl['page'] . CRLF . @implode(CRLF, array_map("trim", $xsl['utilities'])) . "\n</xsl:stylesheet>";
			
			##Compile the final stylesheet
			$xsl_param = NULL;
			
			if(is_array($param) && !empty($param)){			
				foreach($param as $key => $val){
					$val = preg_replace('/[\'"]/', '', stripslashes($val));
					$xsl_param .= '    <xsl:param name="'.$key.'" select="\''.$val.'\'" />' . CRLF;
			    }
			    
				if(!_USING_SABLOTRON_) 
					$xsl_param = '<!--' . CRLF 
								. '    // Parameters are defined via PHP in the background //'
								. CRLF . $xsl_param . '-->';
			
				preg_match_all('/(<xsl:output[\\s\\w="\'-\/:.@]*\/>)/', $this->_xsl_final, $result, PREG_SET_ORDER);	
				
				$this->_xsl_final = str_replace($result[0][1], $result[0][1] . CRLF . CRLF . $xsl_param, $this->_xsl_final);
			}

			$this->_xsl_final = trim($this->_xsl_final);
			
			return $this->_xsl_final;
		}
			
		function render($param){

			$doctor = new XMLRepair;			
			
			$cDate = new SymDate($this->getConfigVar("time_zone", "region"), "Y-m-d");
							
			if(!is_array($param)) $param = array();
			
			if(is_array($this->_env["url"]))
				$param = array_merge($param, $this->_env["url"]);
				
			if(is_array($param)) $param = array_merge($param, $this->_param);
			else $param = $this->_param;
			
			## Depending on the page type, generate appropriate headers			
			if($this->_page_type == 'error')
				$this->addHeaderToPage("HTTP/1.0 404 Not Found");
				
			elseif($this->_page_type == 'xml')
				$this->addHeaderToPage("Content-Type", "text/xml; charset=utf-8");

			
			####
			# Delegate: PreRender
			# Description: Prior to anything being rendered. Both profiler and XSL params are passed as references.
			#              Altering the param arry will effect the page XSL		
			$this->_CampfireManager->notifyMembers('PreRender', 
												   '/frontend/', 
												   array(
												  		 	'page-param' => &$param,
												  		 	'profiler' => &$this->_profiler
														)
												   );	
	
			
			$this->buildXSL(NULL, NULL, $param);
			
			####
			# Delegate: XSLRender
			# Description: XSL has been rendered and can be manipulated		
			$this->_CampfireManager->notifyMembers('XSLRender', '/frontend/', array('xsl' => &$this->_xsl_final));		
			
			$this->_profiler->sample("XSL Creation", PROFILE_LAP);
			
			
			$this->_xml_final = $doctor->entities2hexadecimal($this->buildXML(NULL, NULL, true, ($this->getConfigVar("caching", "public") == 'on' ? true : false)));

			####
			# Delegate: XMLRender
			# Description: XML has been rendered and can be manipulated				
			$this->_CampfireManager->notifyMembers('XMLRender', '/frontend/', array('xml' => &$this->_xml_final));
															
			$this->_profiler->sample("XML Creation", PROFILE_LAP);
			
			$output =& new XsltProcess($this->_xml_final, $this->_xsl_final);
						
			$this->_result = $output->process(null, null, $param);	

			if($this->_verbose && $output->isErrors()){
				
				$message = "<p>Some errors were encountered while trying to render this page. Please see the list below for details.</p><dl>" . CRLF;
				
				while($e = $output->getError()){
					$message .= "<dt>" . ($e["type"] != NULL ? strtoupper($e["type"]) . " processing error:" : "") . "</dt><dd>" . $e["message"] . "</dd>" . CRLF;
					
				}
				$message .= "</dl>" . CRLF;
				
				
				$this->fatalError($message . ($this->isLoggedIn() ? '<p>Check the <a href="?debug">page debug information here</a></p>' : ""), "Symphony XSLT Processing Error");
			}
			
			$this->_profiler->sample("XSLT Transformation", PROFILE_LAP);
			
			#Record the render time
			$this->_profiler->sample("Total Page Render Time");
			
			if($this->getConfigVar("status", "public") == "offline" && $this->isLoggedIn()){
			
				$this->_result .= "\n <!-- \n\nPROFILER INFO \n\n";
				foreach($this->_profiler->retrieve() as $x){	
					list($msg, $time, $start) = $x;		
					$this->_result .= "> $msg: $time sec\n";			
				}
	
				$this->_result .= "\n\n Page Parameters \n\n";
				
				foreach($param as $key => $val)				
					$this->_result .= "$key => $val \n";
				
				$this->_result .= "\n\n\nEvents XML\n\n" . $this->_events->generate(true) . "\n\n -->\n";
			}
			
			####
			# Delegate: PostRender
			# Description: Page code has been created and can me manipulated. Page parameters and profiler are also provided.		
			$this->_CampfireManager->notifyMembers('PostRender', 
												   '/frontend/', 										   
												   array(
												  		 	'page-param' => $param,
												  		 	'profiler' => $this->_profiler,
															'output' => &$this->_result
														)
													);	
			
		}
		
	}
	
?>