<?php

require( $v_incdir . '/uri_convert.php' );

// Using the page number, find out what content we need to pull
$v_query = "
	SELECT Content from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "contents
	WHERE Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
	AND Type = 'page'
	ORDER BY Page DESC
	LIMIT 1
";
$o_results = $o_mysql_connection->query( $v_query );
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such page content. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_content = $a_row['Content'];

// Create the variables that will store the head, the body, and any errors
$out_head = "<html>\n<head>\n";
$out_body = '';
$out_error = "";

// split the contents by line and parse them into an object
$a_content_list = preg_split( "/(\r)?\n/", $v_content );
list( $o_content, $v_errors ) = fn_parse_content( $a_content_list, true, "page" );

require( $v_incdir . '/final_full.php' );
