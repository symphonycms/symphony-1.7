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

	Class MySQLDump extends Object {
		var $connection;
	
		function __construct ($connection) {
			$this->connection = $connection;
		}

		function takeDump ($match=null, $flag="BOTH", $condition=NULL) {
			$data = '';

			$tables = $this->_getTables ($match);
			foreach ($tables as $name => $info) {
			
				if($flag == "BOTH" || $flag == "STRUCTURE_ONLY"){
					$data .= "\n\n-- *** STRUCTURE: `$name` ***\n";
					$data .= "DROP TABLE IF EXISTS `$name`;\n";
					$data .= $this->_dumpTableSQL ($name, $info['type'], $info['fields'], $info['indexes']);
				}
			
				if($flag == "BOTH" || $flag == "DATA_ONLY"){
					$data .= "\n\n-- *** DATA: `$name` ***\n";
					if (strtoupper ($info['type']) == 'INNODB') {
						$data .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
					}
				
					$data .= $this->_dumpTableData ($name, $info['fields'], $condition);
					if (strtoupper ($info['type']) == 'INNODB') {
						$data .= "SET FOREIGN_KEY_CHECKS = 1;\n\n";
					}
				}
			}

			return $data;
		}
	
		function _dumpTableData ($name, $fields, $condition=NULL) {
			$fieldList = join (', ', array_map (create_function ('$x', 'return "`$x`";'), array_keys ($fields)));
			$query = 'SELECT ' . $fieldList;
			$query .= ' FROM `' . $name . '`';
			if($condition != NULL) $query .= ' WHERE ' . $condition;
			$rows = $this->connection->fetch ($query);
			$value = '';

			foreach ($rows as $row) {
				$value .= 'INSERT INTO `' . $name . '` (' . $fieldList . ") VALUES (";
				$fieldValues = array ();
			
				foreach ($fields as $fieldName => $info) {
					$fieldValue = $row[$fieldName];

					if ($info['null'] == 1 && trim($fieldValue) == "") {
						$fieldValues[] = "NULL";
					
					}elseif(substr($info['type'], 0, 4) == 'enum'){
						$fieldValues[] = "'".$fieldValue."'";
						
					}elseif (is_numeric ($fieldValue)) {
						$fieldValues[] = $fieldValue;
					
					}else {
						$fieldValues[] = "'" . mysql_real_escape_string ($fieldValue) . "'";
					}
				}

				$value .= join (', ', $fieldValues) . ");\n";

			}

			return $value;
		}
	
		function _dumpTableSQL ($table, $type, $fields, $indexes) {

			$query = 'SHOW CREATE TABLE `' . $table . '`';
			$result = $this->connection->fetch ($query);
			$result = array_values ($result[0]);
			return $result[1] . ";\n\n";
		}

		function _getTables ($match=null) {
			$query = 'SHOW TABLES' . ($match ? " LIKE '$match%'" : "");
		
			$rows = $this->connection->fetch ($query);
			$rows = array_map (create_function ('$x', 'return array_values ($x);'), $rows);
			$tables = array_map (create_function ('$x', 'return $x[0];'), $rows);

			$result = array ();

			foreach ($tables as $table) {
				$result[$table]            = array ();
				$result[$table]['fields']  = $this->_getTableFields ($table);
				$result[$table]['indexes'] = $this->_getTableIndexes ($table);
				$result[$table]['type']    = $this->_getTableType ($table);
			}

			return $result;
		}


		function _getTableType ($table) {
			$query = 'SHOW TABLE STATUS LIKE \'' . addslashes ($table) . '\'';
			$info = $this->connection->fetch ($query);
			return $info[0]['Type'];
		}


		function _getTableFields ($table) {
			$result = array ();
			$query  = 'DESC `' . $table . '`';
			$fields = $this->connection->fetch ($query);

			foreach ($fields as $field) {
				$name    = $field['Field'];
				$type    = $field['Type'];
				$null    = (strtoupper ($field['Null']) == 'YES');
				$default = $field['Default'];
				$extra   = $field['Extra'];

				$field = array (
					'type'    => $type,
					'null'    => $null,
					'default' => $default,
					'extra'   => $extra
				);
			
				$result[$name] = $field;
			}

			return $result;
		}


		function _getTableIndexes ($table) {
			$result  = array ();
			$query   = 'SHOW INDEX FROM `' . $table . '`';
			$indexes = $this->connection->fetch ($query);

			foreach ($indexes as $index) {
				$name     = $index['Key_name'];
				$unique   = !$index['Non_unique'];
				$column   = $index['Column_name'];
				$sequence = $index['Seq_in_index'];
				$length   = $index['Cardinality'];

				if (!isset ($result[$name])) {
					$result[$name] = array ();
					$result[$name]['columns'] = array ();
					if (strtoupper ($name) == 'PRIMARY') {
						$result[$name]['type'] = 'PRIMARY KEY';
					}
					elseif ($unique) {
						$result[$name]['type'] = 'UNIQUE';
					}
					else {
						$result[$name]['type'] = 'INDEX';
					}
				}

				$result[$name]['columns'][$sequence-1] = array ('name' => $column, 'length' => $length);
			}

			return $result;
		}

		function getData ($table) {
		}
	}

?>