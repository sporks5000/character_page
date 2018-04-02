<?php

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	$v_incdir = '.';

	$v_db_alt_user = $_POST['user'];
	$v_db_alt_password = $_POST['pass'];

	require( $v_incdir . '/config.php' );
	require( $v_incdir . '/connect.php' );
	require( $v_incdir . '/exp_functions.php' );

	$v_out = '<div><textarea name="text" cols="60" rows="20">';
	$a_arguments = explode( "&", $_POST['values'] );
	if ( $_POST['type'] == "names" ) {
		$v_query = "
			SELECT URI, ID from " . $v_table_prefix . "names
			WHERE URI = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			ORDER BY ID ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "\"names\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare name " . $a_row['URI'] . " " . fn_minimize_ID($a_row['ID']);
			$v_out .= '</textarea><br /><input type="hidden" value=">>>>> delete name ' . $a_row['URI'] . '">';
		}
	} elseif ( $_POST['type'] == "contents" ) {
		$v_query = "
			SELECT ID, Content, Type, Name from " . $v_table_prefix . "contents
			WHERE ID = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND Type = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "'
			ORDER BY ID ASC
			LIMIT 1

		";
		$o_results = fn_query_check( "\"contents\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			if ( $a_row['Type'] == "block" ) {
				$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " block " . $a_row['Name'] . "\n" . $a_row['Content'];
			} else {
				$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Content'];
			}
			$v_out .= '</textarea><br /><input type="hidden" value=">>>>> delete content ' . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'] . '">';
		}
	} elseif ( $_POST['type'] == "items" ) {
		$v_query = "
			SELECT Name, ID, Description from " . $v_table_prefix . "items
			WHERE Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND ID = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			ORDER BY ID ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "\"items\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare item " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . "\n" . $a_row['Description'];
			$v_out .= '</textarea><br /><input type="hidden" value=">>>>> delete item ' . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . '">';
		}
	} elseif ( $_POST['type'] == "styles" ) {
		$v_query = "
			SELECT Type, Name, ID, Description from " . $v_table_prefix . "styles
			WHERE Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND ID = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			ORDER BY ID ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "\"styles\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare style " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Description'];
			$v_out .= '</textarea><br /><input type="hidden" value=">>>>> delete style ' . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . '">';
		}
	} elseif ( $_POST['type'] == "categories" ) {
		$v_query = "
			SELECT Name, Category, Start, End from " . $v_table_prefix . "categories
			WHERE Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND Category = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			AND Start = '" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "'
			ORDER BY Category ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "\"categories\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare category " . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_category_id($a_row['Start'], $a_row['End']);
			$v_out .= '</textarea><br /><input type="hidden" value=">>>>> delete category ' . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_minimize_ID($a_row['Start']) . '">';
		}
	} elseif ( $_POST['type'] == "documents" ) {
		$v_query = "
			SELECT URI, Description from " . $v_table_prefix . "documents
			ORDER BY URI ASC
		";
		$o_results = fn_query_check( "\"documents\"", $v_query, false );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare document " . $a_row['URI'] . "\n" . $a_row['Description'];
			$v_out .= '</textarea><br /><input type="hidden" value=">>>>> delete document ' . $a_row['URI'] . '">';
		}
	}
	$v_out .= '<input type="submit" value="Delete" onclick="fn_delete(this)">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Submit" onclick="fn_submit(this)"></div>';
	echo $v_out;
	fn_close();
}
