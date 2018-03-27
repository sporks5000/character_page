<?php

$v_db_alt_user = "1";
$v_db_alt_password = "1";

$v_rootdir = dirname( __FILE__ );
$v_incdir = $v_rootdir . '/includes';
require( $v_incdir . '/config.php' );

// If the databases have already been initialized, go back to the main page
if ( file_exists( $v_incdir . "/" . TABLE_PREFIX . 'initialized' ) ) {
	// determine the main page for the site (not the source)
	$v_prot = 'http';
	if ( isset( $_SERVER['HTTPS'] ) ) {
		$v_prot .= 's';
	}
	$v_main_page = $v_prot . '://' . $_SERVER['HTTP_HOST'] . BASE_URI;
	header("Location: " . $v_main_page );
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// use the username and password from the POST data
	if ( $_POST['user'] ) {
		$v_db_alt_user = $_POST['user'];
	}
	if ( $_POST['pass'] ) {
		$v_db_alt_password = $_POST['pass'];
	}

	require( $v_incdir . '/connect.php' );

	$o_results = $o_mysql_connection->query( "
		CREATE TABLE IF NOT EXISTS " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names (
			URI VARCHAR(100) PRIMARY KEY, Page FLOAT(8.2) 
		)
	");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"names\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("
		CREATE TABLE IF NOT EXISTS " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "contents (
			Page FLOAT(8.2), Content MEDIUMTEXT, Type TINYTEXT, Name VARCHAR(100), PRIMARY KEY ( Name, Page )
		)
	");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"contents\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("
		CREATE TABLE IF NOT EXISTS " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items (
			Name VARCHAR(100), Page FLOAT(8.2), Description LONGTEXT, Next FLOAT(8.2), Previous FLOAT(8.2), PRIMARY KEY ( Name, Page )
		)
	");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"items\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("
		CREATE TABLE IF NOT EXISTS " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles ( 
			Type TINYTEXT, Name VARCHAR(100), Page FLOAT(8.2), Description LONGTEXT, PRIMARY KEY ( Name, Page ) 
		)
	");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"items\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("
		CREATE TABLE IF NOT EXISTS " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types (
			Name VARCHAR(100), Type VARCHAR(100), PRIMARY KEY ( Name, Type )
		)
	");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"types\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}
	$o_results = $o_mysql_connection->query("
		CREATE TABLE IF NOT EXISTS " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "documents (
			URI VARCHAR(100) PRIMARY KEY, Description LONGTEXT
		)
	");
	if ( $o_mysql_connection->errno ) {
		echo "Failed to create \"documents\" table: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
		exit;
	}

	echo "Tables have been created.\n";
	// create a file to indicate that the databases have been initialized
	touch( $v_incdir . "/" . TABLE_PREFIX . 'initialized' );
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
