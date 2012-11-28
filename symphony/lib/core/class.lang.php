<?php

	/***
	 *
	 * Symphony web publishing system
	 *
	 * Copyright 2004–2006 Twenty One Degrees Pty. Ltd.
	 *
	 * @version 1.7
	 * @licence https://github.com/symphonycms/symphony-1.7/blob/master/LICENCE
	 *
	 ***/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

	Class Lang{

		/***

		Method: createHandle
		Description: given a string, this will clean it for use as a Symphony handle
		Param: $string - string to clean
		       $max_length - the maximum number of characters in the handle
			   $delim - all non-valid characters will be replaced with this
			   $uriencode - force the resultant string to be uri encoded making it safe for URL's
			   $apply_transliteration - If true, this will run the string through an array of substitution characters
		Return: resultant handle

		***/
		function createHandle($string, $max_length=50, $delim="-", $uriencode=false, $apply_transliteration=true){

			## Use the transliteration table if provided
			if($apply_transliteration){
				include(LANG . '/transliteration.php');
				$string = strtr($string, $kTransliteration);
			}

			$max_length = intval($max_length);

			## Strip out any tag
			$string = strip_tags($string);

			## Remove punctuation
			$string = preg_replace('/([\\.\'"]++)/', "", $string);

			## Trim it
			$string = General::limitWords($string, $max_length);

			## Replace spaces (tab, newline etc) with the delimiter
			$string = preg_replace('/([\s]++)/', $delim, $string);

			## Replace underscores and other non-word, non-digit characters with hyphens
			//$string = preg_replace('/[^a-zA-Z0-9]++/', $delim, $string);
			$string = preg_replace('/[<>?@:!-\/\[-`ëí;‘’]++/', $delim, $string);

			## Remove leading or trailing delim characters
			$string = trim($string, $delim);

			## Encode it for URI use
			if($uriencode) $string = urlencode($string);

			## Make it lowercase
			$string = strtolower($string);

			return $string;

		}


	}

?>