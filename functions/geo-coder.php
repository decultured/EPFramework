<?php

/* street_address_format
	- function accepts an array with the following members:
		street_1, street_2, city, state, zipcode
	
	- returns a string formatted for US addresses.
	
	TIP: use nl2br() on the result if you need it for HTML.
*/
function street_address_format($data = null) {
	if($data == null) {
		return '';
	}
	
	$formatted_address = '';
	$formatted_address .= ($data['street_1'] != null ? "{$data['street_1']}\n" : "");
	$formatted_address .= ($data['street_2'] != null ? "{$data['street_2']}\n" : "");
	$formatted_address .= ($data['city'] != null ? "{$data['city']}, " : "");
	$formatted_address .= ($data['state'] != null ? "{$data['state']} " : "");
	$formatted_address .= ($data['zipcode'] != null && $data['zipcode'] != 0 ? "{$data['zipcode']}\n" : "");
	
	return $formatted_address;
}

/*
//
// this code was for the geocoder.us geocoding service, its now been replaced by the google geocoder.
//
define('GEOCODER_URL', 'http://pplante:sellock@geocoder.us/member/service/csv/geocode?address=');

function lookup_geocode($address1, $address2, $city, $state, $zipcode) {
	$address = GEOCODER_URL.urlencode("{$address1} {$address2} {$city} {$state} {$zipcode}");
	// make the request to the server.
	$response = fopen($address, "r");
	$csv_dataset = fgetcsv($response, 1000);
	fclose($response);
	
	if($csv_dataset != false && is_array($csv_dataset) == true && is_float($csv_dataset[0]) == true) {
		$results = array('longitude' => $csv_dataset[1], 'latitude' => $csv_dataset[0]);
		
		return $results;
	}
	
	return null;
}
*/

include("includes/external/JSON.php");

// this function uses the google maps geocoding api
// documentation for this api is available at: http://www.google.com/apis/maps/documentation/#Geocoding_HTTP_Request
function lookup_geocode($address1, $address2, $city, $state, $zipcode) {
	global $_CONFIG;
	
	$address = "http://maps.google.com/maps/geo?q=".urlencode("{$address1} {$address2} {$city} {$state} {$zipcode}")."&output=json&key={$_CONFIG['GOOGLE_API_KEY']}";
	$json_string = file_get_contents( $address );
	
	$json = new Services_JSON();
	$geo_data = $json->decode( $json_string );
	
	if($geo_data != null && $geo_data->Status->code == 200) {
		if(is_array($geo_data->Placemark) == true) {
			// generate an array to return, right now all we capture from the google api is the lat, long for the address.
			$results = array('longitude' => $geo_data->Placemark[0]->Point->coordinates[0], 'latitude' => $geo_data->Placemark[0]->Point->coordinates[1]);
			return $results;
		}
	}
	
	return null;
}

?>