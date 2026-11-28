<?php
defined( 'ABSPATH' ) || exit;

function estudiamed_ehep_register_bank() {
	register_post_type( 'estudia_ehep_item', array(
		'labels' => array( 'name' => 'Preguntas EHEP', 'singular_name' => 'Pregunta EHEP' ),
		'public' => false,
		'show_ui' => true,
		'show_in_rest' => false,
		'supports' => array( 'title', 'revisions' ),
		'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
			'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts',
			'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_options' ),
		'register_meta_box_cb' => 'estudiamed_ehep_register_editor',
	) );
}
add_action( 'init', 'estudiamed_ehep_register_bank' );

function estudiamed_ehep_register_editor() {
	add_meta_box( 'estudia_ehep_fields', 'Contenido de práctica EHEP', 'estudiamed_ehep_editor', 'estudia_ehep_item' );
}

function estudiamed_ehep_editor( $post ) {
	$item = get_post_meta( $post->ID, '_estudia_ehep', true );
	wp_nonce_field( 'estudia_ehep_edit', 'estudia_ehep_nonce' );
	$module = get_post_meta( $post->ID, '_estudia_question_module', true ) ?: 'ehep';
	echo '<p><label>Módulo <select name="estudia_question_module"><option value="ehep"' . selected( $module, 'ehep', false ) . '>EHEP</option><option value="general"' . selected( $module, 'general', false ) . '>Quiz General</option></select></label></p>';
	foreach ( array( 'call' => 'Convocatoria', 'category' => 'Categoría de Quiz General', 'type' => 'Tipo (mcq, case, reasoning, basic o application)', 'original' => 'ID original EHEP para corregir (p. ej. html-4)' ) as $field => $label ) {
		echo '<p><label>' . esc_html( $label ) . ' <input name="ehep_' . esc_attr( $field ) . '" value="' . esc_attr( $item[ $field ] ?? '' ) . '"></label></p>';
	}
	echo '<p><label>Idioma de Quiz General <select name="ehep_lang"><option value="es">Español</option><option value="pt"' .
		selected( $item['lang'] ?? '', 'pt', false ) . '>Português</option></select></label></p>';
	echo '<p><label>Dificultad de Quiz General <select name="ehep_difficulty"><option value="">Sin nivel asignado</option>';
	foreach ( array( 'beginner' => 'Inicial', 'intermediate' => 'Intermedio', 'advanced' => 'Avanzado', 'exam' => 'Examen universitario' ) as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $item['difficulty'] ?? '', $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></label></p><p><label>URL de la referencia <input name="ehep_url" type="url" value="' . esc_attr( $item['url'] ?? '' ) . '"></label></p>';
	echo '<p><label>Explicación general <textarea class="large-text" name="ehep_explanation" rows="3">' . esc_textarea( $item['explanation'] ?? '' ) . '</textarea></label></p>';
	echo '<p><label><input type="checkbox" name="ehep_archived" value="1" ' . checked( ! empty( $item['archived'] ), true, false ) . '> Archivar para intentos futuros</label></p>';
	echo '<p><label for="ehep_subject">Especialidad</label><br><input class="regular-text" id="ehep_subject" name="ehep_subject" value="' . esc_attr( $item['subject'] ?? '' ) . '"></p>';
	echo '<p><label for="ehep_topic">Tema</label><br><input class="regular-text" id="ehep_topic" name="ehep_topic" value="' . esc_attr( $item['topic'] ?? '' ) . '"></p>';
	echo '<p><label for="ehep_source">Fuente o procedencia</label><br><input class="regular-text" id="ehep_source" name="ehep_source" value="' . esc_attr( $item['source'] ?? '' ) . '"></p>';
	echo '<p>Usa contenido educativo propio o autorizado. Selecciona la respuesta correcta y explica cada alternativa. La opción E es opcional y está disponible en Quiz General.</p>';
	foreach ( range( 0, 4 ) as $i ) {
		echo '<fieldset><legend>Opción ' . esc_html( chr( 65 + $i ) ) . '</legend>';
		echo '<label><input type="radio" name="ehep_key" value="' . esc_attr( $i ) . '" ' . checked( $item['key'] ?? -1, $i, false ) . '> Correcta</label>';
		echo '<p><label for="ehep_option_' . esc_attr( $i ) . '">Respuesta</label><br><textarea class="large-text" id="ehep_option_' . esc_attr( $i ) . '" name="ehep_options[' . esc_attr( $i ) . ']" rows="2">' . esc_textarea( $item['options'][ $i ] ?? '' ) . '</textarea></p>';
		echo '<p><label for="ehep_reason_' . esc_attr( $i ) . '">Explicación</label><br><textarea class="large-text" id="ehep_reason_' . esc_attr( $i ) . '" name="ehep_reasons[' . esc_attr( $i ) . ']" rows="2">' . esc_textarea( $item['reasons'][ $i ] ?? '' ) . '</textarea></p></fieldset>';
	}
	$history = get_post_meta( $post->ID, '_estudia_key_log', false );
	if ( $history ) {
		echo '<details><summary>Historial editorial</summary><ul>';
		foreach ( array_reverse( $history ) as $change ) {
			$previous = $change['before']['key'] ?? -1;
			$current = $change['after']['key'] ?? -1;
			echo '<li>' . esc_html( $change['at'] . ' · Clave ' . ( $previous < 0 ? '—' : chr( 65 + $previous ) ) .
				' → ' . ( $current < 0 ? '—' : chr( 65 + $current ) ) . ' · Usuario ' . $change['user'] ) . '</li>';
		}
		echo '</ul></details>';
	}

}

function estudiamed_ehep_valid_choices( $item, $module ) {
	$options = $item['options'] ?? null;
	$reasons = $item['reasons'] ?? null;
	$key = $item['key'] ?? null;
	if ( ! is_array( $options ) || ! is_array( $reasons ) || ! is_int( $key ) ||
		! in_array( count( $options ), 'general' === $module ? array( 4, 5 ) : array( 4 ), true ) ||
		array_values( $options ) !== $options || array_keys( $reasons ) !== array_keys( $options ) ||
		! isset( $options[ $key ] ) || count( array_unique( $options ) ) !== count( $options ) ) {
		return false;
	}
	return ! in_array( '', $options, true ) && ! in_array( '', $reasons, true );
}

function estudiamed_ehep_save_item( $id ) {
	if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ! current_user_can( 'manage_options' ) ||
		! isset( $_POST['estudia_ehep_nonce'] ) || ! is_string( $_POST['estudia_ehep_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['estudia_ehep_nonce'] ) ), 'estudia_ehep_edit' ) ) {
		return;
	}
	$module = 'general' === ( $_POST['estudia_question_module'] ?? '' ) ? 'general' : 'ehep';
	$item = array(
		'subject' => sanitize_text_field( wp_unslash( $_POST['ehep_subject'] ?? '' ) ),
		'topic' => sanitize_text_field( wp_unslash( $_POST['ehep_topic'] ?? '' ) ),
		'source' => sanitize_text_field( wp_unslash( $_POST['ehep_source'] ?? '' ) ),
		'key' => in_array( $_POST['ehep_key'] ?? '', array( '0', '1', '2', '3', '4' ), true ) ? (int) $_POST['ehep_key'] : -1,
		'options' => array(),
		'reasons' => array(),
	);
	foreach ( range( 0, 4 ) as $i ) {
		$option = sanitize_textarea_field( wp_unslash( $_POST['ehep_options'][ $i ] ?? '' ) );
		$reason = sanitize_textarea_field( wp_unslash( $_POST['ehep_reasons'][ $i ] ?? '' ) );
		if ( 4 === $i && '' === $option && '' === $reason ) {
			break;
		}
		$item['options'][] = $option;
		$item['reasons'][] = $reason;
	}
	foreach ( array( 'call', 'category', 'type', 'original', 'explanation' ) as $field ) {
		$item[ $field ] = sanitize_textarea_field( wp_unslash( $_POST[ 'ehep_' . $field ] ?? '' ) );
	}
	$item['archived'] = isset( $_POST['ehep_archived'] );
	if ( 'general' === $module ) {
		$item['lang'] = 'pt' === ( $_POST['ehep_lang'] ?? '' ) ? 'pt' : 'es';
		$difficulty = sanitize_key( $_POST['ehep_difficulty'] ?? '' );
		$item['difficulty'] = in_array( $difficulty, array( 'beginner', 'intermediate', 'advanced', 'exam' ), true ) ? $difficulty : '';
	}
	if ( isset( $_POST['ehep_url'] ) && is_string( $_POST['ehep_url'] ) ) {
		$item['url'] = esc_url_raw( wp_unslash( $_POST['ehep_url'] ), array( 'https', 'http' ) );
	}
	update_post_meta( $id, '_estudia_question_module', $module );
	update_post_meta( $id, '_estudia_ehep', wp_slash( $item ) );
}
add_action( 'save_post_estudia_ehep_item', 'estudiamed_ehep_save_item' );

function estudiamed_ehep_editorial_items( $module ) {
	$posts = get_posts( array( 'post_type' => 'estudia_ehep_item', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
	$items = array();
	foreach ( $posts as $post ) {
		$target = get_post_meta( $post->ID, '_estudia_question_module', true ) ?: 'ehep';
		if ( $target !== $module ) continue;
		$item = get_post_meta( $post->ID, '_estudia_ehep', true );
		if ( empty( $item['subject'] ) || empty( $item['source'] ) || ! estudiamed_ehep_valid_choices( $item, $module ) || '' === trim( $post->post_title ) ) continue;
		$item['id'] = 'wp-' . $post->ID;
		$item['stem'] = $post->post_title;
		$items[] = $item;
	}
	return $items;
}

function estudiamed_ehep_bank() {
	$items = require __DIR__ . '/practice.php';
	$imported = require dirname( __DIR__ ) . '/data/html-practice.php';
	$bank = array_column( array_merge( $items, $imported ), null, 'id' );
	foreach ( estudiamed_ehep_editorial_items( 'ehep' ) as $item ) {
		$original = $item['original'] ?? '';
		if ( isset( $bank[ $original ] ) ) {
			$prior = $bank[ $original ];
			if ( ! empty( $item['archived'] ) ) {
				unset( $bank[ $original ] );
				continue;
			}
			$item['id'] = $original;
			$item['caces_source'] = $prior['caces_source'] ?? $prior['source'];
			$item['caces_answer'] = $prior['caces_answer'] ?? $prior['options'][ $prior['key'] ];
			$item['fixedOrder'] = true;
			if ( isset( $prior['image'] ) ) $item['image'] = $prior['image'];
		}
		if ( empty( $item['archived'] ) ) $bank[ $item['id'] ] = $item;
	}
	return array_values( $bank );
}

add_filter( 'estudia_quiz_editorial_items', static function ( $bank ) {
	foreach ( estudiamed_ehep_editorial_items( 'general' ) as $item ) {
		if ( empty( $item['archived'] ) && ! empty( $item['explanation'] ) &&
			in_array( $item['subject'], estudia_quiz_catalog( $item['lang'] ?? 'es' )[ $item['category'] ?? '' ] ?? array(), true ) &&
			in_array( $item['type'] ?? '', ( function_exists( 'estudia_quiz_types' ) ? estudia_quiz_types() : array( 'mcq', 'case' ) ), true ) ) {
			$bank[] = $item + array( 'lang' => 'es', 'origin' => 'bank' );
		}
	}
	return $bank;
} );

add_action( 'update_post_meta', static function ( $meta_id, $id, $key, $new ) {
	if ( '_estudia_ehep' !== $key || 'estudia_ehep_item' !== get_post_type( $id ) ) return;
	$old = get_post_meta( $id, $key, true );
	$fields = array_flip( array( 'key', 'explanation', 'reasons' ) );
	$before = array_intersect_key( (array) $old, $fields );
	$after = array_intersect_key( (array) $new, $fields );
	if ( $before !== $after ) add_post_meta( $id, '_estudia_key_log', array( 'at' => gmdate( 'c' ), 'user' => get_current_user_id(), 'before' => $before, 'after' => $after ) );
}, 10, 4 );

function estudiamed_ehep_topic( $item ) {
	if ( ! empty( $item['topic'] ) ) {
		return $item['topic'];
	}
	$topics = array(
		'epi-01' => 'Medidas de frecuencia', 'epi-02' => 'Pruebas diagnósticas', 'epi-03' => 'Diseños de estudio',
		'html-2' => 'Obstrucción intestinal mecánica',
		'bio-01' => 'Fisiología endocrina', 'bio-02' => 'Fisiología renal', 'bio-03' => 'Fisiología cardiovascular',
	);
	return $topics[ $item['id'] ] ?? ( 0 === strpos( $item['id'], 'html-' ) ? 'Convocatoria I · 2023' : 'Sin tema asignado' );
}

function estudiamed_ehep_favorites() {
	return array_fill_keys( get_user_meta( get_current_user_id(), '_estudia_ehep_favorite', false ), true );
}

function estudiamed_ehep_favorite( $request ) {
	$row = estudiamed_ehep_load( (int) $request['id'] );
	if ( is_wp_error( $row ) ) {
		return $row;
	}
	$index = $request['item'];
	$value = $request['favorite'];
	if ( ! is_int( $index ) || ! is_bool( $value ) || ! isset( $row['items'][ $index ] ) ) {
		return estudiamed_ehep_error( 'ehep_favorite_input', 'No se pudo identificar la pregunta.', 400 );
	}
	$id = $row['items'][ $index ]['id'];
	$user = get_current_user_id();
	$exists = isset( estudiamed_ehep_favorites()[ $id ] );
	if ( $value && ! $exists ) {
		add_user_meta( $user, '_estudia_ehep_favorite', $id );
	} elseif ( ! $value && $exists ) {
		delete_user_meta( $user, '_estudia_ehep_favorite', $id );
	}
	$saved = isset( estudiamed_ehep_favorites()[ $id ] );
	return $saved === $value ? array( 'favorite' => $saved ) :
		estudiamed_ehep_error( 'ehep_favorite_store', 'No se pudo guardar el favorito.', 500 );
}
