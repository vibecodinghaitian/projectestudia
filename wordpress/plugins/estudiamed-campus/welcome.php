<?php
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/welcome-providers.php';
require_once __DIR__ . '/welcome-admin.php';

function estudia_welcome_table() {
	global $wpdb;
	return $wpdb->prefix . 'estudia_welcome';
}

function estudia_welcome_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = estudia_welcome_table();
	$collation = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE $table (
		id bigint unsigned NOT NULL AUTO_INCREMENT,
		event_key varchar(80) NOT NULL,
		user_id bigint unsigned NOT NULL,
		channel varchar(12) NOT NULL,
		state varchar(14) NOT NULL,
		target varchar(254) NOT NULL DEFAULT '',
		sender varchar(32) NOT NULL DEFAULT '',
		provider_id varchar(191) NOT NULL DEFAULT '',
		created bigint unsigned NOT NULL,
		updated bigint unsigned NOT NULL,
		attempts int unsigned NOT NULL DEFAULT 0,
		note varchar(250) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		UNIQUE KEY event_key (event_key),
		KEY state_id (state,id),
		KEY provider_id (provider_id)
	) $collation;" );
	update_option( 'estudia_welcome_schema', '1', false );
}
register_activation_hook( ESTUDIA_CAMPUS_FILE, 'estudia_welcome_install' );

add_filter( 'cron_schedules', static function ( $schedules ) {
	$schedules['estudia_minute'] = array( 'interval' => 60, 'display' => 'Cada minuto · EstudiaMed' );
	return $schedules;
} );

add_action( 'init', static function () {
	if ( '1' !== get_option( 'estudia_welcome_schema' ) ) estudia_welcome_install();
	if ( ! wp_next_scheduled( 'estudia_welcome_process' ) ) {
		wp_schedule_event( time() + 60, 'estudia_minute', 'estudia_welcome_process' );
	}
} );

register_deactivation_hook( ESTUDIA_CAMPUS_FILE, static function () { wp_clear_scheduled_hook( 'estudia_welcome_process' ); } );

function estudia_welcome_settings() {
	return (array) get_option( 'estudia_welcome_settings', array() ) + array(
		'email' => false, 'whatsapp' => false, 'sender' => '', 'template' => '', 'language' => 'es',
		'subject' => '¡Bienvenido a EstudiaMed, {{nombre}}!',
		'body' => "Hola, {{nombre}}. Tu cuenta de EstudiaMed ya está activa.\n\nExplora el banco de preguntas, los temas de Medicina y el Examen de Ecuador (EHEP).",
		'verified' => '',
	);
}

function estudia_welcome_number( $number ) {
	if ( ! is_string( $number ) ) return '';
	$number = preg_replace( '/[\s().-]+/', '', trim( $number ) );
	return preg_match( '/^\+[1-9][0-9]{7,14}$/D', $number ) ? $number : '';
}

function estudia_welcome_form_values() {
	$raw = isset( $_POST['estudia_whatsapp'] ) && is_string( $_POST['estudia_whatsapp'] ) ? trim( wp_unslash( $_POST['estudia_whatsapp'] ) ) : '';
	$number = estudia_welcome_number( $raw );
	$consent = '1' === ( $_POST['estudia_wa_consent'] ?? '' );
	return array( $number, $consent, ( $raw || $consent ) && ! $number );
}

function estudia_welcome_consent( $user_id ) {
	return (array) get_user_meta( $user_id, '_estudia_whatsapp', true );
}

function estudia_welcome_save_consent( $user_id, $number, $consent ) {
	$old = estudia_welcome_consent( $user_id );
	$active = $consent && '' !== $number;
	$same_number = ( $old['number'] ?? '' ) === $number;
	$was_active = $same_number && ! empty( $old['granted'] );
	$now = time();
	update_user_meta( $user_id, '_estudia_whatsapp', array(
		'number' => $number, 'granted' => $active,
		'granted_at' => $active ? ( $was_active ? (int) ( $old['granted_at'] ?? $old['recorded_at'] ?? $now ) : $now ) :
			( $same_number ? (int) ( $old['granted_at'] ?? 0 ) : 0 ),
		'withdrawn_at' => ! $active && $was_active ? $now : ( $same_number && ! $active ? (int) ( $old['withdrawn_at'] ?? 0 ) : 0 ),
		'policy' => 1,
	) );
}

function estudia_welcome_fields( $number = '', $allowed = false ) {
	?>
	<label>WhatsApp con código de país (opcional)
		<input type="tel" name="estudia_whatsapp" autocomplete="tel" maxlength="30" placeholder="+593…" value="<?php echo esc_attr( $number ); ?>">
	</label>
	<label class="campus-consent"><input type="checkbox" name="estudia_wa_consent" value="1" <?php checked( $allowed ); ?>>
		Acepto recibir por WhatsApp la bienvenida y los avisos relacionados con mi cuenta de EstudiaMed.
	</label>
	<?php
}

