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

	Class Admin extends Cacheable {

		var $pathArray   	     = array();
		var $_author_ID  	     = NULL;
		var $_author_Name 	     = NULL;
		var $_author_Super       = 0;
		var $_author_TextFormat  = NULL;
		var $_author		     = array();
		var $_pageTitle   	     = NULL;
		var $_sesStarted         = false;
		var $_Config             = NULL;
		var $_db			     = NULL;
		var $_cfm			     = NULL;
		var $_nav			     = array();
		var $_pageContents       = NULL;
		var $log                 = NULL;
		var $_date				 = NULL;
		var $_headers			 = array();
		var $Author				 = NULL;
		
		function __construct($args = NULL){
		
			## Check the config file is writable
			if(!is_writeable(CONFIG)) $this->fatalError(NULL, "<p>Symphony's configuration file is not writable. Please check permissions on <code>/manifest/config.php</code>.</p>", true, true);
		
			$this->_config = new Configuration(true);	
		
			if(isset($args['config_xml']))
				General::loadConfiguration($args['config_xml'], $this->_config, new XmlDoc);
			
			if(isset($args['config']))
				$this->_config->setArray($args['config']);	
				
			if($args['start_session'])
				$this->startSession();
			
			if($this->getConfigVar("prune_logs", "symphony"))
				$this->pruneLogs();

			$this->_date =& new SymDate($this->getConfigVar("time_zone", "region"), $this->getConfigVar("date_format", "region"), ($this->getConfigVar("dst", "region") == "yes" ? 1 : 0));	
		
			parent::__construct(array("parent" => $this));

		}
	
		function __destruct(){
		
			if(is_object($$his->_config)) $this->_config->flush();
			unset($this->_config);
			unset($this->log);
		
		}
	
		function getDateObj($reset = true){
			if($reset){
				$this->_date =& new SymDate($this->getConfigVar("time_zone", "region"),
				 							$this->getConfigVar("date_format", "region"), 
											($this->getConfigVar("dst", "region") == "yes" ? 1 : 0));
				
			}
		
			return $this->_date;
		}
	
		function pageAlert($err='default', $tokens=NULL, $custom=false, $type='notice'){
		
			if(!defined(($type == 'error' ? '__SYM_ERROR_MESSAGE__' : '__SYM_NOTICE_MESSAGE__'))){
			
				require_once(LANG . "/lang.english.php");
			
				if($err == 'default' || (!$custom && !isset($errors[$err]))){
					$err = "default"; 
					$tokens = array(URL."/symphony/?page=/settings/logs/view/&_l=".date("Ymd", $this->_date->get(false)));		
				}
			
				if($custom)
					$message = $err;
				
				else
					$message = "<strong>" . $errors[$err][0] . "</strong> " . $errors[$err][1];
			
				if($tokens && !is_array($tokens)){
					$tokens = array($tokens);
				}
			
				if(is_array($tokens) && !empty($tokens)){	
					for($ii = (count($tokens) - 1); $ii >= 0; $ii--)
						$message = str_replace("%" . ($ii+1), $tokens[$ii], $message);
				}
			
				define(($type == 'error' ? '__SYM_ERROR_MESSAGE__' : '__SYM_NOTICE_MESSAGE__'), $message);
					
			}		
		}
	
		function fatalError($heading=NULL, $message=NULL, $kill=false, $show_footer=false){
		
			if(!$heading) $heading = "Symphony System Error";
			if(!$message) $message = "An Unknown Error Has Occurred.";
		
			if($kill){
		
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
		
			$error = '
	    <form id="announcements" action="" method="get">
			<h2>Symphony System Error</h2>
	    	<h3>'.$heading.'</h3>		
			<p>'.$message.'</p>
		</form>';
	
			$GLOBALS['pageTitle'] = $heading;
			define("__SYM_FATAL_ERROR__", $error);	   
	   
	    }
	
		function resolvePagePath($page_id){
		
			$path = array();
		
			$page = $this->_db->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `id` = '".$page_id."' OR `handle` = '".$page_id."'");
		
			$path[] = $page['handle'];
		
			if($page['parent'] != NULL) {
		
				$next_parent = $page['parent'];
			
				while($parent = $this->_db->fetchRow(0, "SELECT * FROM `tbl_pages` WHERE `id` = '". $next_parent ."'")){
				
					array_unshift($path, $parent['handle']);
					$next_parent = $parent['parent'];
				
				}
			}
		
			return @implode("/", $path);
			
		}
		
		function setLog($log){
			$this->log = $log;	
		}
	
		function setCFM($cfm){
			$this->_cfm = $cfm;			
		}
	
		function processLogs($alert_level = SYM_LOG_ALL){
			$this->__checkForDatabaseErrors();
		}
	
		function __checkForDatabaseErrors(){
			$errors = $this->_db->debug();
		
			if(empty($errors) or !is_array($errors))
				return false;
		
			$error_list = "";
			
			foreach($this->_db->debug() as $e){
				extract($e);
				$this->log->pushToLog("Mysql Error $num in page '".$_GET['page']."': $msg ", SYM_LOG_ERROR, true);
				$error_list .= "<dt>MySQL Error $num</dt><dd>$msg</dd>" . CRLF;
			}
		
			$log_url = URL . "/symphony/?page=/settings/logs/view/&_l=" . date("Ymd", $this->_date->get(false));
		
			$this->fatalError(NULL, '<p>Some non-recoverable database errors occurred. Please check messages below for specifics.</p><dl>'.$error_list.'</dl>', true, true);	
		}
	
		function uninstall(){
			
			$errors = $notices = array();
						
			## REMOVE CAMPFIRE FOLDER
			General::rmdirr(DOCROOT . '/campfire', $errors, $notices);
		
			## REMOVE WORKSPACE FOLDER
			General::rmdirr(DOCROOT . '/workspace', $errors, $notices);
								
			## REMOVE MANIFEST FOLDER
			General::rmdirr(DOCROOT . '/manifest', $errors, $notices);
		
			@unlink(DOCROOT . '/.htaccess');
			
			return;
			
		}
	
		function pruneLogs($age=5){
		
			$logs = array();
			$logs = General::listStructure(LOGS, array("log"), false, 'asc');
		
			if(!empty($logs)){
				foreach($logs['filelist'] as $f){
					$name = basename($f, ".log");
					if(General::dateDiff(strtotime($name)) > intval($age)){
						@unlink(LOGS . "/" . $f);
					}	
				}
			}
		
			return true;
		
		}

		function addScriptToHead($path){
			$this->_head['js'][] = $path;
		}
	
		function addStylesheetToHead($path, $type='screen'){
			$this->_head['css'][$type][] = $path;
		}

		function addStringToHead($string){
			$this->_head['string'][] = $string;
		}
	
		function generateHeadExtras(){
		
			## Javascript
			if(is_array($this->_head['js']) && !empty($this->_head['js'])){
				foreach($this->_head['js'] as $val)
					$GLOBALS['headExtras'][] = '<script type="text/javascript" src="'.$val.'"></script>';
			}
		
			## String
			if(is_array($this->_head['string']) && !empty($this->_head['string'])){		
				foreach($this->_head['string'] as $string)
					$GLOBALS['headExtras'][] = $string;
			}
		
			## Stylesheets
			if(is_array($this->_head['css']) && !empty($this->_head['css'])){
				foreach($this->_head['css'] as $type => $stylesheets){	
				
					if(is_array($stylesheets) && !empty($stylesheets)){	
						foreach($stylesheets as $path)
							$GLOBALS['headExtras'][] = '<link rel="stylesheet" type="text/css" media="'.$type.'" href="'.$path.'" />';
					}
				}
			}	
		}
	
		function removeFromHead($kind, $val, $type='screen'){
		
			switch($kind){
			
				case 'js':
				case 'string':
					if(is_array($this->_head[$kind]) && !empty($this->_head[$kind])){
						foreach($this->_head[$kind] as $k => $v){
							if($v == $val){
								unset($this->_head[$kind][$k]);
								return true;
							}
						}
					}
					
					break;
				
				case 'css':
					if(is_array($this->_head['css'][$type]) && !empty($this->_head['css'][$type])){
						foreach($this->_head['css'][$type] as $k => $v){
							if($v == $val){
								unset($this->_head['css'][$type][$k]);
								return true;
							}
						}
					}
				
					break;
				
			}
		
			return false;
		}
	
		function addHeaderToPage($name, $value=NULL){
			$this->_headers[$name] = $value;
		}
	
		function renderHeaders(){
		
			if(headers_sent($filename, $line)){
				@$log->pushToLog("Output already started in $filename on line $line", SYM_LOG_ERROR, true);
				return;
			}
		
			if(!is_array($this->_headers) || empty($this->_headers)) return;
		
			foreach($this->_headers as $name => $value){
				if($value) @header("$name: $value");
				else @header($name);
			}
		}
	
		function getNavArray(){
			return $this->_nav;
		}
	
		function buildNavigation($xml){
			
			if($this->getConfigVar('enabled', 'filemanager') != 'yes'){
				$xml = str_replace('<node link="/publish/filemanager/"  name="File Manager" />', NULL, file_get_contents($xml));
			}
			
			$this->_nav = array();
			$nav =& $this->_nav;
		
			$XML = new XmlDoc();
		
			if(@is_file($xml)) $XML->parseFile($xml);
			else $XML->parseString($xml);
		
			$nodes = $XML->getArray();
		
			$nav = array();

			foreach($nodes["navigation"] as $n){
			
				$content = $n["node"]["attributes"];
				$children = $n["node"][0]["children"];
			
				if(!empty($content))
					$nav[] = $content;
			
				if(@is_array($children)){
					foreach($children as $n){
						if(!empty($n["node"]["attributes"]))
							$nav[count($nav) - 1]["children"][] = $n["node"]["attributes"];		
					}		
				
				}
			
			}

			$sections = $this->_db->fetch("SELECT * FROM `tbl_sections` ORDER BY `sortorder` DESC");
		
			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s){

					if($_REQUEST['_sid'] == $s['id']
					    && preg_match('/publish\/section/i', $_REQUEST['page']) 
						&& !$this->authorIsSuper() 
						&& !@in_array($s['id'], $this->getAuthorAllowableSections())){
							$this->fatalError("Access Denied", "<p>Access denied. You are not authorised to access this page.</p>", true, true);
					}

					if($this->authorIsSuper() || (!$this->authorIsSuper() && @in_array($s['id'], $this->getAuthorAllowableSections())))
						array_unshift($nav[0]["children"], array("link" => "/publish/section/&amp;_sid=" .$s['id'],  "name" => $s['name']));
				}
			}

			$services = $this->_cfm->listAll();

			if(is_array($services) && !empty($services)){
			
				foreach($services as $owner => $list){
					foreach($list as $handle => $about){				
						if(@in_array("$owner/$handle", $this->_config->_vars['campfire-menu'])){

							if(@is_file(CAMPFIRE . "/$owner/$handle/interface/content.index.php")){
				        	$nav[3]["children"][] = array("link" => "/campfire/service/$owner/$handle/",  "name" => $about['name']);
		        	
							}else{
									$nav[3]["children"][] = array("link" => "/campfire/info/&amp;name=$owner/$handle",  "name" => $about['name']);
							}
						
						}elseif(!$this->authorIsSuper() 
							&& preg_match('/campfire\/service\/'.$owner.'\/'.$handle.'/i', $_REQUEST['page']) 
							&& @!in_array("$owner/$handle", $this->_config->_vars['campfire-menu'])){
								$this->fatalError("Access Denied", "<p>Access denied. You are not authorised to access this page.</p>", true, true);
												
						}
					}	
				}
			}

			if(count($nav[3]['children']) < 3 && !$this->authorIsSuper()) unset($nav[3]);
		
			return $this->_nav;
			
		}
	
		function setDatabase(&$db){
			$this->_db =& $db;
		}
	
		function startSession($ses_path=null){
			if($this->_sesStarted)
				return true;
			
			if(!empty($ses_path) && isset($ses_path) && is_writable($ses_path))
				session_save_path($ses_path);
		
			session_start();
		}
	
		function getCurrentPageURL($full_query = false){
			return (!$full_query ? $this->_currentPage : $_SERVER['REQUEST_URI']);
		}
	
		function getContent($page = NULL, $isAction = false, $silent=false) {

			$this->_currentPage = URL . "/symphony/?page=" . $page;
		
			$dir = "content";
		
			if($isAction){
				$dir = "actions";
			}		
		
			if($this->authorIsLoggedIn()) {
			
				if(trim($page, "/") == "") General::redirect(URL . "/symphony/?page=" . str_replace('&amp;', '&', $this->_nav[0]['children'][0]['link']));
			
				if(stristr($page, "campfire/service") !== false){
			
					$parts = explode("/", trim($page, "/"));
				
					$parts = array_slice($parts , 2);
				
					$owner = array_shift($parts);
					$service = array_shift($parts);
				
					if(empty($parts)){
						$parts = array("index");
					}
				
					$path = CAMPFIRE . "/$owner/$service/interface/" . ($isAction ? "action" : "content") . "." . implode("_", $parts) . ".php";

				}else{
				
					$page_real = trim($page, '/');
					$page_real = "sym_" . str_replace("/", "_", $page_real);
				
					$user_access_level = "author";
			
					if($this->authorIsOwner()) $user_access_level = "owner";
					elseif($this->authorIsSuper()) $user_access_level = "super";
					
					$page_limit = "author";
						
					foreach($this->_nav as $item){
					
						if(General::in_array_multi($page, $item['children'])){
		      
			                if(isset($item['limit']))
			                    $page_limit	= $item['limit'];
		                      
			                elseif(is_array($item['children'])){
			                    foreach($item['children'] as $c){
			                        if($c['link'] == $page && isset($c['limit']))
			                            $page_limit	= $c['limit'];	          
			                    }
			                }
		               				
						}elseif(($page == $item['link']) && isset($item['limit'])){						
							$page_limit	= $item['limit'];	  	
						}
					}

					$can_access = false;

					if($page_limit == "author")
						$can_access = true;
					
					elseif($page_limit == "super" && ($user_access_level == "super" || $user_access_level == "owner"))
						$can_access = true;		
					
					elseif($page_limit == "owner" && $user_access_level == "owner")
						$can_access = true;	

					if(!$can_access){
						if(!$silent)
							$this->fatalError("Access Denied", "<p>Access denied. You are not authorised to access this page.</p>", true, true);
						return false;
					}	
				
					$path = CORE . "/" . $dir . "/" . $page_real . ".php";

				}
		
				if(@is_file($path))
					return($path);
				
				if(!$silent)			
					$this->fatalError("Page Not Found", "<p>The page you were looking for could not be found.</p>", true, true);
					
				return false;
			
			} else {
				return(CORE."/".$dir."/sym_login.php");
			}
		}
	
		function getCookieDomain(){
			$domain = parse_url_compat(URL, PHP_URL_PATH);
			$domain = '/' . trim($domain, '/');		
			return $domain;
		}
	
		function setCookie($name, $data, $prefix="sym_", $expiry=31536000) {
			setcookie($prefix . $name, $data, time() + TWO_WEEKS, $this->getCookieDomain());
		}	
	
		function clearCookie($name, $prefix="sym_") {	
			setcookie($prefix . $name, ' ', time() - TWO_WEEKS, $this->getCookieDomain());
		}

		function logout(){
			$this->clearCookie("auth", $this->getConfigVar('cookie_prefix', 'symphony'));
			$this->clearCookie("auth_safe", $this->getConfigVar('cookie_prefix', 'symphony'));

			####
			# Delegate: Initialisation
			# Description: Just before the administration page is rendered. Good place to manipulate 
			#              environment variables, set more headers or add items to the page head element
			$this->_cfm->notifyMembers('Logout', CURRENTPAGE);

		}
	
		function login($username, $password, $already_md5=false, $update=true){
			
			$sql  = "SELECT *\n";
			$sql .= "FROM `tbl_authors`\n";
			$sql .= "WHERE `username` = '".addslashes($username)."'\n";
			$sql .= "AND `password` = '".addslashes(!$already_md5 ? md5($password) : $password)."'\n";
		
			$row = $this->_db->fetchRow(0, $sql);
		
			$date = $this->getDateObj();
			
			if(!empty($row) && is_array($row)) {

				$refresh = date("Y-m-d H:i:00", $date->get(false, false));

				if($update || (strtotime($refresh) - strtotime($row['last_refresh'])) > 3600){
					@unlink(TMP . "/done"); 
					$session = $row['last_refresh'];
					$sql = "UPDATE `tbl_authors` SET `last_refresh` = '$refresh' " . ($session ? ", `last_session` = '$session' " : "") . " WHERE `id` = '".$row['id']."'";
					$this->_db->query($sql);
					$this->registerAuthor($this->_db->fetchRow(0, "SELECT * FROM `tbl_authors` WHERE `id` = '" . $row['id'] . "' LIMIT 1"));
				}
			
				return true;
			
			} else {

				if(is_object($this->log)) $this->log->pushToLog("Failed Login Attempt [".$_SERVER['REMOTE_ADDR']."]", true);
				return false;
			}
		}
		
		function addStatusFeedLink(){
			if($this->Author->get('auth_token_active') == 'no') return;
			$this->addStringToHead('<link rel="alternate" type="application/rss+xml" title="Your Symphony Status" href="' . URL . '/symphony/?page=/settings/status/feed/&auth='.$this->getAuthorToken().'" />');
			
		}		
	
		function registerAuthor($args=null) {

			if(isset($args['auth-token'])){			

				$sql  = "SELECT * "
					  . "FROM `tbl_authors` "
				      . "WHERE `auth_token_active` = 'yes' 
								AND LEFT(MD5(CONCAT(`username`, `password`)), 8) = '".addslashes($args['auth-token'])."' LIMIT 1";		

				$row = $this->_db->fetchRow(0, $sql);		
				
				if(!is_array($row) || empty($row)) return true;

				$result = $this->login($row['username'], $row['password'], true, false);
			
				if(!$result) return false;	
				else $args = $row;		

			}elseif(!$args){ //Try and load the cookie if it exists
				if(isset($_COOKIE[__SYM_COOKIE__])){
					$args = unserialize($_COOKIE[__SYM_COOKIE__]);				
					$result = $this->login($args['username'], $args['password'], true, false);
				}else
					return false;
				
				if(!$result) return false;	
					
			}
		
			$this->Author = new Author($this, $args['id']);

			$this->_author = $args;

			$this->clearCookie("auth", $this->getConfigVar('cookie_prefix', 'symphony'));
			$this->clearCookie("auth_safe", $this->getConfigVar('cookie_prefix', 'symphony'));

			$this->setCookie("auth", serialize($this->_author), $this->getConfigVar('cookie_prefix', 'symphony'));
			$this->setCookie("auth_safe", base64_encode(serialize($this->_author)), $this->getConfigVar('cookie_prefix', 'symphony'));
		
			return true;
		}
		
		function authorGenerateAuthToken($id=NULL){
		
			if($id) $author = $this->_db->fetchRow(0, "SELECT username, password FROM `tbl_authors` WHERE `id` = '$id' LIMIT 1");
			else $author = array("username" => $this->getAuthorUsername(), "password" => $this->Author->get('password'));
		
			if(!$author) return NULL;
		
			return substr(md5($author['username'] . $author['password']), 0, 8);
		}		

		function authorIsOwner() {
			if(is_object($this->Author) && $this->Author->get('owner') == 1 && $this->authorIsSuper()) return true;
			return false;			
		}

		function authorIsSuper() {
			if(is_object($this->Author) && $this->Author->get('superuser') == 1) return true;
			return false;	
		}

		function authorIsLoggedIn() {
			if(is_object($this->Author) && $this->Author->get('id') != NULL) return true;		
			return false;
		}
		
		function getAuthorAllowableSections(){
			
			if(is_object($this->Author) && !$sections = $this->Author->get('allow_sections')) return array();
			
			$sections = preg_split('/,/', $sections, -1, PREG_SPLIT_NO_EMPTY);
			@array_map("trim", $sections);
			
			return (is_array($sections) && !empty($sections) ? $sections : array());
		}
		
		function getAuthorTextFormatter() {
			return $this->Author->get('textformat');
		}
	
		function getAuthorName() {
			return $this->Author->get('firstname') . ' ' . $this->Author->get('lastname');
		}
	
		function getAuthorEmail() {
			return $this->Author->get('email');
		}
		
		function getAuthorUsername() {
			return $this->Author->get('username');
		}
		
		function getAuthorID() {
			return (is_object($this->Author) ? $this->Author->get('id') : NULL);
		}
	
		function getAuthorToken(){
		   return substr(md5($this->Author->get('username') . $this->Author->get('password')), 0, 8);
		}
	
		function getConfigVar($name, $index=NULL){
			return $this->_config->get($name, $index);
		}
	
		function setConfigVar($name, $val, $index=NULL){
			$this->_config->set($name, $val, $index);
		}
		
		function removeConfigVar($name, $index=NULL){
			$this->_config->remove($name, $index);
		}		

		function saveConfig(){

			if(!defined('DOMAIN')){
						
				$clean_path = $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);
			    $clean_path = rtrim($clean_path, '/\\');
			    $clean_path = preg_replace('/\/{2,}/i', '/', $clean_path);
			
				## Strip the /symphony part from the URL
				$clean_path = substr($clean_path, 0, strlen($clean_path) - 9);
			
				define('DOMAIN', $clean_path);
				
			}
			
			$string  = '<?php' . CRLF
					 . "define('DOCROOT','".DOCROOT."');" . CRLF
					 . "define('DOMAIN','".DOMAIN."');" . CRLF . CRLF
					 . '$settings = array();' . CRLF;
		
			$string .= $this->_config->create("php");
		
			$string .= CRLF . "require_once(DOCROOT . '/symphony/lib/boot/bundle.php');" . CRLF . '?>';	
		
			return General::writeFile(CONFIG, $string, $this->getConfigVar("write_mode", "file"));
			
		}
	
		function verifyAuthToken($t){
	
			$passwords = $this->_db->fetch("SELECT `password` FROM `tbl_authors`");
		
			$serial = $this->_config->get("serial", "symphony");
		
			foreach($passwords as $p){
				$token = md5($serial . $p['password']);
			
				$token = General::substrmin($token, 8);
			
				if($t == $token)
					return true; 
			}
		
			return false;
	
		}
	
		function createAuthToken(){
		
			$token = md5($this->_config->get("serial", "symphony") . $this->_author['password']);
			$token = General::substrmin($token, 8);
		
			return $token;
	
		}
	
		function updateMetadata($class, $relation_id, $insert=true){
			
			$meta = array();
	
			$meta['class']	= $class;
		
			$this->_date->set();
			$this->_date->setFormat("Y-m-d H:i:s");
		
			if(!$insert){
			
				$meta['modified_date'] 		= $this->_date->get(true, true);
				$meta['modified_date_gmt'] 	= $this->_date->get(false, true);			
				$meta['modifier_id'] 		= $this->getAuthorID();
				$meta['modifier_ip']		= $_SERVER['REMOTE_ADDR'];
				$meta['referrer']			= $_SERVER['HTTP_REFERER'];		
				$this->_db->update($meta, "tbl_metadata", "WHERE `relation_id` = '".$relation_id."' AND `class` = '$class' LIMIT 1");

			}else{
			
				$meta['relation_id'] 		= $relation_id;
				$meta['creation_date'] 		= $this->_date->get(true, true);
				$meta['creation_date_gmt'] 	= $this->_date->get(false, true);		
				$meta['creator_ip']			= $_SERVER['REMOTE_ADDR'];
				$meta['referrer']			= $_SERVER['HTTP_REFERER'];
				$this->_db->insert($meta, "tbl_metadata");	
			}					
		}
	
		function synchroniseWorkspace(){
		
			if(@md5_file(WORKSPACE . "/workspace.conf") != $this->getConfigVar("config_checksum", "workspace"))
				return $this->importWorkspaceConfig();
			
			elseif(!@is_file(WORKSPACE . "/workspace.conf"))
				return $this->rebuildWorkspaceConfig();
				
			return true;
			
		}
	
		function importWorkspaceConfig(){
		
			$this->log->pushToLog("Importing Workspace Configuration", SYM_LOG_NOTICE, true); 
		
			if(!$config = @file_get_contents(WORKSPACE . "/workspace.conf")){
				$this->log->pushToLog("ERROR: Could not read configuration file. Re-building.", SYM_LOG_ERROR, true); 
				$this->rebuildWorkspaceConfig();
				return false;
			}
		
			$this->log->pushToLog("Updating Symphony Configuration...", SYM_LOG_NOTICE, true, false); 
			if(!$hash = md5_file(WORKSPACE . "/workspace.conf")){
				$this->log->pushToLog("Failed. Re-building.", SYM_LOG_NOTICE, true, true, true); 	
				$this->rebuildWorkspaceConfig();
				return false;
			}
			$this->log->pushToLog("Done", SYM_LOG_NOTICE, true, true, true); 	
		
			$this->setConfigVar("config_checksum", $hash, "workspace");
			if(!$this->saveConfig()){
				$this->log->pushToLog("ERROR: Could not read Symphony configuration file. Please check it is writable.", SYM_LOG_ERROR, true); 
				return false;
			}
			
			$this->log->pushToLog("Preparing Tables...", SYM_LOG_NOTICE, true); 
 
			$sql = array(
					
						"TRUNCATE `tbl_pages`;",
						"TRUNCATE `tbl_customfields`;",
						"TRUNCATE `tbl_customfields_selectoptions`;",
						"TRUNCATE `tbl_utilities`;",
						"TRUNCATE `tbl_utilities2datasources`;",
						"TRUNCATE `tbl_utilities2events`;",
						"TRUNCATE `tbl_masters`;",
						"TRUNCATE `tbl_sections`;",
						"TRUNCATE `tbl_sections_visible_columns`;",
										
						"DELETE FROM `tbl_metadata` WHERE `class` IN('master','page','customfield','section','utility')"
					);
		
			foreach($sql as $s){
				$this->log->pushToLog("QUERY: " . $s, SYM_LOG_NOTICE, true); 
				$this->_db->query($s);
			}
		
			$this->log->pushToLog("Updating Tables...", SYM_LOG_NOTICE, true, false); 						
			if(!$this->_db->import($config)){
				$this->log->pushToLog("Failed.", SYM_LOG_NOTICE, true, true, true); 
				return false;
			}
		
			$this->log->pushToLog("Done", SYM_LOG_NOTICE, true, true, true); 
		
			$this->log->pushToLog("Workspace Synchronisation Complete", SYM_LOG_NOTICE, true); 
		
			return true;
		}
	
	
		function rebuildWorkspaceConfig(){
		
			require_once(TOOLKIT . "/class.mysqldump.php");
		
			$dump = new MySQLDump($this->_db);
			$prefix = $this->getConfigVar("tbl_prefix", "database");
				
			$data = $dump->takeDump($prefix . "pages", "DATA_ONLY");
			$data .= $dump->takeDump($prefix . "customfields", "DATA_ONLY");
		
			$meta = $dump->takeDump($prefix . "metadata", "DATA_ONLY", "`class` IN('page','customfield', 'section', 'master', 'utility')");
		
			$data .= $dump->takeDump($prefix . "utilities", "DATA_ONLY");
			$data .= $dump->takeDump($prefix . "masters", "DATA_ONLY");
			$data .= $dump->takeDump($prefix . "sections", "DATA_ONLY");				
		
			unset($dump);
		
			## Make sure the prefix is correct. We need it as 'tbl_' not the user specified one
			str_replace("`$prefix", '`tbl_', $data);
		
			## Make sure we dont get metadata primary key collisions
			$meta = preg_replace('/VALUES \(\d+/i', "VALUES (''", $meta);

			$data .= $meta;
		
			@file_put_contents(WORKSPACE . "/workspace.conf", $data);
		
			if($hash = @md5_file(WORKSPACE . "/workspace.conf")){
				$this->setConfigVar("config_checksum", $hash, "workspace");
				$this->saveConfig();
				$this->flush_cache("ALL");
				return true;
			}
		
			$this->log->pushToLog("ERROR: Rebuilding of workspace configuration failed", SYM_LOG_ERROR, true, true);
			return false;
		}
	}
?>