<?php

$out_error .= $v_errors . "<!--\n";
$v_full_style = $o_content['style'];

// pull the full style
$v_query = "
	SELECT Description from " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles
	WHERE Type = 'full'
	AND Name = '" . $o_mysql_connection->real_escape_string($v_full_style) . "'
	AND Page<='" . $o_mysql_connection->real_escape_string($v_page) . "'
	ORDER BY Page DESC
	LIMIT 1
";
$o_results = $o_mysql_connection->query( $v_query );
if ( $o_results->num_rows == 0 ) {
	// #####
	echo "No such full page style. I have to figure out something better to do for this...";
	exit;
}
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

exit;
