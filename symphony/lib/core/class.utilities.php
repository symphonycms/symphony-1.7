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

	if(!function_exists("stripos")):
	
		function stripos($haystack,$needle,$offset = 0){
		   return(@strpos(strtolower($haystack),strtolower($needle),$offset));
		}
		
	endif;

	if(!function_exists("file_put_contents")){
	   
	   function file_put_contents($file, $data){
	   
            if(!$fp = @fopen($file, 'w'))
                return false;
            
            if(!@fwrite($fp, $data))
                return false;
            
            @fclose($fp);
	   
	       return true;
	   }
	   
	}

	if(!function_exists("htmlspecialchars_decode")){
		function htmlspecialchars_decode ($str, $quote_style = ENT_COMPAT) {
		   return strtr($str, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
		}
	}
	
	if(!function_exists("strallpos")):
	
		function strallpos($haystack, $needle, &$count, $offset=0) {
			$match = array();
			
			if($offset > strlen($haystack)) return $match; 
				
			for ($count=0; (($pos = strpos($haystack, $needle, $offset)) !== false); $count++) {
				$match[] = $pos;
				$offset = $pos + strlen($needle);
			}
			
			return $match;
		}
		
	endif;
	
	## Defines only available in PHP 5.
	if(!defined('PHP_URL_SCHEME')) define('PHP_URL_SCHEME', 1);
	if(!defined('PHP_URL_HOST')) define('PHP_URL_HOST', 2);
	if(!defined('PHP_URL_PORT')) define('PHP_URL_PORT', 3);
	if(!defined('PHP_URL_USER')) define('PHP_URL_USER', 4);
	if(!defined('PHP_URL_PASS')) define('PHP_URL_PASS', 5);
	if(!defined('PHP_URL_PATH')) define('PHP_URL_PATH', 6);
	if(!defined('PHP_URL_QUERY')) define('PHP_URL_QUERY', 7);						
	if(!defined('PHP_URL_FRAGMENT')) define('PHP_URL_FRAGMENT', 8);	
	
	function parse_url_compat($url, $component=NULL){
		
		if(!$component) return parse_url($url);
	
		## PHP 5
		if(version_compare(phpversion(), '5.1.2', 'ge'))
			return parse_url($url, $component);

		## PHP 4
		$bits = parse_url($url);
		
		switch($component){
			case PHP_URL_SCHEME: return $bits['scheme'];
			case PHP_URL_HOST: return $bits['host'];
			case PHP_URL_PORT: return $bits['port'];
			case PHP_URL_USER: return $bits['user'];
			case PHP_URL_PASS: return $bits['pass'];
			case PHP_URL_PATH: return $bits['path'];
			case PHP_URL_QUERY: return $bits['query'];
			case PHP_URL_FRAGMENT: return $bits['fragment'];
		}
		
	}
			
	function stristr_a($haystack, $needle, $flag="ANY"){
		
		$needle = array_map("strtolower", $needle);
		$haystack = strtolower($haystack);
		
		$result = false;
		
		foreach($needle as $n){
			if(stristr($haystack,$n)){
				if($flag == "ANY") return true;
				$result = true;
			}else{
				if($flag != "ANY") return false;
			}
		}
		
		return $result;
		
	}
	
?>