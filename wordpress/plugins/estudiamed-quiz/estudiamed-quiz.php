<?php
/**
 * Plugin Name: EstudiaMed Quiz General
 * Description: Quiz bilingüe con banco local y generación opcional.
 * Version: 1.1.0
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

function estudia_quiz_bank() {
	$items = require __DIR__ . '/practice.php';
	foreach ( get_option( 'estudia_quiz_bank', array() ) as $id => $item ) {
		$items[] = $item + array( 'id' => (string) $id, 'topic' => 'Sin tema asignado' );
	}
	return apply_filters( 'estudia_quiz_editorial_items', $items );
}

function estudia_quiz_topics( $lang ) {
	$topics = array();
	foreach ( estudia_quiz_bank() as $item ) {
		if ( $lang === $item['lang'] ) {
			$subject = $item['quiz_subject'] ?? $item['subject'];
			$topics[ $item['category'] ][ $subject ][ $item['type'] ][ $item['topic'] ] = true;
		}
	}
	return $topics;
}

function estudia_quiz_prepare( $request ) {
	$lang = $request['lang'];
	$category = $request['category'];
	$subject = $request['subject'];
	$type = $request['type'];
	$count = $request['count'];
	$topic = $request['topic'] ?? '';
	$favorites = $request['favorites'] ?? null;
	if ( ! in_array( $lang, array( 'es', 'pt' ), true ) || ! is_string( $category ) || ! is_string( $subject ) || ! is_string( $topic ) || strlen( $topic ) > 200 ||
		( null !== $favorites && ( ! is_array( $favorites ) || count( $favorites ) > 300 ) ) ||
		! in_array( $type, array( 'mcq', 'case' ), true ) || ! is_int( $count ) || $count < 1 || $count > 10 ||
		! in_array( $subject, estudia_quiz_catalog( $lang )[ $category ] ?? array(), true ) ) {
		return new WP_Error( 'estudia_quiz_input', 'Revisa la configuración del quiz.', array( 'status' => 400 ) );
	}
	$matches = array_values( array_filter( estudia_quiz_bank(), static function ( $item ) use ( $lang, $category, $subject, $type, $topic, $favorites ) {
		return $item['lang'] === $lang && $item['category'] === $category &&
			( $item['quiz_subject'] ?? $item['subject'] ) === $subject && $item['type'] === $type &&
			( '' === $topic || $item['topic'] === $topic ) &&
			( null === $favorites || in_array( $item['id'], $favorites, true ) );
	} ) );
	shuffle( $matches );
	$items = array_slice( $matches, 0, $count );
	if ( null === $favorites && count( $items ) < $count && true === $request['allow_ai'] ) {
		$generated = estudia_quiz_generate( $lang, $category, $subject, $type, $count - count( $items ), $topic );
		if ( is_wp_error( $generated ) && ! $items ) {
			return $generated;
		}
		if ( ! is_wp_error( $generated ) ) {
			$items = array_merge( $items, $generated );
		}
	}
	if ( 'exam' !== $request['mode'] || ! $items ) {
		return rest_ensure_response( array( 'items' => $items ) );
	}
	$token = wp_generate_uuid4();
	$storage = 'estudia_quiz_' . get_current_user_id() . '_' . $token;
	if ( ! set_transient( $storage, $items, 2 * HOUR_IN_SECONDS ) ) {
		return new WP_Error( 'estudia_quiz_save', 'No se pudo iniciar el simulacro.', array( 'status' => 500 ) );
	}
	$public = array_map( static function ( $item ) {
		unset( $item['key'], $item['explanation'], $item['reasons'] );
		return $item;
	}, $items );
	return rest_ensure_response( array( 'items' => $public, 'token' => $token ) );
}

function estudia_quiz_finish( $request ) {
	$token = $request['token'];
	$answers = $request['answers'];
	if ( ! is_string( $token ) || ! wp_is_uuid( $token ) || ! is_array( $answers ) ) {
		return new WP_Error( 'estudia_quiz_response', 'El intento no es válido.', array( 'status' => 400 ) );
	}
	$items = get_transient( 'estudia_quiz_' . get_current_user_id() . '_' . $token );
	if ( false === $items ) {
		return new WP_Error( 'estudia_quiz_expired', 'El intento venció. Inicia otro simulacro.', array( 'status' => 404 ) );
	}
	if ( count( $answers ) !== count( $items ) || array_values( $answers ) !== $answers ) {
		return new WP_Error( 'estudia_quiz_response', 'La lista de respuestas no es válida.', array( 'status' => 400 ) );
	}
	$correct = 0;
	$omitted = 0;
	foreach ( $answers as $index => $answer ) {
		if ( null === $answer ) {
			++$omitted;
		} elseif ( ! is_int( $answer ) || ! isset( $items[ $index ]['options'][ $answer ] ) ) {
			return new WP_Error( 'estudia_quiz_response', 'Hay una respuesta no válida.', array( 'status' => 400 ) );
		} elseif ( $answer === $items[ $index ]['key'] ) {
			++$correct;
		}
	}
	return rest_ensure_response( array(
		'items' => $items, 'correct' => $correct, 'omitted' => $omitted, 'incorrect' => count( $items ) - $correct - $omitted,
	) );
}

add_action( 'rest_api_init', static function () {
	register_rest_route( 'estudiamed/v1', '/quiz/general', array(
		'methods' => 'POST', 'callback' => 'estudia_quiz_prepare', 'permission_callback' => 'is_user_logged_in',
	) );
	register_rest_route( 'estudiamed/v1', '/quiz/general/finish', array(
		'methods' => 'POST', 'callback' => 'estudia_quiz_finish', 'permission_callback' => 'is_user_logged_in',
	) );
} );

add_shortcode( 'estudiamed_quiz', static function () {
	$lang = function_exists( 'estudiamed_language' ) && 'pt-BR' === estudiamed_language() ? 'pt' : 'es';
	if ( ! is_user_logged_in() ) {
		$url = wp_login_url( ( function_exists( 'estudiamed_home' ) ? estudiamed_home() : ( get_permalink() ?: home_url( '/' ) ) ) . '#quiz-general' );
		return '<p><a class="btn" href="' . esc_url( $url ) . '">' . ( 'pt' === $lang ? 'Entrar para praticar' : 'Iniciar sesión para practicar' ) . '</a></p>';
	}
	wp_enqueue_script( 'estudia-general', plugins_url( 'assets/quiz.js', __FILE__ ), array(), '1.1.0', true );
	$settings = get_option( 'estudia_quiz_ai', array() );
	return '<div class="panel" data-estudia-quiz data-profile="' . esc_attr( (string) get_current_user_id() ) . '" data-topics="' . esc_attr( wp_json_encode( estudia_quiz_topics( $lang ) ) ) . '" data-lang="' . esc_attr( $lang ) . '" data-endpoint="' . esc_url( rest_url( 'estudiamed/v1/quiz/general' ) ) .
		'" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '" data-ai="' . ( empty( $settings['provider'] ) ? '0' : '1' ) .
		'" data-catalog="' . esc_attr( wp_json_encode( estudia_quiz_catalog( $lang ) ) ) . '"></div><noscript>JavaScript requerido.</noscript>';
} );

require_once __DIR__ . '/admin.php';
