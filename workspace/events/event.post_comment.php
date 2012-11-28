<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Symphony Error</h2><p>You cannot directly access this file</p>");

	Class eventPost_Comment Extends Event{

		function __construct($args = array()){
			parent::__construct($args);
		}

		function preview(){

			$xml = new XMLElement("post-comment");
			$xml->addChild(new XMLElement("notice", "Missing Author Name"));

			$code = $xml->generate(true);

			$xml = new XMLElement("post-comment");
			$cookie = new XMLElement("cookie");
			$cookie->addChild(new XMLElement("name", "fred"));
			$cookie->addChild(new XMLElement("email", "fred@email.com"));
			$cookie->addChild(new XMLElement("url", "http://www.fred.com"));
			$xml->addChild($cookie);

			$code .= CRLF . $xml->generate(true);

			return $code;

		}

		function about(){
			return array(
						 "name" => "Post Comment",
						 "description" => "Will post a comment against an entry.",
						 "author" => array("name" => "Alistair Kearney",
										   "website" => "http://www.pointybeard.com",
										   "email" => "alistair@pointybeard.com"),
						 "version" => "1.5",
						 "release-date" => "2006-04-18",
						 "trigger-condition" => "action[comment] field",
						 "recognised-fields" => array(
													array("name", true),
													array("website", true),
													array("email", true),
													array("comment", true),
													array("entry-handle", true),
													array("section", true),
													array("remember (on|off)", true)
												));
		}

		function load(){

			if(isset($_POST['action']['comment'])){
				$this->_parent->flush_cache(array("entries", "authors", "comments"));
				return $this->trigger();
			}

			$prefix = $this->_parent->getConfigVar('cookie_prefix', 'symphony');

			if(isset($_COOKIE[$prefix . 'comment-remember']['name'])){
				$result = new XMLElement("post-comment");

				$cookie = new XMLElement("cookie");
				$cookie->addChild(new XMLElement("name", General::sanitize($_COOKIE[$prefix . 'comment-remember']['name'])));
				$cookie->addChild(new XMLElement("email", General::sanitize($_COOKIE[$prefix . 'comment-remember']['email'])));
				$cookie->addChild(new XMLElement("url", General::validateURL($_COOKIE[$prefix . 'comment-remember']['url'])));
				$result->addChild($cookie);

				return $result;
			}

			return;
		}

		function trigger(){

			$result = new XMLElement("post-comment");

			$comment = array();

			$comment['author_name'] = $_POST['name'];
			$comment['author_url'] = $_POST['website'];
			$comment['author_email'] = $_POST['email'];
			$comment['body'] = $_POST['comment'];
			$comment['entry_handle'] = $_POST['entry-handle'];
			$comment['section'] = $_POST['section'];

			$comment = array_map("stripslashes", $comment);

			## Create the cookie elements
			$cookie = new XMLElement("cookie");
			$cookie->addChild(new XMLElement("name", General::sanitize($comment['author_name'])));
			$cookie->addChild(new XMLElement("email", General::sanitize($comment['author_email'])));
			$cookie->addChild(new XMLElement("url", General::validateURL($comment['author_url'])));
			$cookie->addChild(new XMLElement("comment", General::sanitize($comment['body'])));
			$result->addChild($cookie);

			$canProceed = true;

			if($comment['author_name'] == ""
				|| $comment['author_email'] == ""
				|| $comment['body'] == ""){

				$xMissing = new XMLElement("missing");

				if($comment['author_name'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "name");
					$xMissing->addChild($missing);
				}

				if($comment['author_email'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "email");
					$xMissing->addChild($missing);
				}

				if($comment['body'] == "") {
					$missing = new XMLElement("input");
					$missing->setAttribute("name", "comment");
					$xMissing->addChild($missing);
				}

				$result->addChild($xMissing);
				$canProceed = false;
			}

			if($comment['author_email'] != "" && !ereg('^[a-zA-Z0-9_\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$', $comment['author_email'])){

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

				require_once(TOOLKIT . "/class.commenting.php");
				$oCommenting = new Commenting(array("parent" => $this->_parent));

				## By default 'body', 'author_name' and 'author_ip' are required, but we
				## want an email ('author_email') address as well.
				$oCommenting->setRequiredField('author_email');

				#if(is_array($oCommenting->_notices) && !empty($oCommenting->_notices)){
				if(!$oCommenting->insertComment($comment)){
					$result->addChild(new XMLElement("notice", $oCommenting->_notices[0]));
					$result->setAttribute("sent", "false");

				}else{
					$result->setAttribute("sent", "true");
					$result->addChild(new XMLElement("notice", "Comment saved successfully"));
				}

				if($oCommenting->isLastCommentSpam) $result->setAttribute("spam", "true");

				$prefix = $this->_parent->getConfigVar('cookie_prefix', 'symphony');

				if($_POST['remember'] == 'on'){

					setcookie($prefix . 'comment-remember[name]', $comment['author_name'], time() + TWO_WEEKS, $this->_parent->getCookieDomain());
					setcookie($prefix . 'comment-remember[url]', $comment['author_url'], time() + TWO_WEEKS, $this->_parent->getCookieDomain());
					setcookie($prefix . 'comment-remember[email]', $comment['author_email'], time() + TWO_WEEKS, $this->_parent->getCookieDomain());

				}else
					setcookie($prefix . 'comment-remember', ' ', time() - TWO_WEEKS, $this->_parent->getCookieDomain());
			}

			return $result;

		}

	}

?>
