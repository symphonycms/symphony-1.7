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

	Class SymDate{

		var $_timestamp = null;
		var $_format = null;

		var $_serverOffset = null;
		var $_serverDST = null;
		var $_localOffset = null;
		var $_localDST = null;

		function SymDate($offset, $format="d/m/Y G:i:s", $dst=false){

			$this->_format = $format;

			$this->_serverOffset = date("Z") - (date("I") * 3600);
			$this->_serverDST = date("I") * 3600;

			$this->_localDST = ($dst ? 1 : 0) *  3600;
			$this->_localOffset = $offset * 3600;

			$this->set();

		}

		function setFormat($format){
			$this->_format = $format;
		}

		function get($local=true, $applyformatting=false, $gmt_timestamp=null){

			if($gmt_timestamp) $this->_timestamp = $gmt_timestamp;

			$stamp = ($local ? $this->__GMT2local($this->_timestamp) : $this->_timestamp);

			if($applyformatting) $stamp = date($this->_format, $stamp);

			return $stamp;
		}

		function set($timestamp=NULL, $local=true){
			if(!$timestamp) $this->_timestamp = $this->__server2GMT(time());
			else{
                $this->_timestamp = ($local
									? $this->__local2GMT($timestamp)
									: $timestamp);
            }
            $this->_timestamp_readable = date("d.m.Y H:i:sa", $this->_timestamp);
		}

		function __server2GMT($timestamp){
			return ($timestamp - ($this->_serverOffset + $this->_serverDST));
		}

		function __local2GMT($localtime){
			return ($localtime - ($this->_localOffset + $this->_localDST));
		}

		function __GMT2local($gmttime){
			return ($gmttime + ($this->_localOffset + $this->_localDST));
		}

	}

?>