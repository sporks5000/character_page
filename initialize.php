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

	$v_query = "
		CREATE TABLE IF NOT EXISTS " . $v_table_prefix . "names (
			URI VARCHAR(100) PRIMARY KEY, ID DECIMAL(8,2), Next DECIMAL(8,2), Previous DECIMAL(8,2)
		)
	";
	fn_query_check( "\"names\"", $v_query, false );
	$v_query = "
		CREATE TABLE IF NOT EXISTS " . $v_table_prefix . "contents (
			ID DECIMAL(8,2), Content MEDIUMTEXT, Type VARCHAR(100), Name VARCHAR(100), PRIMARY KEY ( Name, ID, Type )
		)
	";
	fn_query_check( "\"contents\"", $v_query, false );
	$v_query = "
		CREATE TABLE IF NOT EXISTS " . $v_table_prefix . "items (
			Name VARCHAR(100), ID DECIMAL(8,2), Description LONGTEXT, Next DECIMAL(8,2), Previous DECIMAL(8,2), PRIMARY KEY ( Name, ID )
		)
	";
	fn_query_check( "\"items\"", $v_query, false );
	$v_query = "
		CREATE TABLE IF NOT EXISTS " . $v_table_prefix . "styles ( 
			Type TINYTEXT, Name VARCHAR(100), ID DECIMAL(8,2), Description LONGTEXT, PRIMARY KEY ( Name, ID ) 
		)
	";
	fn_query_check( "\"styles\"", $v_query, false );
	$v_query = "
		CREATE TABLE IF NOT EXISTS " . $v_table_prefix . "categories (
			Name VARCHAR(100), Category VARCHAR(100), Start DECIMAL(8,2), End DECIMAL(8,2), PRIMARY KEY ( Name, Category, Start )
		)
	";
	fn_query_check( "\"categories\"", $v_query, false );
	$v_query = "
		CREATE TABLE IF NOT EXISTS " . $v_table_prefix . "documents (
			URI VARCHAR(100) PRIMARY KEY, Description LONGTEXT
		)
	";
	fn_query_check( "\"documents\"", $v_query, false );

	echo "Tables have been created.\n";
	// create a file to indicate that the databases have been initialized
	touch( $v_incdir . "/" . TABLE_PREFIX . 'initialized' );
	fn_close();
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
