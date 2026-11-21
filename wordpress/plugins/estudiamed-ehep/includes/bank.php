<?php
defined( 'ABSPATH' ) || exit;

function estudiamed_ehep_register_bank() {
	register_post_type( 'estudia_ehep_item', array(
		'labels' => array( 'name' => 'Preguntas EHEP', 'singular_name' => 'Pregunta EHEP' ),
		'public' => false,
		'show_ui' => true,
		'show_in_rest' => false,
		'supports' => array( 'title', 'revisions' ),
		'capability_type' => 'page',
		'map_meta_cap' => true,
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
	echo '<p><label for="ehep_subject">Especialidad</label><br><input class="regular-text" id="ehep_subject" name="ehep_subject" value="' . esc_attr( $item['subject'] ?? '' ) . '"></p>';
	echo '<p><label for="ehep_source">Fuente o procedencia</label><br><input class="regular-text" id="ehep_source" name="ehep_source" value="' . esc_attr( $item['source'] ?? '' ) . '"></p>';
	echo '<p>Usa contenido educativo propio o autorizado. Selecciona la respuesta correcta y explica las cuatro alternativas.</p>';
	foreach ( range( 0, 3 ) as $i ) {
		echo '<fieldset><legend>Opción ' . esc_html( chr( 65 + $i ) ) . '</legend>';
		echo '<label><input type="radio" name="ehep_key" value="' . esc_attr( $i ) . '" ' . checked( $item['key'] ?? -1, $i, false ) . '> Correcta</label>';
		echo '<p><label for="ehep_option_' . esc_attr( $i ) . '">Respuesta</label><br><textarea class="large-text" id="ehep_option_' . esc_attr( $i ) . '" name="ehep_options[' . esc_attr( $i ) . ']" rows="2">' . esc_textarea( $item['options'][ $i ] ?? '' ) . '</textarea></p>';
		echo '<p><label for="ehep_reason_' . esc_attr( $i ) . '">Explicación</label><br><textarea class="large-text" id="ehep_reason_' . esc_attr( $i ) . '" name="ehep_reasons[' . esc_attr( $i ) . ']" rows="2">' . esc_textarea( $item['reasons'][ $i ] ?? '' ) . '</textarea></p></fieldset>';
	}
}

function estudiamed_ehep_save_item( $id ) {
	if ( wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ! current_user_can( 'edit_post', $id ) ||
		! isset( $_POST['estudia_ehep_nonce'] ) || ! is_string( $_POST['estudia_ehep_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['estudia_ehep_nonce'] ) ), 'estudia_ehep_edit' ) ) {
		return;
	}
	$item = array(
		'subject' => sanitize_text_field( wp_unslash( $_POST['ehep_subject'] ?? '' ) ),
		'source' => sanitize_text_field( wp_unslash( $_POST['ehep_source'] ?? '' ) ),
		'key' => in_array( $_POST['ehep_key'] ?? '', array( '0', '1', '2', '3' ), true ) ? (int) $_POST['ehep_key'] : -1,
		'options' => array(),
		'reasons' => array(),
	);
	foreach ( range( 0, 3 ) as $i ) {
		$item['options'][] = sanitize_textarea_field( wp_unslash( $_POST['ehep_options'][ $i ] ?? '' ) );
		$item['reasons'][] = sanitize_textarea_field( wp_unslash( $_POST['ehep_reasons'][ $i ] ?? '' ) );
	}
	update_post_meta( $id, '_estudia_ehep', wp_slash( $item ) );
}
add_action( 'save_post_estudia_ehep_item', 'estudiamed_ehep_save_item' );

function estudiamed_ehep_bank() {
	$items = require __DIR__ . '/practice.php';
	$posts = get_posts( array(
		'post_type' => 'estudia_ehep_item', 'post_status' => 'publish', 'numberposts' => -1,
		'orderby' => 'ID', 'order' => 'ASC', 'update_post_term_cache' => false,
	) );
	foreach ( $posts as $post ) {
		$item = get_post_meta( $post->ID, '_estudia_ehep', true );
		if ( empty( $item['subject'] ) || empty( $item['source'] ) || $item['key'] < 0 ||
			count( array_filter( $item['options'] ) ) !== 4 || count( array_filter( $item['reasons'] ) ) !== 4 ||
			'' === trim( $post->post_title ) ) {
			continue;
		}
		$item['id'] = 'wp-' . $post->ID;
		$item['stem'] = $post->post_title;
		$items[] = $item;
	}
	return $items;
}
