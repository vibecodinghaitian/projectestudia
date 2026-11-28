<?php
defined( 'ABSPATH' ) || exit;

function estudia_welcome_secret( $name ) {
	$value = defined( $name ) ? constant( $name ) : getenv( $name );
	return is_string( $value ) ? trim( $value ) : '';
}

function estudia_welcome_mail_status() {
	if ( ! defined( 'FLUENTMAIL_PLUGIN_FILE' ) ) return 'FluentSMTP no está activo.';
	$conf = (array) get_option( 'fluentmail-settings', array() );
	if ( ( defined( 'FLUENTMAIL_SIMULATE_EMAILS' ) && FLUENTMAIL_SIMULATE_EMAILS ) || 'yes' === ( $conf['misc']['simulate_emails'] ?? '' ) ) {
		return 'FluentSMTP está en modo simulación.';
	}
	if ( empty( $conf['connections'] ) ) return 'FluentSMTP está activo y necesita una conexión de correo.';
	return '';
}

function estudia_welcome_fingerprint( $settings ) {
	return hash_hmac( 'sha256', implode( '|', array( $settings['sender'], $settings['template'], $settings['language'] ) ), estudia_welcome_secret( 'KAPSO_API_KEY' ) );
}

function estudia_welcome_kapso_status( $settings ) {
	if ( ! estudia_welcome_secret( 'KAPSO_API_KEY' ) ) return 'Falta KAPSO_API_KEY en el servidor.';
	if ( ! preg_match( '/^[0-9]{5,30}$/D', $settings['sender'] ) || ! preg_match( '/^[a-z][a-z0-9_]{0,127}$/D', $settings['template'] ) ||
		! preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/D', $settings['language'] ) ) return 'Completa el ID remitente, la plantilla y el idioma.';
	if ( ! hash_equals( estudia_welcome_fingerprint( $settings ), $settings['verified'] ) ) return 'Verifica el remitente y la plantilla aprobada con Kapso.';
	return '';
}

function estudia_welcome_verify_kapso( $settings ) {
	$key = estudia_welcome_secret( 'KAPSO_API_KEY' );
	if ( ! $key || ! preg_match( '/^[0-9]{5,30}$/D', $settings['sender'] ) ||
		! preg_match( '/^[a-z][a-z0-9_]{0,127}$/D', $settings['template'] ) || ! preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/D', $settings['language'] ) ) return false;
	$args = array( 'headers' => array( 'X-API-Key' => $key ), 'timeout' => 8, 'redirection' => 0, 'limit_response_size' => 65536 );
	$response = wp_remote_get( 'https://api.kapso.ai/platform/v1/whatsapp/phone_numbers/' . $settings['sender'], $args );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) return false;
	$number = json_decode( wp_remote_retrieve_body( $response ), true )['data'] ?? array();
	$account = (string) ( $number['business_account_id'] ?? '' );
	if ( (string) ( $number['phone_number_id'] ?? '' ) !== $settings['sender'] ||
		'CONNECTED' !== strtoupper( (string) ( $number['status'] ?? '' ) ) || ! preg_match( '/^[0-9]{5,30}$/D', $account ) ) return false;
	$url = add_query_arg( array( 'name' => $settings['template'], 'language' => $settings['language'], 'status' => 'APPROVED' ),
		'https://api.kapso.ai/meta/whatsapp/v24.0/' . $account . '/message_templates' );
	$response = wp_remote_get( $url, $args );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) return false;
	foreach ( json_decode( wp_remote_retrieve_body( $response ), true )['data'] ?? array() as $template ) {
		if ( ( $template['name'] ?? '' ) !== $settings['template'] || ( $template['language'] ?? '' ) !== $settings['language'] ||
			'APPROVED' !== strtoupper( $template['status'] ?? '' ) ) continue;
		$body = '';
		foreach ( $template['components'] ?? array() as $component ) {
			$type = strtoupper( $component['type'] ?? '' );
			if ( 'BODY' === $type ) $body = $component['text'] ?? '';
			elseif ( 'FOOTER' !== $type ) continue 2;
		}
		preg_match_all( '/{{\s*([a-z0-9_]+)\s*}}/i', $body, $parameters );
		$names = array_unique( $parameters[1] );
		sort( $names );
		if ( array( 'enlace_panel', 'nombre' ) === $names ) return true;
	}
	return false;
}

