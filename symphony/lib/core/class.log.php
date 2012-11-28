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

	define("SYM_LOG_NOTICE", 0);
	define("SYM_LOG_WARNING", 1);
	define("SYM_LOG_ERROR", 2);
	define("SYM_LOG_ALL", 3);

	Class Log extends Object {

		var $_log_path;
		var $_log;
		var $_serverOffset;

		function __construct($args = NULL){
			$this->setLogPath($args['log_path']);
			$this->_serverOffset = date("Z") - (date("I") * 3600);
		}

		function __destruct(){
		}

		function setLogPath($path){
			$this->_log_path = $path;
		}

		function getLogPath(){
			return $this->_log_path;
		}

		function __defineNameString($type){

			switch($type){

				case SYM_LOG_NOTICE:
					return "SYM_LOG_NOTICE";

				case SYM_LOG_WARNING:
					return "SYM_LOG_WARNING";

				case SYM_LOG_ERROR:
					return "SYM_LOG_ERROR";

				case SYM_LOG_ALL:
					return "SYM_LOG_ALL";

				default:
					return "SYM_LOG_UNKNOWN";

			}

		}

		function pushToLog($message, $type=SYM_LOG_NOTICE, $writeToLog=false, $addbreak=true, $append=false){

			if(empty($this->_log) && !is_array($this->_log))
				$this->_log = array();

			if($append){
				$this->_log[count($this->_log) - 1]["message"] =  $this->_log[count($this->_log) - 1]["message"] . $message;

			}else{
				array_push($this->_log, array("type" => $type, "time" => time(), "message" => $message));
				$message = date("H:i:s", (time() - $this->_serverOffset)) . " > " . $this->__defineNameString($type) . ": " . $message;
			}

			if($writeToLog)
				$this->writeToLog($message, $addbreak);

		}

		function popFromLog(){
			if(count($this->_log) != 0)
				return array_pop($this->_log);

			return false;
		}

		function writeToLog($message, $addbreak=true){

			if (!$handle = @fopen($this->_log_path, 'a')) {
				$this->pushToLog("Could Not Open Log File '".$this->_log_path."'", SYM_LOG_ERROR);
				return false;
			}

			if (@fwrite($handle, $message . ($addbreak ? "\r\n" : "")) === FALSE) {
				$this->pushToLog("Could Not Write To Log", SYM_LOG_ERROR);
				return false;
			}

			@fclose($handle);

			return true;

		}

		function getLog(){
			return $this->_log;
		}

		function open($mode = "APPEND"){

			if($mode == "OVERRIDE"){
				@unlink($this->_log_path);

				$this->writeToLog("============================================", true);
				$this->writeToLog("Log Created: " . date("d.m.y H:i:s", (time() - $this->_serverOffset)) . " GMT", true);
				$this->writeToLog("============================================", true);
			}

		}

		function close(){

			$this->writeToLog("============================================", true);
			$this->writeToLog("Log Closed: " . date("d.m.y H:i:s", (time() - $this->_serverOffset)) . " GMT", true);
			$this->writeToLog("============================================\r\n\r\n\r\n", true);

		}
	}

?>