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

    Class CampfireManager extends Manager{
	    
        function __construct($args){
			parent::__construct($args);
        }
        
        function __getClassName($name, $owner){
	        return "service_" . $owner . $name;
        }
        
        function __getClassPath($name, $owner){
	        return CAMPFIRE . strtolower("/$owner/$name");
        }
        
        function __getDriverPath($name, $owner){
	        return $this->__getClassPath($name, $owner) . "/service.driver.php";
        }       
        
        function getClassPath($name, $owner){
	        return CAMPFIRE . strtolower("/$owner/$name");
        }

		function sortByStatus($s1, $s2){
			
			if($s1['status'] == 'enabled') $status_s1 = 2;
			elseif($s1['status'] == 'disabled') $status_s1 = 1;
			else $status_s1 = 0;
			
			if($s2['status'] == 'enabled') $status_s2 = 2;
			elseif($s2['status'] == 'disabled') $status_s2 = 1;			
			else $status_s2 = 0;	
			
			return $status_s2 - $status_s1;		
		}
 
		function update($name, $owner){

			$id = $this->registerService($name, $owner, false);
			
			## Run the update() function of the service
			if(false != ($obj =& $this->create($name, $owner))){
				$obj->update();
				unset($obj);
			}
			
			return $this->pruneService($name, $owner, true);			
		}
 
		function install($name, $owner){
			
			$id = $this->registerService($name, $owner);

			## Run the install() function of the service
			if(false != ($obj =& $this->create($name, $owner))){
				$obj->install();
				unset($obj);
			}
						
		}
      
		function enable($name, $owner){
			
			$id = $this->registerService($name, $owner);
			
			## Run the enable() function of the service
			if(false != ($obj =& $this->create($name, $owner))){
				$obj->enable();
				unset($obj);
			}
						
		}

		function disable($name, $owner){
			
			$id = $this->registerService($name, $owner, false);
			
			## Run the disable() function of the service
			if(false != ($obj =& $this->create($name, $owner))){
				$obj->disable();
				unset($obj);
			}
						
			return $this->pruneService($name, $owner, true);			
		}

		function uninstall($name, $owner){
			
			## Run the uninstall() function of the service
			if(false != ($obj =& $this->create($name, $owner))){
				$obj->uninstall();
				unset($obj);
			}
						
			return $this->pruneService($name, $owner);			
		}
		
		function fetchStatus($name, $owner){
			$status = $this->_db->fetchVar('status', 0, "SELECT `status` FROM `tbl_campfire` WHERE `id` = MD5('$name$owner') LIMIT 1");			
			return ($status ? $status : 'not installed');
		}
		
		function pruneService($name, $owner, $delegates_only = false){

	        $classname = $this->__getClassName($name, $owner);   
	        $path = $this->__getDriverPath($name, $owner);

			if(!@file_exists($path)) return false;
			
			$this->_db->query("DELETE FROM `tbl_campfire2delegates` WHERE `campfire_id` = MD5('$name$owner')");
			if(!$delegates_only) $this->_db->query("DELETE FROM `tbl_campfire` WHERE `id` = MD5('$name$owner')");
			
			## Remove the unused campfire DB records
			$this->__cleanupDatabase();
			
			return true;					
		}
		
		function registerService($name, $owner, $enable=true){

	        $classname = $this->__getClassName($name, $owner);   
	        $path = $this->__getDriverPath($name, $owner);

			if(!@file_exists($path)) return false;

			require_once($path);
			
			$subscribed = call_user_func(array(&$classname, "getSubscribedDelegates"));
			
			$this->_db->query("DELETE FROM `tbl_campfire2delegates` WHERE `campfire_id` = MD5('$name$owner')");
			$this->_db->query("DELETE FROM `tbl_campfire` WHERE `id` = MD5('$name$owner')");		
			
			if(is_array($subscribed) && !empty($subscribed)){
				foreach($subscribed as $s){
					
					$sql = "INSERT INTO `tbl_campfire2delegates` 
							VALUES ('', MD5('$name$owner'), '".$s['page']."', '".$s['delegate']."', '".$s['callback']."')";
							
					$this->_db->query($sql);
					
				}
			}
			
			$info = $this->about($name, $owner);
			
			$sql = "INSERT INTO `tbl_campfire` 
					VALUES (MD5('$name$owner'), '$owner', '$name', '".($enable ? 'enabled' : 'disabled')."', ".floatval($info['version']).")";
			
			$this->_db->query($sql);	
			
			$id = $this->_db->getInsertID();
			
			## Remove the unused campfire DB records
			$this->__cleanupDatabase();
			
			return $id;
		}

        ##Will return a list of all services and their about information
        function listAll(){
	        
			$result = array();	
			$people = array();
			
	        $structure = General::listStructure(CAMPFIRE, array(), true, "asc", CAMPFIRE);
	
	        $directories = $structure['dirlist'];
	        
	        if(is_array($directories) && !empty($directories)){
		        foreach($directories as $d){			        
			        $people[$d] = $structure["/$d/"]['dirlist'];			        
		        }
	        }

	        if(is_array($people) && !empty($people)){
		        foreach($people as $owner => $dirlist){

			        if(is_array($dirlist) && !empty($dirlist)){
			        	foreach($dirlist as $name){
				        	
							if($about = $this->about($name, $owner)){
								$about['status'] = $this->fetchStatus($name, $owner);
								$result[$owner][$name] = $about;
									
							}
																				
						}
					}
				}
			}

			return $result;
        }
        
        function notifyMembers($delegate, $page, $context=array()){

	        if($this->_parent->getConfigVar("allow_page_subscription", "symphony") != '1') return;
	        
			$services = $this->_db->fetch("SELECT t1.*, t2.callback FROM `tbl_campfire` as t1 
											LEFT JOIN `tbl_campfire2delegates` as t2 ON t1.id = t2.campfire_id
											WHERE (t2.page = '$page' OR t2.page = '*')
											AND t2.delegate = '$delegate'
											AND t1.status = 'enabled'");

			if(!is_array($services) || empty($services)) return NULL;
	
	        $context += array("parent" => &$this->_parent, 'page' => $page, 'delegate' => $delegate);
			
			foreach($services as $s) {

				if(false != ($obj =& $this->create($s['name'], $s['owner']))){

					if(is_callable(array($obj, $s['callback']))){
						$obj->{$s['callback']}($context);
						unset($obj);
					}				
				}	
								
			}
		  	
        }
        
        ##Creates a new campfire object and returns a pointer to it
        function &create($name, $owner, $param=array(), $slient=false){
	        
	        $classname = $this->__getClassName($name, $owner);	        
	        $path = $this->__getDriverPath($name, $owner);
	        
	        if(!@is_file($path)){
		        if(!$slient && @is_object($this->_log))
		        	$this->_log->pushToLog("CAMPFIRE: Could not find service at location '$path'", SYM_LOG_ERROR, true);
		        	
		        return false;
	        }
	        
			if(!class_exists($classname))									
				require_once($path);
			
			if(!is_array($param)) $param = array();	
				
			if(!isset($param['parent'])) $param['parent'] =& $this->_parent;	
			
			##Create the object
			$this->_pool[] =& new $classname($param);	
								
			return end($this->_pool);
	        
        }

  		function __hasUpdateMethod($classname){
			if(!class_exists($classname)) return false;
			
			$methods = get_class_methods($classname);

			return (@in_array('update', $methods) ? true : false);
			
		} 
		     
		function __hasUninstallMethod($classname){
			if(!class_exists($classname)) return false;
			
			$methods = get_class_methods($classname);

			return (@in_array('uninstall', $methods) ? true : false);
			
		}

		function requiresUpdate($name, $owner){
			$info = $this->about($name, $owner);
			
			if($info['status'] == 'not installed' || !$info['has-update-method']) return false;
			
			if($version = $this->fetchInstalledVersion($name, $owner))
				return $version < floatval($info['version']);
				
			return false;
			
		}
		
		function fetchInstalledVersion($name, $owner){
			$version = $this->_db->fetchVar('version', 0, "SELECT `version` FROM `tbl_campfire` WHERE `id` = MD5('$name$owner') LIMIT 1");			
			return ($version ? floatval($version) : NULL);
		}
		
        ##Returns the about details of a service
        function about($name, $owner){
	
	        $classname = $this->__getClassName($name, $owner);   
	        $path = $this->__getDriverPath($name, $owner);

			if(!@file_exists($path)) return false;

			require_once($path);	
			        
	        if($about = @call_user_func(array(&$classname, "about"))){
				
				$about['has-uninstall-method'] = $this->__hasUninstallMethod($classname);
				$about['has-update-method'] = $this->__hasUpdateMethod($classname);
				$about['status'] = $this->fetchStatus($name, $owner);
				$about['panel'] = (@is_file($this->__getClassPath($name, $owner) . "/interface/content.index.php") ? 1 : 0);

				## Support the old method of storing author details
				if(!is_array($about['author'])){				
					$about['author'] = array(
						'name' => $about['author'],
						'website' => $about['url'],
						'email' => $about['contact']					
					);
				}

				return $about;
			}

			return false;
									        
        }

		function __cleanupDatabase(){
			
			## Grab any services sitting in the database
			$rows = $this->_db->fetch("SELECT * FROM `tbl_campfire`");
			
			## Iterate over each row
			if(is_array($rows) && !empty($rows)){
				foreach($rows as $r){
					$name = $r['name'];
					$owner = $r['owner'];
					
					## Grab the install location of the service
					$path = $this->__getClassPath($name, $owner);
					
					## If it doesnt exist, remove the DB rows
					if(!@is_dir($path)){
						
						$sql = "DELETE FROM `tbl_campfire` WHERE `owner` = '$owner' AND `name` = '$name' LIMIT 1";
						$this->_db->query($sql);

						$sql = "DELETE FROM `tbl_campfire2delegates` WHERE `campfire_id` = MD5('$owner$name')";
						$this->_db->query($sql);
						
					}
				}
			}
			
			## Remove old delegate information
			$disabled = $this->_db->fetchCol('id', "SELECT `id` FROM `tbl_campfire` WHERE `status` = 'disabled'");
			$sql = "DELETE FROM `tbl_campfire2delegates` WHERE `campfire_id` IN ('".@implode("', '", $disabled)."')";
			$this->_db->query($sql);			
			
		}
    
    }
    

?>