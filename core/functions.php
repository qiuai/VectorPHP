<?php

function _loadDatabase() {
	$db_info = array(
		'host' => MYSQL_ADDRESS,
		'user' => MYSQL_USERNAME,
		'pass' => MYSQL_PASSWORD,
		'db' => MYSQL_DATABASE,
		'port' => MYSQL_CPORT,
		'identifier' => 'framework',
		'utf8' => true
	);
	$GLOBALS['db'] = new myDB($db_info, true, 'apc', 0);
	//$GLOBALs['db'] = new myDB($db_info, true, $GLOBALS['cache'], 0); //Use it if you are using memcached as caching method
	$GLOBALS['nocache_db'] = new myDB($db_info, false);
	
	/*
	
	new myDB($db_info, $enable_cache, $method, $expire);
	
	$db_info: as you can see.
	$enable_cache: cache or not
	$method: non-array for APC, array for Memcached
	$expire: ok. 0 means never expire
	
	*/
}

function _isLogin() {
	return isset($_SESSION['user']);	
}

function _RemoveSlashes() {
	$array = array(
		'//',
		'./',
		'../'
	);
	$real = str_replace($array, '/', $_SERVER['REQUEST_URI']);
	if ($real !== $_SERVER['REQUEST_URI']) {
		while(true) {
			$real2 = str_replace($array, '/', $real);
			if ($real2 == $real) {
				header('Location: '.$real2);
				die;
			}
			$real = $real2;
		}
	}
}

function _cleanData($data) {
	if (is_array($data)) {
		$data = array_map('_cleanData', $data);
	}else{
		if (!get_magic_quotes_gpc()) $data = htmlspecialchars(mysql_escape_string($data));
		else $data = htmlspecialchars($data);
	}
	return $data;

}

function _cleanGlobal() {
	$GLOBALS['_GET'] = _cleanData($GLOBALS['_GET']);
	$GLOBALS['_POST'] = _cleanData($GLOBALS['_POST']);
}

function _urlRouter() {
	$requestURI = explode('/', $_SERVER['REQUEST_URI']);
	$scriptName = explode('/', $_SERVER['SCRIPT_NAME']);
	for($i= 0;$i < sizeof($scriptName);$i++)
		{
			if ($requestURI[$i]==$scriptName[$i])
			{
				unset($requestURI[$i]);
			}
		}
	
	foreach (array_values($requestURI) as $single) {
		$return[] = addslashes($single);
	}
	return array_values($return);
}

function _randomString($len) {
	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$str = '';
	for ($i=0; $i < $len; $i++){
		$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
	}
	return $str;
}

function _directAccess() {
	return;
}