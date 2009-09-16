<?php

define('INTERFAX_SOAP_URL', 'http://ws.interfax.net/dfs.asmx?wsdl');
define('INTERFAX_USERNAME', 'ajaxteam_qt_dev');
define('INTERFAX_PASSWORD', 'pass4fax');

define('FAX_DEV_NUMBER', '+18882457773');

define('FAX_STATUS_SUBMIT_FAILED', -2);
define('FAX_STATUS_SUBMIT_SUCCESS', -1);

require_once('./includes/config.inc.php');
require_once('./includes/functions.inc.php');
require_once('./includes/nusoap/nusoap.php');

function fax_send($to, $message) {
	global $sqlDb;
	
	$client = new soapclient_nu(INTERFAX_SOAP_URL, true);
	$params[] = array('Username'      => INTERFAX_USERNAME,
	                'Password'        => INTERFAX_PASSWORD,
	                'FaxNumber'       => $to,
	                'Data'            => $message,
	                'FileType'        => 'TXT'
	                );

	$result = $client->call("SendCharFax", $params);
	
	$fax_num = strip_phone_number($to);
	$fax_status = FAX_STATUS_SUBMIT_FAILED;
	$submitted_on = time();
	$transaction_id = array_safe_value($result, 'SendCharFaxResult');
	
	if($transaction_id != null && is_numeric($transaction_id) == true) {
		$fax_status = FAX_STATUS_SUBMIT_SUCCESS;
	}
	
	$sql = "Insert Into fax_log(to_num, transaction_id, submitted_on, fax_data, status) Values({$fax_num}, {$transaction_id}, {$submitted_on}, '{$message}', {$fax_status})";
	return ObjectCache('database')->Insert($sql);
}

function fax_receive_feedback() {
	global $sqlDb;
	
	$filename = './tmp/fax_feedback.log';
	$handle = fopen($filename, 'a');

	fwrite($handle, "=== POST ===\n");
	foreach($_POST as $key => $value) {
	    fwrite($handle, "{$key} = {$value}\n");
	} 
	fclose($handle);
	
	$transaction_id = array_safe_value($_POST, 'TransactionID');
	$fax_status = array_safe_value($_POST, 'Status');
	$fax_sent_on = time();
	
	if($transaction_id != null) {
		ObjectCache('database')->Update("Update fax_log Set sent_on = {$fax_sent_on}, status = {$fax_status} Where(transaction_id = {$transaction_id})");
	}
}

?>