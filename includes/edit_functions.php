<?php

function fn_minimize_ID ( $v_ID ) {
	if ( preg_match( '/\.00$/', $v_ID ) ) {
		$v_ID = substr( $v_ID, 0, -3 );
	} elseif ( preg_match( '/\..0$/', $v_ID ) ) {
		$v_ID = substr( $v_ID, 0, -1 );
	}
	return $v_ID;
}

function fn_category_id ( $v_start, $v_end ) {
	if ( $v_start == "0" && $v_end == "999999.99" ) {
		return "";
	} elseif ( $v_end == "999999.99" ) {
		return fn_minimize_ID( $v_start );
	} elseif ( $v_start == "0" ) {
		return "_ " . fn_minimize_ID( $v_end );
	} else {
		return fn_minimize_ID( $v_start ) . " " . fn_minimize_ID( $v_end );
	}
}

function fn_make_links ( $v_type, $a_row ) {
	$v_data = '';
	$v_disp = '';
	if ( $v_type == "name" ) {
		$v_data = $a_row['URI'];
		$v_disp = $v_type . ': ' . $a_row['URI'] . " " . fn_minimize_ID($a_row['ID']);
	} elseif ( $v_type == "content" ) {
		$v_data = fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'];
		if ( $a_row['Type'] == "block" || $a_row['Type'] == "list" ) {
			$v_disp = $v_type . ': ' . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'] . " " . $a_row['Name'];
		} else {
			$v_disp = $v_type . ': ' . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'];
		}
	} elseif ( $v_type == "item" ) {
		$v_data = $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']);
		$v_disp = $v_type . ': ' . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']);
	} elseif ( $v_type == "style" ) {
		$v_data = $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'];
		$v_disp = $v_type . ': ' . $a_row['Name'] . " " . fn_minimize_ID($a_row['ID']) . " " . $a_row['Type'];
	} elseif ( $v_type == "category" ) {
		$v_data = $a_row['Name'] . " " . $a_row['Category'] . " " . fn_minimize_ID($a_row['Start']);
		$v_disp = $v_type . ': ' . $a_row['Name'] . " " . $a_row['Category'] . " " . fn_category_id($a_row['Start'], $a_row['End']);
	} elseif ( $v_type == "document" ) {
		$v_data = $a_row['URI'];
		$v_disp = $v_type . ': ' . $a_row['URI'];
	}
	return '<li>' . $v_disp . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-size:smaller;">[<a href="' . $v_type . '" cp_data="' . $v_data . '" onclick="fn_edit_object(this);return false;">EDIT</a>]&nbsp;&nbsp;[<a href="' . $v_type . '" cp_data="' . $v_data . '" onclick="fn_delete_object(this);return false;">DELETE</a>]</span></li>';
}





