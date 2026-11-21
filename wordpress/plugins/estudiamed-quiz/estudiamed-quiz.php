<?php
/**
 * Plugin Name: EstudiaMed Quiz General
 * Description: Quiz bilingüe con banco local y generación opcional.
 * Version: 1.0.0
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/provider.php';

function estudia_quiz_catalog( $lang ) {
	return json_decode( (string) file_get_contents( __DIR__ . '/catalog.json' ), true )[ $lang ] ?? array();
}

function estudia_quiz_item( $item ) {
	if ( ! is_array( $item ) || ! is_string( $item['stem'] ?? null ) || ! is_array( $item['options'] ?? null ) ||
		count( $item['options'] ) !== 4 || ! is_int( $item['key'] ?? null ) || $item['key'] < 0 || $item['key'] > 3 ||
		! is_string( $item['explanation'] ?? null ) ) {
		return null;
	}
	$options = array();
	foreach ( $item['options'] as $option ) {
		if ( ! is_string( $option ) || '' === trim( $option ) ) {
			return null;
		}
		$options[] = sanitize_text_field( $option );
	}
	$stem = sanitize_textarea_field( $item['stem'] );
	if ( ! $stem || count( array_unique( $options ) ) !== 4 || ! sanitize_textarea_field( $item['explanation'] ) ) {
		return null;
	}
	return array( 'stem' => $stem, 'options' => $options, 'key' => $item['key'], 'explanation' => sanitize_textarea_field( $item['explanation'] ) );
}

function estudia_quiz_prepare( $request ) {
	$lang = $request['lang'];
	$category = $request['category'];
	$subject = $request['subject'];
	$type = $request['type'];
	$count = $request['count'];
	if ( ! in_array( $lang, array( 'es', 'pt' ), true ) || ! is_string( $category ) || ! is_string( $subject ) ||
		! in_array( $type, array( 'mcq', 'case' ), true ) || ! is_int( $count ) || $count < 1 || $count > 10 ||
		! in_array( $subject, estudia_quiz_catalog( $lang )[ $category ] ?? array(), true ) ) {
		return new WP_Error( 'estudia_quiz_input', 'Revisa la configuración del quiz.', array( 'status' => 400 ) );
	}
	$bank = get_option( 'estudia_quiz_bank', array() );
	$matches = array_values( array_filter( $bank, static function ( $item ) use ( $lang, $category, $subject, $type ) {
		return $item['lang'] === $lang && $item['category'] === $category && $item['subject'] === $subject && $item['type'] === $type;
	} ) );
	shuffle( $matches );
	$items = array_slice( $matches, 0, $count );
	if ( count( $items ) < $count && true === $request['allow_ai'] ) {
		$generated = estudia_quiz_generate( $lang, $category, $subject, $type, $count - count( $items ) );
		if ( is_wp_error( $generated ) && ! $items ) {
			return $generated;
		}
		if ( ! is_wp_error( $generated ) ) {
			$items = array_merge( $items, $generated );
		}
	}
	return rest_ensure_response( array( 'items' => $items ) );
}

add_action( 'rest_api_init', static function () {
	register_rest_route( 'estudiamed/v1', '/quiz/general', array(
		'methods' => 'POST', 'callback' => 'estudia_quiz_prepare', 'permission_callback' => 'is_user_logged_in',
	) );
} );

add_shortcode( 'estudiamed_quiz', static function () {
	$lang = function_exists( 'estudiamed_language' ) && 'pt-BR' === estudiamed_language() ? 'pt' : 'es';
	if ( ! is_user_logged_in() ) {
		$url = wp_login_url( ( function_exists( 'estudiamed_home' ) ? estudiamed_home() : ( get_permalink() ?: home_url( '/' ) ) ) . '#quiz-general' );
		return '<p><a class="btn" href="' . esc_url( $url ) . '">' . ( 'pt' === $lang ? 'Entrar para praticar' : 'Iniciar sesión para practicar' ) . '</a></p>';
	}
	wp_enqueue_script( 'estudia-general', plugins_url( 'assets/quiz.js', __FILE__ ), array(), '1.0.0', true );
	$settings = get_option( 'estudia_quiz_ai', array() );
	return '<div class="panel" data-estudia-quiz data-lang="' . esc_attr( $lang ) . '" data-endpoint="' . esc_url( rest_url( 'estudiamed/v1/quiz/general' ) ) .
		'" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '" data-ai="' . ( empty( $settings['provider'] ) ? '0' : '1' ) .
		'" data-catalog="' . esc_attr( wp_json_encode( estudia_quiz_catalog( $lang ) ) ) . '"></div><noscript>JavaScript requerido.</noscript>';
} );

require_once __DIR__ . '/admin.php';
