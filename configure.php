<?php
date_default_timezone_set('Asia/Calcutta');
error_reporting(1);
@ini_set('display_errors', '1');
@ini_set('session.name', 'sid');
/**
*	Define the webserver and path parameters
*	DIR_FS_* = Filesystem directories (local/physical)
*	DIR_WS_* = Webserver directories (virtual/URL)
*/
$cfg									=	array();

// eg, http://localhost - should not be empty for productive servers
define("BASE_URL", 						"http://".$_SERVER['HTTP_HOST']."/eva/dev/");
define("HTTP_SERVER", 					"http://".$_SERVER['HTTP_HOST']."/");
define("DIR_WS", 						"/var/www/html/");

define("TITLE", 						":: EVE");
define("ADMIN_NAME", 					"EVE ADMIN");
define("ADMIN_EMAIL", 					"");
define("ADMIN_TITLE", 					"EVE ADMIN - ");
define("ADMIN_CONTACT", 				"");
define("INFO_EMAIL", 					"suman.samanta@encoders.co.in");
define("SITE_NAME", 					"EVE");

// eg, https://localhost - should not be empty for productive servers
define("HTTPS_SERVER", 					"https://".$_SERVER['HTTP_HOST']."/");
define("ENABLE_SSL", 					FALSE);

//Database Details
define("DB_SERVER", 					"192.168.10.240");
define("DB_SERVER_USERNAME", 			"root");
define("DB_SERVER_PASSWORD", 			"root");
define("DB_DATABASE", 					"eve_main");

define("DB_TYPE", 						"mysql");
define("USE_PCONNECT", 					FALSE);


?>