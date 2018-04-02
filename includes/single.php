<?php

$v_current = $_GET['current'];
$v_ID = $_GET['id'];
$v_style = $_GET['style'];
$v_item = preg_replace( '/^' . preg_quote( BASE_URI, '/' ) . 'single\//', '', explode( '?', $_SERVER['REQUEST_URI'] )[0] );
$v_relative_path = "../";

// pull the single style
$v_query = "
	SELECT Description from " . $v_table_prefix . "styles
	WHERE Type = 'single'
	AND Name = '" . $o_mysql_connection->real_escape_string($v_style) . "'
	AND ID<='" . $o_mysql_connection->real_escape_string($v_current) . "'
	ORDER BY ID DESC
	LIMIT 1
";
$o_results = fn_query_check( "styles->Name: " . $v_style . ", " . $v_current, $v_query, true );
$a_single_style = $o_results->fetch_assoc();
$v_single_style_text = $a_single_style['Description'];
$a_single_style_list = preg_split( "/(\r)?\n/", $v_single_style_text );

// Pull the URI for the source content and create a source URL
$v_query = "
	SELECT URI from " . $v_table_prefix . "names
	WHERE ID='" . $o_mysql_connection->real_escape_string($v_ID) . "'
	ORDER BY ID DESC
	LIMIT 1
";
$o_results = fn_query_check( "names->ID: " . $v_ID, $v_query, true );
$a_row = $o_results->fetch_assoc();
$v_uri = $a_row['URI'];
$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_uri;

// Create the variables that will store the head, the body, and any errors
$out_head = "<html>\n<head>\n";
$out_body = '';
$out_error = "<!--\n";

list( $v_body, $v_error, $v_head, $v_iframe ) = fn_parse_descriptions( $a_single_style_list, 'single' );

$out_head .= file_get_contents( $v_incdir . '/js_single.txt' );

$out_body .= $v_body . "</body>\n</html>";
$out_error .= $v_error . "-->\n";
$out_head .= $v_head . "</head>\n";

if ( $_GET['only_body'] ) {
	echo $out_body;
} else {
	echo $out_head . $out_error . $out_body;
}

fn_close();
