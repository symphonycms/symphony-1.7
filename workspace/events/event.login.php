<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	Class eventLogin Extends Event{
		
		function __construct($args = array()){
			parent::__construct($args);
		}
		
		function about(){		
			return array(
						 "name" => "Login Info",
						 "description" => "This is an event that displays basic login details (such as their real name, username and author type) if the person viewing the site have been authenticated by logging in to Symphony.</p><p>This event is useful if you want to do something special with the site if the person viewing it is an authenticated member.",
						 "author" => array("name" => "Alistair Kearney",
										   "website" => "http://www.pointybeard.com",
										   "email" => "alistair@pointybeard.com"),
						 "version" => "1.2",
						 "release-date" => "2007-03-11",
						 "trigger-condition" => "action[login] field or an already valid Symphony cookie",						 
						 "recognised-fields" => array(
													array("username", true), 
													array("password", true)
												));						 
		}
				
		function load(){

			if(isset($_POST['action']['login']) || isset($_COOKIE[__SYM_COOKIE__])) return $this->trigger();
			
			$result = new XMLElement("user");
			$result->setAttribute("logged-in", "false");

			return $result;	
			
		}
		
		function trigger(){

			$username = $_POST['username'];
			$password = md5($_POST['password']);			
		
			if(isset($_COOKIE[__SYM_COOKIE__]) && !isset($_POST['action']['login'])){					
				$args = unserialize(base64_decode($_COOKIE[__SYM_COOKIE_SAFE__]));
				$username = $args['username'];
				$password = $args['password'];
			}
				
			$sql  = "SELECT *
					FROM `tbl_authors`
					WHERE `username` = '".addslashes($username)."'
					AND `password` = '".$password . "'";
			
			$row = $this->_db->fetchRow(0, $sql);
		
			if(!empty($row) && is_array($row)) {
				
				$sql = "UPDATE `tbl_authors` SET `lastvisit` = UNIX_TIMESTAMP() WHERE `id` = '".$row['id']."'";
				$this->_db->query($sql);
		
				setcookie(__SYM_COOKIE__, serialize($row), time() + 31536000, $this->_parent->getCookieDomain());		
				setcookie(__SYM_COOKIE_SAFE__, base64_encode(serialize($row)), time() + 31536000, $this->_parent->getCookieDomain());	

				$status = 'Author';
				
				if($row['owner'] == 1) $status = 'Owner';
				elseif($row['superuser'] == 1) $status = 'Administrator';
			
				$result = new XMLElement("user");
				$result->setAttribute("logged-in", "true");
				$result->addChild(new XMLElement("username", $row['username']));
				$result->addChild(new XMLElement("first-name", $row['firstname']));
				$result->addChild(new XMLElement("last-name", $row['lastname']));
				$result->addChild(new XMLElement("email", $row['email']));
				$result->addChild(new XMLElement("account-type", $status));
				
			}else{
				
				$result = new XMLElement("user");
				$result->setAttribute("logged-in", "false");
			}
			
			return $result;
			
		}
	}

?>