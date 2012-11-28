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

	##Interface for cacheable objects
	Class Cacheable Extends Object{

		function __construct($args = null){
			$this->_parent = $args['parent'];
			$this->_db = (isset($args['db']) ? $args['db'] : $this->_parent->_db);
		}

		function check_cache($hash, $time = NULL){

			if(!$time) $time = time();

			$c = $this->_db->fetchRow(0, "SELECT * FROM `tbl_cache` WHERE `hash` = '$hash' LIMIT 1");

			if(is_array($c) && !empty($c)){
				if(($time - $c['creation']) < CACHE_LIFETIME){

					if(!$data = $this->decompressData($c['data'])){
						$this->cache_force_expiry($hash);
						return false;
					}

					return $data;
					exit();
				}

				$this->cache_force_expiry($hash);
				return false;
				exit();
			}

			$this->clean();
			return false;
		}

		function decompressData($data){
			if(!$data = @gzuncompress(@base64_decode($data))) return false;
			return $data;
		}

		function compressData($data){
			if(!$data = @base64_encode(@gzcompress($data))) return false;
			return $data;
		}

		function cache_force_expiry($hash){
			$this->_db->query("DELETE FROM `tbl_cache` WHERE `hash` = '$hash' LIMIT 1");
		}

		function write_to_cache($hash, $data, $sections=array()){

			$time = time();

			if(!$data = $this->compressData($data)) return false;

			if(is_array($sections) && !empty($sections)){
				foreach($sections as $s)
					$this->_db->query("INSERT INTO `tbl_cache` VALUES('', '$hash', '$s', '$time', '$data')");
			}

		}

		function clean(){
			$this->_db->query("DELETE FROM `tbl_cache` WHERE (UNIX_TIMESTAMP() - `creation`) >= '".CACHE_LIFETIME."' ");
			$this->__optimise();
		}

		function __optimise(){
			$this->_db->query("OPTIMIZE TABLE `tbl_cache`");
		}

		function flush_cache($sections=array()){

			if(!is_array($sections) && $sections = "ALL"){
				$this->_db->query("TRUNCATE TABLE `tbl_cache`");
				$this->__optimise();
				return;
			}

			$hash_ids = $this->_db->fetchCol('hash', "SELECT DISTINCT `hash` FROM `tbl_cache` WHERE `section` IN ('".@implode("', '", $sections)."') ");
			$this->_db->query("DELETE FROM `tbl_cache` WHERE `hash` IN ('".@implode("', '", $hash_ids)."')");
			$this->__optimise();
			return;


		}

	}

?>