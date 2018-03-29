<?php

$v_document_uri = preg_replace( '/^' . preg_quote( BASE_URI, "/" ) . '/', '/', $_SERVER['REQUEST_URI'] );

$v_query = "
	SELECT Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "documents
	WHERE URI='" . $o_mysql_connection->real_escape_string($v_document_uri) . "'
";
$o_results = $o_mysql_connection->query( $v_query );
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such document. I have to figure out something better to do for this...";
	exit;
}
$a_document = $o_results->fetch_assoc();
$v_document_text = $a_document['Description'];
$a_document_list = preg_split( "/(\r)?\n/", $v_document_text );

$out_head = "<html>\n<head>\n";
$out_body = '';
$out_error = "<!--\n";

list( $v_body, $v_error, $v_head, $v_iframe ) = fn_parse_descriptions( $a_document_list, 'document' );

##### Do I REALLY need to add the javascript here?
$js_top = file_get_contents( $v_incdir . '/js_top_full.txt' );
while ( substr($js_top, -1) != '"' ) {
	$js_top = substr( $js_top, 0, -1 );
}
$out_head .= $js_top . $v_iframe . file_get_contents( $v_incdir . '/js_bottom_full.txt' );

$out_body .= $v_body . "</body>\n</html>";
$out_error .= $v_error . "-->\n";
$out_head .= $v_head . "</head>\n";

echo $out_head . $out_error . $out_body;

exit;
