<?php

function smarty_modifier_torrent_strip_title($title) {
	if(preg_match('/^([\w\d\s\.\'"\&\$\pL]+)[^\W\s]?/i', $title, $match)) {
		$title = $match[0];
	}
	
	$title = preg_replace(array(
		'/\.|_|\-/',
		// '/\W|_|\-/', // all non-alphanumeric characters
		// '/S\d{1,4}E\d{1,4}/i', // episode numbering
		'/\d?(?:torrent|dvd|bluray|hdtv|hd|hidef|highdef|pdtv|cam|xvid|divx|rip|read\s?nfo|nfo|vcd|ac3|aac|r5|line|repack|x264|telesync|unrated|screener|scr|(?:720|1080)p?)/mi', // common release terms
		'/REAL|PROPER|LiMiTED|dTV/m',
		'/(?:avi|wmv|mov)/i', // filetypes
		'/\s\d{4}\s/', // strip years such as "2009"
		'/\s\s+/', // blocks of spaces
	), ' ', $title);
	
	return $title;
}

?>