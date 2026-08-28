<?php
//do not use any modern php features in this file

if(version_compare("8.0.0", PHP_VERSION) > 0){
	echo "[ERROR] Use PHP >= 8.0.0 to launch this server software.\n";
	exit(1);
}

require_once(dirname(__FILE__)."/config_post.php"); //that config uses modern php features which may cause errors before php version check has been completed