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

	Class SimpleHTML{
		
		var $_hashTable;
		var $_data;
		
		function SimpleHTML(){			
			$this->_hashTable = array();
		}
		
		function __addHashKeyToTable($value){
			$this->_hashTable[md5($value)] = $value;
		}
		
		function __replaceWithHash($value, $addToTable = true){
			$this->_data = str_replace($value, md5($value), $this->_data); 
			$this->__addHashKeyToTable($value);
		}
		
		function __resolveHashTable(){
			foreach($this->_hashTable as $key => $value)
				$this->_data = str_replace($key, $value, $this->_data); 	
		}
		
		function process($data, $groups=NULL, $encode=false){
			
			$priority = array("entities", "images", "link", "acronym");
			
			if(empty($groups))
				$groups = array("entities", "heading", "list", "quote", "text", "link", "images", "acronym");	
			
			$this->_data = $data;
			
			foreach($priority as $group){
				if(in_array($group, $groups)){ 
					$this->{"__$group"}();
				}	
			}	
						
			$this->_data = General::sanitize($this->_data);
			$this->_data = "<p>" . trim($this->_data) . "</p>";
				
			foreach($groups as $group)
				if(!in_array($group, $priority)) $this->{"__$group"}();
			
			$this->_data = str_replace("\r\n\r\n", "</p><p>", $this->_data);
			
			$this->_data = str_replace("<ol></p><p>", "<ol>", $this->_data);
			$this->_data = str_replace("<ul></p><p>", "<ul>", $this->_data);
			$this->_data = str_replace("</li></p><p>", "</li>", $this->_data);
			
			$this->_data = preg_replace('/<p>[\\s]*<\/p>/i', '', $this->_data);
			$this->_data = preg_replace('/<\/p>[\\s]*+<p>/i', "</p>\n<p>", $this->_data);
						
			if($encode) $this->_data = General::sanitize($this->_data);//, ENT_COMPAT, "UTF-8");
			
			$this->__resolveHashTable();
			
			return $this->_data;
			
		}
	
		function __heading(){
			
			$replacements = array();
			$patterns = array();
			
			for($ii = 1; $ii < 7; $ii++){
				$replacements[] = "</p><h$ii>";
				$replacements[] = "</h$ii><p>";
				
				$patterns[] = "&lt;h$ii&gt;";
				$patterns[] = "&lt;/h$ii&gt;";					
			}
			
			$this->_data = str_replace($patterns, $replacements, $this->_data);	
			
		}
		
		function __list(){
			
			$replacements = array();
			$patterns = array();			
			
			$replacements[] = "</p><ul>";
			$replacements[] = "</ul><p>";
			$patterns[] = "&lt;ul&gt;";
			$patterns[] = "&lt;/ul&gt;";	
							
			$replacements[] = "</p><ol>";
			$replacements[] = "</ol><p>";				
			$patterns[] = "&lt;ol&gt;";
			$patterns[] = "&lt;/ol&gt;";	
			
			$replacements[] = "<li>";				
			$patterns[] = "&lt;li&gt;";	
								
			$replacements[] = "</li>";				
			$patterns[] = "&lt;/li&gt;";	
						
			$this->_data = str_replace($patterns, $replacements, $this->_data);	
			
		}
		
		function __entities(){			
			preg_match_all('/(&[\\w\\d\\#x]{2,6};)/', $this->_data, $result, PREG_PATTERN_ORDER);
			for ($i = 0; $i < count($result[0]); $i++) {
				$this->__replaceWithHash($result[0][$i]);
			}			
		}
		
		function __acronym(){	
			$this->__searchAndReplaceElement("acronym");	
		}
		
		function __quote(){
		}
				
		function __text(){
			
			$replacements = array(
									"<strong>", "</strong>",
									"<em>", "</em>",
									"<i>", "</i>",
									"<b>", "</b>",
									"<u>", "</u>",
									"<strike>", "</strike>"
								);
			
			foreach($replacements as $r)
				$patterns[] = General::sanitize($r);//, ENT_COMPAT, "UTF-8");
			
			$replacements[] = "&mdash;";
			$replacements[] = " &ndash; ";
			$replacements[] = "&hellip;";
			$replacements[] = "&quot;";
			
			$patterns[] = "--";
			$patterns[] = " - ";
			$patterns[] = "...";	
			$patterns[] = "&amp;quot;";	
			
			$this->_data = str_replace($patterns, $replacements, $this->_data);	
		}	
		
		function __link(){
			
			$tokens = $this->__tokenize();
			
			// there should always be at least one token, but check just in case
			if (isset($tokens) && is_array($tokens) && count($tokens) > 0){
				$i = 0;
				foreach ($tokens as $token){
					
					if (++$i % 2 && $token != ''){ // this token is (non-markup) text
						if ($anchor_level == 0){ // linkify if not inside anchor tags			
							$token = General::autolink($token);
						}
						
					}else{  // this token is markup
					
						if (preg_match("#<\s*a\s+[^>]*>#i", $token))      // found <a ...>
							$anchor_level++;
							
						else if (preg_match("#<\s*/\s*a\s*>#i", $token))  // found </a>
							$anchor_level--;
					}
					$filtered .= $token;          // this token has now been filtered
				}
				$this->_data = $filtered;         // filtering completed for this link
			}   			
			
			$this->__searchAndReplaceElement("a");

		}	
		
		function __images(){
			$this->__searchAndReplaceElement("img");	
			
		}		
		
		function __searchAndReplaceElement($name){
			
			preg_match_all("#<\s*$name\s+[^>]*>#i", $this->_data, $result);
			
			if(is_array($result[0]) && !empty($result[0])){
				foreach($result[0] as $match)
					$this->__replaceWithHash($match);		
			}  
			
			$this->__replaceWithHash("</$name>");		
		}
		
		function __tokenize(){
			
			## Thanks to http://greghurrell.net/wp/wp-content/plugins/autolink.phps for some help
			## on the tokenisation part
			
			$comment                = '(?s:<!(?:--.*?--\s*)+>)|';
			$processing_instruction = '(?s:<\?.*?\?>)|';
			$tag = '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';
			$markup         = $comment . $processing_instruction . $tag;
			$flags          = PREG_SPLIT_DELIM_CAPTURE;
			$tokens         = preg_split("{($markup)}", $this->_data, -1, $flags);
			
			return $tokens;
		}			
			
	}
	
?>
