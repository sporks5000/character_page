<html>
<head>
</head>
<body>

<?php

$v_db_alt_user = '';
$v_db_alt_password = '';

$v_rootdir = dirname( __FILE__ );
$v_incdir = $v_rootdir . '/includes';

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
			echo "<h3>The following items were successfully imported</h3>\n" . $v_success;
		}
		if ( $v_delete ) {
			echo "<h3>The following items were successfully deleted</h3>\n" . $v_delete;
		}
		if ( $v_error ) {
			echo "<h3>The following lines did not make sense</h3>\n" . $v_error;
		}
	}
}

?>

<form action="import.php" method="post">
	Username: <input type="text" name="user"<?php
if ( $v_db_alt_user ) {
	echo " value=\"" . $v_db_alt_user . "\"";
}
?>><br />
	Password: <input type="password" name="pass"<?php
if ( $v_db_alt_user ) {
	echo " value=\"" . $v_db_alt_password . "\"";
}
?>><br />
	New Site Data:<br><textarea name="text" cols="60" rows="20"></textarea><br />
	<input type="submit" value="Submit">
</form>
</body>
</html>

<?php
exit;
?>
