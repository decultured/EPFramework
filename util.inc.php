<?php

function __autoload($class) {
	@include_once("{$class}.php");
}

function dump($var = null, $die = true, $format = '') {
	// header("Content-Type: text/plain");
	
	if($die == true) {
		while(@ob_end_clean() == true) {
			// clear all the output buffers.
		}
	}
	
	if(g('framework') != null && g('framework')->getRunMode() == 'web') { echo "<pre>"; }
	if(is_array($var) == true && $format == 'csv') {
		if(is_array($var[0]) == true) {
			foreach($var as $row) {
				echo implode(', ', $row) . "\n";
			}
		} else {
			echo implode(', ', $var) . "\n";
		}
	} else {
		var_dump($var);
	}
	if(g('framework') != null && g('framework')->getRunMode() == 'web') { echo "</pre>"; }
	
	if($die == true) { die(); }
}

function cron_lock($lock_file = __FILE__) {
	if(file_exists("{$lock_file}.cron") == true) {
		// determine if the other version is actually running
		$other_pid = file_get_contents("{$lock_file}.cron");
		if(posix_kill($other_pid, 0) == true) {
			return false;
		} else {
			// not running, remove the existing .cron lock file
			cron_unlock($lock_file);
		}
	}

	file_put_contents("{$lock_file}.cron", posix_getpid());
	return true;
}

function cron_unlock($lock_file = __FILE__) {
	@unlink("{$lock_file}.cron");
}

function file_exists_incpath ($file) {
	$paths = explode(PATH_SEPARATOR, get_include_path());
 
	foreach($paths as $path) {
		// Formulate the absolute path
		$fullpath = $path . DIRECTORY_SEPARATOR . $file;
 
		// Check it
		if(file_exists($fullpath)) {
			return $fullpath;
		}
	}
 
	return false;
}

function set_page_title($title) {
	global $tplEngine;
	
	ObjectCache('template')->assign('page_title', $title);
}

/*
	add_to_sidebar
		- accepts either a string or file to include
*/
function add_to_sidebar($data = null, $type = 'string') {
	global $global_sidebar_items;
	
	$global_sidebar_items[] = array('type' => $type, 'data' => $data);
}

/*
	display_user_errors
		- accepts an error message to prepend to pages
*/
function display_user_errors($error_messages) {
	if(is_string($error_messages) == true) {
		$error_messages = array($error_messages);
	}
	
	ObjectCache('framework')->error_messages = array_merge(ObjectCache('framework')->error_messages, $error_messages);
}

/* display_user_warnings
		- accepts a warning messsage to prepend to pages
*/
function display_user_warnings($warnings) {
	if(is_string($warnings) == true) {
		$warnings = array($warnings);
	}
	
	ObjectCache('framework')->warning_messages = array_merge(ObjectCache('framework')->warning_messages, $warnings);
}

/* index_add_javascript_file
		- accepts a filename to include in the index wrapper template
*/
function index_add_javascript_file($file) {
	ObjectCache('framework')->javascript_files[] = $file;
}

/* index_add_css_stylesheet
		- accepts a filename to include in the index wrapper template
*/
function index_add_css_stylesheet($file) {
	ObjectCache('framework')->css_stylesheets[] = $file;
}

/**
 * Generates a pseudo-random UUID according to RFC 4122
 * http://us3.php.net/manual/en/function.uniqid.php#69164
 *
 * @return string
 */
function uuid() {
   return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
       mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
       mt_rand( 0, 0x0fff ) | 0x4000,
       mt_rand( 0, 0x3fff ) | 0x8000,
       mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
}

function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

if(function_exists('extract_querystring_var') == false) {
	function extract_querystring_var(&$url, $key, $remove_var = false) {
		$parts = @parse_url($url);
		if($parts == false) {
			return;
		}
		
		$extracted_param = null;
		if(array_key_exists('query', $parts) == true) {
			parse_str($parts['query'], $params);
			if(array_key_exists($key, $params) == false) {
				return false;
			}
			
			$extracted_param = $params[$key];
			
			if($remove_var == true) {
				unset($params[$key]);
			}
			
			$parts['query'] = http_build_query($params);
		}
		
		$new_url = (array_key_exists('scheme', $parts) && $parts['scheme'] != null ? "{$parts['scheme']}://" : '');
		$new_url .= (array_key_exists('host', $parts) && $parts['host'] != null ? "{$parts['host']}" : '');
		$new_url .= (array_key_exists('path', $parts) && $parts['path'] != null ? "{$parts['path']}" : '');
		$new_url .= (array_key_exists('query', $parts) && $parts['query'] != null ? "?{$parts['query']}" : '');
		
		$url = $new_url;
		
		return $extracted_param;
	}
}

?>