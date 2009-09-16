<?php

function validation_required(&$errors = null, $value = null, $message = 'Field empty') {
	if(is_array($errors) != true) {
		return false;
	}
	
	if(is_string($value) == true && (strlen($value) == 0 || $value == null || $value == '')) {
		$errors[] = $message;
	} else if(is_numeric($value) && ($value == null)) {
		$errors[] = $message;
	}
}

?>