<?php

/*	array_safe_value
		- this function should be used everytime an array is accessed.
			
		- returns the value from the array, or the value in $default if the key is not found.
*/
function array_safe_value($array, $key, $default = null, $case_insensitive = false, $type = false) {
	if($case_insensitive == true) {
		foreach($array as $the_key => $value) {
			if(strtolower($key) == strtolower($the_key)) {
				return $value;
			}
		}
	}
	
	if(is_array($array) == false || array_key_exists($key, $array) == false) {
		return $default;
	}
	
	if($array[$key] == 'true') {
		return true;
	} else if($array[$key] == 'false') {
		return false;
	}
	
	return $array[$key];
}

function require_type($type = false, $value = null, $default_value = null) {
	if($type == false) {
		return $value;
	}
	
	switch(strtolower($type)) {
		case 'bool':
		case 'boolean':
			if(is_bool($value) == true || (is_numeric($value) && ($value == 1 || $value == 0))) {
				return (boolean)$value;
			} else if(is_string($value)) {
				if(strtolower($value) == 'true') {
					return true;
				} else if(strtolower($value) == 'false') {
					return false;
				}
			}
		break;
		case 'int':
		case 'integer':
			if(is_numeric($value) == true) {
				return (int)$value;
			}
		break;
		case 'double':
			if(is_numeric($value) == true) {
				return (float)$value;
			}
		break;
		case 'float':
			if(is_numeric($value) == true) {
				return (float)$value;
			}
		break;
		case 'string':
			if(is_string($value) == true) {
				return $value;
			}
		break;
		case 'object':
			if(is_object($value) == true) {
				return $value;
			}
		break;
	}
		
	return $default_value;
}

/* array_multi_diff
	- this function is used to compute the difference between to multi-dimensional arrays.
	- it was taken from the php.net article on array_diff.
*/
function array_multi_diff($a1,$a2) {
	$diff = array();
	
	foreach($a1 as $k=>$v){
		$dv = null;
		if(is_int($k)){
			// Compare values
			if(array_search($v,$a2)===false) $dv=$v;
			else if(is_array($v)) $dv = array_multi_diff($v,$a2[$k]);
			if($dv) $diff[]=$dv;
		} else {
			// Compare noninteger keys
			if(!$a2[$k]) $dv=$v;
			else if(is_array($v)) $dv = array_multi_diff($v,$a2[$k]);
			if($dv) $diff[$k]=$dv;
		}
	}
	
	return $diff;
}

/*	array_key_multi_sort
		- this function is used to sort a multi-dimensional array by a key
*/
function array_key_multi_sort(&$arr, $l , $f='strnatcasecmp') {
	return usort($arr, create_function('$a, $b', "return $f(\$a['$l'], \$b['$l']);"));
}

function array_key_multi_sort_objects(&$arr, $l , $f='strnatcasecmp') {
	return usort($arr, create_function('$a, $b', "return $f(\$a->$l, \$b->$l);"));
}

function array_key_values($arr, $key) {
	$values = array();
	
	foreach($arr as $row) {
		$values[] = $row->$key;
	}
	
	return $values;
}

function array_max_multi($array, $key) {
	array_key_multi_sort($array, $key);
	
	$top = array_pop($array);
	
	return $top[$key];
}

function array_min_multi($array, $key) {
	array_key_multi_sort($array, $key);
	
	$top = array_shift($array);
	
	return $top[$key];
}

function array_average_multi($array, $key) {
	$count = count($array);
	$total = 0;
	foreach($array as $row) {
		$total += $row[$key];
	}
	
	return ($total / $count);
}

function array_multi_precent_change($data = array(), $value_field = 'hits', $percent_change_field = 'percent_change') {
	if(is_array($data) == false || count($data) == 0) {
		return false;
	}
	
	$last_value = null;
	$length = count($data);
	foreach($data as $key => &$row) {
		if($last_value > 0 && $row[$value_field] > 0) {
			$row[$percent_change_field] = (($row[$value_field] - $last_value) / $last_value) * 100.0;
		} else if($last_value <= 0 && $row[$value_field] > 0) {
			$row[$percent_change_field] = 100.0;
		} else {
			$row[$percent_change_field] = 0;
		}
		// store last value
		$last_value = $row[$value_field];
	}
	
	return $data;
}

?>