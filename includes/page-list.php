<?php

if ($v_type == "page" ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', PAGE_DIR ) ) - 1 ) );
	$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_source_uri;
} elseif ( $v_type == "list" ) {
	$v_uri = preg_replace( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( LIST_DIR, '/' ) . '/', '', $_SERVER['REQUEST_URI'] );
	$a_uri = preg_split( '/[\/&?]+/', $v_uri );
	$v_item_category = $a_uri[0];
	$v_source_uri = $a_uri[1];
	$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_source_uri;
	$v_relative_path = str_repeat( "../", ( count( explode( '/', LIST_DIR ) ) ) );
}

// Using the URI, get the ID number
$v_query = "
	SELECT a.ID, a.Next, a.Previous, b.URI AS pURI, c.URI AS nURI FROM " . $v_table_prefix . "names a
	LEFT JOIN " . $v_table_prefix . "names b
	ON a.Previous = b.ID
	LEFT JOIN " . $v_table_prefix . "names c
	ON a.Next = c.ID
	WHERE a.URI='" . $o_mysql_connection->real_escape_string($v_source_uri) . "'
";
$o_results = fn_query_check( "names->URI: " . $v_source_uri, $v_query, true );
$a_row = $o_results->fetch_assoc();
$v_ID = $a_row['ID'];
$v_next_int_uri = $a_row['nURI'];
$v_prev_int_uri = $a_row['pURI'];

// Using the ID number, find out what content we need to pull
$o_results = array();
if ( $v_type == "page" ) {
	$v_query = "
		SELECT Content from " . $v_table_prefix . "contents
		WHERE ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
		AND Type = 'page'
		ORDER BY ID DESC
		LIMIT 1
	";
	$o_results = fn_query_check( "contents->ID: " . $v_ID . ", page", $v_query, true );
} elseif ( $v_type == "list" ) {
	$v_query = "
		SELECT Content from " . $v_table_prefix . "contents
		WHERE ID <= '" . $o_mysql_connection->real_escape_string($v_ID) . "'
		AND Type = 'list'
		AND Name = '" . $o_mysql_connection->real_escape_string($v_item_category) . "'
		ORDER BY ID DESC
		LIMIT 1
	";
	$o_results = fn_query_check( "contents->ID: " . $v_ID . ", list, " . $v_item_category, $v_query, true );
}

$a_row = $o_results->fetch_assoc();
$v_content = $a_row['Content'];

// Create the variables that will store the head, the body, and any errors
$out_head = "<html>\n<head>\n";
$out_body = '';
$out_error = '';

// split the contents by line and parse them into an object
$a_content_list = preg_split( "/(\r)?\n/", $v_content );
list( $o_content, $v_errors ) = fn_parse_content( $a_content_list, true, $v_type );

$out_error .= $v_errors . "<!--\n";
$v_full_style = $o_content['style'];

// pull the full style
$v_query = "
	SELECT Description from " . $v_table_prefix . "styles
	WHERE Type = 'full'
	AND Name = '" . $o_mysql_connection->real_escape_string($v_full_style) . "'
	AND ID<='" . $o_mysql_connection->real_escape_string($v_ID) . "'
	ORDER BY ID DESC
	LIMIT 1
";
$o_results = fn_query_check( "styles->Name: " . $v_full_style . ", full, " . $v_ID, $v_query, true );
$a_full_style = $o_results->fetch_assoc();
$v_full_style_text = $a_full_style['Description'];
$a_full_style_list = preg_split( "/(\r)?\n/", $v_full_style_text );

list( $v_body, $v_error, $v_head, $v_iframe ) = fn_parse_descriptions( $a_full_style_list, 'full' );
$js_top = file_get_contents( $v_incdir . '/js_top_full.txt' );
while ( substr($js_top, -1) != '"' ) {
	$js_top = substr( $js_top, 0, -1 );
}
$out_head .= $js_top . $v_iframe . file_get_contents( $v_incdir . '/js_bottom_full.txt' );

$out_body .= $v_body . "</body>\n</html>";
$out_error .= $v_error . "-->\n";
$out_head .= $v_head . "</head>\n";

echo $out_head . $out_error . $out_body;

fn_close();
