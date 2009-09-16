<?php

define('ZIPCODE_WEB_SERVICE', 'http://www.webservicex.net/uszip.asmx/GetInfoByZIP?USZip=');

/* This code makes use of the following SQL table:

CREATE TABLE `system_zipcodes` (
  `zipcode` int(5) unsigned zerofill NOT NULL DEFAULT '00000',
  `city` varchar(26) NOT NULL DEFAULT '',
  `state` char(2) NOT NULL DEFAULT '',
  `areacode` int(11) NOT NULL DEFAULT '0',
  `latitude` decimal(13,10) NOT NULL DEFAULT '0.0000000000',
  `longitude` decimal(13,10) NOT NULL DEFAULT '0.0000000000',
  `timezone` varchar(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`zipcode`),
  KEY `city` (`city`),
  KEY `state` (`state`)
);

*/

define('EARTH_RADIUS', 6372.795); // km

/// Calculate the distance between two latitude/longitude pairs in km and mi
function calculate_distance($a, $b) {
  $deg2rad = M_PI / 180;

  $lata = $a['latitude'] * $deg2rad;
  $lona = $a['longitude'] * $deg2rad;
  $latb = $b['latitude'] * $deg2rad;
  $lonb = $b['longitude'] * $deg2rad;

  $dlon = $lonb - $lona;
  $term1 = cos($latb) * sin($dlon);
  $term2 = cos($lata) * sin($latb) - sin($lata) * cos($latb) * cos($dlon);
  $angle = atan2(sqrt($term1 * $term1 + $term2 * $term2),
		 sin($lata) * sin($latb) + cos($lata) * cos($latb) * cos($dlon));
  $distance = EARTH_RADIUS * $angle;

  return array('km' => $distance, 'mi' => $distance / 1.609344);
}

function lookup_zipcode($zipcode) {
	global $sqlDb, $tmpEngine;
	
	$found_city = '';
	$found_state = '';

	ObjectCache('database')->Query("Select city, state From system_zipcodes Where(zipcode = '{$zipcode}') Limit 1");
	if(ObjectCache('database')->NumResults() > 0) {
		$tmpData = ObjectCache('database')->MoveNext();
	
		$found_city = $tmpData['city'];
		$found_state = $tmpData['state'];
	} else {
		/*
		ini_set("soap.wsdl_cache_enabled", "1"); // disabling WSDL cache
		ini_set("soap.wsdl_cache_dir", "/tmp"); // where to store WDSL
		ini_set("soap.wsdl_cache_ttl", "86400"); // time for WSDL to live
		
		$client = new SoapClient( ZIPCODE_WEB_SERVICE );
		var_dump( $client->GetInfoByZIP($zipcode) );
		
		print "<pre>\n";
		print "Request :\n".htmlspecialchars($client->__getLastRequest()) ."\n";
		print "Response:\n".htmlspecialchars($client->__getLastResponse())."\n";
		print "</pre>";
		*/
		include_once("includes/xml_domit/xml_domit_lite_include.php");
		
		$response = file_get_contents(ZIPCODE_WEB_SERVICE.$zipcode);
		
		$xmlDoc =& new DOMIT_Lite_Document();
        $success = $xmlDoc->parseXML($response, true); //parse document
		
		if($success) {
			//process XML
			$docElem =& $xmlDoc->documentElement;
			
			if($docElem->hasChildNodes() == false) {
			    return false;
			}
			
			$found_city = $docElem->getElementsByTagName("CITY")->item(0)->getText();
			$found_state = $docElem->getElementsByTagName("STATE")->item(0)->getText();
			$areacode = $docElem->getElementsByTagName("AREA_CODE")->item(0)->getText();
			$timezone = $docElem->getElementsByTagName("TIME_ZONE")->item(0)->getText();
			
			ObjectCache('database')->Insert("Insert Into system_zipcodes(zipcode, city, state, areacode, timezone) Values('{$zipcode}', '{$found_city}', '{$found_state}', '{$areacode}', '{$timezone}')");
		} else {
			return null;
		}
	}

	return array('city' => $found_city, 'state' =>$found_state);
}

?>