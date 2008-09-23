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

	/*******************
		XMLElement

		Original Author: Daniel Bogan <ghost@waferbaby.com>
		Creation Date: 2003/06/05 08:14:59
		Based on Version: 1.2
	
		Modified by: Alistair Kearney
	
		Changes: - Made entire class more standalone
				 - Added setEncoding, setVersion, setIncludeHeader functions
				 - Added output indentation formatting on generate function
				 - Added ability to include XML header with output
	
	********************/

	Class XMLElement {

		var	$_name;
		var	$_value;
		var	$_attributes;
		var	$_children;
		var $_encoding;
		var $_version;
		var $_includeHeader;
	
		function XMLElement( $name, $value = NULL ) {
	
			$this->_name = $name;
		
			if( $value != NULL ) {
				$this->setValue( $value );
			}
		
			$this->_attributes = array();
			$this->_children = array();
		
			$this->setEncoding("utf-8");
			$this->setVersion("1.0");
			$this->setIncludeHeader();
	
		}
	
		function setEncoding($value){
			$this->_encoding = $value;
		}
	
		function setVersion($value){
			$this->_version = $value;		
		}
	
		function setIncludeHeader($value = false){
			$this->_includeHeader = $value;		
		}
	
		function setValue( $value ) {
			$this->_value = $value;
		}
	
		function setAttribute( $name, $value ) {
			$this->_attributes[$name] = $value;
		}
	
		function addChild( $child ) {
			$this->_children[] = $child;
		}
	
		function getNumberOfChildren() {	
			return count( $this->_children );
		}
		
		function generate($indent=false, $tab_depth=0) {
	
			$result = "";
		
			$newline = ($indent ? "\n" : "");
		
			if($this->_includeHeader){
				$result .= '<?xml version="'.$this->_version.'" encoding="'.$this->_encoding.'" ?>' . $newline;
			}
		
			$result .= ($indent ? General::repeatStr("\t", $tab_depth) : "") . "<" . $this->_name;
		
			if( count( $this->_attributes ) > 0 ) {
			
				foreach( $this->_attributes as $attribute => $value ) {
			
					if( $value != NULL ) {
						$result .= " $attribute=\"$value\"";
					}
				}
			}
		
			$numberOfchildren = $this->getNumberOfChildren();
		
			if( $numberOfchildren > 0 || $this->_value != NULL ) {
		
				$result .= ">";
			
				if( $this->_value != NULL ) {
			
					$result .= $this->_value;
				}
			
				if( $numberOfchildren > 0 ) {
			
					$result .= $newline;
				
					foreach( $this->_children as $child ) {
						$result .= $child->generate($indent, $tab_depth + 1);
					}
				
					if($indent)
						$result .= General::repeatStr("\t", $tab_depth);
				}
			
				$result .= "</" . $this->_name . ">" . $newline;	
			
			
			} else {
		
				$result .= " />" . $newline;
			}
		
			return $result;
		}
	}

?>