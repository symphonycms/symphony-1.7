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
	
	Class General{
		
		/**
		
		DEPRECATED FUNCTION. WILL BE REMOVED IN NEXT VERSION.
		
		**/
		function createHandle($string, $max_length=50, $delim='-', $uriencode=false, $apply_transliteration=true){	
			trigger_error('This function, General::createHandle(), is deprecated and replaced by Lang::createHandle(). 
							If you have just updated, you may need to re-save your Data Sources by opening them and pressing the save button.'
						  , E_USER_WARNING);	
							
			Lang::createHandle($string, $max_length, $delim, $uriencode, $apply_transliteration);
		}
				
		/***
		
		Method: fieldValue
		Description: Used to simplify creation of form elements.
		Param: $type - the type of field. (value | select | checkbox | textarea)
		       $value - The existing subject to match
		       $alternate - Returned if there is no match agains the critera
		       $criteria - This is compared to the $value
		Return: $alternate or the formatted xHTML
		
		***/
		function fieldValue($type, $value, $alternate="", $criteria=null){
			
			if(trim($value) == "") return $alternate;
			
			if($criteria && $value != $criteria) return $alternate;
			
			switch($type){
			
				case 'value':
					return 'value="'.$value.'"';
					break;
					
				case 'select':
					return 'selected="selected"';
					break;
					
				case 'checkbox':
					return 'checked="checked"';
					break;
					
				case 'textarea':
					return $value;
					break;
					
			}
				
		}

		/***
		
		Method: substr_hellip
		Description: Given a string and a length, this sill truncate the string
		             to the desired length and append a hellip entity
		Param: $string - The input string to operate on
		       $length - length to truncate to
		Return: The modified string
		
		***/
		function substr_hellip($string, $length=50){

			$x = substr($string, 0, min(strlen($string), $length - 1));

			if(strlen($string) > strlen($x)) return $x . '&hellip;';

			return $string;
			

		}
		
		/***
		
		Method: getFileMeta
		Description: Will return information about a file
		Param: $file - A valid path to a file
		Return: false or an array of file details
		
		***/		
		function getFileMeta($file){
			
			$meta['creation_date'] 		= @filectime($file);	
			$meta['creation_date_gmt'] 	= $meta['creation_date'] - (date("Z") * 3600);	
			$meta['modified_date'] 		= @filemtime($file);	
			$meta['modified_date_gmt'] 	= $meta['modified_date']  - (date("Z") * 3600);	
			$meta['accessed_date'] 		= @fileatime($file);	
			$meta['accessed_date_gmt'] 	= $meta['accessed_date']  - (date("Z") * 3600);	
			$meta['size'] 				= @filesize($file);
			
			return (@is_file($file) ? $meta : false);
				
		}
		
		/***
		
		Method: getImageMeta
		Description: Will return information about an image
		Param: $file - A valid path to an image
		Return: false or an array of image details
		
		***/		
      	function getImageMeta($file){
            $meta = array();

            if(!$array = @getimagesize($file)) return false;

            $meta['width']    = $array[0];
            $meta['height']   = $array[1];
            $meta['type']     = $array[2];
            $meta['channels'] = $array['channels'];
            return $meta;        
        }		
		
		/***
		
		Method: sanitize
		Description: Will convert any special characters into their entity equivalents
		Param: $str - a string to operate on
		Return: the encoded version of the string
		
		***/
		function sanitize($str){
			return htmlspecialchars($str);
		}
		
		/***
		
		Method: reverse_sanitize
		Description: Will revert any html entities to their character equivalents
		Param: $str - a string to operate on
		Return: the decoded version of the string
		
		***/		
		function reverse_sanitize($str){		 
		   return htmlspecialchars_decode($str);
		}
		
		/***
		
		Method: nl2p
		Description: Replaces any newline characters with paragraph elements
		Param: $string - a string to operate on
		Return: the resultant string, wrapped in <p> tags
		
		***/		
		function nl2p($string){
			$string = str_replace("<br />", "</p><p>", nl2br(trim($string)));
			return "<p>$string</p>";
		}

		/***
		
		Method: substr_f
		Description: grabs the first character of a string
		Param: $string - a string to operate on
		Return: the first character of the input string
		
		***/		
		function substr_f($string){
			return (string)$string{0};
		}


		/***
		
		Method: url2a
		Description: given a string, this will attempt to convert any URL's into
		             valid xHTML anchor tags
		Param: $str - a string to operate on
		       $nofollow (optional) - if true, will add rel="nofollow" to the elements
		Return: the resultant string
		
		***/		
		function url2a($str, $nofollow=false){
			
			preg_match_all("/http:\/\/?[^ ][^<]+/i", $str, $lnk);
			
			$size = sizeof($lnk[0]);
			
			$i = 0;
			
			while ($i < $size) {
				$len = strlen($lnk[0][$i]);
				
				if($len > 30) {
					$lnk_txt = substr($lnk[0][$i], 0, 30)."...";
				
				} else {
					$lnk_txt = $lnk[0][$i]; 
				}
				
				$url = General::validateURL($lnk[0][$i]);
				
				if(!empty($url)){
					$str = str_replace($url,"<a href=\"$url\" ".($nofollow ? "rel=\"nofollow\"" : "").">$lnk_txt</a>",$str);
				}
				
				$i++;
			}
			
			return $str;
	
		}
		
		
		/***
		
		Method: findKeywordsArray
		Description: using any array of strings, this function will attempt to find
		             any occurance of a needle
		Param: $needle - string to search for
		       $haystack - the string or array to search
		       $fields (optional) - 
			   $any (optional) - if true, the function will return if any match is found
		Return: true or false
		
		***/		
		function findKeywordsArray($needle, $haystack, $fields=array(), $any=true){
		
			
			$result = array();
			
			foreach($haystack as $h){
			
				$match = false;
				
				foreach($needle as $n){
					foreach($fields as $f){
					
						if(stripos($h[$f], $n))
							$match = true;
							
						else
							if(!$any) $match = false;
						
					}
				}
				
				if($match) array_push($result, $h);
			}		
			
			return $result;
		
		}

		/***
		
		Method: stripEntities
		Description: will replace any html entity with the replacement string
		Param: $string - string to operate on
		       $replacement - string to replace any entities with
		Return: the resultant string
		
		***/		
		function stripEntities($string, $replacement=""){
			return preg_replace('/(&[\\w]{2,6};)/', $replacement, $string);
		}
		
		/***
		
		Method: validateString
		Description: will validate a string against a set of reqular expressions
		Param: $string - string to operate on
		       $rule - a single rule or array of rules
		Return: true or false
		
		***/
		function validateString($string, $rule){
			
			if(!is_array($rule) && $rule == '') return false;
			if(!is_array($string) && $string == '') return false;
			
			if(!is_array($rule)) $rule = array($rule);
			if(!is_array($string)) $string = array($string);
						
			foreach($rule as $r){
				foreach($string as $s){
					if(!preg_match($r, $s)) return false;
				}
			}
			return true;
		}


		/***
		
		Method: validateXML
		Description: This checks an xml document for well-formedness
		Param: $data - filename or xml document as a string
		       $errors - pointer to an array which will contain any validation errors
		       $isFile (optional) - if this is true, the method will attempt to read
		                            from a file ($data) instead.
			   $xsltProcessor (optional) - If set, the validation will be done using this
										   xslt processor rather than the built in XML parser
			   $encoding (optional) - If no XML header is expected, than this should be set to
			 						  match the encoding of the XML
		Return: true or false
		
		***/		
		function validateXML($data, &$errors, $isFile=true, $xsltProcessor=NULL, $encoding='UTF-8') {
			$_parser 	= null;
			$_data	 	= null;
			$_vals		= array();
			$_index		= array();
			
			if($isFile)
				$_data = @file_get_contents($data);
				
			else
				$_data = $data;

			$_data = preg_replace('/(&[\\w]{2,6};)/', "",$_data);
			$_data = preg_replace('/<!DOCTYPE[-.:"\'\/\\w\\s]+>/' , "", $_data);
			
			if(strpos($_data, '<?xml') === false){
				$_data = "<?xml version=\"1.0\" encoding=\"$encoding\"?><rootelement>\n".$_data."\n</rootelement>";
			}
			
			if(@is_object($xsltProcessor)){
				
				$xsl = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:template match="/"></xsl:template>

				</xsl:stylesheet>';

				$xsltProcessor->process($_data, $xsl, array());

				if($xsltProcessor->isErrors()) {
					$errors = $xsltProcessor->getError(true);
					return false;
				}
				
			}else{
			
				$_parser = xml_parser_create();
				xml_parser_set_option($_parser, XML_OPTION_SKIP_WHITE, 0);
				xml_parser_set_option($_parser, XML_OPTION_CASE_FOLDING, 0);
				
				if (!@xml_parse($_parser, $_data)) {
					$errors = array("error" => xml_get_error_code($_parser) . ': ' . xml_error_string(xml_get_error_code($_parser)), 
									"col" => xml_get_current_column_number($_parser), 
									"line" => (xml_get_current_line_number($_parser) - 2));
					return false;
				}

				xml_parser_free($_parser);
			}
			
			return true;
			
		}
		

		/***
		
		Method: validateURL
		Description: will check that a string is a valid URL
		Param: $string - string to operate on
		Return: true or false
		
		***/		
		function validateURL($url){
			if ($url != ""){
				if (!preg_match('#^http[s]?:\/\/#i', $url)){
					$url = 'http://' . $url;
				}
		
				if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $url)){
					$url = '';
				}
			}
			
			return $url;
		}

		/***
		
		Method: nullIfZero
		Description: mainly for SQL statements, this will convert a 0 value to 'IS NULL'
		Param: $value - string to operate on
		Return: the formatted string suitable for use in the SQL statement
		
		***/		
		function nullIfZero ($value) { 
			if (intval ($value) == 0) { 
				return 'IS NULL'; 
			}else { 
				return '= ' . $value; 
			} 
		}  


		/***
		
		Method: get_class_methods_filter
		Description: similar to get_class_methods, however this allows you to filter the
		             result set using a regular expressions
		Param: $classname - the name of the class. Must be in the class pool
		       $critera (optional) - a regular expression to match against. Leave blank 
		                             to return all results
		Return: the formatted string suitable for use in the SQL statement
		
		***/		
		function get_class_methods_filter($classname, $criteria=NULL){
			
			$methods = get_class_methods($classname);
			
			if($criteria == NULL) return $methods;
			
			$result = array();
			
			foreach($methods as $m){
				
				if(preg_match($criteria, $m))
					$result[] = $m;
				
			}
			
			return $result;
			
		}

		/***
		
		Method: cleanArray
		Description: Will strip any slashes from all array values
		Param: &$arr - pointer to an array to operate on. Can be multi-dimensional		
		
		***/		
		function cleanArray(&$arr) {
			
			foreach($arr as $k => $v){
				
				if (is_array($v))
					General::cleanArray($arr[$k]);
				else
					$arr[$k] = stripslashes($v);
			}
		}


		/***
		
		Method: generatePassword
		Description: uses random numbers and 2 arrays to create friendly passwords such as
		             4LargeWorms or 11HairyMonkeys
		Return: string
		
		***/			
		function generatePassword(){
		
			$words[] = array("Large", "Small", "Hot", "Cold", "Big", "Hairy", "Round", "Lumpy", "Coconut");
			$words[] = array("Cats", "Dogs", "Weasels", "Birds", "Worms", "Bugs", "Pigs", "Monkeys");
			
			return (rand(2, 15) . $words[0][rand(0, 8)] . $words[1][rand(0, 7)]); 
				
		}

		/***
		
		Method: sendEmail
		Description: Allows you to send emails. It includes some simple injection attack
		             protection and more comprehensive headers
		Param: $to_email - email of the recipient
		       $from_email - the from email address. This is usually your email
		       $from_name - The name of the sender
		       $subject - subject of the email
		       $message - contents of the email
		Return: true or false
		
		***/		
		function sendEmail($to_email, $from_email, $from_name, $subject, $message){	
			
			##Check for injection attacks (http://securephp.damonkohler.com/index.php/Email_Injection)
			if ((eregi("\r", $from_email) || eregi("\n", $from_email))
				|| (eregi("\r", $from_name) || eregi("\n", $from_name))){
					return false;
		   	}
			####
					
			$header  = "From: $from_name <$from_email>\n";
			$header .= "Reply-To: $from_name <$from_email>\n";
			$header .= "X-Sender: Symphony Email Module <DONOTREPLY@symphony21.com>\n";
			$header .= "X-Mailer: Symphony Email Module\n"; 
			$header .= "X-Priority: 3\n"; 
			$header .= "Return-Path: <$from_email>\n";
								
			if(@mail($to_email, $subject, $message, $header))
				return true;
				
			else
				return false;
			
			
		}


		/***
		
		Method: padString
		Description: Pads a string with a specified string until it is of a certain length
		Param: $str - string to operate on
		       $length - desired string length
		       $spacer (optional) - the character you wish to pad with
		       $align (optional) - right, left or middle padding
		Return: padded string
		
		***/
		function padString($str, $length, $spacer=" ", $align="left"){
			
			switch($align){
				
				case "right":
					return $str . General::repeatStr($spacer, $length - strlen($str));
					break;
					
				case "center":
					return General::repeatStr($spacer, ($length - strlen($str)) * 0.5 ) . $str . General::repeatStr($spacer, ($length - strlen($str)) * 0.5 );
					break;
					
				case "left":
				default:
					return General::repeatStr($spacer, $length - strlen($str)) . $str;
					break;
				
			}
				
		}


		/***
		
		Method: repeatStr
		Description: This will repeat a string XX number of times.
		Param: $str - string to repeat
		       $xx - Number of times to repeat the string
		Return: resultant string
		
		***/		
		function repeatStr($str, $xx){
			
			if($xx < 0)
				$xx = 0;
			
			$xx = ceil($xx);	
				
			$result = "";
			
			for($ii = 0; $ii < $xx; $ii++){
				
				$result .= $str;	
				
			}
			
			return $result;
				
		}


		/***
		
		Method: strPlural
		Description: Given a number, this will return the singular or plural variable.
		             This is useful when dealing with counts. 1 frog or 2 frogs.
		Param: $num - number to base the result on. 0 - plural, 1 - singular, >1 - plural
		       $singular - string to return if it is a singular
			   $plural - string to return if it is a plural
		Return: resultant string
		
		***/		
		function strPlural($num, $singular, $plural){		
			$num = intval($num);			
			return ($num == 1 ? $singular : $plural);				
		}
		
		/***
		
		Method: substrmin
		Description: takes a string and compares it length with val. returns the substr with
	      			 length of the smaller value. IE strlen($str) or $val
		Param: $str - the string to operate on
			   $val - the number to compare lengths with
		Return: the smaller string
		
		***/
		function substrmin($str, $val){
			return(substr($str, 0, min(strlen($str), $val)));
		}
		
		/***
		
		Method: substrmax
		Description: takes a string and compares it length with val. returns the substr with
	      			 length of the larger value. IE strlen($str) or $val
		Param: $str - the string to operate on
			   $val - the number to compare lengths with
		Return: the larger string
		
		***/
		function substrmax($str, $val){
			return(substr($str, 0, max(strlen($str), $val)));
		}	
		
		/***
		
		Method: toUpperFirst
		Description: Capitalizes the first letter of a string
		Param: $str - the string to operate on
		Return: string with a capital letter
		
		***/
		function toUpperFirst($str){
			return ucfirst($str);
		}
		
		/***
		
		Method: right
		Description: creates a string from the right by $num characters
		Param: $str - the string to operate on
			   $num - the number of characters to return
		Return: resultant string portion
		
		***/	
		function right($str, $num){
			$str = substr($str, strlen($str)-$num,  $num);
			return $str;
		}
		
		/***
		
		Method: left
		Description: creates a string from the left by $num characters
		Param: $str - the string to operate on
			   $num - the number of characters to return
		Return: resultant string portion
		
		***/	
		function left($str, $num){			
			$str = substr($str, 0, $num);
			return $str;	
		}

		/***
		
		Method: realiseDirectory
		Description: Given a path, this function will attempt to create all directories
		             within that path until the end folder is reached.
		Param: $path - folder path to create
			   $mode (optional) - the octal permission value to chmod the new folders to
		Return: true or false
		
		***/		
		function realiseDirectory($path, $mode=0755){
		
			if(!empty($path)){
				
				if(@file_exists($path) && !@is_dir($path)){
					return false;
					
				}elseif(!@is_dir($path)){
				
					preg_match_all('/([^\/]+)\/?/i', $path, $directories);
		
					$currDir = "";
				
					foreach($directories[0] as $dir){
					
						$currDir = $currDir . $dir;
						
						if(!@file_exists($currDir)){
							if(!mkdir($currDir, intval($mode, 8))){
								print $currDir;
								return false;
							}
						}	
					}
				}
			}
				
			return true;
		}

		/***
		
		Method: in_array_multi
		Description: looks for a value inside a multi-dimensional array
		Param: $needle - value to look for
			   $haystack - array to search in
		Return: true or false
		
		***/		
		function in_array_multi($needle, $haystack){
			
			if($needle == $haystack) return true;
			
			if(is_array($haystack)){
			
				foreach($haystack as $key => $val){
					
					if(is_array($val) && General::in_array_multi($needle, $val)){
						return true;	
					
					}elseif(!strcmp($needle, $key) || !strcmp($needle, $val)){ 
						return true;
													
					}
				}
			}
				
			return false;					
		}


		/***
		
		Method: loadConfiguration
		Description: loads an XML Symphony configuration file into a Config object pointer
		Param: $file - path to the XML file to load
			   $obConf - pointer to a Config object
			   $parser (optional) - Allows you to provide an existing XmlDoc object
		
		***/		
		function loadConfiguration($file, &$obConf, $parser=NULL){
		
			if(!@is_file($file) || !@is_readable($file))
				return false;
				
			$this->_Config->flush();
			
			if(!$parser) $parser = new XmlDoc();
			
			$parser->parseFile($file); 
			
	     	$tmpData = $parser->getArray();
	       	      	
	       	foreach ($tmpData['configuration'] as $item) {
		       	
		       	foreach ($item as $key => $val) {
			       	$obConf->set($key, $val['attributes']);
	       		}
	       	}
	       	
	       	unset($parser);		
				
		}


		/***
		
		Method: redirect
		Description: redirects the browser to a specified location
		Param: $url - location to redirect to
		
		***/		
	   	function redirect ($url){
			
			$url = str_replace("Location:", "", $url); //Just make sure.
			
			if(headers_sent($filename, $line)){			
				print "<h1>Error: Cannot redirect to <a href=\"$url\">$url</a></h1><p>Output has already started in $filename on line $line</p>";
				exit();
			}
			
			header('Expires: Mon, 12 Dec 1982 06:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');
	        header("Location: $url");
	        exit();	
	    }

			
		/***
		
		Method: key_in_array
		Description: checks an array for a key value
		Param: $needle - key to look for
			   $haystack - array to search
		Return: true or false
		
		***/			
		function key_in_array($needle, $haystack){
			
			foreach($haystack as $key => $value){
			
				if($key == $needle)
					return true;
					
			}
			
			return false;
	
		}

		/***
		
		Method: array_remove_duplicates
		Description: rebuilds an indexed array to contain no duplicate values
		Param: $array - array to search through
		Return: rebuilt array
		
		***/		
		function array_remove_duplicates($array){
		
			/*
			//Flip once to remove duplicates
			$array = array_flip($array);
			
			//Flip back to get desired result
			$array = array_flip($array);
			
			return $array;
			
			*/
			
			if(!is_array($array)) return array($array);
			elseif(empty($array)) return array();
			
			$tmp = array();
			
			foreach($array as $item){
			
				if(!@in_array($item, $tmp))
					$tmp[] = $item;
			}
			
			return $tmp;
				
		}


		/***
		
		Method: str2array
		Description: splits a string by a delimiter
		Param: $string - string to operate on
		       $preserve (optional) - if true, this will retain any empty array values
		       $delim (optional) - a string to split by
		Return: array of string parts
		
		***/		
		function str2array($string, $preserve=true, $delim="\\r\\n"){	
				
			if(!$preserve)
				return preg_split('/[\\r\\n]/', $string, -1, PREG_SPLIT_NO_EMPTY);
				
			return preg_split('/[\\r\\n]/', $string);
						
		}


		/***
		
		Method: codeToDisplay
		Description: prepares code for display, converting it into a xHTML list
		Param: $code - string to operate on
		       $beautify (optional) - !!not used!!
		       $linenumbers (optional) - effectivly tells the method to return 
		                                 an ordered list		
		Return: xHTML code
		
		***/		
		function codeToDisplay($code, $beautify=true, $linenumbers=true){
		
			$code = htmlentities($code);
			
			$lines = General::str2array($code);
			
			$final_code = ""; $ii = 1;
			
			foreach($lines as $l){
				
				$l=trim($spaces . $l);
				
				if ($l == "") { $l = "&nbsp;"; }
				
				if($linenumbers)	
					$final_code .= "<li>$l</li>\n";	
				else
					$final_code .= "$l\n";		
				
				$ii++;
			}
			
			if($linenumbers)
				return "<ol>\n$final_code\n</ol>";
			else
				return "$final_code\n";			
		}


		/***
		
		Method: writeFile
		Description: writes the contents of $data to a file $file.
		Param: $file - file path
		       $data - string to write
		       $perm (optional) - octal permission to apply to the file via CHMOD		
		Return: XHTML code
		
		***/			
		function writeFile($file, $data, $perm = 0644){
			
			if(empty($perm)) $perm = 0644;
			
			if(!$handle = @fopen($file, 'w')) {
				return false;
				exit;
			}
			
			if(@fwrite($handle, $data, strlen($data)) === false) {
				return false;
				exit;
			}
			
			@fclose($handle);
	
			@chmod($file, intval($perm, 8));

			return true;
		}


		/***
		
		Method: deleteFile
		Description: deletes a file using the unlink function
		Param: $file - file to delete	
		Return: true or false
		
		***/		
		function deleteFile($file){
			return @unlink($file);
		}


		/***
		
		Method: getExtension
		Description: finds the file extension of a file
		Param: $file - name of the file to examine
		Return: extension
		
		***/		
		function getExtension($file){
		    $parts = explode('.', basename($file));
			return array_pop($parts);
		}
	

		/***
		
		Method: listDirStructure
		Description: will index a directory struction from start point $dir
		Param: $dir (optional) - path to start indexing at. must be readable
			   $filters (optional) - either a regular expression or an array of allowable
			                         file types
			   $recurse (optional) - if true, the method will recursively traverse 
			                         the directory stucture
			   $sort (optional) - sort order of indexed files
			   $strip_root (optional) - can remove the $dir portion of the file path for
			                            array keys.
			   $exclude (optional) - ignores file types contained in this array			
		Return: nested array containing the directory structure
		
		***/	    
	    function listDirStructure($dir=".", $recurse=true, $sort="asc", $strip_root=NULL, $exclude=array(), $ignore_hidden=true){
		    
		    $filter_pattern_match = false;
		    
		    if(isset($filters) && !is_array($filters)) $filter_pattern_match = true;
		    
		    $files = array();
		    
			if(!$handle = @opendir($dir)) return array();
			
			while(($file = @readdir($handle)) != false){
				if($file != '.' && $file != '..' && (!$ignore_hidden || ($ignore_hidden && $file{0} != '.'))){
					
					if(@is_dir("$dir/$file")){
						if($recurse){
							$files[] = str_replace($strip_root, "", $dir) ."/$file/";
							
							$files = @array_merge($files, General::listDirStructure("$dir/$file", $recurse, $sort, $strip_root, $exclude, $ignore_hidden));
						}
							
					}
				}
			}
			
			@closedir($handle);
			return $files;
		}
	
		/***
		
		Method: listStructure
		Description: will index a directory struction from start point $dir
		Param: $dir (optional) - path to start indexing at. must be readable
			   $filters (optional) - either a regular expression or an array of allowable
			                         file types
			   $recurse (optional) - if true, the method will recursively traverse 
			                         the directory stucture
			   $sort (optional) - sort order of indexed files
			   $strip_root (optional) - can remove the $dir portion of the file path for
			                            array keys.
			   $exclude (optional) - ignores file types contained in this array			
		Return: nested array containing the directory structure
		
		***/	    
	    function listStructure($dir=".", $filters=array(), $recurse=true, $sort="asc", $strip_root=NULL, $exclude=array(), $ignore_hidden=true){
		    
		    $filter_pattern_match = false;
		    
		    if(isset($filters) && !is_array($filters)) $filter_pattern_match = true;
		    
		    $files = array();
		    
			if(!$handle = @opendir($dir)) return array();
			
			while(($file = @readdir($handle)) != false){
				if($file != '.' && $file != '..' && (!$ignore_hidden || ($ignore_hidden && $file{0} != '.'))){
					
					if(@is_dir("$dir/$file")){
						if($recurse)
							$files[str_replace($strip_root, "", $dir) . "/$file/"] = General::listStructure("$dir/$file", $filters, $recurse, $sort, $strip_root, $exclude, $ignore_hidden);	
						
						$files["dirlist"][] = $file;	
							
					}elseif($filter_pattern_match || (!empty($filters) && is_array($filters))){
					
						if($filter_pattern_match){	
							if(preg_match($filters, $file)){						
								$files["filelist"][] = $file;
								
								if($sort == 'desc') rsort($files["filelist"]);
								else sort($files["filelist"]);	
							}						
							
						}elseif(in_array(General::getExtension($file), $filters)){
							$files["filelist"][] = $file;
							
							if($sort == 'desc') rsort($files["filelist"]);
							else sort($files["filelist"]);
						}
						
					}elseif(empty($filters)){
						$files["filelist"][] = $file;
						
						if($sort == 'desc') rsort($files["filelist"]);
						else sort($files["filelist"]);					
		
					}
				}
			}
			@closedir($handle);
			return $files;
		}
	
		/***
		
		Method: filemtimeSort
		Description: Used by usort. Takes 2 file names and returns -1, 0 or 1. Should 
		             only be called using usort or similar. E.G. 
		             usort($files, array('General', 'filemtimeSort'));
		Param: $f1 - path to first file
		       $f2 - path to second file
		Return: -1, 0 or 1
		
		***/
		function filemtimeSort($f1, $f2){
			return @filemtime($f1['path'] . '/' . $f1['name']) - @filemtime($f1['path'] . '/' . $f1['name']);
		}

		/***
		
		Method: fileSort
		Description: Used by usort. Takes 2 file names and returns -1, 0 or 1. Should 
		             only be called using usort or similar. E.G. 
		             usort($files, array('General', 'fileSort'));
		Param: $f1 - path to first file
		       $f2 - path to second file
		Return: -1, 0 or 1
		
		***/
		function fileSort($f1, $f2){
			return strcmp($f1['name'], $f2['name']);
		}
		
		/***
		
		Method: fileSortR
		Description: Used by usort. Takes 2 file names and returns -1, 0 or 1. Should 
		             only be called using usort or similar. E.G. 
		             usort($files, array('General', 'fileSortR'));
		Param: $f1 - path to first file
		       $f2 - path to second file
		Return: -1, 0 or 1
		
		***/
		function fileSortR($f1, $f2){
			return strcmp($f2['name'], $f1['name']);
		}
				
		/***
		
		Method: listStructureFlat
		Description: will index a directory struction from start point $dir, returning 
		             a flat array where each array key is a path to a file
		Param: $dir (optional) - path to start indexing at. must be readable
			   $filters (optional) - either a regular expression or an array of allowable
			                         file types
			   $recurse (optional) - if true, the method will recursively traverse 
			                         the directory stucture
			   $sort (optional) - sort order of indexed files
			   $strip_root (optional) - can remove the $dir portion of the file path for
			                            array keys.
			   $exclude (optional) - ignores file types contained in this array
		Return: flat array containing the directory/file structure
		
		***/		
		function listStructureFlat($dir=".", $filters=array(), $recurse=true, $sort="asc", $strip_root=NULL, $exclude=array(), $ignore_hidden=true){
			
		    $files = array();
		    
			if(!$handle = @opendir($dir)) return array();
				
			while(($file = @readdir($handle)) != false){
				if($file != '.' && $file != '..' && (!$ignore_hidden || ($ignore_hidden && $file{0} != '.'))){	
					if(@is_dir("$dir/$file")){
						if($recurse)
							$files = array_merge($files, General::listStructureFlat("$dir/$file", $filters, $recurse, $sort, $strip_root, $exclude, $ignore_hidden));
					}else{
						
						$can_include = true;
						
						if(!empty($filters)){
							$can_include = in_array(General::getExtension($file), $filters);
						}
						
						if(!empty($exclude)){
							$can_include = !in_array(General::getExtension($file), $exclude);
						}					
						
						if($can_include){
							$files[] = array("name" => $file, "path" => $dir);
						}				
					}
				}
			}
			@closedir($handle);
			
			if($sort == 'desc') usort($files, array('General', 'fileSortR'));		
			elseif($sort == 'mtime') usort($files, array('General', 'filemtimeSort'));			
			else usort($files, array('General', 'fileSort'));
			
			if($strip_root){
				for($ii = 0; $ii < count($files); $ii++)
					$files[$ii]['path'] = str_replace($strip_root, "", $files[$ii]['path']);
			}
			
			return $files;	
			
		}

		
		/***
		
		Method: rmdirr
		Original Author: Anton Makarenko (makarenkoa@ukrpost.net)
		Description: recursively removes a directory and all files
		Param: $target - the directory to remove
			   $verbose - if true, this will echo status messages to the browser for
			              debugging purposes
		Return: true or false
		
		***/		
		function rmdirr($target, &$errors, &$notices){
			
			$errors = $notices = array();
			
			$exceptions = array('.','..');
			if (!$sourcedir = @opendir($target)){
				$errors[] = 'Could not open '.$target;
				return false;
			}
			
			while(false !== ($sibling = readdir($sourcedir))){
				if(!in_array($sibling, $exceptions)){
					$object = str_replace('//', '/', $target . '/' . $sibling);
					
					$notices[] = 'Processing: '.$object;
				
					if(@is_dir($object)) General::rmdirr($object, $errors, $notices);
				
					if(@is_file($object)){				
						if($result = @unlink($object)) $notices[] = $object . ' has been removed.';					
						else $errors[] = 'Could not remove file - ' . $object;
					}
				}
			}
			
			closedir($sourcedir);
			
			if($result = @rmdir($target)){
				$notices[] = $target . ' has been removed.';					
				return true;
			}
			
			$errors[] = $target . ' could not be removed.';			
			return false;
		}	


		/***
		
		Method: createFileName
		Description: given a string, this will clean it for use as a filename
		Param: $string - string to clean
			   $delim - all non-valid characters will be replaced with this
		Return: resultant string
		
		***/
		function createFileName($string, $max_length=50, $delim="-"){
									
			##Replace underscores and other non-word, non-digit characters with hyphens
			$string = trim(preg_replace('/[^a-zA-Z0-9\\._]++/', $delim, $string), $delim);
			
			##Trim it
			$string = General::limitWords($string, $max_length);
			
			return strtolower($string);
			
		}		


		/***
		
		Method: countWords
		Description: counts the number of words in a string
		Param: $string - string to examine
		Return: number of words contained in the string
		
		***/		
		function countWords($string){
			
			$string = preg_replace('/[^\\w\\s]/i', '', $string);
			
			$words = $chars = preg_split('/ /', $string, -1, PREG_SPLIT_NO_EMPTY);
			
			return count($words);
			
		}


		/***
		
		Method: limitWords
		Description: truncates a string so that it contains no more than a certain
		             number of characters, preserving whole words
		Param: $string - string to operate on
		       $maxChars - maximum number of characters
		       $appendHellip (optional) - can optionally append a hellip entity 
		                                  to the string if it is smaller than 
		                                  the input string
		Return: resultant string
		
		***/		
		function limitWords($string, $maxChars=200, $appendHellip=false, $truncateToSpace=false) {
			
			if($appendHellip) $maxChars -= 3;

			$string = trim(strip_tags(nl2br($string)));
			$original_length = strlen($string);
			
			if(trim($string) == '') return NULL;
			elseif(strlen($string) < $maxChars) return $string;
			
			$string = substr($string, 0, $maxChars);
			
			if($truncateToSpace && strpos($string, ' ')){
				$string = str_replace(strrchr($string, ' '), '', $string);
			}		
					
			$array  = explode(' ', $string);
			$result =  '';
						
			while(is_array($array) && !empty($array) && strlen(@implode(" ", $array)) > $maxChars){
				array_pop($array);				
			}			
			
			$result = trim(@implode(" ", $array));

			if($appendHellip && strlen($result) < $original_length)
				$result .= '...';
			
			return($result);
		}


		/***
		
		Method: dateDiff
		Description: this will calculate the number of seconds between two dates
		Param: $date1 - start date. Must be a valid unix timestamp
		       $date2 (optional) - end date. Must be a valid unix timestamp. If blank
		                           the current date is used
		Return: number of seconds elapsed
		
		***/		
		function dateDiff($date1, $date2=null){
			
			if(!$date2) $date2 = time();
			
			if($date1 < $date2)
				$diff = $date2 - $date1;
			else
				$diff = $date1 - $date2;	
				
			$m1 = (1 / 60);
			$m2 = (1 / 24);	
			
			return floor(((($diff * $m1) * $m1) * $m2));
			
		}


		function uploadFile($dest_path, $dest_name, $tmp_name, $perm=0777){
			
			##Upload the file
			if(@is_uploaded_file($tmp_name)) {
				
				$dest_path = rtrim($dest_path, "/") . "/";
			
				##Try place the file in the correction location	
				if(@move_uploaded_file($tmp_name, $dest_path . $dest_name)) {				
					@chmod($dest_path . $dest_name, intval($perm, 8));
					return true;					
				}
			}

			##Could not move the file
			return false;	
			
		}
		
		/***
		
		Method: formatFilesize
		Description: giving a filesize in bytes, this will format it for easier reading
		Param: $file_size - file size in bytes
		Return: formatted file size
		
		***/		
		function formatFilesize($file_size){
			
			$file_size = intval($file_size); //, 0);
			
			if($file_size >= (1024 * 1024)) 	$file_size = number_format($file_size * (1 / (1024 * 1024)), 2) . " mb";
			elseif($file_size >= 1024) 			$file_size = intval($file_size * (1/1024)) . " kb";
			else 								$file_size = intval($file_size) . " bytes";
			
			return $file_size;
		}


		/***

		Method: autolink
		Original Author: J de Silva (http://www.gidforums.com/t-1816.html)
		Description: handles converting URLs in a string, into anchor tags
		Param: $text - string to operate on
		       $target (optional) - the link target (E.G. _blank)
		       $nofollow (optional) - appends a rel="nofollow" into the link

		***/		
		function autolink($text, $target=NULL, $nofollow=true){
			
		  // grab anything that looks like a URL...
		  $urls = General::__autolink_find_URLS($text);
		  
		  if(!empty($urls)){ // i.e. there were some URLS found in the text{
		    array_walk($urls, array('General', '__autolink_create_html_tags'), array('target'=>$target, 'nofollow'=>$nofollow));
		    $text = strtr($text, $urls);
		  }
		  
		  return $text;
		}
		
		function __autolink_find_URLS($text){
			
		  // build the patterns
		  $scheme         =       '(http:\/\/|https:\/\/)';
		  $www            =       'www\.';
		  $ip             =       '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
		  $subdomain      =       '[-a-z0-9_]+\.';
		  $name           =       '[a-z][-a-z0-9]+\.';
		  $tld            =       '[a-z]+(\.[a-z]{2,2})?';
		  $the_rest       =       '\/?[a-z0-9._\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1}';            
		  $pattern        =       "$scheme?(?(1)($ip|($subdomain)?$name$tld)|($www$name$tld))$the_rest";
		    
		  $pattern        =       '/'.$pattern.'/is';
		  
		  $c              =       preg_match_all($pattern, $text, $m);
		  
		  unset($text, $scheme, $www, $ip, $subdomain, $name, $tld, $the_rest, $pattern);
		  
		  if($c){
		    return( array_flip($m[0]) );
		  }
		  
		  return(array());
		}
		
		function __autolink_create_html_tags( &$value, $key, $other=null){
		  $target = $nofollow = null;
		  
		  if(is_array($other)){
		    $target      =  ( $other['target']   ? " target=\"$other[target]\"" : null );
		    $nofollow    =  ( $other['nofollow'] ? ' rel="nofollow"'            : null );     
		  }
		  
		  $value = "<a href=\"$key\"$target$nofollow>$key</a>";
		} 
		
		function createXMLDateObject($timestamp){
			if(!class_exists('XMLElement')) return false;
							
			$xDate = new XMLElement('date', date('Y-m-d', $timestamp));
			$xDate->setAttribute('year', date('Y', $timestamp));
			$xDate->setAttribute('month', date('m', $timestamp));
			$xDate->setAttribute('date', date('d', $timestamp));
			$xDate->setAttribute('weekday', date('w', $timestamp));			
			
			return $xDate;
			
		}
		
		function createXMLTimeObject($timestamp){
			if(!class_exists('XMLElement')) return false;
			
			$xTime = new XMLElement('time', date('H:i', $timestamp));
			$xTime->setAttribute('hour', date('H', $timestamp));
			$xTime->setAttribute('minute', date('i', $timestamp));	
			
			return $xTime;					
		}
		
	}
	
?>