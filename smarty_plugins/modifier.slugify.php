<?php

function smarty_modifier_slugify($string) {
	return string_strip_nonalpha($string);
}

?>