function estudia_welcome_deliver( $job ) {
	$settings = estudia_welcome_settings();
	$test = 0 === strpos( $job['event_key'], 'test:' );
	$channel = $job['channel'];
	$user = $test ? null : get_userdata( (int) $job['user_id'] );
	$fail = static function ( $note ) { return array( 'state' => 'failed', 'note' => $note ); };
	if ( ! $test && ! $user ) return $fail( 'La cuenta ya no existe.' );
	if ( ! $test && empty( $settings[ $channel ] ) ) return $fail( 'El canal está desactivado.' );
	$name = $test ? 'Prueba EstudiaMed' : trim( preg_replace( '/\s+/u', ' ', $user->display_name ) );
	$panel = estudia_campus_url( 'mi-panel' );
	if ( 'email' === $channel ) {
		$blocked = estudia_welcome_mail_status();
		if ( $blocked ) return $fail( $blocked );
		$recipient = $test ? $job['target'] : $user->user_email;
		if ( ! is_email( $recipient ) ) return $fail( 'El correo no es válido.' );
		$tokens = array( '{{nombre}}' => $name, '{{enlace_panel}}' => $panel );
		$subject = str_replace( array( "\r", "\n" ), ' ', strtr( $settings['subject'], $tokens ) );
		$body = wpautop( esc_html( strtr( $settings['body'], $tokens ) ) );
		$body .= '<p><a href="' . esc_url( $panel ) . '" style="background:#126157;color:#fff;padding:12px 18px;text-decoration:none">Ir a mi panel</a></p>';
		try {
			return wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) )
				? array( 'state' => 'accepted', 'note' => 'FluentSMTP aceptó el envío; entrega sin confirmar.' )
				: $fail( 'WordPress informó un fallo de correo. Revisa el registro de FluentSMTP.' );
		} catch ( Throwable $error ) {
			return array( 'state' => 'unknown', 'note' => 'Resultado incierto; revisa FluentSMTP.' );
		}
	}
	$blocked = estudia_welcome_kapso_status( $settings );
	if ( $blocked ) return $fail( $blocked );
	$target = estudia_welcome_whatsapp_target( $job );
	if ( ! $target ) return $fail( 'Sin consentimiento vigente para el número original.' );
	$response = wp_remote_post( 'https://api.kapso.ai/meta/whatsapp/v24.0/' . $settings['sender'] . '/messages', array(
		'headers' => array( 'Content-Type' => 'application/json', 'X-API-Key' => estudia_welcome_secret( 'KAPSO_API_KEY' ) ),
		'body' => wp_json_encode( array(
			'messaging_product' => 'whatsapp', 'to' => substr( $target, 1 ), 'type' => 'template',
			'template' => array( 'name' => $settings['template'], 'language' => array( 'code' => $settings['language'] ),
				'components' => array( array( 'type' => 'body', 'parameters' => array(
					array( 'type' => 'text', 'parameter_name' => 'nombre', 'text' => $name ?: 'Estudiante' ),
					array( 'type' => 'text', 'parameter_name' => 'enlace_panel', 'text' => $panel ),
				) ) ) ),
		) ), 'timeout' => 8, 'redirection' => 0, 'limit_response_size' => 65536,
	) );
	if ( ! is_wp_error( $response ) ) {
		$status = wp_remote_retrieve_response_code( $response );
		$id = json_decode( wp_remote_retrieve_body( $response ), true )['messages'][0]['id'] ?? '';
		if ( $status >= 200 && $status < 300 && is_string( $id ) && $id && strlen( $id ) <= 191 ) {
			return array( 'state' => 'accepted', 'sender' => $settings['sender'], 'provider_id' => $id, 'note' => 'Kapso aceptó el envío.' );
		}
		if ( $status >= 400 && $status < 500 && ! in_array( $status, array( 408, 409 ), true ) ) return $fail( 'Kapso rechazó el envío (HTTP ' . $status . ').' );
	}
	return array( 'state' => 'unknown', 'note' => 'Kapso no confirmó el resultado. Revisa el proveedor antes de volver a enviar.' );
}

add_action( 'rest_api_init', static function () {
	register_rest_route( 'estudiamed/v1', '/welcome/kapso', array(
		'methods' => 'POST', 'permission_callback' => 'estudia_welcome_webhook_verify', 'callback' => 'estudia_welcome_webhook',
	) );
} );

function estudia_welcome_webhook_verify( $request ) {
	$secret = estudia_welcome_secret( 'KAPSO_WEBHOOK_SECRET' );
	$body = $request->get_body();
	return $secret && strlen( $body ) <= 65536 &&
		hash_equals( hash_hmac( 'sha256', $body, $secret ), (string) $request->get_header( 'x-webhook-signature' ) );
}

function estudia_welcome_webhook( $request ) {
	global $wpdb;
	$event = $request->get_json_params();
	$sender = $event['phone_number_id'] ?? '';
	$id = $event['message']['id'] ?? '';
	$status = $event['message']['kapso']['status'] ?? '';
	if ( ! is_string( $sender ) || ! is_string( $id ) || ! $sender || ! $id || strlen( $id ) > 191 ||
		'outbound' !== ( $event['message']['kapso']['direction'] ?? '' ) ) return array( 'ok' => true );
	$table = estudia_welcome_table();
	if ( in_array( $status, array( 'delivered', 'read' ), true ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE $table SET state='delivered',updated=%d,note='Entrega confirmada por Kapso.' WHERE sender=%s AND provider_id=%s AND state IN ('accepted','failed','pending')", time(), $sender, $id ) );
	} elseif ( 'failed' === $status ) {
		$wpdb->update( $table, array( 'state' => 'failed', 'updated' => time(), 'note' => 'Kapso confirmó un fallo de entrega.' ),
			array( 'sender' => $sender, 'provider_id' => $id, 'state' => 'accepted' ) );
	}
	return array( 'ok' => true );
}
