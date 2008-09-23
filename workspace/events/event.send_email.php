<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

	Class eventSend_Email Extends Event{
		
		function __construct($args = array()){
			parent::__construct($args);
		}
		
		function about(){		
			return array(
						 "name" => "Send Email",
						 "description" => "Send someone an email",
						 "author" => array("name" => "Alistair Kearney",
										   "website" => "http://www.pointybeard.com",
										   "email" => "alistair@pointybeard.com"),
						 "version" => "1.2",
						 "release-date" => "2006-03-22",
						 "trigger-condition" => "action[send-email] field",						 
						 "recognised-fields" => array(
													array("name", true),
													array("email", true),
													array("subject", true),
													array("message", true),	
													array("recipient-username", true))
												);						 
		}	
			
		function load(){
			if(isset($_POST['action']['send-email'])) return $this->trigger();
			return;
			
		}
		
		function trigger(){
	
			$result = new XMLElement("send-email");			
			
			$fields['recipient_username'] = $_POST['recipient-username'];
			$fields['email'] = $_POST['email'];
			$fields['name'] = $_POST['name'];
			
			$fields['subject'] = stripslashes(strip_tags($_POST['subject']));	
			$fields['message'] = stripslashes(strip_tags($_POST['message']));	
			
			$fields = array_map("trim", $fields);
			
			## Create the cookie elements
			$cookie = new XMLElement("cookie");
			$cookie->addChild(new XMLElement("name", $fields['name']));
			$cookie->addChild(new XMLElement("email", $fields['email']));
			$cookie->addChild(new XMLElement("subject", $fields['subject']));
			$cookie->addChild(new XMLElement("message", General::sanitize($fields['message'])));
			$result->addChild($cookie);			
			
			$usernames = @implode("', '", @explode(" ", $fields['recipient_username']));				
			$email_addresses = $this->_parent->_db->fetchCol("email", "SELECT `email` FROM `tbl_authors` WHERE `username` IN ('".$usernames."')");
					
			$canProceed = true;		
							
			if($fields['email'] == ""
				|| $fields['name'] == ""
				|| $fields['subject'] == ""
				|| $fields['message'] == "" ){
					
				$xMissing = new XMLElement("missing");	
					
				if($fields['email'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "email");
					$xMissing->addChild($missing);
				}
				
				if($fields['name'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "name");
					$xMissing->addChild($missing);
				}	
				
				if($fields['subject'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "subject");
					$xMissing->addChild($missing);
				}	
				
				if($fields['message'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "message");
					$xMissing->addChild($missing);				
				}					
				
				$result->addChild($xMissing);
				$canProceed = false;
			}
			
			if(!ereg('^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$', $fields['email'])){
					$invalid = new XMLElement("invalid");	
					$xInvalid = new XMLElement("input");
					$xInvalid->setAttribute("name", "email");
					$invalid->addChild($xInvalid);
					$result->addChild($invalid);		
					$canProceed = false;
			}
			
			if(!$canProceed)
				$result->setAttribute("sent", "false");	
							
			else{
				
				$errors = array();
				
				foreach($email_addresses as $e){
					if(!General::sendEmail($e, 
									   $fields['email'], 
									   $fields['name'], 
									   $fields['subject'], 
									   $fields['message']))
									       $errors[] = $fields['recipient-email'];
					
				}
				
				if(!empty($errors)){
                    $result->addChild(new XMLElement("notice", "Email could not be sent. An unknown error occurred."));
                    $result->setAttribute("sent", "false");		
                    			
				}else{
                    $result->addChild(new XMLElement("notice", "Email sent successfully"));
                    $result->setAttribute("sent", "true");	
				}
			}
			
			return $result;
			
		}		
		
	}

?>