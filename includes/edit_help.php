<?php

$v_incdir = '.';
require( $v_incdir . '/session.php' );

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_SESSION['user'] ) && isset( $_SESSION['pass'] ) ) {
	$v_db_alt_user = $_SESSION['user'];
	$v_db_alt_password = $_SESSION['pass'];

	require( $v_incdir . '/connect.php' );
	require( $v_incdir . '/edit_functions.php' );

	$v_out = '<div><textarea name="text" cols="60" rows="20">';
	$a_arguments = explode( " ", $_POST['values'] );
	if ( $_POST['type'] == "name" ) {
		$v_query = "
			SELECT URI, ID from " . $v_table_prefix . "names
			WHERE URI = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			ORDER BY ID ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "names->URL: " . $a_arguments[0], $v_query, true );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare name " . $a_row['URI'] . " " . fn_minimize_ID($a_row['ID']) . '</textarea><br />';
		}
	} elseif ( $_POST['type'] == "content" ) {
		$v_query = "
			SELECT ID, Content, Type, Name from " . $v_table_prefix . "contents
			WHERE ID = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND Type = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			AND Name = '" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "'
			ORDER BY ID ASC
			LIMIT 1

		";
		$o_results = fn_query_check( "contents->ID: " . $a_arguments[0] . "," . $a_arguments[1] . "," . $a_arguments[2], $v_query, true );
		while ( $a_row = $o_results->fetch_assoc() ) {
			if ( $a_row['Type'] == "block" || $a_row['Type'] == "list" ) {
				$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'] . "\n" . $a_row['Content'];
			} else {
				$v_out .= ">>>>> declare content " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Content'];
			}
			$v_out .= '</textarea><br />';
		}
	} elseif ( $_POST['type'] == "item" ) {
		$v_query = "
			SELECT Name, ID, Description from " . $v_table_prefix . "items
			WHERE Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND ID = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			ORDER BY ID ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "items->Name: " . $a_arguments[0] . "," . $a_arguments[1], $v_query, true );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare item " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . "\n" . $a_row['Description'] . '</textarea><br />';
		}
	} elseif ( $_POST['type'] == "style" ) {
		$v_query = "
			SELECT Type, Name, ID, Description from " . $v_table_prefix . "styles
			WHERE Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND ID = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			ORDER BY ID ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "styles->Name: " . $a_arguments[0] . "," . $a_arguments[1], $v_query, true );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare style " . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . "\n" . $a_row['Description'] . '</textarea><br />';
		}
	} elseif ( $_POST['type'] == "category" ) {
		$v_query = "
			SELECT Name, Category, Start, End from " . $v_table_prefix . "categories
			WHERE Name = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			AND Category = '" . $o_mysql_connection->real_escape_string($a_arguments[1]) . "'
			AND Start = '" . $o_mysql_connection->real_escape_string($a_arguments[2]) . "'
			ORDER BY Category ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "categories->Name: " . $a_arguments[0] . "," . $a_arguments[1] . "," . $a_arguments[2], $v_query, true );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare category " . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_category_id($a_row['Start'], $a_row['End']) . '</textarea><br />';
		}
	} elseif ( $_POST['type'] == "document" ) {
		$v_query = "
			SELECT URI, Description from " . $v_table_prefix . "documents
			WHERE URI = '" . $o_mysql_connection->real_escape_string($a_arguments[0]) . "'
			ORDER BY URI ASC
			LIMIT 1
		";
		$o_results = fn_query_check( "documents->URI: " . $a_arguments[0], $v_query, true );
		while ( $a_row = $o_results->fetch_assoc() ) {
			$v_out .= ">>>>> declare document " . $a_row['URI'] . "\n" . $a_row['Description'] . '</textarea><br />';
		}
	} elseif ( $_POST['type'] == "new" ) {
		$v_out .= ">>>>> declare " . '</textarea><br />';
	}
	$v_out .= '<input type="submit" value="Cancel" onclick="fn_submit(this)">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Submit" onclick="fn_submit(this)"></div>';
	echo $v_out;
	fn_close();
} else {
	echo "<div>no content</div>";
}
