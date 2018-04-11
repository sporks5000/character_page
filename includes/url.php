<?php

// Have the databases been initialized
if ( ! file_exists( $v_incdir . "/" . TABLE_PREFIX . 'initialized' ) ) {
	require( $v_incdir . '/initialize.php' );
	exit;
}

// determine the main page for the site (not the source)
$v_prot = 'http';
if ( isset( $_SERVER['HTTPS'] ) ) {
	$v_prot .= 's';
} elseif ( FORCE_HTTPS ) {
	http_response_code(301);
	header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	exit();
}
$v_main_page = $v_prot . '://' . $_SERVER['HTTP_HOST'] . BASE_URI;

// Determine the source URI
$v_type = '';
$v_source_uri = preg_replace( '/(\?|&)show_parse=[12]/', '', $_SERVER['REQUEST_URI'] );
if ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . 'single\//', $_SERVER['REQUEST_URI'] ) ) {
	$v_type = "single";
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( LIST_DIR, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	$v_type = "list";
} elseif ( preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '.+/', $_SERVER['REQUEST_URI'] ) ) {
	// if the URI is constructed to match the URI of the source site
	$v_type = "page";
} elseif ( preg_match( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', $_SERVER['HTTP_REFERER'] ) && preg_match( '/^' . preg_quote( BASE_URI, '/' ) . preg_quote( PAGE_DIR, '/' ) . '/', $_SERVER['REQUEST_URI'] ) ) {
	// If the referrer is the source, and the pages directory is target, and grab the URI of the referrer
	$v_source_uri = preg_replace( '/https?:\/\/' . preg_quote( REFERER_BASE, '/' ) . '/', '', $_SERVER['HTTP_REFERER'] );
	header("Location: " . $v_main_page . PAGE_DIR . $v_source_uri );
	exit();
} else {
	$v_type = "document";
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

if ( $v_type == "single" ) {
	require( $v_incdir . '/single.php' );
} elseif ( $v_type == "document" ) {
	// Don't need to define $v_relative_path here
	require( $v_incdir . '/document.php' );
} elseif ( $v_type == "page" || $v_type == "list" ) {
	require( $v_incdir . '/page-list.php' );
}
