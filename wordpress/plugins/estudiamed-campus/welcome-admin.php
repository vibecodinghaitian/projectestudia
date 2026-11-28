<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', static function () {
	add_submenu_page( 'edit.php?post_type=estudia_notice', 'Bienvenida', 'Bienvenida', 'manage_options', 'estudia-welcome', 'estudia_welcome_admin' );
} );

function estudia_welcome_admin_url( $args = array() ) {
	return add_query_arg( $args + array( 'post_type' => 'estudia_notice', 'page' => 'estudia-welcome' ), admin_url( 'edit.php' ) );
}

function estudia_welcome_admin() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$settings = estudia_welcome_settings();
	$mail = estudia_welcome_mail_status();
	$wa = estudia_welcome_kapso_status( $settings );
	$messages = array( 'saved' => 'Configuración guardada.', 'verified' => 'Kapso confirmó el remitente y la plantilla.',
		'verify-failed' => 'Kapso no confirmó un número conectado y una plantilla aprobada compatible. WhatsApp quedó desactivado.',
		'queued' => 'Envío añadido a la cola.', 'invalid' => 'Canal, destinatario o consentimiento no válido. No se programó ningún envío.' );
	$result = isset( $_GET['resultado'] ) && is_string( $_GET['resultado'] ) ? sanitize_key( $_GET['resultado'] ) : '';
	?>
	<div class="wrap"><h1>Bienvenida</h1>
	<?php if ( isset( $messages[ $result ] ) ) : ?><p role="status"><?php echo esc_html( $messages[ $result ] ); ?></p><?php endif; ?>
	<p><strong>Correo:</strong> <?php echo esc_html( $mail ?: ( $settings['email'] ? 'Activado. Prueba la conexión para comprobar su funcionamiento.' : 'Conexión detectada. Bienvenida desactivada.' ) ); ?></p>
	<p><strong>WhatsApp:</strong> <?php echo esc_html( $wa ?: ( $settings['whatsapp'] ? 'Activado con remitente y plantilla verificados.' : 'Configuración verificada. Bienvenida desactivada.' ) ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="estudia_welcome_settings">
		<?php wp_nonce_field( 'estudia_welcome_settings' ); ?>
		<h2>Correo · FluentSMTP</h2>
		<p><label><input type="checkbox" name="email" value="1" <?php checked( $settings['email'] ); ?> <?php disabled( (bool) $mail ); ?>> Activar correo de bienvenida</label></p>
		<p><label>Asunto <input class="regular-text" name="subject" required maxlength="180" value="<?php echo esc_attr( $settings['subject'] ); ?>"></label></p>
		<p><label>Texto del correo <textarea class="large-text" name="body" rows="5" required maxlength="3000"><?php echo esc_textarea( $settings['body'] ); ?></textarea></label></p>
		<p>Variables: <code>{{nombre}}</code>, <code>{{enlace_panel}}</code>. El botón «Ir a mi panel» se añade automáticamente.</p>
		<h2>WhatsApp · Kapso</h2>
		<p><label><input type="checkbox" name="whatsapp" value="1" <?php checked( $settings['whatsapp'] ); ?> <?php disabled( (bool) $wa ); ?>> Activar WhatsApp de bienvenida</label></p>
		<p><label>ID del número remitente <input name="sender" maxlength="30" value="<?php echo esc_attr( $settings['sender'] ); ?>"></label></p>
		<p><label>Nombre de la plantilla aprobada <input name="template" maxlength="128" value="<?php echo esc_attr( $settings['template'] ); ?>"></label></p>
		<p><label>Idioma de la plantilla <input name="language" maxlength="6" value="<?php echo esc_attr( $settings['language'] ); ?>"></label></p>
		<p>Plantilla de cuerpo con parámetros nombrados <code>nombre</code> y <code>enlace_panel</code>.</p>
		<p>Webhook de entrega: <code><?php echo esc_html( rest_url( 'estudiamed/v1/welcome/kapso' ) ); ?></code></p>
		<p><button type="submit" class="button button-primary" name="operation" value="save">Guardar</button>
		<button type="submit" class="button" name="operation" value="verify">Verificar remitente y plantilla</button></p>
	</form>
	<h2>Envío de prueba</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="estudia_welcome_test">
		<?php wp_nonce_field( 'estudia_welcome_test' ); ?>
		<p><label>Canal <select name="channel"><option value="email">Correo</option><option value="whatsapp">WhatsApp</option></select></label></p>
		<p><label>Destinatario <input name="target" maxlength="254" required></label></p>
		<p><label><input type="checkbox" name="recipient_ok" value="1" required> Este destinatario ha autorizado recibir esta prueba.</label></p>
		<?php submit_button( 'Programar prueba' ); ?>
	</form>
	<?php estudia_welcome_history(); ?>
	</div>
	<?php
}

