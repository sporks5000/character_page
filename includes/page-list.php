<?php

//find variants on the URI
$v_source_uri1 = NULL;
$v_source_uri2 = NULL;
$v_source_uri3 = NULL;
$v_source_uri4 = NULL;
$v_item_category = NULL;
$v_type_dir = PAGE_DIR;
if ( $v_type == "list" ) {
	$v_type_dir = LIST_DIR;
}
$v_dir_path = BASE_URI . "/" . $v_type_dir;
$v_dir_path = preg_replace( '/\/+/', '/', $v_dir_path );
$v_source_uri1 = preg_replace( '/^' . preg_quote( $v_dir_path, '/' ) . '/', '', $v_source_uri );
if ( ! URI_STRICT ) {
	if ( $v_type == "list" ) {
		$a_uri = preg_split( '/\//', $v_source_uri1 );
		$v_item_category = $a_uri[0];
		$v_source_uri1 = preg_replace( '/^' . preg_quote( $v_item_category, '/' ) . '\//', '', $v_source_uri1 );
		$v_dir_path = $v_dir_path . $v_item_category . "/";
	}
	$v_source_uri4 = preg_replace( '/[?]/', '/?', $v_source_uri1 );
	$a_uri = $a_uri = preg_split( '/[?]/', $v_source_uri1 );
	$v_source_uri2 = $a_uri[0];
	$v_source_uri3 = $v_source_uri2 . "/";
	if ( substr( $v_source_uri2, -1 ) == "/" ) {
		$v_source_uri3 = substr( $v_source_uri2, 0, -1 );
	}
}

// Using the URI, get the ID number
$v_query = NULL;
if ( URI_STRICT ) {
	$v_query = "
		SELECT a.URI as URI, a.ID, a.Next, a.Previous, b.URI AS pURI, c.URI AS nURI FROM " . $v_table_prefix . "names a
		LEFT JOIN " . $v_table_prefix . "names b
		ON a.Previous = b.ID
		LEFT JOIN " . $v_table_prefix . "names c
		ON a.Next = c.ID
		WHERE a.URI='" . $o_mysql_connection->real_escape_string($v_source_uri1) . "'
	";
} else {
	$v_query = "
		SELECT a.URI as URI, a.ID, a.Next, a.Previous, b.URI AS pURI, c.URI AS nURI FROM " . $v_table_prefix . "names a
		LEFT JOIN " . $v_table_prefix . "names b
		ON a.Previous = b.ID
		LEFT JOIN " . $v_table_prefix . "names c
		ON a.Next = c.ID
		WHERE a.URI IN (
			'" . $o_mysql_connection->real_escape_string($v_source_uri1) . "',
			'" . $o_mysql_connection->real_escape_string($v_source_uri2) . "',
			'" . $o_mysql_connection->real_escape_string($v_source_uri3) . "',
			'" . $o_mysql_connection->real_escape_string($v_source_uri4) . "'
		)
		ORDER BY a.URI
		LIMIT 1
	";
}
$o_results = fn_query_check( "names->URI: " . $v_source_uri1, $v_query, true );
$a_row = $o_results->fetch_assoc();
$v_source_uri = $a_row['URI'];
// If the URI we've been given doesn't match what was returned, change the browser location
if ( $v_source_uri != $v_source_uri1 ) {
	http_response_code(301);
	header("Location: " . $v_prot . "://" . $_SERVER['HTTP_HOST'] . $v_dir_path . $v_source_uri );
}
// put together the rest of the variables that we need
$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_source_uri;
$v_sibling_path = str_repeat( "../", ( count( explode( '/', $v_source_uri ) ) - 1 ) );
$v_relative_path = "";
if ($v_type == "page" ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', PAGE_DIR . $v_source_uri ) ) - 1 ) );
} elseif ( $v_type == "list" ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', LIST_DIR . $v_item_category . "/" . $v_source_uri ) ) -1 ) );
}
$v_ID = $a_row['ID'];
$v_next_int_uri = NULL;
if ( $a_row['nURI'] ) {
	$v_next_int_uri = $v_sibling_path . $a_row['nURI'];
}
$v_prev_int_uri = NULL;
if ( $a_row['pURI'] ) {
	$v_prev_int_uri = $v_sibling_path . $a_row['pURI'];
}

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
