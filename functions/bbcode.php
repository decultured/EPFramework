<?php

/*	bbcode_close_open_tags
		- accepts a string formatted with BBCode, it will then check to see if there are any open tags, and close them if nessicary.
		
		- returns the corrected string
*/
function bbcode_close_open_tags($source) {
	$open_tags = array();
	
	for($i = 0; $i < strlen($source); $i++) {
		$char = substr($source, $i, 1);
		$next_char = substr($source, $i + 1, 1);
		if($char == '[' && $next_char != '/') {
			// beginning of tags
			$found_end = strpos($source, ']', $i);
			// check to see if the ] character was found within 50 chars of where we are.
			if($found_end !== false) {
				$new_tag = substr($source, $i + 1, $found_end - $i - 1);
				$open_tags[] = $new_tag;
				
				$i = $found_end;
			}
		} else if($char == '[' && $next_char == '/') {
			// this is an end of a tag.
			$found_end = strpos($source, ']', $i+1);
			// check to see if the ] character was found within 50 chars of where we are.
			if($found_end !== false) {
				$new_tag = substr($source, $i + 2, $found_end - $i - 2);
				
				$open_tag = array_search($new_tag, $open_tags);
				if($open_tag === false) {
					// the current end tag wasn't previously opened. we should probably remove it.
					echo "new string = ".substr($source, 0, $i) . substr($source, $found_end + 1, strlen($source) - $found_end)."<br />";
					$source = substr($source, 0, $i) . substr($source, $found_end + 1, strlen($source) - $found_end);
				} else {
					// it was found, so remove it from the stack.
					unset($open_tags[$open_tag]);
				}
			}
		}
	}
	
	// check the open tags array to see if we need to close anything.
	if(count($open_tags) > 0) {
		// force the array to re-index numerically
		$open_tags = array_values($open_tags);
		for($i = 0; $i < count($open_tags); $i++) {
			$source .= "[/{$open_tags[$i]}]";
		}
	}
	
	return $source;
}

/*	bbcode_from_html
		- accepts a string formatted with HTML that needs to be translated to BBCode
		
		- returns the BBCode version of the string
*/
function bbcode_from_html($source) {
	// strip unsafe tags
	$source = preg_replace('/<\/strong>/i', "[/b]", $source);
	$source = preg_replace('/<strong>/i', "[b]", $source);

	$source = preg_replace('/<\/em>/i', "[/i]", $source);
	$source = preg_replace('/<em>/i', "[i]", $source);

	$source = preg_replace('/<\/u>/i', "[/u]", $source);
	$source = preg_replace('/<u>/i', "[u]", $source);

	$source = preg_replace('/<a href=\"(.*?)\".*?>(.*?)<\/a>/i', "[url]$1[/url]", $source);
	
	$source = preg_replace('/<font.*?color=\"(.*?)\".*?>(.*?)<\/font>/i', "[color=$1]$2[/color]", $source);
	$source = preg_replace('/<font>(.*?)<\/font>/i', "$1", $source);
	$source = preg_replace('/<img.*?src=\"(.*?)\".*?\/>/i', "[img]$1[/img]", $source);

	$source = preg_replace('/<div align="(.*?)">(.*?)<\/div>/si', "[align=$1]$2[/align]", $source);
	
	// lists
	$source = preg_replace('/<\/ol>/i', "[/ordered_list]", $source);
	$source = preg_replace('/<ol>/i', "[ordered_list]", $source);
	
	$source = preg_replace('/<\/ul>/i', "[/unordered_list]", $source);
	$source = preg_replace('/<ul>/i', "[unordered_list]", $source);
	
	$source = preg_replace('/<\/li>/i', "[/listitem]", $source);
	$source = preg_replace('/<li>/i', "[listitem]", $source);

	$source = preg_replace('/<br \/>/i', "\n", $source);
	$source = preg_replace('/<br\/>/i', "\n", $source);
	$source = preg_replace('/<br>/i', "\n", $source);

	$source = preg_replace('/&nbsp;/i', " ", $source);
	$source = preg_replace('/&quot;/i', "\"", $source);
	$source = preg_replace('/&lt;/i', "<", $source);
	$source = preg_replace('/&gt;/i', ">", $source);
	$source = preg_replace('/&amp;/i', "&", $source);
	$source = preg_replace('/&undefined;/i', "'", $source); // quickfix
	
	// well if anyone has tried to get past these checks with their html
	// we will just remove all remaining HTML
	$source = strip_tags($source);
	
	return $source;
}

/* bbcode_to_html
	- translate BBCode to HTML
	
	- returns HTML formatted string
*/ 
function bbcode_to_html($source) {
	$source = str_replace('[b]', '<strong>', $source);
	$source = str_replace('[/b]', '</strong>', $source);
	
	$source = preg_replace('/\[align=(.*?)\](.*?)\[\/align\]/si', "<div align=\"$1\">$2</div>", $source);
	
	// return the translated string
	return $source;
}

?>