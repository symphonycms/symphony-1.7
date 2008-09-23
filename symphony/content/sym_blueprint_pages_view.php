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

	print '<?xml version="1.0" encoding="utf-8"?'.'>'; 
	
	$page_handle = ($_GET['handle'] ? $_GET['handle'] : NULL);
	
	require_once(LIBRARY . "/class.site.php");
	require_once(TOOLKIT . "/class.profiler.php");	
	require_once(TOOLKIT . "/class.xmlrepair.php");
	
	$active = "xml";
	
	switch($_REQUEST['type']){
		case "page":	
			$profiler = new Profiler;	
		
			$Site = new Site($page_handle, $DB, $Admin->_config, $profiler, false);	
			
			$page_name = $DB->fetchVar("title", 0, "SELECT `title` FROM `tbl_pages` WHERE `handle` = '".$Site->_page."'");
			
			$profiler->sample("Page Initialization", PROFILE_LAP);	
				
			##Use preview mode	
			if($_GET['mode'] == 'preview')
				$Site->togglePreviewMode();
		
			#Render the page
			$output = $Site->display(array(), 'TRANSFORMED', false);
			$xml = $Site->buildXML(NULL, NULL, true, false);
			$xsl = $Site->display(array(), "XSL", false);	
		
			#Record the render time
			$profiler->sample("Total Page Render Time");
			break;
			
		case "datasource":
			$DSM = new DatasourceManager(array('parent' => &$Admin));
			$obXML = new XMLElement("data");
			$obXML->setIncludeHeader(true);
			
			##DATASOURCES
			$dsParam = array("indent-depth" => 2, 
							 "caching" => false, 
							 "indent" => true, 
							 "preview" => true);
							 
			$ds =& $DSM->create($page_handle, array('parent' => $this, 'env' => array()));
			$result = $ds->preview($dsParam);

			if(@is_object($result))
				$xml = trim($result->generate(true, 0));
				
			else
				$xml = trim($result);
				
			$page_name = $page_handle;
			
			$active = "xml";
			if($xml == "") $output = $xml = $xsl = "No Datasource by the name '$page_handle' was found.";
			
			break;
			
	}
	
	$xml = trim($xml);
	$xml = General::sanitize($xml);
	
	$xsl = trim($xsl);
	$xsl = str_replace("\r\n", "\n", trim($xsl));
	$xsl = General::sanitize($xsl);
	
	$output = trim($output);
	$output = General::sanitize($output);

	$xml = ($xml != "" ? General::str2array($xml, false) : NULL);
	$xsl = ($xsl != "" ? General::str2array($xsl) : NULL);
	$output = ($output != "" ? General::str2array($output, false) : NULL);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
	<title>Symphony &#8211; <?php print $page_name ?></title>
	<link rel="stylesheet" type="text/css" media="screen" href="<!-- URL -->/symphony/assets/main.css" />
	<script type="text/javascript" src="<!-- URL -->/symphony/assets/main.js"></script>
</head>

<body id="view">
	<h1><?php print $page_name ?></h1>

	<ul>
		<li<?php print ($active == "xml" ? ' id="active"' : ''); ?>><a href="#xml"><acronym title="eXtensible Markup Language">XML</acronym></a></li>
		<li<?php print ($active == "xslt" ? ' id="active"' : ''); ?>><a href="#xslt"><acronym title="eXtensible Stylesheet Language Transformation">XSLT</acronym></a></li>
		<li<?php print ($active == "output" ? ' id="active"' : ''); ?>><a href="#output">Output</a></li>
	</ul>

			<ol id="xml">
<?php 
	if(is_array($xml) && !empty($xml)){ 
			foreach($xml as $line){
				print "				<li><code>$line</code></li>\n";
			}
	} 
?>
			</ol>
		
			<ol id="xslt">
<?php 
	if(is_array($xsl) && !empty($xsl)){ 
			foreach($xsl as $line){
				print "				<li><code>$line</code></li>\n";
			}
	} 
?>
			</ol>
	
			<ol id="output">
<?php 
	if(is_array($output) && !empty($output)){ 
			foreach($output as $line){
				print "				<li><code>$line</code></li>\n";
			}
	} 
?>
			</ol>

</body>
</html>
<?php exit(); ?>
