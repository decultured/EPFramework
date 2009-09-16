<?php

if (!function_exists('str_getcsv')) { 
	function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") { 
		$fiveMBs = 5 * 1024 * 1024; 
		$fp = fopen("php://temp/maxmemory:$fiveMBs", 'r+'); 
		fputs($fp, $input); 
		rewind($fp); 
		
		$data_array = array();
		
		while(($data = fgetcsv($fp, 1000, $delimiter, $enclosure)) !== false) {
			$data_array[] = $data;
		}
		
		fclose($fp);
		return $data_array;
    } 
}


/*	string_strip_nonalpha
		- this function will format a string that has been stripped of any characters not allowed in the URL spec.
			- spaces are converted to underscores.
		
		- returns the formatted string.
*/
function string_strip_nonalpha($string, $seperator = '-') {
	$string = str_replace("'", '', $string); // strip ' characters
	$string = str_replace('"', '', $string); // strip " characters
	$string = preg_replace('/[^_a-z0-9]/i', $seperator, $string); // replace anything thats not a-z or 0-9 with an underscore
	$string = preg_replace('/[-]+/', $seperator, $string); // replace multiple underscores with single underscore
	$string = preg_replace("/{$seperator}\$/", '', $string); // if the last char is an underscore, then remove it
	$string = preg_replace("/^{$seperator}/", '', $string); // if the first char is an underscore, then remove it
	
	return $string;
}

function string_pluralize($string) {
	if(is_numeric($string) == true) {
		return $string . date('S', mktime(0,0,0,1,$string,2000));
	}
	
	$plural = array(
		array('/(quiz)$/i', "$1zes"),
		array('/^(ox)$/i', "$1en"),
		array('/([m|l])ouse$/i', "$1ice"),
		array('/(matr|vert|ind)ix|ex$/i', "$1ices"),
		array('/(x|ch|ss|sh)$/i', "$1es"),
		array('/([^aeiouy]|qu)y$/i', "$1ies"),
		array('/([^aeiouy]|qu)ies$/i', "$1y"),
		array('/(hive)$/i', "$1s"),
		array('/(?:([^f])fe|([lr])f)$/i', "$1$2ves"),
		array('/sis$/i', "ses"),
		array('/([ti])um$/i', "$1a"),
		array('/(buffal|tomat)o$/i', "$1oes"),
		array('/(bu)s$/i', "$1ses"),
		array('/(alias|status)$/i', "$1es"),
		array('/(octop|vir)us$/i', "$1i"),
		array('/(ax|test)is$/i', "$1es"),
		array('/s$/i', "s"),
		array('/$/', "s"),
	);

	$irregular = array(
		array('move', 'moves'),
		array('sex', 'sexes'),
		array('child', 'children'),
		array('man', 'men'),
		array('person', 'people'),
	);

	$uncountable = array('sheep', 'fish', 'series', 'species', 'money', 'rice', 'information', 'equipment');

	// save some time in the case that singular and plural are the same
	if (in_array(strtolower($string), $uncountable)) {
		return $string;
	}

	// check for irregular singular forms
	foreach ($irregular as $noun) {
		if (strtolower($string) == $noun[0])
			return $noun[1];
	}

	// check for matches using regular expressions
	foreach ($plural as $pattern) {
		if (preg_match($pattern[0], $string))
			return preg_replace($pattern[0], $pattern[1], $string);
	}

	return $string;
}

// underscore
//	- This functiona takes a human readable string such as "TemplateResource" and turns it into "template_resource".
function string_underscore($string) {
	$string = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $string));
	$string = preg_replace('/[_]+/', '_', $string); // replace multiple underscores with single underscore
	return $string;
}

/*	filename_strip_extension
		- this function accepts a filename, and will return the filename minus the extension.
*/
function filename_strip_extension($filename) {
	$path_parts = pathinfo($filename);
	
	return substr($path_parts['basename'], 0, -(strlen($path_parts['extension']) + ($path_parts['extension'] == '' ? 0 : 1)));
}

function filename_get_extension($filename) {
	$path_parts = pathinfo($filename);
	
	return $path_parts['extension'];
}

/*
	str_truncatewords()
		- truncate a string to a maximum number of words.  appends an '...' elipse at the end.
		
		- returns the truncated block of words.
*/
function str_truncatewords($string, $max_words, $ignore_punctuation = false) {
	$word_array = explode(' ', $string);
	
	if(count($word_array) > $max_words && $max_words > 0) {
		$string = implode(' ', array_slice($word_array, 0, $max_words));
		
		$prev_char = substr($string, strlen($string) - 1, 1);
		if($prev_char == '.' || $prev_char == ',') {
			// the last character is punctuation, strip it :)
			$string = substr($string, 0, strlen($string) - 1);
		}
		
		$string = "{$string}...";
	}
	
	return $string;
}

/*
	str_filesize_human_readable()
		- returns a human readable size
*/
function str_filesize_human_readable($size){
	$i=0;
	$iec = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
	while (($size/1024)>1) {
		$size = $size/1024;
		$i++;
	}
	
	$size = round($size, 2);
	
	return substr($size,0,strpos($size,'.')+4).$iec[$i];
}

function str_shorthand_numbers($size){
	$i=0;
	$iec = array("", "K", "M", "B");
	while (($size/1000)>1) {
		$size = $size/1000;
		$i++;
	}
	
	$size = round($size);
	
	return substr($size,0,strpos($size,'.')+4).$iec[$i];
}

/*
	string_bytes_from_shorthand()
		- returns the total number of bytes represented in a php short hand notation.  eg "2M" = 2048000 bytes
*/
function string_bytes_from_shorthand($val) {
   $val = trim($val);
   $last = strtolower($val{strlen($val)-1});
   switch($last) {
	   // The 'G' modifier is available since PHP 5.1.0
	   case 'g':
		   $val *= 1024;
	   case 'm':
		   $val *= 1024;
	   case 'k':
		   $val *= 1024;
   }

   return $val;
}

/*	br2nl
		- this function is the opposite of the php function "br2nl"
		
		- accepts a html string with "<br>" or "<br />" and replaces those with an "\n"
*/
function br2nl($text) {
   return  preg_replace('/<br\\s*?\/??>/i', '', $text);
}

/*	string_is_email
		- accepts a string, checks if its a properly formatted email address
		
		- returns true or false depending on if the email is properly formatted
*/
function string_is_email($email){
   $x = '\d\w!\#\$%&\'*+\-/=?\^_`{|}~';    //just for clarity

   return count($email = explode('@', $email, 3)) == 2
       && strlen($email[0]) < 65
       && strlen($email[1]) < 256
       && preg_match("#^[$x]+(\.?([$x]+\.)*[$x]+)?$#", $email[0])
       && preg_match('#^(([a-z0-9]+-*)?[a-z0-9]+\.)+[a-z]{2,6}.?$#', $email[1]);
}

function utf8_urldecode($str) {
	$str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
	return html_entity_decode($str,null,'UTF-8');;
}

?>