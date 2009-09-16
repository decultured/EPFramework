<?php

define('TAG_CLOUD_MAX_DISPLAY', 100);

function tag_list_format($obj_tag_data) {
	// find the maximum value & how many unique values there are
	$max_val = 0;
	$values = array();
	for($i = 0; $i < count($obj_tag_data); $i++) {
		if($obj_tag_data[$i]['times_used'] > $max_val) {
			$max_val = $obj_tag_data[$i]['times_used'];
		}
		$values[] = $obj_tag_data[$i]['times_used'];
	}
	
	$values = array_unique($values);

	if(count($values) <= 1) {
		return $obj_tag_data;
	}
	// calculate the needed tag sizes
	for($i = 0; $i < count($obj_tag_data); $i++) {
		$percentage = ($obj_tag_data[$i]['times_used'] / $max_val) * 100;
		
		if($percentage >= 80) {
			$obj_tag_data[$i]['tag_size'] = 'h1';
		} else if($percentage < 80 && $percentage >= 60) {
			$obj_tag_data[$i]['tag_size'] = 'h2';
		} else if($percentage < 60 && $percentage >= 40) {
			$obj_tag_data[$i]['tag_size'] = 'h3';
		}
	}
	
	return $obj_tag_data;
}

?>