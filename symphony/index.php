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

	if(!is_file('../manifest/config.php')) die("<h2>Error</h2><p>Symphony Engine could not be loaded.</p>");
			
	require_once('../manifest/config.php');
			
	//Fix for double login problem	
	$url_bits = parse_url(URL);

	if($_SERVER['HTTP_HOST'] != $url_bits['host'] && $_SERVER['HTTP_HOST'] != ($url_bits['host'] . ':' . $url_bits['port'])){
		General::redirect(URL . "/symphony/?" . $_SERVER['QUERY_STRING']);
		exit();
	}
	
	require_once(LIBRARY . "/class.admin.php");
	$Admin =& new Admin(array("start_session" => true, "config" => $settings));	
	
	$Admin->addHeaderToPage("Content-Type", "text/html; charset=UTF-8");
	
	$dbDriver = $Admin->getConfigVar("driver", "database");
	
	if (!class_exists($dbDriver)) {
		$dbDriver = "MySQL";										
	}
	
	$DB = new $dbDriver($Admin->getConfigVar("database"));
	
    if(!$DB->isConnected())
        $Admin->fatalError(NULL, "<p>There was a problem establishing a connection to the MySQL server. Check that the details in your configuration file <code>/manifest/config.php</code> are correct.</p>", true, true);
   
    if(!$DB->getSelected())
       $Admin->fatalError(NULL, "<p>There was a problem establishing a connection to the specified database. Check that the details in your configuration file <code>/manifest/config.php</code> are correct.</p>", true, true);

    ##Make sure the table encoding settings are right
    if($Admin->getConfigVar("runtime_character_set_alter", "database")){
    	$DB->setCharacterSet($Admin->getConfigVar("character_encoding", "database"));
    	$DB->setCharacterEncoding($Admin->getConfigVar("character_set", "database"));
	}
       
	$Admin->setDatabase($DB);
	$Admin->setLog($symLog);	
	$Admin->registerAuthor((isset($_REQUEST['auth']) ? array('auth-token' => $_REQUEST['auth']) : NULL));
	
	include_once(TOOLKIT . "/class.profiler.php");
	$profiler = new Profiler;	
	
	## Create the campfire manager. Order is important 
	## since the CFM relies on the Admin's Database object
	$CampfireManager = new CampfireManager(array('parent' => &$Admin));
	$Admin->setCFM($CampfireManager);
	
	$page = $_GET['page'];

	##Grab the page, and tidy up the value
	$page = "/" . trim($page, "/") . "/";
	
	define("CURRENTPAGE", $page);
	
	####
	# Delegate: Initialisation
	# Description: Just before the administration page is rendered. Good place to manipulate 
	#              environment variables, set more headers or add items to the page head element
	$CampfireManager->notifyMembers('Initialisation', CURRENTPAGE);

	if(isset($_REQUEST['action']) && $inc = $Admin->getContent(CURRENTPAGE, true, true)){
		include($inc);
	}
	
	$Admin->addStringToHead('<link rel="icon" type="image/png" href="assets/images/icons/bookmark.png" />');
	
	## Set the appropriate template and head extras
	if($Admin->authorIsLoggedIn()){		
		$Admin->addStylesheetToHead('assets/main.css');
		$Admin->addScriptToHead('assets/main.js');
				
		$Admin->addStatusFeedLink();
		$template = 'main';
		
	}else{
		$Admin->addScriptToHead('assets/main.js');		
		$Admin->addStylesheetToHead('assets/login.css');
		$template = 'login';	
	}
					
	$Admin->buildNavigation(CORE . '/navigation.xml');

	####
	# Delegate: SetTemplate
	# Description: Template has been set, but this delegate will let you change it to whatever you wish
	$CampfireManager->notifyMembers('SetTemplate', CURRENTPAGE, array('template' => &$template));

	ob_start();
	include($Admin->getContent(CURRENTPAGE));
	$content = (!defined("__SYM_FATAL_ERROR__") ? ob_get_contents() : __SYM_FATAL_ERROR__);
	ob_end_clean();

	####
	# Delegate: GenerateHead
	# Description: All head information is generated just after this call. This is where you can remove/add items
	$CampfireManager->notifyMembers('GenerateHead', CURRENTPAGE);

	$Admin->generateHeadExtras();
	
	ob_start();
	include(CORE."/template/".$template.".php");
	$final_page = ob_get_clean();
	
	$replace 	= array("<!-- CONTENT -->"		=> $content,
						"<!-- RENDER TIME -->"	=> precision_timer("stop", STARTTIME), 
						"<!-- PAGE TITLE -->"	=> $GLOBALS['pageTitle'],
						"<!-- ONLOAD EVENT -->"	=> $GLOBALS['onloadEvent'],
						"<!-- HEAD EXTRAS -->"	=> "\t" . @implode("\n\t", $GLOBALS['headExtras'])
						);
					
	$final_page = str_replace(array_keys($replace), array_values($replace), $final_page);	
	
	$Admin->processLogs();

	if($template == "login" || $template == "update")
		$final_page = str_replace("<!-- ERRORS -->", (defined("__SYM_ERROR_MESSAGE__") ? "<p>".__SYM_ERROR_MESSAGE__."</p>" : ""), $final_page);	
	
	else{
		
		$error = NULL;
		
		if(defined('__SYM_ERROR_MESSAGE__')):
			$error = '<p id="notice" class="error">'.__SYM_ERROR_MESSAGE__.'</p>';		
		
		elseif(defined('__SYM_NOTICE_MESSAGE__')):
			$error = '<p id="notice">'.__SYM_NOTICE_MESSAGE__.'</p>';
			
		endif;
					
		$final_page = str_replace("<!-- ERRORS -->", ($error ? $error : ""), $final_page);
	}	
	
	$profiler->sample("Page Render Time");	
		
	##Generate page headers
	$Admin->renderHeaders();	

	####
	# Delegate: PreRender
	# Description: Immediately before displaying the admin page. Provided with the final page code.
	#              Manipulating it will alter the output for this page
	$CampfireManager->notifyMembers('PreRender', CURRENTPAGE, array('output' => &$final_page));
	
	##Display the final rendered code
	print $final_page;
		
	$render_time = $profiler->retrieve(0);

	####
	# Delegate: PostRender
	# Description: After rendering the page contents. Profiler and also final page source provided.
	#              Manipulation will not effect anything.
	$CampfireManager->notifyMembers('PostRender', CURRENTPAGE, array('profiler' => $profiler, 'output' => $final_page));
										
	##Clean Up
	unset($DB);
	unset($Admin);
	unset($CampfireManager);
	
	print "\r\n\r\n<!-- Page Render Time: ".$render_time[1]." sec. -->";
	
?>