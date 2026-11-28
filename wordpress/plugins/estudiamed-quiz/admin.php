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
		<h2>Configuración del proveedor de IA</h2>
		<p>Quiz General usa el banco editorial y ofrece la cantidad disponible. Estos ajustes se conservan para el proveedor existente.</p>
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
		<h2>Categorías y materias adicionales</h2>
		<p>Usa un objeto JSON por idioma. Los temas se toman de las preguntas publicadas de cada materia.</p>
		<p><code>{"es":{"SALUD PÚBLICA":["Salud Comunitaria"]},"pt":{}}</code></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="estudia_quiz_catalog">
			<?php wp_nonce_field( 'estudia_quiz_catalog' ); ?>
			<label for="estudia-quiz-catalog">Catálogo adicional</label>
			<textarea class="large-text code" rows="8" id="estudia-quiz-catalog" name="catalog"><?php echo esc_textarea( wp_json_encode( (object) get_option( 'estudia_quiz_categories', array() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
			<?php submit_button( 'Guardar catálogo' ); ?>
		</form>
		<h2>Importar preguntas</h2>
		<?php if ( function_exists( 'estudia_ehep_import_screen' ) ) : ?>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=estudia_ehep_item&page=estudia-ehep-import' ) ); ?>">Previsualizar y guardar una importación como borrador</a></p>
		<?php else : ?>
			<p>Activa EstudiaMed EHEP para usar el importador editorial.</p>
		<?php endif; ?>
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
	wp_safe_redirect( admin_url( 'edit.php?post_type=estudia_ehep_item&page=estudia-ehep-import' ) );
	exit;
} );

add_action( 'admin_post_estudia_quiz_catalog', static function () {
	estudia_quiz_admin_guard( 'estudia_quiz_catalog' );
	$raw = isset( $_POST['catalog'] ) && is_string( $_POST['catalog'] ) ? wp_unslash( $_POST['catalog'] ) : '';
	$input = strlen( $raw ) <= 50000 ? json_decode( $raw, true ) : null;
	if ( ! is_array( $input ) || array_diff( array_keys( $input ), array( 'es', 'pt' ) ) ) {
		estudia_quiz_admin_finish( false );
	}
	$catalog = array();
	foreach ( $input as $lang => $categories ) {
		if ( ! is_array( $categories ) ) {
			estudia_quiz_admin_finish( false );
		}
		foreach ( $categories as $category => $subjects ) {
			if ( ! is_string( $category ) || ! sanitize_text_field( $category ) || ! is_array( $subjects ) ||
				array_filter( $subjects, static function ( $value ) { return ! is_string( $value ) || ! sanitize_text_field( $value ); } ) ) {
				estudia_quiz_admin_finish( false );
			}
			$catalog[ $lang ][ sanitize_text_field( $category ) ] = array_values( array_unique( array_map( 'sanitize_text_field', $subjects ) ) );
		}
	}
	update_option( 'estudia_quiz_categories', $catalog, false );
	estudia_quiz_admin_finish( true );
} );
