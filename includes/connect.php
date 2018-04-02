<?php

function fn_query_check( $v_identifier, $v_query, $v_checkrows ) {
// check query results to see if there are errors
	global $o_mysql_connection;
	$o_results = $o_mysql_connection->query( $v_query );
	if ( $o_mysql_connection->warning_count || $o_mysql_connection->errno ) {
		echo "Encountered an error with the following object:<br /><br />" . $v_identifier . "<br /><br />The error was as follows:" . $o_mysql_connection->error;
		$o_mysql_connection->rollback();
		fn_close();
	} elseif ( $v_checkrows && $o_results->num_rows == 0 ) {
		echo "No such object: " . $v_identifier;
		fn_close();
	}
	return $o_results;
}

function fn_close() {
	global $o_mysql_connection;
	$o_mysql_connection->close();
	exit;
}

// Initialize the mysql connection
$o_mysql_connection = '';
if ( $v_db_alt_user && $v_db_alt_password ) {
	$o_mysql_connection = new mysqli(DB_HOST, $v_db_alt_user, $v_db_alt_password, DB_NAME);
	if ( $o_mysql_connection->connect_errno ) {
		echo "Failed to connect to MySQL: (" . $o_mysql_connection->connect_errno . ") " . $o_mysql_connection->connect_error;
		exit;
	}
} else {
	$o_mysql_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if ( $o_mysql_connection->connect_errno ) {
		echo "Failed to connect to MySQL: (" . $o_mysql_connection->connect_errno . ") " . $o_mysql_connection->connect_error;
		exit;
	}
}

$v_table_prefix = $o_mysql_connection->real_escape_string(TABLE_PREFIX);
