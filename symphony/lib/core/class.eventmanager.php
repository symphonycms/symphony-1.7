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

    Class EventManager extends Manager{

	    var $_pageEvents;


        function __construct($parent){
			parent::__construct($parent);
			$this->_pageEvents = array();
        }

	    function __find($name){

		    if(@is_file(EVENTS . "/event.$name.php")) return EVENTS;
		    else{
				$structure = General::listStructure(CAMPFIRE, array(), false, "asc", CAMPFIRE);
				$owners = $structure['dirlist'];

				if(is_array($owners) && !empty($owners)){
					foreach($owners as $o){
						$services = General::listStructure(CAMPFIRE . "/$o", array(), false, "asc", CAMPFIRE . "/$o");

						if(is_array($services['dirlist']) && !empty($services['dirlist'])){
							foreach($services['dirlist'] as $s)
								if(@is_file(CAMPFIRE . "/$o/$s/events/event.$name.php")) return CAMPFIRE . "/$o/$s/events";
						}
					}
				}
	    	}

		    return false;
	    }

        function __getClassName($name, $owner = NULL){
	        return "event" . $name;
        }

        function __getClassPath($name, $owner = NULL){
	        return $this->__find($name);
        }

        function __getDriverPath($name, $owner = NULL){
	        return $this->__getClassPath($name) . "/event.$name.php";
        }

		function __getHandleFromFilename($filename){
			return str_replace(array('event.', '.php'), '', $filename);
		}

        function addEvent($name){
	        $this->_pageEvents[] = $name;
        }

        function fireEvents(&$xml, $param=array()){

			$called_data_sources = array();
			$events_prioritised[kHIGH] = array();
			$events_prioritised[kNORMAL] = array();
			$events_prioritised[kLOW] = array();

			if(@is_array($this->_pageEvents) && !empty($this->_pageEvents)){
				foreach($this->_pageEvents as $name){

					if(!@is_file($this->__getDriverPath($name)))
						$this->_parent->fatalError("Specified event '".$name."' could not be loaded");

					$classname = $this->__getClassName($name);

					if(!@in_array($classname, $called_events)){

						$event =& $this->create($name, $param);

						$events_prioritised[$event->getPriority()][] = $name;

						$called_events[] = $classname;
						unset($event);
					}
				}

				$final_event_list = @array_merge($events_prioritised[kHIGH],
												$events_prioritised[kNORMAL],
												$events_prioritised[kLOW]);

				foreach($final_event_list as $name){

					$event =& $this->create($name, $param);
					$result = $event->load();

					if(@is_object($result))
						$xml->addChild($result);

					else
						$xml->setValue($xml->_value . $result);

					unset($event);

				}
			}
        }

        function flush(){
	        parent::flush();
	        $this->_pageEvents = array();
        }

        function listAll(){

			$result = array();
			$people = array();

	        $structure = General::listStructure(EVENTS, '/event.[\\w-]+.php/', false, "asc", EVENTS);

	        if(is_array($structure['filelist']) && !empty($structure['filelist'])){
	        	foreach($structure['filelist'] as $f){
		        	$f = str_replace(array("event.", ".php"), "", $f);

					if($about = $this->about($f)){

						$classname = $this->__getClassName($f);
						$path = $this->__getDriverPath($f);
						$can_parse = false;
						$type = NULL;

						if(is_callable(array($classname,'allowEditorToParse')))
							$can_parse = @call_user_func(array(&$classname, "allowEditorToParse"));

						if(is_callable(array($classname,'getType')))
							$type = @call_user_func(array(&$classname, "getType"));

						$about['priority'] = @call_user_func(array(&$classname, "getPriority"));
						$about['can_parse'] = $can_parse;
						$about['type'] = $type;
						$result[$f] = $about;

					}
				}
			}

			$structure = General::listStructure(CAMPFIRE, array(), false, "asc", CAMPFIRE);
			$owners = $structure['dirlist'];

			if(is_array($owners) && !empty($owners)){
				foreach($owners as $o){
					$services = General::listStructure(CAMPFIRE . "/$o", array(), false, "asc", CAMPFIRE . "/$o");

					if(is_array($services) && !empty($services)){
						foreach($services['dirlist'] as $s){

							$tmp = General::listStructure(CAMPFIRE . "/$o/$s/events", '/event.[\\w-]+.php/', false, "asc", CAMPFIRE . "/$o/$s/events");

				        	if(is_array($tmp['filelist']) && !empty($tmp['filelist'])){
				        		foreach($tmp['filelist'] as $f){
					        		$f = str_replace(array("event.", ".php"), "", $f);

									if($about = $this->about($f, NULL, CAMPFIRE . "/$o/$s/events")){

										$classname = $this->__getClassName($f);

										$can_parse = false;
										$type = NULL;

										$about['priority'] = @call_user_func(array(&$classname, "getPriority"));
										$about['can_parse'] = $can_parse;
										$about['type'] = $type;
										$result[$f] = $about;
									}

								}
							}
						}
					}
				}
			}

			ksort($result);
			return $result;
        }

        ##Creates a new campfire object and returns a pointer to it
        function &create($name, $param=array(), $slient=false){

	        $classname = $this->__getClassName($name);
	        $path = $this->__getDriverPath($name);

	        if(!@is_file($path)){
		        if(!$slient && @is_object($this->_log))
		        	$this->_log->pushToLog("EVENT: Could not find event at location '$path'", SYM_LOG_ERROR, true);

		        return false;
	        }

			if(!@class_exists($classname))
				require_once($path);

			if(!@is_array($param)) $param = array();

			if(!isset($param['parent'])) $param['parent'] = $this->_parent;

			##Create the object
			$this->_pool[] =& new $classname($param);

			return end($this->_pool);

        }

    }

?>