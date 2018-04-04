<?php

function fn_query_check( $v_identifier, $v_query, $v_checkrows ) {
// check query results to see if there are errors
	global $o_mysql_connection;
	$o_results = $o_mysql_connection->query( $v_query );
	if ( $o_mysql_connection->warning_count || $o_mysql_connection->errno ) {
		if ( ! headers_sent() ) {
			http_response_code(500);
		}
		echo "Encountered a mysql error with the following object:<br /><br />" . $v_identifier . "<br /><br />The error was as follows:" . $o_mysql_connection->error;
		error_log("Character Page at " . $_SERVER['REQUEST_URI'] . " - Encountered a mysql error with the following object: " . $v_identifier, 0);
		$o_mysql_connection->rollback();
		fn_close();
	} elseif ( $v_checkrows && $o_results->num_rows == 0 ) {
		if ( ! headers_sent() ) {
			http_response_code(500);
		}
		echo "No such object: " . $v_identifier;
		error_log("Character Page at " . $_SERVER['REQUEST_URI'] . " - No such object: " . $v_identifier, 0);
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
		if ( ! headers_sent() ) {
			http_response_code(500);
		}
		echo "Failed to connect to MySQL: (" . $o_mysql_connection->connect_errno . ") " . $o_mysql_connection->connect_error;
		exit;
	}
} else {
	$o_mysql_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if ( $o_mysql_connection->connect_errno ) {
		if ( ! headers_sent() ) {
			http_response_code(500);
		}
		echo "Failed to connect to MySQL: (" . $o_mysql_connection->connect_errno . ") " . $o_mysql_connection->connect_error;
		exit;
	}
}

$v_table_prefix = $o_mysql_connection->real_escape_string(TABLE_PREFIX);
