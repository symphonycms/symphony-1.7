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

	define("PROFILE_RUNNING_TOTAL", 0);
	define("PROFILE_LAP", 1);

	Class Profiler{

		var $_starttime;
		var $_records;

		function Profiler(){
			$this->_records = array();
			$this->_starttime = precision_timer();
		}

		function retrieveLast(){
			return end($this->_records);
		}

		function quickDisplayItem($index='LAST'){
			if($index == 'LAST')
				$item = $this->retrieveLast();

			else
				$item = $this->retrieve($index);

			return $item[0] . " (" . $item[1] . "sec) \n";
		}

		function sample($msg, $type=PROFILE_RUNNING_TOTAL){

			if($type == PROFILE_RUNNING_TOTAL)
				$this->_records[] = array($msg, precision_timer("stop", $this->_starttime), precision_timer());

			else{
				$prev = end($this->_records);
				$this->_records[] = array($msg, precision_timer("stop", $prev[2]), precision_timer());
			}
		}

		function retrieve($index=NULL){
			return ($index !== NULL ? $this->_records[$index] : $this->_records);
		}
	}

?>