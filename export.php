<html>
<head>
</head>
<body>

<?php

$v_db_alt_user = '';
$v_db_alt_password = '';
$v_out = '';

$v_rootdir = dirname( __FILE__ );
$v_incdir = $v_rootdir . '/includes';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	// use the username and password from the POST data
	$v_db_alt_user = $_POST['user'];
	$v_db_alt_password = $_POST['pass'];

	require( $v_incdir . '/config.php' );
	require( $v_incdir . '/connect.php' );

	$o_results = $o_mysql_connection->query("
		SELECT URI, Page from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names 
		ORDER BY Page ASC
	");
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare name " . $a_row['URI'] . " " . $a_row['Page'] . "\n";
	}
	$v_out .= "\n\n\n";

	$o_results = $o_mysql_connection->query("
		SELECT Name, Type from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types
		ORDER BY Type ASC
	");
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare type " . $a_row['Name'] . " " . $a_row['Type'] . "\n";
	}
	$v_out .= "\n\n\n";

	$o_results = $o_mysql_connection->query("
		SELECT Page, Content from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "contents
		ORDER BY Page ASC
	");
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare content " . $a_row['Page'] . "\n" . $a_row['Content'] . "\n\n";
	}
	$v_out .= "\n\n\n";

	$o_results = $o_mysql_connection->query("
		SELECT Name, Page, Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
		ORDER BY Page ASC
	");
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare item " . $a_row['Name'] . " " . $a_row['Page'] . "\n" . $a_row['Description'] . "\n\n";
	}
	$v_out .= "\n\n\n";

	$o_results = $o_mysql_connection->query("
		SELECT Type, Name, Page, Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles
		ORDER BY Page ASC
	");
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare style " . $a_row['Name'] . " " . $a_row['Page'] . " " . $a_row['Type'] . "\n" . $a_row['Description'] . "\n\n";
	}
	$v_out .= "\n\n\n";

	$o_results = $o_mysql_connection->query("
		SELECT URI, Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "documents
		ORDER BY URI ASC
	");
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare document " . $a_row['URI'] . "\n" . $a_row['Description'] . "\n\n";
	}

	while ( substr( $v_out, -1 ) == "\n" ) {
		$v_out = substr( $v_out, 0, -1 );
	}

	echo 'Site Data:<br><textarea name="text" cols="60" rows="20">' . $v_out . '</textarea><br />' . "\n";
	exit;
}

?>

<form action="export.php" method="post">
	Username: <input type="text" name="user"<?php
if ( $v_db_alt_user ) {
	echo " value=\"" . $v_db_alt_user . "\"";
}
?>>
	<br />
	Password: <input type="password" name="pass"<?php
if ( $v_db_alt_user ) {
	echo " value=\"" . $v_db_alt_password . "\"";
}
?>>
	<br />
	<input type="submit" value="Submit">
</form>
</body>
</html>

<?php
exit;
?>
