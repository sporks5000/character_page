<?php

// Using the URI, get the page number
$o_results = $o_mysql_connection->query("SELECT Page from " . TABLE_PREFIX . "names where URI='" . $v_source_uri . "'");
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such page. I have to figure out something better to do for this...";
	exit;
}
$a_row = $o_results->fetch_assoc();
$v_page = $a_row['Page'];

// Using the page number, find out what content we need to pull
$o_results = $o_mysql_connection->query("SELECT Content from " . TABLE_PREFIX . "contents where Page<='" . $v_page . "' ORDER BY Page DESC LIMIT 1");
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
$out_error = "<!--\n";

// split the contents by line and parse them into an object
$a_content_list = preg_split( "/(\r)?\n/", $v_content );
list( $v_full_style, $o_content, $v_errors ) = fn_parse_content ($a_content_list);
$out_error .= $v_errors;

// pull the full style
$o_results = $o_mysql_connection->query("SELECT Description from " . TABLE_PREFIX . "styles where Type = 'full' AND Name = '" . $v_full_style . "' AND Page<='" . $v_page . "' ORDER BY Page DESC LIMIT 1");
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such full page style. I have to figure out something better to do for this...";
	exit;
}
$a_full_style = $o_results->fetch_assoc();
$v_full_style_text = $a_full_style['Description'];
$a_full_style_list = preg_split( "/(\r)?\n/", $v_full_style_text );

list( $v_body, $v_error, $v_head, $v_iframe ) = fn_parse_descriptions( $a_full_style_list, 'full' );
$js_top = file_get_contents( dirname( __FILE__ ) . '/js_top.txt' );
while ( substr($js_top, -1) != '"' ) {
	$js_top = substr( $js_top, 0, -1 );
}
$out_head .= $js_top . $v_iframe . file_get_contents( dirname( __FILE__ ) . '/js_bottom.txt' );

$out_body .= $v_body . "</body>\n</html>";
$out_error .= $v_error . "-->\n";
$out_head .= $v_head . "</head>\n";

echo $out_head . $out_error . $out_body;

exit;
