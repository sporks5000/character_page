<?php

// determine the main page for the site (not the source)
$v_prot = 'http';
if ( isset( $_SERVER['HTTPS'] ) ) {
	$v_prot .= 's';
}
$v_main_page = $v_prot . '://' . $_SERVER['HTTP_HOST'] . BASE_URI;

// Determine the page URI
$b_do_single = false;
$b_do_document = false;
$b_do_list = false;
$v_source_uri = '';
if ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . 'single\//', $_SERVER['REQUEST_URI'] ) ) {
	$b_do_single = true;
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( LIST_DIR, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	$b_do_list = true;
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	// if the URI is constructed to match the URI of the source site
	$v_source_uri = preg_replace( '/' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '/', '', $_SERVER['REQUEST_URI'] );
	$v_source_uri = preg_replace( '/(\?|&)show_parse=[12]/', '', $v_source_uri );
} elseif ( preg_match( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', $_SERVER['HTTP_REFERER'] ) && preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '/', $_SERVER['REQUEST_URI'] ) ) {
	// If the referrer is the source, and the posts directory is target, and grab the URI of the referrer
	$v_source_uri = preg_replace( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', '', $_SERVER['HTTP_REFERER'] );
	header("Location: " . $v_main_page . PAGE_DIR . $v_source_uri );
	exit();
} else {
	$b_do_document = true;
}

// allows you to see what the parser is parsing
$v_show_parse_level = 0;
if ( $_GET['show_parse'] == 1 ) {
	$v_show_parse_level = 1;
} elseif ( ! empty( $_GET['show_parse'] ) ) {
	$v_show_parse_level = 2;
}

require( $v_incdir . '/parse.php' );
require( $v_incdir . '/connect.php' );

if ( $b_do_single ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', LIST_DIR ) ) - 1 ) ); ##### This needs to be revised
	require( $v_incdir . '/single.php' );
} elseif ( $b_do_document ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', $_SERVER['REQUEST_URI'] ) ) - count( explode( '/', BASE_URI ) ) ) ); ##### Is this right?
	$v_source_url = PROTOCOL . '://' . REFERER_MAIN;
	require( $v_incdir . '/document.php' );
} elseif ( $b_do_list ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', LIST_DIR ) ) ) );
	require( $v_incdir . '/list.php' );
} else {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', PAGE_DIR ) ) - 1 ) );
	$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_source_uri;
	require( $v_incdir . '/full.php' );
}
