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

	Class MySQL extends Object {

	    var $_connection = array();
	    var $_log = array();
	    var $_result;
	    var $_lastResult = array();
	    var $_lastQuery;
	    var $_affectedRows;
	    var $_insertID;
		var $_dumpTables = array();
		var $_client_info;
		var $_client_encoding;

	    function __construct ($args=null) {

	        if(isset($args['host']))
	            $this->connect($args['host'], $args['user'], $args['password'], $args['port']);

	        if (isset($args['db'])) {
	            $this->select($args['db']);
	        }

	        if (isset($args['tbl_prefix'])) {
	            $this->setPrefix($args['tbl_prefix']);
			}

	    }

	    function setPrefix($prefix){
	        $this->_connection['tbl_prefix'] = $prefix;
	    }

	    function __destruct() {
	        $this->flush();
	        $this->close();
	    }

	    function isConnected(){
	        return is_resource($this->_connection['id']);
	    }

	    function getSelected(){
	        return $this->_connection['database'];
	    }

	    function connect($host = NULL, $user = NULL, $password = NULL, $port = "3306") {

	        if($host) $this->_connection['host'] = $host;
	        if($user) $this->_connection['user'] = $user;
	        if($password) $this->_connection['pass'] = $password;
	        if($port) $this->_connection['port'] = $port;

	        $this->_connection['id'] = @mysql_connect($this->_connection['host'] . ":" . $this->_connection['port'], $this->_connection['user'], $this->_connection['pass']);

	        if (!$this->isConnected()) {
	            $this->__error("Error establishing database connection: Check details");
	            return false;
	        }

	        $this->_client_info = mysql_get_client_info();
			$this->_client_encoding = mysql_client_encoding($this->_connection['id']);

	        return true;

	    }

	    function setCharacterSet($set = "utf8"){
		    if(version_compare("4.1", $this->_client_info))
		    	$this->query("SET CHARACTER SET '$set'");
	    }

	    function setCharacterEncoding($set = "utf8"){
		    if(version_compare("4.1", $this->_client_info))
	        	$this->query("SET NAMES '$set'");
	    }

	    function select ($db = NULL) {

	        if($db) $this->_connection['database'] = $db;

	        if (!@mysql_select_db($this->_connection['database'], $this->_connection['id'])) {
	            $this->__error("Cannot find database ".$this->_connection['database']);
	            $this->_connection['database'] = null;
	            return false;
	        }

	        return true;
	    }

		function tree($sql, $primary, $parent, $Parent_ID=0, $Level=0) {
			$result = $this->fetch($sql);

			if(empty($result) || !is_array($result))
				return array ();

			foreach($result as $data) {
				$array[$data[$primary]] = $data;
			}

			return($this->buildTree($array, $parent, $Parent_ID, $Level));
		}

		function buildTree($rawArray, $parent, $Parent_ID, $Level, $return=array()) {

			if (is_array ($rawArray) && !empty($rawArray)) {

				foreach($rawArray as $Primary_ID => $array) {

					if($Parent_ID == $array[$parent] && $Primary_ID != 0) {
						$return[$Primary_ID] = $array;
						$return[$Primary_ID]['level'] = $Level;
						$return = $this->buildTree($rawArray, $parent, $Primary_ID, $Level+1, $return);
					}

				}

				return($return);

			}

			return array ();

		}

		function countChildren($tree) {

			foreach($tree as $key => $array)
				$children[$array['parent_id']]++;

			return($children);
		}

		function cleanFields(&$array) {
			foreach($array as $key => $val) {

				if($val == "")
					$array[$key] = "NULL";

				else
					$array[$key] = "'".addslashes($val)."'";

			}
		}

		function insert($fields, $table, $method="INSERT") {
			if(is_array(current($fields))) {
				// Multiple Insert
				$sql  = "$method INTO `$table` (`".implode("`, `", array_keys(current($fields)))."`) VALUES ";

				foreach($fields as $key => $array) {
					$this->cleanFields($array);
					$rows[] = "(".implode(", ", $array).")";
				}

				$sql .= implode(", ", $rows);

			} else {
				// Single Insert
				$this->cleanFields($fields);
				$sql  = "$method INTO `$table` (`".implode("`, `", array_keys($fields))."`) VALUES (".implode(", ", $fields).")\n";
			}

			return $this->query($sql);
		}

		function update($fields, $table, $where) {
			$this->cleanFields($fields);
			$sql = "UPDATE $table SET ";

			foreach($fields as $key => $val)
				$rows[] = " `$key` = $val";

			$sql .= implode(", ", $rows) . ' ' . $where;

			return $this->query($sql);
		}

		function delete($table, $where) {
			$this->query("DELETE FROM $table $where");
		}

	    function close () {
	        return @mysql_close($this->_connection['id']);
	    }

	    function query ($query) {

		    if(empty($query)) return false;

	        if ($this->_connection['tbl_prefix'] != "tbl_"){
	            $query = preg_replace("/tbl_(\S+?)([\s\.,]|$)/", $this->_connection['tbl_prefix']."\\1\\2", $query);
	        }

	        $query = trim($query);

	        $this->flush();
	        $this->_lastQuery = $query;
	        $this->_result = @mysql_query($query, $this->_connection['id']);

	        if (@mysql_error()) {
	            $this->__error();
	            return false;
	        }

	        if (stristr($query, "insert") || stristr($query, "replace") || stristr($query, "delete") || stristr($query, "update")){

	            $this->_affectedRows = @mysql_affected_rows();

	            if (stristr($query, "insert") || stristr($query, "replace"))	{
	                $this->_insertID = @mysql_insert_id($this->_connection['id']);
	            }

	        }

	        while ($row = @mysql_fetch_object($this->_result)){
	            @array_push($this->_lastResult, $row);
	        }

	        @mysql_free_result($this->_result);

	        return true;

	    }

	    function numOfRows () {
	        return count($this->_lastResult);
	    }

	    function getInsertID () {
	        return $this->_insertID;
	    }

	    function fetch ($query = NULL, $index_by_field = NULL) {

	        if ($query == ""){
		        return array();
	        }

	        $this->query($query);

	        if ($this->_lastResult == NULL) {
	            return array();
	        }

	        foreach ($this->_lastResult as $row) {
	            $newArray[] = get_object_vars($row);
	        }

			if($index_by_field && isset($newArray[0][$index_by_field])){

			  $n = array();

			  foreach($newArray as $ii)
			      $n[$ii[$index_by_field]] = $ii;

			  $newArray = $n;

			}

	        return $newArray;

	    }

	    function fetchRow ($offset = 0, $query = NULL) {

	        $arr = $this->fetch($query);
	        return (empty($arr) ? array() : $arr[$offset]);

	    }

	    function fetchXML($query = NULL){

	        $arr = $this->fetch($query);

	        if (empty($arr))
	            return NULL;

		    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?><result>\n";
			$ii = 0;

			foreach($arr as $row){
				$xml .= "\t<row>\n";

				foreach($row as $key => $val){
					$xml .= "\t\t<$key><![CDATA[$val]]></$key>\n";
				}

				$xml .= "\t</row>\n";
			}

			$xml .= "</result>";

			return $xml;

	    }

	    function fetchCol ($name, $query = NULL) {

	        $arr = $this->fetch($query);

		      if (empty($arr))
		            return array();

	        foreach ($arr as $row) {
	            $result[] = $row[$name];
	        }

	        return $result;

	    }

	    function fetchVar ($varName, $offset = 0, $query = NULL) {

	        $arr = $this->fetch($query);
	        return (empty($arr) ? NULL : $arr[$offset][$varName]);

	    }

	    function flush() {

	        $this->_result = NULL;
	        $this->_lastResult = array();
	        $this->_lastQuery = NULL;

	    }

	    function __error($msg = NULL) {

	        if (!$msg){
	            $msg = @mysql_error($this->_connection['id']);
	            $errornum = @mysql_errno($this->_connection['id']);
	        }

	        $this->_log[] = array ("query" => $this->_lastQuery,
	                               "msg" => $msg,
	                               "num" => $errornum);



	    }

	    function debug() {
	        return $this->_log;
	    }

	    function import($sql){

			$queries = preg_split('/;[\\r\\n]+/', $sql, -1, PREG_SPLIT_NO_EMPTY);

			if(is_array($queries) && !empty($queries)){
			    foreach($queries as $sql) {
			        if(trim($sql) != "") $result = $this->query($sql);
			        if(!$result) return false;
			    }
			}

			return true;

	    }

	}

?>
