<?php

defined('IN_MODULE') or die("ERROR: Cannot run this file outside of the framework.");

include_once("config.inc.php");
include_once("functions.inc.php");
include_once("globals.inc.php");

class CSMSSession
{
	var $user_id;
	var $status;
	var $last_access_time;
	var $module;
	var $last_message;
	var $data;
	
	function GetExistingSession($user_id)
	{
		global $sqlDb, $_CONFIG;
		
		$result = ObjectCache('database')->Query("SELECT * FROM sms_session WHERE(user_id = '{$user_id}')");
		
		if ($result != false && ObjectCache('database')->NumRows() > 0)
		{
			$data_array = ObjectCache('database')->MoveNext();
			
			$this->user_id = $data_array['user_id'];
			$this->last_access_time = $data_array['last_access_time'];
			// check if the session expired.
			// if($this->last_access_time + $_CONFIG['SMS_SESSION_TIMEOUT'] < time()) {
				$this->status = $data_array['status'];
			
				$this->module = $data_array['module'];
				$this->data = $data_array['data'];
			// }
			
			return true;
		}
		return false;
	}
	
	function StartSession($user_id, $module, $last_message)
	{
		$this->user_id = $user_id;
		$this->module = $module;
		$this->status = 1;
		$this->last_access_time = time();
		$this->last_message = $last_message;
	
		global $sqlDb;

		ObjectCache('database')->Insert("INSERT INTO sms_session (user_id, last_access_time, status, module, data) VALUES ('{$this->user_id}', '{$this->last_access_time}', '{$this->status}' , '{$this->module}', 0)");
	}
	
	function UpdateSession()
	{
		global $sqlDb;
		
		$this->last_access_time = time();
		
		$data = array('last_access_time' => $this->last_access_time, 'status' => $this->status, 'module' => $this->module, 'data' => $this->data);
		
		if ($this->status == 0) {
			ObjectCache('database')->InsertArray('sms_session', $data);
		} else {
			ObjectCache('database')->UpdateArray('sms_session', $data, array('user_id' => $this->user_id));
		}
	}
	
	function DeleteSession() {
		global $sqlDb;
		
		return ObjectCache('database')->Update("DELETE FROM sms_session WHERE user_id='{$this->user_id}'");
	}

}

?>
