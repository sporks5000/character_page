<?php

$v_db_alt_user = '';
$v_db_alt_password = '';

$v_incdir = '.';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	// use the username and password from the POST data
	if ( $_POST['user'] ) {
		$v_db_alt_user = $_POST['user'];
	}
	if ( $_POST['pass'] ) {
		$v_db_alt_password = $_POST['pass'];
	}
	if ( $_POST['text'] ) {
		require( $v_incdir . '/config.php' );
		require( $v_incdir . '/connect.php' );
		require( $v_incdir . '/parse2.php' );

		$a_import_text = preg_split( "/(\r)?\n/", $_POST['text'] );
		// Parse the import text
		list( $v_error, $v_success, $v_delete ) = fn_parse_import( $a_import_text );
		if ( $v_success ) {
			echo '<div class="cp_object"><h3>The following items were successfully imported:</h3>' . "\n" . $v_success . "</div>\n";
		}
		if ( $v_delete ) {
			echo '<div class="cp_object"><h3>The following items were successfully deleted:</h3>' . "\n" . $v_delete . "</div>\n";
		}
		if ( $v_error ) {
			echo '<div class="cp_object"><h3>The following lines did not make sense:</h3>' . "\n" . $v_error . "</div>\n";
		}
		fn_close();
	}
}
exit;
?>
