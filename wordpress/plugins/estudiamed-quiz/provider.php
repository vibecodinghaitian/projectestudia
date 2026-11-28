<?php
defined( 'ABSPATH' ) || exit;

function estudia_quiz_generate( $lang, $category, $subject, $type, $count, $topic = '' ) {
	$settings = get_option( 'estudia_quiz_ai', array() );
	$provider = $settings['provider'] ?? '';
	$key = $settings['key'] ?? '';
	$model = $settings['model'] ?? '';
	$error = new WP_Error( 'estudia_quiz_provider', 'El proveedor no pudo generar preguntas. Inténtalo más tarde.', array( 'status' => 502 ) );
	if ( ! $key || ! in_array( $provider, array( 'gemini', 'openai' ), true ) || ! preg_match( '/^[a-zA-Z0-9._-]{1,80}$/D', $model ) ) {
		return new WP_Error( 'estudia_quiz_configuration', 'La generación aún no está configurada.', array( 'status' => 503 ) );
	}
	// A shared cooldown limits provider requests across accounts.
	if ( ! add_option( 'estudia_quiz_ai_lock', time() + 30, '', false ) ) {
		$old = (int) get_option( 'estudia_quiz_ai_lock' );
		if ( $old >= time() ) {
			return new WP_Error( 'estudia_quiz_rate', 'Espera antes de solicitar otra generación.', array( 'status' => 429 ) );
		}
		global $wpdb;
		$claimed = $wpdb->update( $wpdb->options, array( 'option_value' => (string) ( time() + 30 ) ),
			array( 'option_name' => 'estudia_quiz_ai_lock', 'option_value' => (string) $old ) );
		wp_cache_delete( 'estudia_quiz_ai_lock', 'options' );
		if ( 1 !== $claimed ) {
			return new WP_Error( 'estudia_quiz_rate', 'Espera antes de solicitar otra generación.', array( 'status' => 429 ) );
		}
	}
	$kind = 'case' === $type ? 'clinical vignettes' : 'multiple-choice questions';
	$language = 'pt' === $lang ? 'Brazilian Portuguese' : 'Spanish';
	$prompt = "Write $count original educational medical $kind in $language for $category / $subject / $topic. These are unreviewed practice drafts. Do not copy official test questions or invent source citations. Each item must have one defensible correct choice. Return only a JSON object with an items array. Each item has: stem (string), options (four distinct strings), key (zero-based integer), explanation (string explaining the correct choice and why the distractors are incorrect).";
	$headers = array( 'Content-Type' => 'application/json' );
	if ( 'gemini' === $provider ) {
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent';
		$headers['x-goog-api-key'] = $key;
		$body = array( 'contents' => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
			'generationConfig' => array( 'responseMimeType' => 'application/json', 'maxOutputTokens' => 4096 ) );
	} else {
		$url = 'https://api.openai.com/v1/chat/completions';
		$headers['Authorization'] = 'Bearer ' . $key;
		$body = array( 'model' => $model, 'messages' => array( array( 'role' => 'user', 'content' => $prompt ) ),
			'response_format' => array( 'type' => 'json_object' ), 'max_completion_tokens' => 4096 );
	}
	$response = wp_remote_post( $url, array( 'headers' => $headers, 'body' => wp_json_encode( $body ), 'timeout' => 22, 'redirection' => 0, 'limit_response_size' => 262144 ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return $error;
	}
	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	$text = 'gemini' === $provider ? ( $payload['candidates'][0]['content']['parts'][0]['text'] ?? '' ) :
		( $payload['choices'][0]['message']['content'] ?? '' );
	$decoded = is_string( $text ) ? json_decode( $text, true ) : null;
	$items = array();
	foreach ( array_slice( (array) ( $decoded['items'] ?? array() ), 0, $count ) as $candidate ) {
		$item = estudia_quiz_item( $candidate );
		if ( $item ) {
			$item['origin'] = 'ai';
			$items[] = $item;
		}
	}
	return $items ?: $error;
}
