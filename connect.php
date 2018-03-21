<?php

// Initialize the mysql connection
$o_mysql_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ( $o_mysql_connection->connect_errno ) {
	echo "Failed to connect to MySQL: (" . $o_mysql_connection->connect_errno . ") " . $o_mysql_connection->connect_error;
	exit;
}
