<?php

require 'o_load.php';

$router = _urlRouter();

/*

Rewrite for _urlRouter()

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