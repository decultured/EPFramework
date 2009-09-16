<?php

require_once('ObjectCache.php');

class Configuration {
	private $values;
	
	public function __construct() {
		$this->values = array();
	}
	
	public function LoadFile($filename = null) {
		if($filename == null) {
			echo ("<p>No Configuration filename provided.</p>");
			return;
		} else if(file_exists($filename) == false) {
			echo ("<p>Configuration file [{$filename}] not found.</p>");
			return;
		} else if(is_file($filename) == false) {
			echo ("<p>Configuration file [{$filename}] is not a file.</p>");
			return;
		}
		
		$config_values = parse_ini_file($filename, true);
		$this->values = array_merge($this->values, $config_values);
	}
	
	public function getValue($key_name = null) {
		if($key_name == null) {
			return null;
		}
		
		$chain = explode(".", $key_name);
		
		$value = $this->values;
		foreach($chain as $key) {
			$value = $value[$key];
		}
		
		return $value;
	}
	
	public function setValue($key_name = null, $value = null) {
		if($key_name == null) {
			return false;
		}
		
		$this->values[$key_name] = $value;
	}
	
	public function __get($property_name) {
		if(array_key_exists($property_name, $this->values) == true) {
			return $this->getValue($property_name);
		}
	}
	
	public function __set($property_name, $value) {
		return $this->setValue($property_name, $value);
	}
	
	public function __toString() {
		return get_class($this) . " object contains " . count($this->values) . " settings.";
	}
}

?>