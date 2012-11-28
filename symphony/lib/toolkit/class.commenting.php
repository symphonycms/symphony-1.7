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

	Class Commenting extends Object {

		var $_notices;
		var $_db;
		var $_options;
		var $_required_fields;
		var $_parent;

		function __construct ($args) {
			parent::__construct();

			$this->_db = $args['parent']->_db;
			$this->_parent = $args['parent'];
			$this->_required_fields = array();

			$this->_notices = $_REQUEST['notices'];

			$this->__loadOptions ();
			$this->setRequiredField();

		}

		function setOption($name, $value){
			$this->_options[$name] = $value;
		}

		function setRequiredField($field=NULL){

			if($field)
				$this->_required_fields[] = $field;

			else
				$this->_required_fields = array('body', 'author_name', 'author_ip');
		}

		function insertComment ($comment, $isSpam=false) {

			$comment['author_id'] = NULL;

			$author_id = $this->_parent->isLoggedIn();
			if($author_id !== false) $comment['author_id'] = $author_id;

			$comment['author_ip'] = $_SERVER['REMOTE_ADDR'];

			####
			# Delegate: CommentPreProcess
			# Description: Just before the comment is processed and saved. Good place to manipulate the data.
			$this->_parent->_CampfireManager->notifyMembers('CommentPreProcess',
												   '/frontend/',
												   array(
												  		 	'isSpam' => &$isSpam,
												  		 	'comment' => &$comment
														)
													);

			$this->isLastCommentSpam = $isSpam;

			unset($comment['remember']);

			$section = $this->_db->fetchRow(0, "SELECT * FROM tbl_sections WHERE `handle` = '".$comment['section']."' LIMIT 1");

			if(!is_array($section) || empty($section)){
				$this->_notices[] = 'Invalid section specified.';
				return false;
			}

			unset($comment['section']);

			if(isset($comment['entry_handle'])){

				include_once(TOOLKIT . "/class.entrymanager.php");
				$entryManager = new EntryManager($this->_parent);

				$entry_id = $entryManager->fetchEntryIDFromPrimaryFieldHandle($section['id'], $comment['entry_handle']);
				$comment['entry_id'] = $entry_id[0];

				unset($comment['entry_handle']);

			}

			if(!$entry = $entryManager->fetchEntriesByID($comment['entry_id'], false, true)){
				$this->_notices[] = 'Invalid entry handle specified.';
				return false;
			}

			if($section['commenting'] == 'off') return false;

			$this->_notices = array ();

			$valid = $this->__validateComment ($comment);

			if(!isset($this->_options['override-automatic-spam-detection']) || $this->_options['override-automatic-spam-detection'] == false)
				$spam  = $this->__isSpam($comment) || $this->__isBlackListed($comment['author_ip']);
			else
				$spam = ($isSpam || $this->__isBlackListed($comment['author_ip']) ? true : false);

			$comment = array_map(array($this, "__doBanWords"), $comment);

			$options = $this->_options;

			require_once(LIBRARY . "/core/class.textformattermanager.php");
			$TFM = new TextformatterManager(array('parent' => &$this->_parent));

			if($options['formatting-type'] != NULL && ($formatter = $TFM->create($options['formatting-type']))){
				$comment['body'] = $formatter->run($comment['body']);

			}else
				$comment['body'] = strip_tags($comment['body']);

			$comment['author_url'] = General::validateUrl ($comment['author_url']);

			$comment['spam'] = ($spam ? "yes" : "no");

			##Check the comment body for well-formedness
			$xml_errors = array();
			General::validateXML($comment['body'], $xml_errors, false);

			if(!empty($xml_errors)){
				$xml_errors = array();
				$comment['body'] = str_replace(array('<', '>', '&'),
											   array('&lt;', '&gt;', '&amp;'),
											   $comment['body']);

				General::validateXML($comment['body'], $xml_errors, false, new XsltProcess());

				if(!empty($xml_errors)){
					$this->_notices[] = "Comment contains invalid text or markup.";
					return false;
				}
			}
			##

			##Check the comment name field for well-formedness
			$xml_errors = array();
			General::validateXML($comment['author_name'], $xml_errors, false);

			if(!empty($xml_errors)){
				$this->_notices[] = "Author name contains invalid text or markup.";
			}
			##

			$dupe = ($options['allow-duplicates'] == "on" ? false : $this->__isDuplicateComment ($comment));

			$nuke_comment = (!empty($xml_errors) || ($spam && $options['nuke-spam'] == 'on'));

			####
			# Delegate: CommentPreSave
			# Description: Just before the comment is inserted into the database. Also, final checks
			#              of its validity have been performed. Good place to manipulate the data and check values
			$this->_parent->_CampfireManager->notifyMembers('CommentPreSave',
												   '/frontend/',
												   array(
												  		 	'nuke' => &$nuke_comment,
															'dupe' => &$dupe,
												  		 	'comment' => &$comment
														)
													);

			if (!$nuke_comment && $valid && !$dupe) {

				if($spam) $this->isLastCommentSpam = true;

				unset($comment['author_ip']);

				if($this->_db->insert($comment, "tbl_comments")){
					$comment_id = $this->_db->getInsertID();
					$this->_parent->updateMetadata("comment", $comment_id);

					####
					# Delegate: CommentPostSave
					# Description: After inserting comment into database. Comment ID is provided
					$this->_parent->_CampfireManager->notifyMembers('CommentPostSave', '/frontend/', array('id' => $comment_id));

					if($options['email-notify'] == 'on' && !$spam)
						$this->__emailEntryAuthor($comment, $entry);

					return true;

				}else{
					$this->_notices[] = 'Comment not successfully saved. An unknown error has occurred.';
				}

			}else if (!$valid || $nuke_comment) {
				$this->_notices[] = 'Comment flagged as spam and has not been saved.';
				$this->isLastCommentSpam = true;

			}else if ($dupe) {
				$this->_notices[] = 'Duplicate post detected.';
			}

			####
			# Delegate: CommentFailedInsert
			# Description: After a failed insert. Notices are provided
			$this->_parent->_CampfireManager->notifyMembers('CommentFailedInsert', '/frontend/', array('notices' => $this->_notices));

			return false;
		}

		function __emailEntryAuthor($comment, $entry){
			#$entry = $this->_db->fetchRow(0, "SELECT * FROM tbl_entries WHERE tbl_entries.id = '".$comment['entry_id']."' LIMIT 1");
			$author = $this->_db->fetchRow(0, "SELECT * FROM tbl_authors WHERE tbl_authors.id = '".$entry['author_id']."' && `email` != '". $comment['author_email'] ."' LIMIT 1");

			General::sendEmail($author['email'], "DONOTREPLY@symphony21.com", "Symphony Concierge", $comment['author_name'] . " has posted a comment on '" . $this->_parent->getConfigVar("sitename", "general") . "'",

						"Hi " . $author["firstname"] . ","
						. "\nThis is to inform you that " . $comment['author_name'] . " has posted a comment to one of your entries (".$_SERVER['HTTP_REFERER']."). Below is a summary."
						. "\n\nEntry: ". strip_tags($entry['fields'][$entry['primary_field']]['value']) ." (" . URL . "/symphony/?page=/publish/section/edit/&_sid=".$entry['section_id']."&id=" . $entry['id'] . ")"
						. "\nComment Author: ". $comment['author_name'] . ($comment['author_url'] != "" ? " (" . $comment['author_url'] . ")" : "")
						. "\nComment Email: ". $comment['author_email']
						. "\n\nComment Body: \n".$comment['body']
						. "\n\n\nYou can moderate this comment by visiting the Comments section of your Symphony Admin. "
						. "\nIf you do not wish to receive emails if the future, simply turn off email notification in your Comments settings area.\n"

			);
		}

		function __validateComment ($comment) {

			$options = $this->_options;

			$comment = array_map("trim", $comment);

			if (@in_array('body', $this->_required_fields) && strlen($comment['body']) == 0) {
				$this->_notices[] = 'Missing comment body.';
			}

			if (@in_array('author_email', $this->_required_fields) && strlen($comment['author_email']) == 0) {
				$this->_notices[] = 'Missing email address.';
			}

			if (@in_array('author_name', $this->_required_fields) && strlen($comment['author_name']) == 0) {
				$this->_notices[] = 'Missing author name.';
			}

			if (@in_array('author_ip', $this->_required_fields) && strlen($comment['author_ip']) == 0) {
				$this->_notices[] = 'Could not detect author IP.';
			}



			return empty($this->_notices);
		}

		function __isDuplicateComment ($comment) {
			$options = $this->_options;

			$query  = "SELECT COUNT(tbl_metadata.relation_id) AS `dupe_count`
					   FROM `tbl_metadata`, `tbl_comments`
					   WHERE `tbl_metadata`.`class` = 'comment'
					   AND `tbl_metadata`.`creator_ip` ='" . $comment['author_ip'] . "'
					   AND MD5(`tbl_comments`.`body`) ='" . md5($comment['body']) . "'
					   AND `tbl_comments`.`id` = `tbl_metadata`.`relation_id`";

			$result = $this->_db->fetchVar ("dupe_count", 0, $query);

			return $result > 0;
		}

		function __doBanWords($data){
			$options = $this->_options;

			if(!empty($options['banned-words']) && is_array($options['banned-words'])){
				foreach ($options['banned-words'] as $word) {
					$data = @str_replace($word, $options['banned-words-replacement'], $data);
				}
			}

			return $data;

		}

		function __isBlackListed($author_ip){
			$options = $this->_options;

			$current_blacklist = preg_split('/,/', $options["ip-blacklist"], -1, PREG_SPLIT_NO_EMPTY);
			$current_blacklist = array_map("trim", $current_blacklist);

			if(is_array($current_blacklist) && !empty($current_blacklist)){
				foreach($current_blacklist as $ip){

					if(preg_match('/^'.$ip.'/i', $author_ip)) return true;

				}
			}

			return false;
		}

		function __isSpam ($comment) {
			$options = $this->_options;

			$valid_referer = true;
			$numMatches = 0;

			/*if(!empty($options['banned-words']) && is_array($options['banned-words'])){
				foreach ($options['banned-words'] as $word) {
					if (@stristr ($comment['body'], $word) !== false) {
						$numMatches++;
					}
				}
			}*/

			if($options['check-referer'] == "on"){
				$valid_referer = preg_match('/^'.str_replace("/", "\/", URL).'/i', $comment['referer']);
			}

			if ($options['maximum-allowed-links'] > -1) {

				$regexes = array (
					'<\/a>', 'http:\/\/'
				);

				$numLinks = 0;
				foreach ($regexes as $regex) {
					$matches = array();
					$numLinks += preg_match_all ('/' . $regex . '/i', $comment['body'], $matches, PREG_SET_ORDER);
				}

			}

			return ($valid_referer
					|| ($options['maximum-allowed-links'] > -1 && $numLinks >= intval($options['maximum-allowed-links'])));
		}

		function __loadOptions () {
			$this->_options = $this->_parent->getConfigVar("commenting");
			$this->_options['banned-words'] = array_map (create_function ('$x', 'return trim ($x);'), explode (',', $this->_options['banned-words']));
		}

		function notices () {

			$notices = "";

			if(!empty ($this->_notices) && is_array ($this->_notices)) {
				$notices = implode(" ", $this->_notices);
			}

			return $notices;
		}

	}

?>
