<?php

defined('IN_MODULE') or die("ERROR: Cannot run this file outside of the framework.");

include_once('./includes/simplewire/SMS.php');
require_once('./includes/config.inc.php');
require_once('./includes/functions.inc.php');

// sms_recieve_msg()
//	- this function will respond to a callback initiated from SimpleWire's servers.
//
//	- returns: an array of the message data, or null when the data was invalid.
function sms_recieve_msg() {
	global $_CONFIG, $sqlDb;
	
	file_put_contents('./tmp/sms.log', "{$_POST['xml']}\r\n\r\n", FILE_APPEND);
	
	$xml_data = null;
	if(get_magic_quotes_gpc() == true || get_magic_quotes_runtime() == true) {
		$xml_data = stripslashes($_POST['xml']);
	} else {
		$xml_data = $_POST['xml'];
	}
	
	$sms = new SMS();
	// make sure its a delivery of a message, then parse if so
	if($sms->parse($xml_data) && $sms->isDeliver() == true) {
		// get source and destination info
		$source = $sms->getSourceAddr();
		$dest = $sms->getDestinationAddr();
		// build the array we're going to return
		$ret_data = array(
				'ticket_id' => $sms->getTicketId(),
				'timestamp' => time(),
				'from' => strip_phone_number($source->getAddress()),
				'to' => strip_phone_number($dest->getAddress()),
				'carrier' => $source->getCarrier(),
				'message' => $sms->getMessageText(),
			);
		// check to see if our message already has been logged.
		ObjectCache('database')->Query("Select msg_time From sms_message_log Where(ticket_id = '{$ret_data['ticket_id']}')");
		if(ObjectCache('database')->NumRows() > 0) {
			// this message has already been processed, we dont wanna re-process it.
			return null;
		}
		// save the message to the database.
		sms_log_message($ret_data['ticket_id'], $ret_data['timestamp'], $ret_data['carrier'], $ret_data['from'], $ret_data['to'], $ret_data['message']);
		// done, return data.
		return $ret_data;
	} else {
		// TODO: i think errors can come back to us through this, look into it later.
		return null;
	}
}

// sms_send_msg()
//	- this function allows the system to send a message to users
//
//	- returns an array of the message data on success, false on failure.
function sms_send_msg($to, $message) {
	if(strlen($message) > 160) {
		die("<pre>message too long!!!!\n\n{$message}</pre>");
	}
	echo "<pre>{$message}</pre>";
	// return;
	global $_CONFIG;
	return;
	if(isset($to) == false || $to == '') {
		echo 'sms_send_msg() - $to was empty.<br />';
		return;
	} else if(isset($message) == false || $message == '') {
		echo 'sms_send_msg() - $message was empty.<br />';
		return;
	}
	// clean the target number
	$to = strip_phone_number($to);
	
	$sms = new SMS();
	// login to our account
	$sms->setAccountId($_CONFIG['SMS_ACCOUNT_ID']);
	$sms->setAccountPassword($_CONFIG['SMS_ACCOUNT_PWD']);
	// set the source to our short code
	$sms->setSourceAddr(new Address($_CONFIG['SMS_SHORT_CODE'], SMS_ADDR_TYPE_NETWORK));
	// set the destination to the user's mobile phone.
	// TODO: Internaltionalization?	 Right now we support US numbers only
	$sms->setDestinationAddr(new Address("+1{$to}", SMS_ADDR_TYPE_UNKNOWN));
	// set the message text
	$sms->setMessageText($message);
	
	// try to send the message
	if($sms->submit() == true) {
		// message was sent okay
		$from = $_CONFIG['SMS_SHORT_CODE'];
		$msg_time = time();
		// log the message
		sms_log_message($sms->getTicketId(), $msg_time, -1, $from, $to, $message);
		// build an array to return back
		$ret_data = array(
				'ticket_id' => $sms->getTicketId(),
				'timestamp' => $msg_time,
				'from' => strip_phone_number($from),
				'to' => strip_phone_number($to),
				'message' => $sms->getMessageText(),
			);
		return $ret_data;
	} else {
		// message failed to send, what do we do here?
		echo "Error Code: " . $sms->getErrorCode() . "<br>\n";
		echo "Error Description: " . $sms->getErrorDescription() . "<br>\n";
		return false;
	}
}

// sms_log_message()
//	- this function is internal to the sms handler, its for storing messages to the database
//
//	- returns nothing.
function sms_log_message($ticket_id, $timestamp, $carrier = '-1', $from, $to, $message) {
	global $sqlDb;
	
	$new_row = array(
			'ticket_id' => $ticket_id,
			'msg_time' => $timestamp,
			'from_carrier' => $carrier,
			'from_num' => strip_phone_number($from),
			'to_num' => strip_phone_number($to),
			'message' => $message,
		);
	// insert array into db
	$result = ObjectCache('database')->InsertArray('sms_message_log', $new_row);
	
	if($result == false) {
		die(ObjectCache('database')->LastError());
	}
	
}

?>