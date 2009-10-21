<?php

class MysqlDatabase {
	private $_server_info;
	private $_connection;
	private $_result;
	private $_num_rows;
	private $_last_query;
	private $_num_queries;
	private $_post_connect_queries;
	
	public function __construct() {
		$this->_server_info = null;
		$this->_connection = false;
		$this->_num_queries = 0;
		
		$this->_post_connect_queries = array(
			"SET time_zone = '+00:00'",
		);
	}
	
	public function Connect($server_info = null) {
		if($server_info == null || is_array($server_info) == false) {
			die("Error: MySQL object requires an array containing 'host', 'username', 'password', and 'database' keys.");
		}
		
		$this->_server_info = $server_info;
	}
	
	private function _makeConnection() {
		if($this->_server_info == null) {
			return false;
		} else if($this->_connection != false) {
			return true;
		}
		
		$this->_connection = mysqli_init();
		
		if($this->_post_connect_queries != null && is_array($this->_post_connect_queries) == true) {
			$this->_connection->options(MYSQLI_INIT_COMMAND, implode(";", $this->_post_connect_queries));
		}
		$this->_connection->options(MYSQLI_CLIENT_COMPRESS, 5);
		$this->_connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
		
		$this->_connection->real_connect(
			array_safe_value($this->_server_info, 'host'),
			array_safe_value($this->_server_info, 'username'),
			array_safe_value($this->_server_info, 'password'),
			array_safe_value($this->_server_info, 'database'),
			array_safe_value($this->_server_info, 'port'),
			array_safe_value($this->_server_info, 'socket')
		) or die("Error: Database server cannot be reached.");
		
		$this->_connection->set_charset('utf8');
		
		
		
		return $this->_connection;
	}
	
	public function Disconnect() {
		if($this->_connection == false) {
			return false;
		}
		
		$this->_connection->close();
	}
	
	public function Dispose() {
		$this->Disconnect();
	}
	
	public function SelectDb($database) {
		if($this->_makeConnection() == false) {
			return false;
		}
		// select the db, since we're connected.
		return $this->_connection->select_db($database);
	}
	
	public function EscapeFieldName($field_name = null) {
		if(is_string($field_name) == false) {
			return $field_name;
		}
		
		$segments = explode(".", $field_name);
		for($i = 0; $i < count($segments); $i++) {
			$segments[$i] = "`{$segments[$i]}`";
		}
		
		return implode(".", $segments);
	}
	
	public function Query($query) {
		if($this->_makeConnection() == false) {
			return false;
		}
		// close any previous result set
		if($this->_result != null) {
			if(is_object($this->_result) == true) {
				$this->_result->free();
			}
			
			$this->_result = null;
		}
		
		if($query == null) {
			return false;
		}
		
		$start = microtime_float();
		$this->_result = $this->_connection->query($query);
		$end = microtime_float();
		if(is_object($this->_result) == true) {
			$this->_num_rows = $this->_result->num_rows;
		}
		
		@ObjectCache('framework')->debug_data['queries_used'] += 1;
		@ObjectCache('framework')->debug_data['queries_list'][] = array('string' => $query, 'backtrace' => debug_backtrace(), 'execution_time' => ($end - $start), 'cached' => false);
		$this->_last_query = $query;
		
		return (is_object($this->_result) == true ? true : $this->_result);
	}
	
	public function Insert($query) { return $this->Query($query); }
	public function Update($query) { return $this->Query($query); }
	public function Delete($query) { return $this->Query($query); }
	
	function InsertArray($table_name, $data_array, $use_insert_ignore = false, $update_on_duplicate = false) {
		if($this->_makeConnection() == false) {
			return false;
		}
		
		$array_keys = array_keys($data_array);
		$array_values = array_values($data_array);
		
		foreach($array_keys as $key) {
			$key = $this->EscapeFieldName($key);
		}
		
		for($i = 0; $i < count($array_values); $i++) {
			if(is_string($array_values[$i]) == true) {
				// escape this string
				$array_values[$i] = $this->EscapeData($array_values[$i]);
			} else if(is_bool($array_values[$i]) == true) {
				$array_values[$i] = (int)$array_values[$i];
			}
		}
		
		$sqlQuery = "Insert ".($use_insert_ignore == true ? "Ignore" : "")." Into {$table_name}(`".implode("`, `", $array_keys)."`) Values('".implode("', '", $array_values)."')";
		
		if($update_on_duplicate == true) {
			$fieldList = array();
			foreach($data_array as $key => $value) {
				$key = $this->EscapeFieldName($key);
				
				$fieldList[] = "{$key} = VALUES({$key})";
			}
			
			$sqlQuery .= " On Duplicate Key Update ".implode(", ", $fieldList);
		}
		// die($sqlQuery);
		return $this->Insert($sqlQuery);
	}
	
