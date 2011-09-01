<?php

require 'o_load.php';

$router = url_router();

/*

Rewrite for url_router()

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . ./index.php [L]

*/

switch ($router[0]) {
	case 'get':
		echo 'This is get';
		break;
	default:
		echo 'This is root';
		break;
}