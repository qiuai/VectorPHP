<?php

if (!isset($_SESSION)) {
	session_start();
}

@ini_set("memory_limit","1024M");

define ('BASE_PATH', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);

define ('CORE_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR);

define ('TPL_PATH', BASE_PATH.'templates'.DIRECTORY_SEPARATOR);

require CORE_PATH.'config.php';

require CORE_PATH.'myDB.class.php';

require CORE_PATH.'functions.php';

require CORE_PATH.'session.class.php';

$session = new Session_Signature();

$session->checkSignature(); //validate the session

_removeSlashes();

_loadDatabase();

$GLOBALS['db']->query('show tables'); //Testing the db connection