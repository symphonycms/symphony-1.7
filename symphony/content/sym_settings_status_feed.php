<?php

	if(!isset($_REQUEST['auth'])) General::redirect(URL . '/symphony/');

 	$ch = new Gateway;
	$ch->init();
    $ch->setopt("URL", URL . '/symphony/ajax/');
    $ch->setopt("POST", 1);
    $ch->setopt("POSTFIELDS", array('action' => 'status', 'token' => $_REQUEST['auth'], 'mode' => 'full'));	
	$data = $ch->exec();

    $parser = new XmlDoc();
    $parser->parseString($data);
    
    $doc = $parser->getArray();
	unset($parser);

	$obDate = $Admin->getDateObj();

	$rss = new XMLElement('rss');
	$rss->setAttribute('version', '2.0');
	
	$channel = new XMLElement('channel');
	$channel->addChild(new XMLElement('title', $Admin->getConfigVar('sitename', 'general')));
	$channel->addChild(new XMLElement('link', URL));
	$channel->addChild(new XMLElement('description', $Admin->getConfigVar('sitename', 'general') . ' Status Feed'));
	$channel->addChild(new XMLElement('language', 'en-us'));
	$channel->addChild(new XMLElement('generator', 'Symphony ' . $Admin->getConfigVar('build', 'symphony')));
	
	function flattenFragment($f, $type){
		
		$f = $f[$type];
		
		$flattened = array();
		
		$flattened['attributes'] = $f['attributes'];
		$flattened['data'] = array();
		
		unset($f['attributes']);
		
		foreach($f as $item){
			$flattened['data'] = array_merge($flattened['data'], $item);
		}
		
		return $flattened;
		
	}
	
	foreach($doc['status'] as $key => $fragment){
		$type = array_keys($fragment);
		$type = $type[0];

		$item = new XMLElement('item');

		$description = $link = $pubdate = $guid = $title = NULL;

		switch($type){
			
			case 'entry':
				$fragment = flattenFragment($fragment, $type);
				$link = $guid =  URL . '/symphony/' . $fragment['data']['link'][0];
				$pubdate = date("D, d M Y H:i:s \G\M\T", $obDate->get(false, false, strtotime($fragment['data']['date'][0])));
				$title = '[Entry] ' . $fragment['data']['title'][0];
				if(isset($fragment['data']['body'][0])) $description = '<p>' . $fragment['data']['body'][0] . '</p><p><em>You can go <a href="'.$link.'">here to edit</a> this entry.</em></p>';
				else $description = '<p>There is no body text associated with this entry. Please go <a href="'.$link.'">here to edit</a> this entry.</p>';
							
				break;
				
			case 'campfire':
				$fragment = flattenFragment($fragment, $type);						
				$link = $guid =  $fragment['data']['link'][0];
				$pubdate = date("D, d M Y H:i:s \G\M\T", $obDate->get(false, false, strtotime($fragment['data']['postdate'][0])));
				$title = '[Campfire Service] ' . $fragment['data']['service'][0] . ' version ' . $fragment['data']['version'][0] . ' available.';
				$description = '<p>' . nl2br($fragment['data']['description'][0]) . '</p><p><em>You can go <a href="'.$link.'">here to download it</a>.</em></p>';	
				break;
				
			case 'comment':	
				$fragment = flattenFragment($fragment, $type);
				$title = '[Comment] ' . $fragment['data']['title'][0];
				$link = $guid = URL . '/symphony/' . $fragment['data']['link'][0];				
				$pubdate = date("D, d M Y H:i:s \G\M\T", $obDate->get(false, false, strtotime($fragment['data']['date'][0])));
					
				$description =
						'<p><strong>Author:</strong> ' . $fragment['data']['author-name'][0] . '<br />'. CRLF .
						'<strong>Email:</strong> ' . $fragment['data']['author-email'][0] . '<br />' . CRLF . 
						(isset($fragment['data']['author-url']) ? '<strong>Website:</strong> ' . $fragment['data']['author-url'][0] . '<br />' . CRLF : '') . 
						'<strong>Entry:</strong> <a href="' . $fragment['data']['referrer'][0] . '">' . $fragment['data']['referrer'][0] . '</a></p>' 
						. CRLF . CRLF . '<p>' . $fragment['data']['body'][0] . '</p>';
		
				break;
				
			case 'version':
				$fragment = flattenFragment($fragment, $type);
				
				## Skip this one if there is no update
				if(!isset($fragment['data']['update'])) continue 2;
				
				$title = '[Update] ' . $fragment['data']['announcement'][0];
				$link = $guid = 'http://accounts.symphony21.com';
				$pubdate = date("D, d M Y H:i:s \G\M\T", $obDate->get(false, false, strtotime($fragment['data']['releasedate'][0])));
				$description = '<p><em>You get this update from <a href="'.$link.'">your account</a> page.</em></p>' . $fragment['data']['change-log'][0];
				
				break;	
			
		}
		
		$item->addChild(new XMLElement('title', General::sanitize($title)));
		if($description) $item->addChild(new XMLElement('description', General::sanitize($description)));	
		$item->addChild(new XMLElement('link', General::sanitize($link)));	
		$item->addChild(new XMLElement('pubDate', $pubdate));	
		$item->addChild(new XMLElement('guid', General::sanitize($guid)));
		$channel->addChild($item);
				
	}
	
	$rss->addChild($channel);
	
	##RSS XML is returned, make sure the browser knows it
	header ("Content-Type: text/xml"); 		
	
	$rss->setIncludeHeader(true);
	print $rss->generate(true);
	
	## Important. Need this otherwise rest of Symphony admin
	## laods.
	exit();

?>