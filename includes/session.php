<?php

function fn_log_out() {
	setcookie( session_name(), session_id(), time()-1, BASE_URI );
	session_destroy();
	header('Location: '.$_SERVER['REQUEST_URI']);
	exit;
}

require( $v_incdir . '/config.php' );
$v_lifetime = 1 * 60 * 60; // session lifetime should be one hour
session_name( TABLE_PREFIX . 'characterpage' );
session_set_cookie_params( $v_lifetime, BASE_URI );
session_start();
// allow the session lifetime to increase every time the user refreshes the page
setcookie( session_name(), session_id(), time()+$v_lifetime, BASE_URI );
