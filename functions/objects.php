<?php

/*	object_serialize_vars()
		- This function accepts an object as a parameter, and also an array of sub classes for the object.
		After reading the properties of the object, the function will then strip out any properties of the
		subclasses passed in the $strip_subclasses parameter.
		
		- This function should be used to serialize an object into memory, so only the properties are saved.
		
		- The function will return an array containing all properties of the object, minus parent classes.
*/
function object_serialize_vars($object = null, $strip_subclasses = array('ActiveQuery')) {
	if($object == null || is_object($object) == false) {
		return false;
	}
	
	$object_vars = get_object_vars($object);
	if(is_subclass_of($object, 'ActiveQuery') == true) {
		$object_vars = array_diff_key($object_vars, get_class_vars('ActiveQuery'));
	}
	
	return serialize($object_vars);
}

/*	object_unserialize_vars()
		- This function accepts an object as a reference parameter, and a data array.
		The function will then proceed to set any properties matching the key-names in the $data arrray.
		
		- This function should be used to unserialize an object from a storage mechanism such as a Session
		variable.
		
		- The function will return a boolean indicating success or failure.
*/
function object_unserialize_vars(&$object = null, $data = null) {
	if($object == null || is_object($object) == false || $data == null) {
		return false;
	}
	
	$data = unserialize($data);
	
	foreach($data as $key => $val) {
		if(property_exists($object, $key) == true) {
			$object->$key = $val;
		}
	}
	
	return true;
}

?>