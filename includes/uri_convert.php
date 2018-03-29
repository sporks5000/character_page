<?php

// Using the URI, get the page number
$v_query = "
	SELECT Page, Next, Previous from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names 
	WHERE URI='" . $o_mysql_connection->real_escape_string($v_source_uri) . "'
";
$o_results = $o_mysql_connection->query( $v_query );
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such page. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_page = $a_row['Page'];
$v_next_int_page = $a_row['Next'];
$v_prev_int_page = $a_row['Previous'];


