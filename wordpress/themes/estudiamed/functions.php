<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'estudiamed', get_stylesheet_uri(), array(), '1.5.0' );
	wp_enqueue_script( 'estudiamed-navigation', get_template_directory_uri() . '/assets/navigation.js', array(), '1.5.0', true );
	if ( ! is_front_page() ) {
		return;
	}
	$assets = get_template_directory_uri() . '/assets/';
	wp_enqueue_script( 'estudiamed-catalog', $assets . 'catalog.js', array(), '1.4.0', true );
	wp_enqueue_script( 'estudiamed-catalog-es', $assets . 'catalog-es.js', array( 'estudiamed-catalog' ), '1.4.0', true );
	wp_enqueue_script( 'estudiamed-study', $assets . 'study.js', array( 'estudiamed-catalog-es' ), '1.5.0', true );
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

add_filter( 'pre_get_document_title', function ( $title ) {
	return is_front_page() ? 'EstudiaMed | Educación médica y preparación de exámenes' : $title;
} );

add_action( 'wp_head', function () {
	if ( is_front_page() ) {
		echo '<meta name="description" content="Estudia medicina por especialidad y practica con simulacros para EHEP Ecuador y Revalida Brasil.">' . "\n";
		if ( is_home() && 0 === absint( get_query_var( 'paged' ) ) ) {
			echo '<link rel="canonical" href="' . esc_url( home_url( '/' ) ) . '">' . "\n";
		}
	}
} );
