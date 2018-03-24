<?php

$v_db_alt_user = "1";
$v_db_alt_password = "1";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// use the username and password from the POST data
	if ( $_POST['user'] ) {
		$v_db_alt_user = $_POST['user'];
	}
	if ( $_POST['pass'] ) {
		$v_db_alt_password = $_POST['pass'];
	}

	require( dirname( __FILE__ ) . '/config.php' );
	require( dirname( __FILE__ ) . '/connect.php' );

	$o_results = $o_mysql_connection->query( "CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "names ( URI VARCHAR(100) PRIMARY KEY, Page FLOAT(8.2) )" );
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"names\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "contents ( Page FLOAT(8.2) PRIMARY KEY, Content MEDIUMTEXT )");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"contents\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "items ( Name VARCHAR(100), Page FLOAT(8.2), Description LONGTEXT, PRIMARY KEY ( Name, Page ) )");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"items\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "styles ( Type TINYTEXT, Name VARCHAR(100), Page FLOAT(8.2), Description LONGTEXT, PRIMARY KEY ( Name, Page ) )");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"items\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("CREATE TABLE IF NOT EXISTS " . TABLE_PREFIX . "types ( Name VARCHAR(100) PRIMARY KEY, Type TINYTEXT )");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"types\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}

	echo "Tables have been created.\n";
	exit;
}
?>

<html>
<head>
</head>
<body>
<form action="initialize.php" method="post">
	Username: <input type="text" name="user"><br>
	Password: <input type="password" name="pass"><br>
	<input type="submit" value="Submit">
</form>
</body>
</html>
