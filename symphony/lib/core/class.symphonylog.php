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

	require_once(dirname(__FILE__) . "/class.log.php");
	Class SymphonyLog extends Log{

		function __construct($args = NULL){
			parent::__construct($args);

			if(@file_exists($this->getLogPath())){
				$this->open();

			}else{
				$this->open("OVERRIDE");
				$this->writeToLog("Symphony Log", true);
				$this->writeToLog("Opened: ". date("d.m.Y G:i:s"), true);
				$this->writeToLog("Build: ". $args["symphony"]["build"], true);
				$this->writeToLog("--------------------------------------------", true);
				$this->writeToLog("Environment Variables", true);
				$this->writeToLog("--------------------------------------------", true);

				$constants = @get_defined_constants(true);

				if(!empty($constants) && is_array($constants)){
					foreach($constants["user"] as $key => $val){
						$this->writeToLog(General::padString($key, 30) . ": \t $val", true);
					}
				}

				$this->writeToLog("--------------------------------------------", true);
			}
		}

	}


?>