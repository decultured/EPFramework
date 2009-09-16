<?php

function smarty_function_implode($params, &$smarty) {
	$values = false;
	$seperator = ', ';
	$print = true;
	$assign = false;
	
	if(isset($params['values']) != false) { $values = $params['values']; }
	if(isset($params['seperator']) != false) { $seperator = $params['seperator']; }
	if(isset($params['print']) != false) { $print = $params['print']; }
	if(isset($params['assign']) != false) { $assign = $params['assign']; }
	
	if($values == false) {
		return null;
	}
	
	$result = implode($seperator, $values);
	if($assign != false) {
		$smarty->assign($assign, $result);
	}
	
	if($print == true) {
		return $result;
	}
}

?>
