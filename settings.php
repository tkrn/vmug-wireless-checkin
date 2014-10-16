<?php

/** variables **/
$dbfile 	= 'Checkin.db';		// no real need to modify
$tmpdir 	= '/tmp/';  		// include trailing slash
$password 	= 'password';		// password for admin page, A-Z,a-z,0-9

/** php defaults **/
set_time_limit(60);								// execution timelimit
date_default_timezone_set('America/New_York');	// default timezone

/** set onscreen reporting **/
error_reporting(E_ALL);

?>
