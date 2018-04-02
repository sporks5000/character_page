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

require( $v_incdir . '/exp_functions.php' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	// use the username and password from the POST data
	$v_db_alt_user = $_POST['user'];
	$v_db_alt_password = $_POST['pass'];

	require( $v_incdir . '/config.php' );
	require( $v_incdir . '/connect.php' );

	$v_query = "
		SELECT URI, ID from " . $v_table_prefix . "names 
		ORDER BY ID ASC
	";
	$o_results = fn_query_check( "\"names\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare name " . $a_row['URI'] . " " . fn_minimize_ID($a_row['ID']) . "\n";
	}
	$v_out .= "\n\n\n";

	$v_query = "
		SELECT Name, Category, Start, End from " . $v_table_prefix . "categories
		ORDER BY Category ASC
	";
	$o_results = fn_query_check( "\"categories\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare category " . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_category_id($a_row['Start'], $a_row['End']) . "\n";
	}
	$v_out .= "\n\n\n";

	$v_query = "
		SELECT ID, Content, Type, Name from " . $v_table_prefix . "contents
		ORDER BY ID ASC
	";
	$o_results = fn_query_check( "\"contents\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		if ( $a_row['Type'] == "block" || $a_row['Type'] == "list" ) {
			$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'] . "\n" . $a_row['Content'] . "\n\n";
		} else {
			$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Content'] . "\n\n";
		}
	}
	$v_out .= "\n\n\n";

	$v_query = "
		SELECT Name, ID, Description from " . $v_table_prefix . "items
		ORDER BY ID ASC
	";
		$o_results = fn_query_check( "\"items\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare item " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . "\n" . $a_row['Description'] . "\n\n";
	}
	$v_out .= "\n\n\n";

	$v_query = "
		SELECT Type, Name, ID, Description from " . $v_table_prefix . "styles
		ORDER BY ID ASC
	";
		$o_results = fn_query_check( "\"styles\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare style " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Description'] . "\n\n";
	}
	$v_out .= "\n\n\n";

	$v_query = "
		SELECT URI, Description from " . $v_table_prefix . "documents
		ORDER BY URI ASC
	";
		$o_results = fn_query_check( "\"documents\"", $v_query, false );
	while ( $a_row = $o_results->fetch_assoc() ) {
		$v_out .= ">>>>> declare document " . $a_row['URI'] . "\n" . $a_row['Description'] . "\n\n";
	}

	while ( substr( $v_out, -1 ) == "\n" ) {
		$v_out = substr( $v_out, 0, -1 );
	}

	echo 'Site Data:<br><textarea name="text" cols="60" rows="20">' . $v_out . '</textarea><br />' . "\n";
	fn_close();
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
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	fn_close();
} else {
	exit;
}
?>