	function InsertArrays($table_name = null, $data_array = array(), $use_insert_ignore = false, $update_on_duplicate = false, $use_insert_delayed = false) {
		if($this->_makeConnection() == false) {
			return false;
		} else if(is_array($data_array) == false || count($data_array) == 0) {
			return false;
		}
		
		if(count($data_array) > 100) {
			$subset = array_splice($data_array, 0, 100);
			$this->InsertArrays($table_name, $data_array, $use_insert_ignore, $update_on_duplicate, $use_insert_delayed);
			$data_array = $subset;
		}
		
		$first_row = array_slice($data_array, 0, 1, true);
		if(is_array($first_row) == false) {
			return false;
		}
		$fields = array_keys(array_pop($first_row));
		foreach($fields as $key => &$field) {
			$field = $this->EscapeFieldName($field);
		}
		
		$num_rows = count($data_array);
		$num_fields = count($fields);
		foreach($data_array as $key => $array_values) {
			foreach($array_values as $key2 => $value) {
				if(is_string($value) == true) {
					// escape this string
					$array_values[$key2] = $this->EscapeData($value);
				} else if(is_bool($value) == true) {
					$array_values[$key2] = (int)$value;
				}
			}
			
			$data_array[$key] = "'" . implode("', '", $array_values) . "'";
		}
		
		$sqlQuery = "Insert ".($use_insert_delayed == true ? "Delayed" : "")." ".($use_insert_ignore == true ? "Ignore" : "")." Into {$table_name}(".implode(", ", $fields).") Values(".implode("), (", $data_array).")";
		// die($sqlQuery);
		
		if($update_on_duplicate == true) {
			$fieldList = array();
			foreach($fields as $key) {
				$fieldList[] = "{$key} = VALUES({$key})";
			}
			
			$sqlQuery .= " On Duplicate Key Update ".implode(", ", $fieldList);
		}
		
		return $this->Insert($sqlQuery);
	}
	
	function UpdateArray($table_name, $data_array, $where_clause) {
		if($this->_makeConnection() == false) {
			return false;
		}
		
		$sqlQuery = "Update {$table_name} Set ";
		
		$fieldList = array();
		foreach($data_array as $key => $value) {
			$key = $this->EscapeFieldName($key);
			
			if(is_string($value) == true) {
				// escape this string
				$fieldList[] = "{$key} = '{$this->EscapeData($value)}'";
			} else if(is_bool($value) == true) {
				$value = (int)$value;
				$fieldList[] = "{$key} = {$value}";
			} else {
				$fieldList[] = "{$key} = '{$this->EscapeData($value)}'";
			}
		}
		
		$sqlQuery = "Update {$table_name} Set ".implode(", ", $fieldList);
		// do we have a where clause?
		if(is_array($where_clause) == true) {
			$whereFieldList = array();
			foreach($where_clause as $key => $value) {
				$key = $this->EscapeFieldName($key);
				$value = $this->EscapeData($value);
				$whereFieldList[] = "{$key} = '{$value}'";
			}
			$sqlQuery .= " Where(".implode(") And (", $whereFieldList).")";
		}
		
		return $this->Update($sqlQuery);
	}
	
	function DeleteArray($table_name, $data_array) {
		if($this->_makeConnection() == false) {
			return false;
		}
		
		$value_list = array();
		foreach($data_array as $key => $value) {
			$key = $this->EscapeFieldName($key);
			$value = $this->EscapeData($value);
			$whereFieldList[] = "{$key} = '{$this->EscapeData($value)}'";
		}
		
		$sqlQuery = "Delete From {$table_name} Where(". implode(") And (", $value_list) .")";
		
		return $this->Update($sqlQuery);
	}
	
	// Note: Transactions only work on a table type of InnoDB
	function BeginTransaction() {
		if($this->_makeConnection() == false) {
			return false;
		}
		
		$this->_connection->autocommit(false);
	}
	
	function CommitTransaction() {
		$this->_connection->commit();
		$this->_connection->autocommit(true);
	}
	
	function CancelTransaction() {
		$this->_connection->rollback();
		$this->_connection->autocommit(true);
	}
	
