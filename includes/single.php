<?php

$v_current = $_GET['current'];
$v_page = $_GET['page'];
$v_style = $_GET['style'];
$v_item = preg_replace( '/^' . preg_quote( BASE_URI, '/' ) . 'single\//', '', explode( '?', $_SERVER['REQUEST_URI'] )[0] );

// pull the single style
$o_results = $o_mysql_connection->query("
	SELECT Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles
	WHERE Type = 'single'
	AND Name = '" . $o_mysql_connection->real_escape_string($v_style) . "'
	AND Page<='" . $o_mysql_connection->real_escape_string($v_current) . "'
	ORDER BY Page DESC
	LIMIT 1
");
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such single page style. I have to figure out something better to do for this...";
	exit;
}
$a_single_style = $o_results->fetch_assoc();
$v_single_style_text = $a_single_style['Description'];
$a_single_style_list = preg_split( "/(\r)?\n/", $v_single_style_text );

// Pull the URI for the source page and create a source URL
$o_results = $o_mysql_connection->query("
	SELECT URI from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names
	WHERE Page='" . $o_mysql_connection->real_escape_string($v_page) . "'
	ORDER BY Page DESC
	LIMIT 1
");
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such URI relevant to that page number. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_uri = $a_row['URI'];
$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_uri;

// Create the variables that will store the head, the body, and any errors
$out_head = "<html>\n<head>\n";
$out_body = '';
$out_error = "<!--\n";

list( $v_body, $v_error, $v_head, $v_iframe ) = fn_parse_descriptions( $a_single_style_list, 'single' );
// ##### This is where I need to add the fancy bits for the iframe

$out_body .= $v_body . "</body>\n</html>";
$out_error .= $v_error . "-->\n";
$out_head .= $v_head . "</head>\n";

echo $out_head . $out_error . $out_body;

exit;
