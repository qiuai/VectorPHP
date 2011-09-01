<?php

class Session_Signature {

	private $func = array();

	public function __construct() {
		if (!isset($_SESSION)) SESSION_START();
	}
	
	private function _generateSignature() {
		return md5($_SERVER["HTTP_HOST"].$_SERVER["HTTP_USER_AGENT"].$_SERVER["REMOTE_ADDR"]);
	}
	
	public function registerFuncOnDestroy($name) {
		$this->func[] = $name;
	}
	
	public function checkSignature($destroy = true, $refresh = true) {
		if (!isset($_SESSION['session_signatrue'])) $_SESSION['session_signatrue'] = $this->_generateSignature();
		if ($_SESSION['session_signatrue'] !== $this->_generateSignature()) {
			if ($destroy) {
				foreach($this->func as $single) {
					call_user_func($single);
				}
				session_destroy();
			}
			if ($refresh) header("Location: ".$_SERVER['REQUEST_URI']);
			return false;
		}
		return true;
	}

}