	/*
		GetInsertId()			
			- returns the last insert id from mysql.
	*/
	function GetInsertId() {
		return $this->_connection->insert_id;
	}
	
	public function NumRows() {
		return $this->_num_rows;
	}
	
	public function MoveNext($use_table_names = true) {
		if($this->_result == false || $this->_result->num_rows == 0) {
			return null;
		}
		
		// get array of our fields
		$fields = $this->_result->fetch_fields();
		$field_names = $this->GetFieldNames($use_table_names);
		// build our final dataset
		$values = $this->_result->fetch_row();
		return array_combine($field_names, $values);
	}
	
	public function GetFieldNames($use_table_names = true) {
		// get array of our fields
		$fields = $this->_result->fetch_fields();
		
		$field_names = array();
		foreach($fields as $field) {
			if($field->table != '' && $use_table_names == true) {
				$field_names[] = "{$field->table}.{$field->name}";
			} else {
				$field_names[] = $field->name;
			}
		}
		
		return $field_names;
	}
	
	public function GetDataArray($use_table_names = true) {
		if($this->_result == false || $this->_result->num_rows == 0) {
			return null;
		}
		// reset data pointer.
		$this->_result->data_seek(0);
		$field_names = array();
		$field_names = $this->GetFieldNames($use_table_names);
		
		// build our final dataset
		$dataset = array();
		while($row = $this->_result->fetch_row()) {
			$dataset[] = array_combine($field_names, $row);
		}
		
		return $dataset;
	}
	
	function FetchAll($sql, $use_table_names = true) {
		$this->Query($sql);
		
		return $this->GetDataArray($use_table_names);
	}

	function FetchOne($sql, $use_table_names = true) {
		$this->Query($sql);
		
		if($this->_result != null) {
			$data = $this->MoveNext($use_table_names);
		} else {
			$data = null;
		}
		
		return $data;
	}
	
	function getTotalResults() {
		if(stristr($this->_last_query, 'SQL_CALC_FOUND_ROWS') != false) {
			// this query uses SQL_CALC_FOUND_ROWS, lets capture the number of rows and save it.
			$result = $this->FetchOne("Select FOUND_ROWS() As num_results", false);
			if($result != null) {
				return $result['num_results'];
			}
		}
		
		return null;
	}
	
	function EscapeData($value) {
		if($this->_makeConnection() == false) {
			return false;
		}
		// properly escape and convert boolean values
		if(is_bool($value) == true) {
			$value = ($value == true ? 1 : 0);
		}
		
		if(get_magic_quotes_gpc() == true || get_magic_quotes_runtime() == true) {
			// this data has already been escaped by the wonderful magic quotes features.
			$value = stripslashes($value);
		}
		
		return $this->_connection->real_escape_string($value);
	}
	
	function LastError() {
		$last_query = $this->LastQuery(true);
		$errors_arr = $this->FetchAll("SHOW ERRORS");
		$warnings_arr = $this->FetchAll("SHOW WARNINGS");
		
		$errors = array();
		$warnings = array();
		if(count($errors_arr) > 0) {
			foreach($errors_arr as $row) {
				$errors[] = $row['Message'];
			}
		}
		
		if(count($warnings_arr) > 0) {
			foreach($warnings_arr as $row) {
				$warnings[] = $row['Message'];
			}
		}
		
		if(g('framework')->getRunMode() == 'web') {
			$output = "<h2>Last Error</h2>";
			$output .= "<p>". implode("<br />", $errors) ."</p>";
			$output .= "<h2>Last Warning</h2>";
			$output .= "<p>". implode("<br />", $warnings) ."</p>";
			$output .= "<h2>Rows Affected</h2>";
			$output .= "<p>{$this->_num_rows}</p>";
			$output .= "<h2>Query Used</h2>";
			$output .= "<p>{$last_query}</p>";
			
			return $output;
		} else {
			// must be console mode
			return "\n\n--------\n".
					"MySQL Error:\n".
						implode("\n", $errors) ."\n\n".
					"MySQL Warnings:\n".
						implode("\n", $warnings) ."\n\n".
					"Rows Affected: {$this->_num_rows}\n".
					"Query Used:\n".
						$last_query ."\n\n";
		}
	}
	
	function LastQuery($pretty_print = false) {
		if($pretty_print == false) {
			return $this->_last_query;
		} else {
			$query = $this->_last_query;
			$query = str_ireplace(" And ", "\n\tAnd ", $query);
			
			return $query;
		}
	}
}

?>