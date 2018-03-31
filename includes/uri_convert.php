<?php

// Using the URI, get the page number
$v_query = "
	SELECT a.Page, a.Next, a.Previous, b.URI AS pURI, c.URI AS nURI FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names a
	LEFT JOIN " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names b
	ON a.Previous = b.Page
	LEFT JOIN " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names c
	ON a.Next = c.Page
	WHERE a.URI='" . $o_mysql_connection->real_escape_string($v_source_uri) . "'
";
$o_results = $o_mysql_connection->query( $v_query );
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such page. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_page = $a_row['Page'];
$v_next_int_page = $a_row['nURI'];
$v_prev_int_page = $a_row['pURI'];

