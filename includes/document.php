<?php

$v_document_uri = preg_replace( '/^' . preg_quote( BASE_URI, "/" ) . '/', '/', $_SERVER['REQUEST_URI'] );
$v_source_url = PROTOCOL . '://' . REFERER_MAIN;

$v_query = "
	SELECT Description from " . $v_table_prefix . "documents
	WHERE URI='" . $o_mysql_connection->real_escape_string($v_document_uri) . "'
";
$o_results = fn_query_check( "documents->URI: " . $v_document_uri, $v_query, false );
// If that URL doesn't exist, take us to the main page.
if ( $o_results->num_rows == 0 ) {
	header( "Location: " . $v_main_page );
	fn_close();
}
$a_document = $o_results->fetch_assoc();
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
