<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', static function () {
	add_submenu_page( 'edit.php?post_type=estudia_ehep_item', 'Importar preguntas', 'Importar CSV/JSON', 'manage_options', 'estudia-ehep-import', 'estudia_ehep_import_screen' );
} );

function estudia_ehep_guard( $nonce ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Acceso restringido a administradores.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( $nonce );
}

function estudia_ehep_import_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$batch = get_transient( 'estudia_import_' . get_current_user_id() );
	?>
	<div class="wrap"><h1>Importar preguntas</h1>
	<p><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=estudia_ehep_template' ), 'estudia_ehep_template' ) ); ?>">Descargar plantilla CSV</a></p>
	<?php if ( isset( $_GET['saved'] ) ) : ?><p role="status">Borradores guardados: <?php echo esc_html( absint( $_GET['saved'] ) ); ?>.</p><?php endif; ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="estudia_ehep_preview"><?php wp_nonce_field( 'estudia_ehep_preview' ); ?>
		<p><label>Módulo <select name="module"><option value="ehep">Examen de Ecuador (EHEP)</option><option value="general">Quiz General</option></select></label></p>
		<p><label>Archivo CSV o JSON <input type="file" name="bank" accept=".csv,.json" required></label></p>
		<?php submit_button( 'Validar y previsualizar' ); ?>
	</form>
	<?php if ( $batch ) : ?>
		<h2>Vista previa · <?php echo 'general' === $batch['module'] ? 'Quiz General' : 'EHEP'; ?></h2>
		<table class="widefat"><thead><tr><th>Fila</th><th>Enunciado</th><th>Estado</th></tr></thead><tbody>
		<?php foreach ( $batch['messages'] as $row ) : ?>
			<tr><td><?php echo esc_html( $row[0] ); ?></td><td><?php echo esc_html( $row[1] ); ?></td><td><?php echo esc_html( $row[2] ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<?php if ( $batch['rows'] ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="estudia_ehep_commit"><?php wp_nonce_field( 'estudia_ehep_commit' ); ?>
				<?php submit_button( 'Guardar filas válidas como borradores' ); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?></div>
	<?php
}

function estudia_ehep_columns() {
	return array( 'stem', 'subject', 'topic', 'source', 'call', 'category', 'type', 'key', 'option_a', 'option_b', 'option_c', 'option_d',
		'explanation', 'reason_a', 'reason_b', 'reason_c', 'reason_d' );
}

add_action( 'admin_post_estudia_ehep_template', static function () {
	estudia_ehep_guard( 'estudia_ehep_template' );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="estudiamed-preguntas.csv"' );
	$file = fopen( 'php://output', 'w' );
	fputcsv( $file, estudia_ehep_columns(), ',', '"', '' );
	fclose( $file );
	exit;
} );

function estudia_ehep_import_normalize( $raw, $module ) {
	if ( ! is_array( $raw ) ) return null;
	$item = array();
	foreach ( array( 'stem', 'subject', 'topic', 'source', 'call', 'category', 'type', 'explanation' ) as $field ) {
		$item[ $field ] = sanitize_textarea_field( is_string( $raw[ $field ] ?? null ) ? $raw[ $field ] : '' );
	}
	$key = strtoupper( (string) ( is_scalar( $raw['key'] ?? null ) ? $raw['key'] : '' ) );
	$item['key'] = strlen( $key ) === 1 ? strpos( 'ABCD', $key ) : false;
	foreach ( array( 'a', 'b', 'c', 'd' ) as $index => $letter ) {
		$item['options'][] = sanitize_textarea_field( $raw['options'][ $index ] ?? $raw[ 'option_' . $letter ] ?? '' );
		$item['reasons'][] = sanitize_textarea_field( $raw['reasons'][ $index ] ?? $raw[ 'reason_' . $letter ] ?? '' );
	}
	if ( ! $item['stem'] || ! $item['subject'] || ! $item['source'] || false === $item['key'] ||
		count( array_unique( array_filter( $item['options'] ) ) ) !== 4 || ! $item['explanation'] || count( array_filter( $item['reasons'] ) ) !== 4 ) return null;
	if ( 'general' === $module && ( ! function_exists( 'estudia_quiz_catalog' ) ||
		! in_array( $item['subject'], estudia_quiz_catalog( 'es' )[ $item['category'] ] ?? array(), true ) ||
		! in_array( $item['type'], array( 'mcq', 'case' ), true ) ) ) return null;
	return $item;
}

function estudia_ehep_stem_key( $s ) {
	return hash( 'sha256', strtolower( remove_accents( preg_replace( '/\s+/u', ' ', trim( $s ) ) ) ) );
}

function estudia_ehep_import_seen( $module ) {
	$seen = array();
	$items = 'ehep' === $module ? estudiamed_ehep_bank() : ( function_exists( 'estudia_quiz_bank' ) ? estudia_quiz_bank() : array() );
	foreach ( $items as $item ) $seen[ estudia_ehep_stem_key( $item['stem'] ) ] = true;
	$posts = get_posts( array( 'post_type' => 'estudia_ehep_item', 'post_status' => array( 'draft', 'pending' ), 'numberposts' => -1 ) );
	foreach ( $posts as $post ) {
		if ( $module === ( get_post_meta( $post->ID, '_estudia_question_module', true ) ?: 'ehep' ) ) {
			$seen[ estudia_ehep_stem_key( $post->post_title ) ] = true;
		}
	}
	return $seen;
}

function estudia_ehep_import_redirect( $args = array() ) {
	wp_safe_redirect( add_query_arg( $args + array( 'post_type' => 'estudia_ehep_item', 'page' => 'estudia-ehep-import' ), admin_url( 'edit.php' ) ) );
	exit;
}

add_action( 'admin_post_estudia_ehep_preview', static function () {
	estudia_ehep_guard( 'estudia_ehep_preview' );
	$file = $_FILES['bank'] ?? array();
	$module = 'general' === ( $_POST['module'] ?? '' ) ? 'general' : 'ehep';
	$batch = array( 'module' => $module, 'rows' => array(), 'messages' => array() );
	$rows = null;
	if ( UPLOAD_ERR_OK === ( $file['error'] ?? -1 ) && $file['size'] <= 2000000 && is_uploaded_file( $file['tmp_name'] ) ) {
		$extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'json' === $extension ) {
			$rows = json_decode( (string) file_get_contents( $file['tmp_name'] ), true );
		} elseif ( 'csv' === $extension && ( $handle = fopen( $file['tmp_name'], 'r' ) ) ) {
			$headers = fgetcsv( $handle, 0, ',', '"', '' );
			if ( $headers ) {
				$headers[0] = ltrim( $headers[0], "\xef\xbb\xbf" );
				$rows = array();
				while ( count( $rows ) <= 500 && false !== ( $line = fgetcsv( $handle, 0, ',', '"', '' ) ) ) {
					$rows[] = count( $headers ) === count( $line ) ? array_combine( $headers, $line ) : null;
				}
			}
			fclose( $handle );
		}
	}
	if ( ! is_array( $rows ) || ! $rows || count( $rows ) > 500 ) {
		$batch['messages'][] = array( '—', '', 'Usa un archivo CSV o JSON válido de hasta 500 filas.' );
	} else {
		$seen = estudia_ehep_import_seen( $module );
		foreach ( array_values( $rows ) as $index => $raw ) {
			$item = estudia_ehep_import_normalize( $raw, $module );
			$status = 'Campos incompletos, clave inválida o clasificación incorrecta.';
			if ( $item ) {
				$hash = estudia_ehep_stem_key( $item['stem'] );
				$status = isset( $seen[ $hash ] ) ? 'Duplicada' : 'Válida';
				if ( ! isset( $seen[ $hash ] ) ) {
					$batch['rows'][] = $item;
					$seen[ $hash ] = true;
				}
			}
			$batch['messages'][] = array( $index + 1, $item['stem'] ?? '', $status );
		}
	}
	set_transient( 'estudia_import_' . get_current_user_id(), $batch, 20 * MINUTE_IN_SECONDS );
	estudia_ehep_import_redirect();
} );

