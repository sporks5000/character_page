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
