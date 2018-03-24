<?php

function fn_parse_import ( $a_import_text ) {
	global $o_mysql_connection;
	static $v_error = '';
	static $v_success = '';
	$c_lines = 0;
	while ( $c_lines < count( $a_import_text ) ) {
		$v_line = $a_import_text[$c_lines];
		$c_lines++;
		if ( preg_match( '/^\s*>>>>>\s+declare\s+([^\s]+)\s+(.*)$/', $v_line, $a_match ) ) {
			$a_arguments = preg_split( "/\s+/", $a_match[2] );
			if ( $a_match[1] == 'name' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("INSERT INTO " . TABLE_PREFIX . "names (URI, Page) VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "') ON DUPLICATE KEY UPDATE Page='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'");
				$v_success .= "<li>name: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'content' && isset( $a_arguments[0] ) ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$o_results = $o_mysql_connection->query("INSERT INTO " . TABLE_PREFIX . "contents (Page, Content) VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "') ON DUPLICATE KEY UPDATE Content='" . $o_mysql_connection->real_escape_string($v_out) . "'");
				$v_success .= "<li>content: " . $a_arguments[0] . "</li>\n";
			} elseif ( $a_match[1] == 'style' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && isset( $a_arguments[2] )  ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$o_results = $o_mysql_connection->query("INSERT INTO " . TABLE_PREFIX . "styles (Type, Name, Page, Description) VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "') ON DUPLICATE KEY UPDATE Type='" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "', Description='" . $o_mysql_connection->real_escape_string($v_out) . "'");
				$v_success .= "<li>style: " . $a_arguments[0] . " | " . $a_arguments[1] . " | " . $a_arguments[2] . "</li>\n";
			} elseif ( $a_match[1] == 'item' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$o_results = $o_mysql_connection->query("INSERT INTO " . TABLE_PREFIX . "items (Name, Page, Description) VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "') ON DUPLICATE KEY UPDATE Description='" . $o_mysql_connection->real_escape_string($v_out) . "'");
				$v_success .= "<li>item: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'type' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("INSERT INTO " . TABLE_PREFIX . "types (Name, Type) VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "') ON DUPLICATE KEY UPDATE Type='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'");
				$v_success .= "<li>type: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} else {
				$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . $v_line . "</li>\n";
			}
			if ( $o_mysql_connection->errno ) {
				echo "Failed import data: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
				exit;
			}
		} else {
			$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . $v_line . "</li>\n";
		}
	}
	if ( $v_success ) {
		$v_success = "<ul>\n" . $v_success . "</ul>\n";
	}
	if ( $v_error ) {
		$v_error = "<ul>\n" . $v_error . "</ul>\n";
	}
	return array( $v_error, $v_success );
}

function fn_extract_lines2( $a_lines, $c_lines ) {
	$v_out = '';
	while ( $c_lines < count( $a_lines ) ) {
		$v_line = $a_lines[$c_lines];
		$c_lines++;
		$b_break = false;
		if ( preg_match( '/\s*>>>>>\s+/', $v_line ) ) {
			$c_lines --;
			break;
		} else {
			$v_out .= $v_line . "\n";
		}
	}
	// trim off any empty lines from the beginning or end
	while ( substr($v_out, -1) == "\n" ) {
		$v_out = substr( $v_out, 0, -1 );
	}
	while ( substr($v_out, 0, -1) == "\n" ) {
		$v_out = substr( $v_out, 1 );
	}
	return array( $v_out, $c_lines );
}
