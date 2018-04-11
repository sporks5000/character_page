<?php

//find variants on the URI
$v_document_uri = NULL;
$v_document_uri1 = NULL;
$v_document_uri2 = NULL;
$v_document_uri3 = NULL;
$v_document_uri4 = NULL;
$v_item_category = NULL;
$v_document_uri1 = preg_replace( '/^' . preg_quote( BASE_URI, '/' ) . '/', '', $v_source_uri );
if ( ! URI_STRICT ) {
	$v_document_uri4 = preg_replace( '/[?]/', '/?', $v_document_uri1 );
	$a_uri = $a_uri = preg_split( '/[?]/', $v_document_uri1 );
	$v_document_uri2 = $a_uri[0];
	$v_document_uri3 = $v_document_uri2 . "/";
	if ( substr( $v_document_uri2, -1 ) == "/" ) {
		$v_document_uri3 = substr( $v_document_uri2, 0, -1 );
	}
}

$v_source_url = PROTOCOL . '://' . REFERER_MAIN;

if ( URI_STRICT ) {
	$v_query = "
		SELECT URI, Description from " . $v_table_prefix . "documents
		WHERE URI='" . $o_mysql_connection->real_escape_string($v_document_uri1) . "'
	";
} else {
	$v_query = "
		SELECT URI, Description from " . $v_table_prefix . "documents
		WHERE URI IN (
			'" . $o_mysql_connection->real_escape_string($v_document_uri1) . "',
			'" . $o_mysql_connection->real_escape_string($v_document_uri2) . "',
			'" . $o_mysql_connection->real_escape_string($v_document_uri3) . "',
			'" . $o_mysql_connection->real_escape_string($v_document_uri4) . "'
		)
		ORDER BY URI
		LIMIT 1
	";
}
$o_results = fn_query_check( "documents->URI: " . $v_document_uri1, $v_query, false );
// If that URL doesn't exist, take us to the main page.
if ( $o_results->num_rows == 0 ) {
	header( "Location: " . $v_main_page );
	fn_close();
}
$a_document = $o_results->fetch_assoc();
$v_document_uri = $a_row['URI'];
// If the URI we've been given doesn't match what was returned, change the browser location
if ( $v_document_uri != $v_document_uri1 ) {
	http_response_code(301);
	$v_dir_path = preg_replace( '/\/+/', '/', BASE_URI . $v_document_uri );
	header("Location: " . $v_prot . "://" . $_SERVER['HTTP_HOST'] . $v_dir_path );
}
$v_document_text = $a_document['Description'];
$a_document_list = preg_split( "/(\r)?\n/", $v_document_text );

$out_head = "<html>\n<head>\n";
$out_body = '';
$out_error = "<!--\n";

list( $v_body, $v_error, $v_head, $v_iframe ) = fn_parse_descriptions( $a_document_list, 'document' );

// I probably don't REALLY need to add the javascript here, but... <shrugs>
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
