<?php
/**
 * PHPUnit Bootstrap
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Mock WordPress functions if needed for unit tests
if ( ! function_exists( 'add_action' ) ) {
	function add_action() {}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return $text; }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return $text; }
}
