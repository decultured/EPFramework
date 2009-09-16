<?php

$smarty_timers = array();

function smarty_function_timer($params, &$smarty) {
	global $smarty_timers;
	
	$name = false;
	$print = false;
	$assign = false;
	
	if(isset($params['name']) != false) { $name = $params['name']; }
	if(isset($params['print']) != false) { $print = $params['print']; }
	if(isset($params['assign']) != false) { $assign = $params['assign']; }
	
	if($name == false) {
		return null;
	}
	
	$result = null;
	if($print == true || $assign == true) {
		if(array_key_exists(md5($name), $smarty_timers) == false) {
			return "unknown timer";
		} else {
			$result = microtime(true) - $smarty_timers[md5($name)];
		}
		
		if($print == true) {
			return $result;
		} else if($assign == true) {
			$smarty->assign($name, $result);
		}
	} else {
		$smarty_timers[md5($name)] = microtime(true);
	}
}

?>
