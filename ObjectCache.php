<?php

class ObjectCache {
	public function __get($property_name) {
		return self::get($property_name);
	}
	
	public function __set($property_name, $value) {
		return self::set($property_name, $value);
	}
	
	static public function get($property_name) {
		return (isset($GLOBALS['ObjectCache'][$property_name]) == true ? $GLOBALS['ObjectCache'][$property_name] : null);
	}
	
	static public function set($property_name, &$value) {
		$GLOBALS['ObjectCache'][$property_name] = $value;
	}
	
	static public function delete($property_name) {
		unset($GLOBALS['ObjectCache'][$property_name]);
	}
	
	static public function Init() {
		$GLOBALS['ObjectCache'] = array();
	}
	
	static public function clearAll() {
		if(array_key_exists('ObjectCache', $GLOBALS) == false) {
			return false;
		}
		
		foreach($GLOBALS['ObjectCache'] as $object) {
			if(method_exists($object, 'Dispose') == true) {
				$object->Dispose();
			}
		}
		
		$GLOBALS['ObjectCache'] = array();
	}
}

function ObjectCache($objname) {
	return ObjectCache::get($objname);
}

function g($objname) {
	return ObjectCache::get($objname);
}

?>