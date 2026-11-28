<?php
/**
 * Plugin Name: EstudiaMed Quiz General
 * Description: Práctica y simulacros bilingües con banco editorial.
 * Version: 1.2.0
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/provider.php';

function estudia_quiz_catalog( $lang ) {
	$base = json_decode( (string) file_get_contents( __DIR__ . '/catalog.json' ), true )[ $lang ] ?? array();
	$extra = get_option( 'estudia_quiz_categories', array() )[ $lang ] ?? array();
	foreach ( $extra as $category => $subjects ) {
		$base[ $category ] = array_values( array_unique( array_merge( $base[ $category ] ?? array(), $subjects ) ) );
	}
	return $base;
}

function estudia_quiz_types() {
	return array( 'mcq', 'case', 'reasoning', 'basic', 'application' );
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
	$bank = array();
	foreach ( apply_filters( 'estudia_quiz_editorial_items', $items ) as $item ) {
		if ( empty( $item['id'] ) || ! empty( $item['archived'] ) || 'ai' === ( $item['origin'] ?? '' ) ) {
			continue;
		}
		$item['topic'] = ( $item['topic'] ?? '' ) ?: ( 'pt' === $item['lang'] ? 'Sem tema atribuído' : 'Sin tema asignado' );
		$bank[ $item['id'] ] = $item;
	}
	return array_values( $bank );
}

function estudia_quiz_topics( $lang ) {
	$topics = array();
	foreach ( estudia_quiz_bank() as $item ) {
		if ( $lang === $item['lang'] ) {
			$subject = $item['quiz_subject'] ?? $item['subject'];
			$topics[ $item['category'] ][ $subject ][ $item['type'] ][ $item['topic'] ][] = (string) $item['id'];
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
	$seen = $request['seen'] ?? array();
	$mode = $request['mode'] ?? 'study';
	$minutes = $request['minutes'] ?? 15;
	if ( ! in_array( $lang, array( 'es', 'pt' ), true ) || ! is_string( $category ) || ! is_string( $subject ) || ! is_string( $topic ) || strlen( $topic ) > 200 ||
		( null !== $favorites && ( ! is_array( $favorites ) || count( $favorites ) > 500 ) ) ||
		! is_array( $seen ) || count( $seen ) > 500 || ! in_array( $mode, array( 'study', 'exam' ), true ) ||
		! in_array( $type, estudia_quiz_types(), true ) || ! is_int( $count ) || $count < 1 || $count > 100 ||
		! is_int( $minutes ) || $minutes < 1 || $minutes > 180 ||
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
	$available = count( $matches );
	if ( $available < $count ) {
		return rest_ensure_response( array( 'items' => array(), 'available' => $available ) );
	}
	usort( $matches, static function ( $a, $b ) use ( $seen ) {
		return in_array( $a['id'], $seen, true ) <=> in_array( $b['id'], $seen, true );
	} );
	$items = array_slice( $matches, 0, $count );
	if ( 'study' === $mode ) {
		return rest_ensure_response( array( 'items' => $items, 'available' => $available ) );
	}
	$token = wp_generate_uuid4();
	$now = time();
	$attempt = array( 'items' => $items, 'deadline' => $now + $minutes * MINUTE_IN_SECONDS, 'answers' => array_fill( 0, $count, null ) );
	if ( ! set_transient( estudia_quiz_storage( $token ), $attempt, DAY_IN_SECONDS ) ) {
		return new WP_Error( 'estudia_quiz_save', 'No se pudo iniciar el simulacro.', array( 'status' => 500 ) );
	}
	$public = array_map( static function ( $item ) {
		return array_intersect_key( $item, array_flip( array( 'id', 'stem', 'options', 'origin', 'type', 'difficulty' ) ) );
	}, $items );
	return rest_ensure_response( array( 'items' => $public, 'token' => $token, 'seconds' => $minutes * MINUTE_IN_SECONDS ) );
}

function estudia_quiz_storage( $token ) {
	return 'estudia_quiz_' . get_current_user_id() . '_' . $token;
}

function estudia_quiz_save_answers( $request ) {
	return estudia_quiz_grade_attempt( $request, false );
}

function estudia_quiz_finish( $request ) {
	return estudia_quiz_grade_attempt( $request, true );
}

function estudia_quiz_grade_attempt( $request, $finish ) {
	$token = $request['token'];
	$answers = $request['answers'];
	if ( ! is_string( $token ) || ! wp_is_uuid( $token ) || ! is_array( $answers ) ) {
		return new WP_Error( 'estudia_quiz_response', 'El intento no es válido.', array( 'status' => 400 ) );
	}
	$storage = estudia_quiz_storage( $token );
	$attempt = get_transient( $storage );
	if ( false === $attempt ) {
		return new WP_Error( 'estudia_quiz_expired', 'El intento venció. Inicia otro simulacro.', array( 'status' => 404 ) );
	}
	if ( isset( $attempt['result'] ) ) {
		return $finish ? rest_ensure_response( $attempt['result'] ) : rest_ensure_response( array( 'saved' => false ) );
	}
	$modern = isset( $attempt['items'] );
	$items = $modern ? $attempt['items'] : $attempt;
	$expired = $modern && time() >= $attempt['deadline'];
	if ( $expired ) {
		$answers = $attempt['answers'];
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
	if ( ! $finish ) {
		if ( $modern && ! $expired ) {
			if ( $attempt['answers'] !== $answers ) {
				$attempt['answers'] = $answers;
				if ( ! set_transient( $storage, $attempt, DAY_IN_SECONDS ) ) {
					return new WP_Error( 'estudia_quiz_save', 'No se pudieron guardar las respuestas.', array( 'status' => 500 ) );
				}
			}
		}
		return rest_ensure_response( array( 'saved' => $modern && ! $expired ) );
	}
	$result = array( 'items' => $items, 'answers' => $answers, 'correct' => $correct, 'omitted' => $omitted, 'incorrect' => count( $items ) - $correct - $omitted );
	if ( $modern ) {
		set_transient( $storage, array( 'result' => $result ), DAY_IN_SECONDS );
	}
	return rest_ensure_response( $result );
}

add_action( 'rest_api_init', static function () {
	register_rest_route( 'estudiamed/v1', '/quiz/general', array(
		'methods' => 'POST', 'callback' => 'estudia_quiz_prepare', 'permission_callback' => 'is_user_logged_in',
	) );
	register_rest_route( 'estudiamed/v1', '/quiz/general/answers', array(
		'methods' => 'POST', 'callback' => 'estudia_quiz_save_answers', 'permission_callback' => 'is_user_logged_in',
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
	wp_enqueue_style( 'estudia-general', plugins_url( 'assets/quiz.css', __FILE__ ), array(), '1.2.0' );
	wp_enqueue_script( 'estudia-general', plugins_url( 'assets/quiz.js', __FILE__ ), array(), '1.2.0', true );
	return '<div class="panel" data-estudia-quiz data-profile="' . esc_attr( (string) get_current_user_id() ) . '" data-topics="' . esc_attr( wp_json_encode( array( 'es' => estudia_quiz_topics( 'es' ), 'pt' => estudia_quiz_topics( 'pt' ) ) ) ) . '" data-lang="' . esc_attr( $lang ) . '" data-endpoint="' . esc_url( rest_url( 'estudiamed/v1/quiz/general' ) ) .
		'" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '"></div><noscript>JavaScript requerido.</noscript>';
} );

require_once __DIR__ . '/admin.php';
