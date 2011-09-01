<?php

define ('SETUP_PATH','http://localhost/');

define ('SUB_DIR', rtrim(substr($_SERVER['PHP_SELF'],0, strrpos($_SERVER['PHP_SELF'],'/'))).'/');

define ('PATH_TO_STATIC','https://localhost/static/');

define ('MYSQL_ADDRESS','localhost');

define ('MYSQL_USERNAME','framework');

define ('MYSQL_PASSWORD','framework');

define ('MYSQL_DATABASE','framework');

define ('MYSQL_CPORT', 3306);

date_default_timezone_set('America/Los_Angeles');

$cache = array(
	'host' => '127.0.0.1',
	'port' => 11211
); //Use it if you are using memcached as caching method