function estudia_welcome_profile() {
	$id = get_current_user_id();
	if ( ! $id ) return;
	$message = '';
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && '1' === ( $_POST['estudia_profile'] ?? '' ) ) {
		$nonce = isset( $_POST['estudia_profile_nonce'] ) && is_string( $_POST['estudia_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['estudia_profile_nonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'estudia_profile' ) ) {
			list( $number, $consent, $invalid ) = estudia_welcome_form_values();
			if ( ! $invalid || ! $consent ) {
				estudia_welcome_save_consent( $id, $number, $consent );
				$message = 'Preferencias guardadas.';
			} else {
				$message = 'Introduce un número válido con código de país.';
			}
		} else {
			$message = 'La sesión del formulario venció.';
		}
	}
	$value = estudia_welcome_consent( $id );
	?>
	<section class="panel campus-account" id="preferencias">
		<h2>Preferencias de WhatsApp</h2>
		<?php if ( $message ) : ?><p role="status"><?php echo esc_html( $message ); ?></p><?php endif; ?>
		<p>Para retirar tu consentimiento, desmarca la casilla y guarda. Puedes borrar el número cuando quieras.</p>
		<form method="post">
			<input type="hidden" name="estudia_profile" value="1">
			<?php wp_nonce_field( 'estudia_profile', 'estudia_profile_nonce' ); ?>
			<?php estudia_welcome_fields( $value['number'] ?? '', ! empty( $value['granted'] ) ); ?>
			<button class="btn">Guardar preferencias</button>
		</form>
	</section>
	<?php
}

function estudia_welcome_queue( $user, $channel, $target = '', $test = false ) {
	global $wpdb;
	$table = estudia_welcome_table();
	$event = $test ? 'test:' . wp_generate_uuid4() : 'welcome:' . $user . ':' . $channel;
	$now = time();
	return 1 === $wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO $table (event_key,user_id,channel,state,target,created,updated) VALUES (%s,%d,%s,'pending',%s,%d,%d)",
		$event, $user, $channel, $target, $now, $now
	) );
}

add_action( 'estudia_campus_account_activated', static function ( $user ) {
	if ( ! get_userdata( $user ) ) return;
	$settings = estudia_welcome_settings();
	if ( $settings['email'] ) estudia_welcome_queue( $user, 'email' );
	$consent = estudia_welcome_consent( $user );
	if ( $settings['whatsapp'] && ! empty( $consent['granted'] ) && estudia_welcome_number( $consent['number'] ?? '' ) ) {
		estudia_welcome_queue( $user, 'whatsapp', hash( 'sha256', $consent['number'] ) );
	}
} );

function estudia_welcome_process() {
	global $wpdb;
	$table = estudia_welcome_table();
	// An interrupted network request is not safe to resend without reconciliation.
	$wpdb->query( $wpdb->prepare( "UPDATE $table SET state='unknown', note='Resultado incierto; revisa el proveedor.' WHERE state='sending' AND updated < %d", time() - 300 ) );
	$started = microtime( true );
	$jobs = $wpdb->get_results( "SELECT * FROM $table WHERE state='pending' ORDER BY id ASC LIMIT 4", ARRAY_A );
	foreach ( $jobs as $job ) {
		if ( microtime( true ) - $started > 16 ) break;
		if ( 1 !== $wpdb->query( $wpdb->prepare( "UPDATE $table SET state='sending', updated=%d, attempts=attempts+1 WHERE id=%d AND state='pending'", time(), $job['id'] ) ) ) continue;
		try {
			$result = estudia_welcome_deliver( $job );
		} catch ( Throwable $error ) {
			$result = array( 'state' => 'unknown', 'note' => 'Resultado incierto; revisa el proveedor.' );
		}
		$wpdb->update( $table, $result + array( 'updated' => time() ), array( 'id' => $job['id'], 'state' => 'sending' ) );
	}
}
add_action( 'estudia_welcome_process', 'estudia_welcome_process' );

function estudia_welcome_whatsapp_target( $job ) {
	if ( 0 === strpos( $job['event_key'], 'test:' ) ) return estudia_welcome_number( $job['target'] );
	$consent = estudia_welcome_consent( (int) $job['user_id'] );
	$number = estudia_welcome_number( $consent['number'] ?? '' );
	return ! empty( $consent['granted'] ) && $number && hash_equals( $job['target'], hash( 'sha256', $number ) ) ? $number : '';
}
