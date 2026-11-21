<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'estudiamed', get_stylesheet_uri(), array(), '1.4.0' );
	if ( ! is_front_page() ) {
		return;
	}
	$assets = get_template_directory_uri() . '/assets/';
	wp_enqueue_script( 'estudiamed-catalog', $assets . 'catalog.js', array(), '1.4.0', true );
	wp_enqueue_script( 'estudiamed-catalog-es', $assets . 'catalog-es.js', array( 'estudiamed-catalog' ), '1.4.0', true );
	wp_enqueue_script( 'estudiamed-study', $assets . 'study.js', array( 'estudiamed-catalog-es' ), '1.4.0', true );
} );

function estudiamed_language() {
	return 'es';
}

function estudiamed_translate( $portuguese, $spanish ) {
	return 'es' === estudiamed_language() ? $spanish : $portuguese;
}

function estudiamed_home( $fragment = '' ) {
	return home_url( '/' ) . $fragment;
}