add_action( 'admin_post_estudia_ehep_commit', static function () {
	estudia_ehep_guard( 'estudia_ehep_commit' );
	$token = 'estudia_import_' . get_current_user_id();
	$batch = get_transient( $token );
	$count = 0;
	if ( $batch ) {
		delete_transient( $token );
		$seen = estudia_ehep_import_seen( $batch['module'] );
		foreach ( $batch['rows'] as $item ) {
			$hash = estudia_ehep_stem_key( $item['stem'] );
			if ( isset( $seen[ $hash ] ) ) continue;
			$id = wp_insert_post( array( 'post_type' => 'estudia_ehep_item', 'post_status' => 'draft', 'post_title' => wp_slash( $item['stem'] ),
				'meta_input' => array( '_estudia_ehep' => wp_slash( $item ), '_estudia_question_module' => $batch['module'] ) ), true );
			if ( ! is_wp_error( $id ) && $id ) {
				++$count;
				$seen[ $hash ] = true;
			}
		}
	}
	estudia_ehep_import_redirect( array( 'saved' => $count ) );
} );

add_action( 'restrict_manage_posts', static function ( $post_type ) {
	if ( 'estudia_ehep_item' !== $post_type ) return;
	$module = isset( $_GET['estudia_module'] ) && is_string( $_GET['estudia_module'] ) ? sanitize_key( $_GET['estudia_module'] ) : '';
	echo '<label class="screen-reader-text" for="estudia-module">Módulo</label><select name="estudia_module" id="estudia-module"><option value="">Todos los módulos</option>';
	echo '<option value="ehep"' . selected( $module, 'ehep', false ) . '>EHEP</option><option value="general"' . selected( $module, 'general', false ) . '>Quiz General</option></select>';
} );

add_action( 'pre_get_posts', static function ( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'estudia_ehep_item' !== $query->get( 'post_type' ) ) return;
	$module = isset( $_GET['estudia_module'] ) && is_string( $_GET['estudia_module'] ) ? sanitize_key( $_GET['estudia_module'] ) : '';
	if ( ! in_array( $module, array( 'general', 'ehep' ), true ) ) return;
	$match = array( 'key' => '_estudia_question_module', 'value' => $module );
	$query->set( 'meta_query', 'ehep' === $module ? array( 'relation' => 'OR', $match, array( 'key' => '_estudia_question_module', 'compare' => 'NOT EXISTS' ) ) : array( $match ) );
} );
