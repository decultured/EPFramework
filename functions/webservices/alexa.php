<?php

include_once("includes/xml_domit/xml_domit_lite_include.php");

function alexa_get_ranking($url = null) {
	if($url == null) {
		return null;
	}
	
	$response = file_get_contents("http://data.alexa.com/data?cli=10&dat=snbamz&url={$url}");
	
	$xmlDoc =& new DOMIT_Lite_Document();
    $success = $xmlDoc->parseXML($response, true); //parse document
	
	$ranking = 0;
	if($success) {
		//process XML
		$docElem =& $xmlDoc->documentElement;
		
		if($docElem->hasChildNodes() == false) {
		    return false;
		}
		try {
			$popularity = $docElem->getElementsByTagName('POPULARITY')->item(0);
			if($popularity == null) {
				return 0;
			}
			
			$ranking = $popularity->getAttribute('TEXT');
		} catch (Exception $e) {
			$ranking = 0;
		}
	}
	
	return $ranking;
}


?>