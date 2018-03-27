<?php

// determine the main page for the site (not the source)
$v_prot = 'http';
if ( isset( $_SERVER['HTTPS'] ) ) {
	$v_prot .= 's';
}
$v_main_page = $v_prot . '://' . $_SERVER['HTTP_HOST'] . BASE_URI;

// Determine the page URI
$b_rewrite = false;
$b_do_single = false;
$b_do_document = false;
$v_source_uri = '';
if ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . 'single\//', $_SERVER['REQUEST_URI'] ) ) {
	$b_do_single = true;
} elseif ( ! empty( $_GET['uri'] ) ) {
	// if the URL has "?uri=" appended to it
	$v_source_uri = $_GET['uri'];
	$b_rewrite = true;
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	// if the URI is constructed to match the URI of the source site
	$v_source_uri = preg_replace( '/' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '/', '', $_SERVER['REQUEST_URI'] );
	$v_source_uri = preg_replace( '/(\?|&)show_parse=[12]/', '', $v_source_uri );
} elseif ( preg_match( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', $_SERVER['HTTP_REFERER'] ) && preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '/', $_SERVER['REQUEST_URI'] ) ) {
	// If the referrer is the source, and the posts directory is target, and grab the URI of the referrer
	$v_source_uri = preg_replace( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', '', $_SERVER['HTTP_REFERER'] );
	$b_rewrite = true;
} else {
	$b_do_document = true;
}

// There are a number of URL styles that we want to be able to work, but if possible, we want to redirect to a specific URL style.
if ( $b_rewrite ) {
	// ##### If ever I'm expecting other query variables besides just "uri", I will need to capture them before running this section.
	header("Location: " . $v_main_page . PAGE_DIR . $v_source_uri );
	exit();
}

if ( $b_do_document ) {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', $_SERVER['REQUEST_URI'] ) ) - count( explode( '/', BASE_URI ) ) ) );
	$v_source_url = PROTOCOL . '://' . REFERER_MAIN;
} else {
	$v_relative_path = str_repeat( "../", ( count( explode( '/', PAGE_DIR ) ) - 1 ) );
	$v_source_url = PROTOCOL . '://' . REFERER_BASE . $v_source_uri;
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
	require( $v_incdir . '/single.php' );
} elseif ( $b_do_document ) {
	require( $v_incdir . '/document.php' );
} else {
	require( $v_incdir . '/full.php' );
}
