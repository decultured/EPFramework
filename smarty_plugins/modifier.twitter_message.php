<?php

function smarty_modifier_twitter_message($string, $hashtag_base_url = 'http://search.twitter.com/search?tag=') {
	$at_reply_regex = '/\B@([\w\d_]{1,20})/';
	$url_regex = '/\b(http\:\/\/[a-z0-9\.\-\_\/\?]{1,})\b/im';
	$hashtag_regex = '/\#([a-z0-9\_\-]+)/im';
	
	// twitter url parser (THIS MUST BE FIRST OR IT BREAKS ALL OTHER REGEX)
	$string = preg_replace($url_regex, '<a href="\\1" target="_blank">\\1</a>\\2', $string);
	
	// twitter @reply parsing
	$string = preg_replace($at_reply_regex, '<a href="http://twitter.com/\\1" target="_blank">@\\1</a>\\2', $string);
	
	// twitter hashtag parsing
	$string = preg_replace($hashtag_regex, "<a href=\"{$hashtag_base_url}\\1\">#\\1</a>\\2", $string);
	
	return $string;
}

?>