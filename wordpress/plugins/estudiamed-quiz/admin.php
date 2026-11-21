<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', static function () {
	add_options_page( 'EstudiaMed Quiz', 'EstudiaMed Quiz', 'manage_options', 'estudia-quiz', 'estudia_quiz_admin' );
} );

function estudia_quiz_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = get_option( 'estudia_quiz_ai', array() );
	?>
	<div class="wrap">
		<h1>EstudiaMed Quiz General</h1>
		<?php if ( isset( $_GET['result'] ) ) : ?>
			<p role="status"><?php echo 'ok' === $_GET['result'] ? 'Cambios guardados.' : 'No se pudo guardar. Revisa los datos y vuelve a intentarlo.'; ?></p>
		<?php endif; ?>
		<h2>Generación opcional</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="estudia_quiz_settings">
			<?php wp_nonce_field( 'estudia_quiz_settings' ); ?>
			<p><label>Proveedor
				<select name="provider">
					<option value="">Desactivado</option>
					<option value="gemini" <?php selected( $settings['provider'] ?? '', 'gemini' ); ?>>Gemini</option>
					<option value="openai" <?php selected( $settings['provider'] ?? '', 'openai' ); ?>>OpenAI</option>
				</select>
			</label></p>
			<p><label>Modelo <input name="model" value="<?php echo esc_attr( $settings['model'] ?? '' ); ?>" maxlength="80"></label></p>
			<p><label>Clave API <input type="password" name="api_key" autocomplete="new-password" maxlength="400"></label></p>
			<p><?php echo empty( $settings['key'] ) ? 'Sin clave configurada.' : 'Hay una clave guardada. Deja el campo en blanco para conservarla en el mismo proveedor.'; ?></p>
			<p><label><input type="checkbox" name="delete_key" value="1"> Eliminar la clave guardada</label></p>
			<?php submit_button(); ?>
		</form>
		<h2>Importar preguntas revisadas</h2>
		<p>Sube un conjunto JSON preparado a partir del PDF y su clave. Revisa la procedencia, las opciones y la explicación antes de la importación.</p>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="estudia_quiz_import">
			<?php wp_nonce_field( 'estudia_quiz_import' ); ?>
			<label>Archivo JSON <input type="file" name="bank" accept=".json,application/json" required></label>
			<?php submit_button( 'Importar' ); ?>
		</form>
	</div>
	<?php
}

function estudia_quiz_admin_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Acceso no autorizado.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( $action );
}

function estudia_quiz_admin_finish( $ok ) {
	wp_safe_redirect( add_query_arg( array( 'page' => 'estudia-quiz', 'result' => $ok ? 'ok' : 'error' ), admin_url( 'options-general.php' ) ) );
	exit;
}

add_action( 'admin_post_estudia_quiz_settings', static function () {
	estudia_quiz_admin_guard( 'estudia_quiz_settings' );
	$old = get_option( 'estudia_quiz_ai', array() );
	$provider = sanitize_key( $_POST['provider'] ?? '' );
	$model = sanitize_text_field( wp_unslash( $_POST['model'] ?? '' ) );
	$key = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
	if ( ! in_array( $provider, array( '', 'gemini', 'openai' ), true ) ||
		( $provider && ! preg_match( '/^[a-zA-Z0-9._-]{1,80}$/D', $model ) ) || strlen( $key ) > 400 ) {
		estudia_quiz_admin_finish( false );
	}
	if ( '' === $key && $provider === ( $old['provider'] ?? '' ) ) {
		$key = $old['key'] ?? '';
	}
	if ( isset( $_POST['delete_key'] ) || '' === $provider ) {
		$key = '';
	}
	update_option( 'estudia_quiz_ai', array( 'provider' => $provider, 'model' => $model, 'key' => $key ), false );
	estudia_quiz_admin_finish( true );
} );

add_action( 'admin_post_estudia_quiz_import', static function () {
	estudia_quiz_admin_guard( 'estudia_quiz_import' );
	$file = $_FILES['bank'] ?? array();
	if ( UPLOAD_ERR_OK !== ( $file['error'] ?? -1 ) || ( $file['size'] ?? 0 ) > 2000000 || ! is_uploaded_file( $file['tmp_name'] ) ) {
		estudia_quiz_admin_finish( false );
	}
	$items = json_decode( (string) file_get_contents( $file['tmp_name'] ), true );
	if ( ! is_array( $items ) || ! $items || count( $items ) > 500 ) {
		estudia_quiz_admin_finish( false );
	}
	$bank = get_option( 'estudia_quiz_bank', array() );
	foreach ( $items as $raw ) {
		$item = estudia_quiz_item( $raw );
		$lang = $raw['lang'] ?? '';
		$type = $raw['type'] ?? '';
		$category = $raw['category'] ?? '';
		$subject = $raw['subject'] ?? '';
		$source = $raw['source'] ?? '';
		if ( ! $item || ! in_array( $lang, array( 'es', 'pt' ), true ) || ! in_array( $type, array( 'case', 'mcq' ), true ) ||
			! is_string( $category ) || ! is_string( $subject ) || ! in_array( $subject, estudia_quiz_catalog( $lang )[ $category ] ?? array(), true ) ||
			! is_string( $source ) || ! trim( $source ) ) {
			estudia_quiz_admin_finish( false );
		}
		$item += array( 'lang' => $lang, 'type' => $type, 'category' => $category, 'subject' => $subject,
			'topic' => sanitize_text_field( $raw['topic'] ?? '' ) ?: 'Sin tema asignado',
			'source' => sanitize_text_field( $source ), 'origin' => 'bank' );
		$bank[ hash( 'sha256', wp_json_encode( array( $lang, $type, $category, $subject, $item['stem'] ) ) ) ] = $item;
	}
	update_option( 'estudia_quiz_bank', $bank, false );
	estudia_quiz_admin_finish( true );
} );