function estudia_welcome_history() {
	global $wpdb;
	$table = estudia_welcome_table();
	$page = max( 1, min( 100000, absint( $_GET['historial'] ?? 1 ) ) );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY id DESC LIMIT 51 OFFSET %d", ( $page - 1 ) * 50 ), ARRAY_A );
	$labels = array( 'pending' => 'Pendiente', 'sending' => 'Pendiente · en curso', 'unknown' => 'Pendiente de confirmación',
		'accepted' => 'Aceptado por el proveedor', 'delivered' => 'Entregado', 'failed' => 'Fallido' );
	echo '<h2>Historial</h2><table class="widefat"><thead><tr><th>ID</th><th>Canal</th><th>Estado</th><th>Fecha</th><th>Intentos</th><th>Detalle</th><th>Acción</th></tr></thead><tbody>';
	foreach ( array_slice( $rows, 0, 50 ) as $row ) {
		$test = 0 === strpos( $row['event_key'], 'test:' );
		echo '<tr><td>' . esc_html( $row['id'] ) . '</td><td>' . esc_html( ( 'email' === $row['channel'] ? 'Correo' : 'WhatsApp' ) . ( $test ? ' · prueba' : '' ) ) . '</td><td>' .
			esc_html( $labels[ $row['state'] ] ?? $row['state'] ) . '</td><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $row['updated'] ) ) .
			'</td><td>' . esc_html( $row['attempts'] ) . '</td><td>' . esc_html( $row['note'] ) . '</td><td>';
		if ( 'failed' === $row['state'] ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="estudia_welcome_retry"><input type="hidden" name="id" value="' .
				esc_attr( $row['id'] ) . '">' . wp_nonce_field( 'estudia_welcome_retry', '_wpnonce', true, false );
			if ( $test && 'whatsapp' === $row['channel'] ) echo '<label><input type="checkbox" name="recipient_ok" value="1" required> Autorización vigente</label> ';
			echo '<button class="button">Reintentar</button></form>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	if ( $page > 1 ) echo '<p><a href="' . esc_url( estudia_welcome_admin_url( array( 'historial' => $page - 1 ) ) ) . '">Anterior</a></p>';
	if ( count( $rows ) > 50 ) echo '<p><a href="' . esc_url( estudia_welcome_admin_url( array( 'historial' => $page + 1 ) ) ) . '">Siguiente</a></p>';
}

function estudia_welcome_admin_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Acceso denegado.', '', array( 'response' => 403 ) );
	check_admin_referer( $action );
}

function estudia_welcome_redirect( $result ) {
	wp_safe_redirect( estudia_welcome_admin_url( array( 'resultado' => $result ) ) );
	exit;
}

add_action( 'admin_post_estudia_welcome_settings', static function () {
	estudia_welcome_admin_guard( 'estudia_welcome_settings' );
	$settings = estudia_welcome_settings();
	foreach ( array( 'sender', 'template', 'language', 'subject', 'body' ) as $key ) {
		if ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) {
			$limits = array( 'sender' => 30, 'template' => 128, 'language' => 6, 'subject' => 180, 'body' => 3000 );
			$raw = wp_unslash( $_POST[ $key ] );
			if ( strlen( $raw ) > $limits[ $key ] * 4 ) estudia_welcome_redirect( 'invalid' );
			$settings[ $key ] = 'body' === $key ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		}
	}
	if ( ! $settings['subject'] || ! $settings['body'] ) estudia_welcome_redirect( 'invalid' );
	$result = 'saved';
	if ( 'verify' === ( $_POST['operation'] ?? '' ) ) {
		$verified = estudia_welcome_verify_kapso( $settings );
		$settings['verified'] = $verified ? estudia_welcome_fingerprint( $settings ) : '';
		$result = $verified ? 'verified' : 'verify-failed';
	}
	$settings['email'] = '1' === ( $_POST['email'] ?? '' ) && ! estudia_welcome_mail_status();
	$settings['whatsapp'] = '1' === ( $_POST['whatsapp'] ?? '' ) && ! estudia_welcome_kapso_status( $settings );
	update_option( 'estudia_welcome_settings', $settings, false );
	estudia_welcome_redirect( $result );
} );

add_action( 'admin_post_estudia_welcome_test', static function () {
	estudia_welcome_admin_guard( 'estudia_welcome_test' );
	$channel = $_POST['channel'] ?? '';
	$target = isset( $_POST['target'] ) && is_string( $_POST['target'] ) ? trim( wp_unslash( $_POST['target'] ) ) : '';
	$ready = false;
	if ( 'email' === $channel ) $ready = is_email( $target ) && ! estudia_welcome_mail_status();
	if ( 'whatsapp' === $channel ) {
		$target = estudia_welcome_number( $target );
		$ready = $target && ! estudia_welcome_kapso_status( estudia_welcome_settings() );
	}
	if ( ! $ready || '1' !== ( $_POST['recipient_ok'] ?? '' ) ) estudia_welcome_redirect( 'invalid' );
	estudia_welcome_redirect( estudia_welcome_queue( get_current_user_id(), $channel, $target, true ) ? 'queued' : 'invalid' );
} );

function estudia_welcome_retry( $id, $test_recipient_ok = false ) {
	global $wpdb;
	$table = estudia_welcome_table();
	$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id=%d AND state='failed'", $id ), ARRAY_A );
	if ( ! $job ) return false;
	if ( 'whatsapp' === $job['channel'] &&
		( ! estudia_welcome_whatsapp_target( $job ) || ( 0 === strpos( $job['event_key'], 'test:' ) && ! $test_recipient_ok ) ) ) return false;
	return 1 === $wpdb->update( $table, array( 'state' => 'pending', 'note' => '', 'updated' => time() ), array( 'id' => $id, 'state' => 'failed' ) );
}

add_action( 'admin_post_estudia_welcome_retry', static function () {
	estudia_welcome_admin_guard( 'estudia_welcome_retry' );
	estudia_welcome_redirect( estudia_welcome_retry( absint( $_POST['id'] ?? 0 ), '1' === ( $_POST['recipient_ok'] ?? '' ) ) ? 'queued' : 'invalid' );
} );
