<?php

function fn_parse_import ( $a_import_text ) {
	global $o_mysql_connection;
	static $v_error = '';
	static $v_success = '';
	static $v_delete = '';
	global $v_table_prefix;
	$c_lines = 0;
	while ( $c_lines < count( $a_import_text ) ) {
		$v_line = $a_import_text[$c_lines];
		$c_lines++;

#==================#
#== Declarations ==#
#==================#

		if ( preg_match( '/^\s*>>>>>\s+declare\s+([^\s]+)\s+(.*)$/', $v_line, $a_match ) ) {
			$a_arguments = preg_split( "/\s+/", $a_match[2] );
			if ( $a_match[1] == 'name' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				// begin a mysql transaction
				$o_mysql_connection->begin_transaction();
				// set the variables for the ID and URI
				$v_query = "SET @v_URI := '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "SET @v_ID := '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'";
				fn_query_check( $v_line, $v_query, false );
				// find the variables for the next and previous page ID's
				$v_query = "
					SET @v_previous_ID := (
						SELECT ID
						FROM " . $v_table_prefix . "names
						WHERE ID < @v_ID
						ORDER BY ID DESC
						LIMIT 1
					)
				";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "
					SET @v_next_ID := (
						SELECT ID
						FROM " . $v_table_prefix . "names
						WHERE ID > @v_ID
						ORDER BY ID ASC
						LIMIT 1
					)
				";
				fn_query_check( $v_line, $v_query, false );
				// update the next and previous row
				$v_query = "
					UPDATE " . $v_table_prefix . "names
					SET Next = @v_ID
					WHERE ID < @v_ID
					ORDER BY ID DESC
					LIMIT 1
				";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "
					UPDATE " . $v_table_prefix . "names
					SET Previous = @v_ID
					WHERE ID > @v_ID
					ORDER BY ID ASC
					LIMIT 1
				";
				fn_query_check( $v_line, $v_query, false );
				// add or update the current row
				$v_query = "
					INSERT INTO " . $v_table_prefix . "names (URI, ID, Next, Previous)
					VALUES (
						@v_URI,
						@v_ID,
						@v_next_ID,
						@v_previous_ID
					)
					ON DUPLICATE KEY UPDATE 
						URI = @v_URI,
						ID = @v_ID,
						Next = @v_next_ID,
						Previous = @v_previous_ID
				";
				fn_query_check( $v_line, $v_query, false );
				$o_mysql_connection->commit();
				// report success
				$v_success .= "<li>name: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'content' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && ( isset( $a_arguments[2] ) || $a_arguments[1] == "page" ) ) {
				$v_con_name = "cp_page_con";
				if ( isset( $a_arguments[2] ) && $a_arguments[1] != "page" ) {
					$v_con_name = $a_arguments[2];
				}
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$v_query = "
					INSERT INTO " . $v_table_prefix . "contents (ID, Content, Type, Name)
					VALUES (
						'" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "',
						'" . $o_mysql_connection->real_escape_string($v_out) . "',
						'" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "',
						'" . $o_mysql_connection->real_escape_string($v_con_name) . "'
					)
					ON DUPLICATE KEY UPDATE Content='" . $o_mysql_connection->real_escape_string($v_out) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_success .= "<li>content: " . $a_arguments[0] . " | " . $a_arguments[1] . " | " . $v_con_name . "</li>\n";
			} elseif ( $a_match[1] == 'style' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && isset( $a_arguments[2] )  ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$v_query = "
					INSERT INTO " . $v_table_prefix . "styles (Type, Name, ID, Description)
					VALUES (
						'" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "',
						'" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "',
						'" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "',
						'" . $o_mysql_connection->real_escape_string($v_out) . "'
					)
					ON DUPLICATE KEY UPDATE
						Type='" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "',
						Description='" . $o_mysql_connection->real_escape_string($v_out) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_success .= "<li>style: " . $a_arguments[0] . " | " . $a_arguments[1] . " | " . $a_arguments[2] . "</li>\n";
			} elseif ( $a_match[1] == 'item' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				// begin a mysql transaction
				$o_mysql_connection->begin_transaction();
				// set the variables for the ID and name
				$v_query = "SET @v_name := '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "SET @v_ID := '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'";
				fn_query_check( $v_line, $v_query, false );
				// find the variables for the next and previous page ID's
				$v_query = "
					SET @v_previous_ID := (
						SELECT ID
						FROM " . $v_table_prefix . "items
						WHERE ID < @v_ID
						AND Name = @v_name
						ORDER BY ID DESC
						LIMIT 1
					)
				";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "
					SET @v_next_ID := (
						SELECT ID
						FROM " . $v_table_prefix . "items
						WHERE ID > @v_ID
						AND Name = @v_name
						ORDER BY ID ASC
						LIMIT 1
					)
				";
				fn_query_check( $v_line, $v_query, false );
				// update the next and previous row
				$v_query = "
					UPDATE " . $v_table_prefix . "items
					SET Next = @v_ID
					WHERE ID < @v_ID
					AND Name = @v_name
					ORDER BY ID DESC
					LIMIT 1
				";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "
					UPDATE " . $v_table_prefix . "items
					SET Previous = @v_ID
					WHERE ID > @v_ID
					AND Name = @v_name
					ORDER BY ID ASC
					LIMIT 1
				";
				fn_query_check( $v_line, $v_query, false );
				// add or update the current row
				$v_query = "
					INSERT INTO " . $v_table_prefix . "items (Name, ID, Description, Next, Previous)
					VALUES (
						@v_name,
						@v_ID,
						'" . $o_mysql_connection->real_escape_string($v_out) . "',
						@v_next_ID,
						@v_previous_ID
					)
					ON DUPLICATE KEY UPDATE
						Description = '" . $o_mysql_connection->real_escape_string($v_out) . "',
						Next = @v_next_ID,
						Previous = @v_previous_ID
				";
				fn_query_check( $v_line, $v_query, false );
				$o_mysql_connection->commit();
				// report success
				$v_success .= "<li>item: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'category' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$v_start = "0";
				$v_end = "999999.99";
				if ( isset( $a_arguments[2] ) && preg_match( '/^[0-9.]+$/', $a_arguments[2] ) ) {
					$v_start = $a_arguments[2];
				}
				if ( isset( $a_arguments[3] ) && preg_match( '/^[0-9.]+$/', $a_arguments[3] ) ) {
					$v_end = $a_arguments[3];
				}
				$v_query = "
					INSERT INTO " . $v_table_prefix . "categories (Name, Category, Start, End)
					VALUES (
						'" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "',
						'" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "',
						'" . $o_mysql_connection->real_escape_string($v_start) . "',
						'" . $o_mysql_connection->real_escape_string($v_end) . "'
					)
					ON DUPLICATE KEY UPDATE
						Start = '" . $o_mysql_connection->real_escape_string($v_start) . "',
						End = '" . $o_mysql_connection->real_escape_string($v_end) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_success .= "<li>category: " . $a_arguments[0] . " | " . $a_arguments[1] . " | " . $v_start . " | " . $v_end . "</li>\n";
			} elseif ( $a_match[1] == 'document' && isset( $a_arguments[0] ) ) {
				list( $v_out, $c_lines ) = fn_extract_lines2( $a_import_text, $c_lines );
				$v_query = "
					INSERT INTO " . $v_table_prefix . "documents (URI, Description)
					VALUES ('" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "','" . $o_mysql_connection->real_escape_string($v_out) . "')
					ON DUPLICATE KEY UPDATE Description='" . $o_mysql_connection->real_escape_string($v_out) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_success .= "<li>document: " . $a_arguments[0] . "</li>\n";
			} else {
				$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . preg_replace( '/</', '&lt;', preg_replace( '/&lt;/', '&amp;lt;', $v_line ) ) . "</li>\n";
			}
			if ( $o_mysql_connection->errno ) {
				echo "Failed import data: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
				fn_close();
			}

#===============#
#== Deletions ==#
#===============#

		} elseif ( preg_match( '/^\s*>>>>>\s+delete\s+([^\s]+)\s+(.*)$/', $v_line, $a_match ) ) {
			$a_arguments = preg_split( "/\s+/", $a_match[2] );
			if ( $a_match[1] == 'name' && isset( $a_arguments[0] ) ) {
				// begin a mysql transaction
				$o_mysql_connection->begin_transaction();
				// set the variable for the URI
				$v_query = "SET @v_URI := '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'";
				fn_query_check( $v_line, $v_query, false );
				// find out the next and previous tables for the row we're deleting
				$v_query = "
					SELECT Next, Previous INTO @v_next_ID, @v_previous_ID
					FROM " . $v_table_prefix . "names
					WHERE URI = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				// update the next and previous rows with eachother's ID numbers
				$v_query = "
					UPDATE " . $v_table_prefix . "names
					SET Previous = @v_previous_ID
					WHERE ID = @v_next_ID
				";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "
					UPDATE " . $v_table_prefix . "names
					SET Next = @v_next_ID
					WHERE ID = @v_previous_ID
				";
				fn_query_check( $v_line, $v_query, false );
				// then remove the actual entry
				$v_query = "
					DELETE FROM " . $v_table_prefix . "names
					WHERE URI = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$o_mysql_connection->commit();
				// report success
				$v_delete .= "<li>name: " . $a_arguments[0] . "</li>\n";
			} elseif ( $a_match[1] == 'content' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && ( isset( $a_arguments[2] ) || $a_arguments[1] == "page" ) ) {
				$v_con_name = "cp_page_con";
				if ( isset( $a_arguments[2] ) && $a_arguments[1] != "page" ) {
					$v_con_name = $a_arguments[2];
				}
				$v_query = "
					DELETE FROM " . $v_table_prefix . "contents
					WHERE ID='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Type='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
					AND Name='" . $o_mysql_connection->real_escape_string($v_con_name) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_delete .= "<li>content: " . $a_arguments[0] . " | " . $a_arguments[1] . " | " . $v_con_name . "</li>\n";
			} elseif ( $a_match[1] == 'style' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) && isset( $a_arguments[2] )  ) {
				$v_query = "
					DELETE FROM " . $v_table_prefix . "sytles
					WHERE Name='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND ID='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_delete .= "<li>style: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'item' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				// begin a mysql transaction
				$o_mysql_connection->begin_transaction();
				// set the variables
				$v_query = "SET @v_name := '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "SET @v_ID := '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'";
				fn_query_check( $v_line, $v_query, false );
				// get the next and previous ID's and set them as variables
				$v_query = "
					SELECT Next, Previous INTO @v_next_ID, @v_previous_ID
					FROM " . $v_table_prefix . "items
					WHERE Name = @v_name
					AND ID = @v_ID
				";
				fn_query_check( $v_line, $v_query, false );
				// update the next and previous rows
				$v_query = "
					UPDATE " . $v_table_prefix . "items
					SET Previous = @v_previous_ID
					WHERE ID = @v_next_ID
					AND Name = @v_name
				";
				fn_query_check( $v_line, $v_query, false );
				$v_query = "
					UPDATE " . $v_table_prefix . "items
					SET Next = @v_next_ID
					WHERE ID = @v_previous_ID
					AND Name = @v_name
				";
				fn_query_check( $v_line, $v_query, false );
				// delete the actual row
				$v_query = "
					DELETE FROM " . $v_table_prefix . "items
					WHERE Name = @v_name
					AND ID = @v_ID
				";
				fn_query_check( $v_line, $v_query, false );
				// close the transaction and report success
				$o_mysql_connection->commit();
				$v_delete .= "<li>item: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'category' && isset( $a_arguments[0] ) && isset( $a_arguments[1] ) ) {
				$v_query = "
					DELETE FROM " . $v_table_prefix . "categories
					WHERE Name='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
					AND Category='" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_delete .= "<li>category: " . $a_arguments[0] . " | " . $a_arguments[1] . "</li>\n";
			} elseif ( $a_match[1] == 'document' && isset( $a_arguments[0] ) ) {
				$v_query = "
					DELETE FROM " . $v_table_prefix . "documents
					WHERE URI='" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
				";
				fn_query_check( $v_line, $v_query, false );
				$v_delete .= "<li>document: " . $a_arguments[0] . "</li>\n";
			} else {
				$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . preg_replace( '/</', '&lt;', preg_replace( '/&lt;/', '&amp;lt;', $v_line ) ) . "</li>\n";
			}
			if ( $o_mysql_connection->errno ) {
				echo "Failed import data: (" . $o_mysql_connection->errno . ") " . $o_mysql_connection->error;
				fn_close();
			}

		} elseif ( $v_line != "" ) {
			$v_error .= "<li>line " . ( $c_lines - 1 ) . ": " . preg_replace( '/</', '&lt;', preg_replace( '/&lt;/', '&amp;lt;', $v_line ) ) . "</li>\n";
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
