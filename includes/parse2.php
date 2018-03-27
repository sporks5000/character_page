<?php

function fn_parse_import ( $a_import_text ) {
	global $o_mysql_connection;
	static $v_error = '';
	static $v_success = '';
	static $v_delete = '';
	$c_lines = 0;
	while ( $c_lines < count( $a_import_text ) ) {
		$v_line = $a_import_text[$c_lines];
		$c_lines++;
		if ( preg_match( '/^\s*>>>>>\s+declare\s+([^\s]+)\s+(.*)$/', $v_line, $a_match ) ) {
			$a_arguments = preg_split( "/\s+/", $a_match[2] );
			if ( $a_match[1] == 'name' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("
					INSERT INTO " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names (URI, Page) 
					VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "')
					ON DUPLICATE KEY UPDATE Page='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				");
				$v_success .= "<li>name: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'content' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$v_con_name = '';
				if ( $a_arguments[1] == "page" ) {
					$v_con_name = 'cp_page_con';
				} elseif ( $a_arguments[1] == "list" ) {
					$v_con_name = 'cp_list_con';
				} elseif ( $a_arguments[1] == "block" && isset( $a_arguments[2] ) ) {
					if ( $a_arguments[2] == 'cp_page_con' || $a_arguments[2] == 'cp_list_con' ) {
						$v_error .= "<li>line " . ( $c_lines - 1 ) . " (exempt content name): " . $v_line . "</li>\n";
						continue;
					}
					$v_con_name = $a_arguments[2];
				} else {
					$v_error .= "<li>line " . ( $c_lines - 1 ) . " (no content name): " . $v_line . "</li>\n";
					continue;
				}
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$o_results = $o_mysql_connection->query("
					INSERT INTO " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "contents (Page, Content, Type, Name)
					VALUES (
						'" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "',
						'" . $o_mysql_connection->real_escape_string($v_out) . "',
						'" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "',
						'" . $o_mysql_connection->real_escape_string($v_con_name) . "'
					)
					ON DUPLICATE KEY UPDATE Content='" . $o_mysql_connection->real_escape_string($v_out) . "'
				");
				$v_success .= "<li>content: " . $a_arguments[0] . " | " . $v_con_name . "</li>\n";
			} elseif ( $a_match[1] == 'style' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && isset( $a_arguments[2] )  ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$o_results = $o_mysql_connection->query("
					INSERT INTO " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "styles (Type, Name, Page, Description)
					VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "')
					ON DUPLICATE KEY UPDATE Type='" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "', Description='" . $o_mysql_connection->real_escape_string($v_out) . "'
				");
				$v_success .= "<li>style: " . $a_arguments[0] . " | " . $a_arguments[1] . " | " . $a_arguments[2] . "</li>\n";
			} elseif ( $a_match[1] == 'item' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$v_previous_page = "NULL";
				$v_next_page = "NULL";

				// determine if there's a previous version of this item, get the page number, and update it with this page number
				$o_results = $o_mysql_connection->query("
					SELECT Page
					FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
					WHERE Page < '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
					AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					ORDER BY Page DESC
					LIMIT 1
				");
				if ( $o_results->num_rows > 0 ) {
					$a_row = $o_results->fetch_assoc();
					$v_previous_page = "'" . $o_mysql_connection->real_escape_string( $a_row['Page'] ) . "'";
					$o_results = $o_mysql_connection->query("
						UPDATE " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
						SET Next = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
						WHERE Page < '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
						AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
						ORDER BY Page DESC
						LIMIT 1
					");
				}

				// determine if there's a next version of this item, get the page number, and update it with this page number
				$o_results = $o_mysql_connection->query("
					SELECT Page
					FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
					WHERE Page > '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
					AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					ORDER BY Page ASC
					LIMIT 1
				");
				if ( $o_results->num_rows > 0 ) {
					$a_row = $o_results->fetch_assoc();
					$v_next_page = "'" . $o_mysql_connection->real_escape_string( $a_row['Page'] ) . "'";
					$o_results = $o_mysql_connection->query("
						UPDATE " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
						SET Previous = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
						WHERE Page > '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
						AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
						ORDER BY Page ASC
						LIMIT 1
					");
				}

				// Update or insert the actual row.
				$v_query = "
					INSERT INTO " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items (Name, Page, Description, Next, Previous)
					VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "'," . $v_next_page . "," . $v_previous_page . ")
					ON DUPLICATE KEY UPDATE Description = '" . $o_mysql_connection->real_escape_string($v_out) . "',
					Next = " . $v_next_page . ",
					Previous = " . $v_previous_page . "
				";
				$o_results = $o_mysql_connection->query( $v_query );
				$v_success .= "<li>item: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'type' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("
					INSERT INTO " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types (Name, Type)
					VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "')
					ON DUPLICATE KEY UPDATE Type='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				");
				$v_success .= "<li>type: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'document' && isset( $a_arguments[0] ) ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$o_results = $o_mysql_connection->query("
					INSERT INTO " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "documents (URI, Description)
					VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "')
					ON DUPLICATE KEY UPDATE Description='" . $o_mysql_connection->real_escape_string($v_out) . "'
				");
				$v_success .= "<li>document: " . $a_arguments[0] . "</li>\n";
			} else {
				$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . $v_line . "</li>\n";
			}
			if ( $o_mysql_connection->errno ) {
				echo "Failed import data: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
				exit;
			}

		} elseif ( preg_match( '/^\s*>>>>>\s+delete\s+([^\s]+)\s+(.*)$/', $v_line, $a_match ) ) {
			$a_arguments = preg_split( "/\s+/", $a_match[2] );
			if ( $a_match[1] == 'name' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("
					DELETE FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "names
					WHERE URL='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
				");
				$v_delete .= "<li>name: " . $a_arguments[0] . "</li>\n";
			} elseif ( $a_match[1] == 'content' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("
					DELETE FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "contents
					WHERE Page='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Name='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				");
				$v_delete .= "<li>content: " . $a_arguments[0] . "</li>\n";

			} elseif ( $a_match[1] == 'style' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && isset( $a_arguments[2] )  ) {
				$o_results = $o_mysql_connection->query("
					DELETE FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "sytles
					WHERE Name='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Page='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				");
				$v_delete .= "<li>style: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'item' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("
					SELECT Next, Previous
					FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
					WHERE Name='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Page='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				");
				if ( $o_results->num_rows > 0 ) {
					$a_row = $o_results->fetch_assoc();
					$v_next_page = $a_row['Next'];
					$v_previous_page = $a_row['Previous'];
					if ( ! is_null( $v_next_page ) ) {
						$v_previous_page_mod = "NULL";
						if ( ! is_null( $v_previous_page ) ) {
							$v_previous_page_mod = "'" . $o_mysql_connection->real_escape_string( $v_previous_page ) . "'";
						}
						$o_results = $o_mysql_connection->query("
							UPDATE " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
							SET Previous = " . $v_previous_page_mod . "
							WHERE Page = '" . $o_mysql_connection->real_escape_string($v_next_page) . "'
							AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
						");
					}
					if ( ! is_null( $v_previous_page ) ) {
						$v_next_page_mod = "NULL";
						if ( ! is_null( $v_next_page ) ) {
							$v_next_page_mod = "'" . $o_mysql_connection->real_escape_string( $v_next_page ) . "'";
						}
						$o_results = $o_mysql_connection->query("
							UPDATE " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
							SET Next = " . $v_next_page_mod . "
							WHERE Page = '" . $o_mysql_connection->real_escape_string($v_previous_page) . "'
							AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
						");
					}
					$o_results = $o_mysql_connection->query("
						DELETE FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "items
						WHERE Name='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
						AND Page='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
					");
				}
				$v_delete .= "<li>item: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'type' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$o_results = $o_mysql_connection->query("
					DELETE FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "types
					WHERE Name='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Type='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				");
				$v_delete .= "<li>type: " . $a_arguments[0] . "</li>\n";
			} elseif ( $a_match[1] == 'document' && isset( $a_arguments[0] ) ) {
				$o_results = $o_mysql_connection->query("
					DELETE FROM " . $o_mysql_connection->real_escape_string(TABLE_PREFIX) . "documents
					WHERE URI='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
				");
				$v_delete .= "<li>document: " . $a_arguments[0] . "</li>\n";
			} else {
				$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . $v_line . "</li>\n";
			}
			if ( $o_mysql_connection->errno ) {
				echo "Failed import data: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
				exit;
			}

		} elseif ( $v_line != "" ) {
			$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . $v_line . "</li>\n";
		}
	}
	if ( $v_success ) {
		$v_success = "<ul>\n" . $v_success . "</ul>\n";
	}
	if ( $v_delete ) {
		$v_delete = "<ul>\n" . $v_delete . "</ul>\n";
	}
	if ( $v_error ) {
		$v_error = "<ul>\n" . $v_error . "</ul>\n";
	}
	return array( $v_error, $v_success, $v_delete );
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
