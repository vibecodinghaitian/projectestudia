<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', static function () {
	foreach ( array( 'estudia_notice' => 'Mensajes y anuncios', 'estudia_topic' => 'Temas de estudio' ) as $type => $label ) {
		register_post_type( $type, array(
			'label' => $label, 'public' => false, 'show_ui' => true, 'supports' => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts',
				'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts',
				'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_options' ),
		) );
	}
} );

function estudia_campus_metadata() {
	return array(
		'post' => array( 'subject' => 'Asignatura', 'topic_id' => 'ID del tema', 'ehep' => 'Vincular al módulo EHEP (sí/no)' ),
		'estudia_topic' => array( 'topic_id' => 'ID del tema existente' ),
		'estudia_notice' => array( 'placement' => 'Ubicación', 'link' => 'Enlace de destino', 'canva' => 'URL del diseño editable en Canva' ),
	);
}

add_action( 'add_meta_boxes', static function () {
	foreach ( estudia_campus_metadata() as $type => $fields ) {
		add_meta_box( 'estudia_campus_meta', 'EstudiaMed', 'estudia_campus_meta_box', $type );
	}
} );

function estudia_campus_meta_box( $post ) {
	wp_nonce_field( 'estudia_campus_meta', 'estudia_campus_nonce' );
	foreach ( estudia_campus_metadata()[ $post->post_type ] as $field => $label ) {
		$value = get_post_meta( $post->ID, '_estudia_' . $field, true );
		echo '<p><label>' . esc_html( $label ) . ' ';
		if ( 'placement' === $field ) {
			echo '<select name="estudia_placement"><option value="home"' . selected( $value, 'home', false ) . '>Portada</option><option value="panel"' . selected( $value, 'panel', false ) . '>Panel del estudiante</option></select>';
		} else {
			echo '<input class="regular-text" name="estudia_' . esc_attr( $field ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</label></p>';
		if ( 'canva' === $field && $value ) {
			echo '<p><a target="_blank" rel="noopener noreferrer" href="' . esc_url( $value ) . '">Abrir diseño en Canva</a></p>';
		}
	}
	if ( 'estudia_notice' === $post->post_type ) {
		echo '<p>Utiliza la imagen destacada para cargar la pieza exportada. Escribe siempre el aviso también en el editor.</p>';
	}
	if ( 'estudia_topic' === $post->post_type ) {
		echo '<p>Conserva el ID que aparece en la biblioteca. El texto publicado amplía el tema existente.</p>';
	}
}

add_action( 'save_post', static function ( $id, $post ) {
	$fields = estudia_campus_metadata()[ $post->post_type ] ?? array();
	if ( ! $fields || ! current_user_can( 'edit_post', $id ) || wp_is_post_revision( $id ) ||
		! isset( $_POST['estudia_campus_nonce'] ) || ! is_string( $_POST['estudia_campus_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['estudia_campus_nonce'] ) ), 'estudia_campus_meta' ) ) {
		return;
	}
	foreach ( $fields as $field => $label ) {
		$value = $_POST[ 'estudia_' . $field ] ?? '';
		$value = is_string( $value ) ? wp_unslash( $value ) : '';
		$value = in_array( $field, array( 'link', 'canva' ), true ) ? esc_url_raw( $value, array( 'https', 'http' ) ) : sanitize_text_field( $value );
		if ( 'canva' === $field && ! in_array( strtolower( (string) wp_parse_url( $value, PHP_URL_HOST ) ), array( 'canva.com', 'www.canva.com' ), true ) ) {
			$value = '';
		}
		if ( 'placement' === $field && 'panel' !== $value ) {
			$value = 'home';
		}
		update_post_meta( $id, '_estudia_' . $field, $value );
	}
}, 10, 2 );

function estudia_campus_announcements( $placement ) {
	$posts = get_posts( array( 'post_type' => 'estudia_notice', 'post_status' => 'publish', 'numberposts' => 6,
		'meta_key' => '_estudia_placement', 'meta_value' => $placement ) );
	foreach ( $posts as $notice ) {
		echo '<aside class="panel campus-notice"><h2>' . esc_html( $notice->post_title ) . '</h2>';
		echo get_the_post_thumbnail( $notice, 'medium_large' );
		echo wp_kses_post( wpautop( $notice->post_content ) );
		$url = get_post_meta( $notice->ID, '_estudia_link', true );
		if ( $url ) {
			echo '<p><a href="' . esc_url( $url ) . '">Más información</a></p>';
		}
		echo '</aside>';
	}
}

function estudia_campus_topic_content() {
	$posts = get_posts( array( 'post_type' => 'estudia_topic', 'post_status' => 'publish', 'numberposts' => -1 ) );
	$map = array();
	foreach ( $posts as $post ) {
		$id = get_post_meta( $post->ID, '_estudia_topic_id', true );
		if ( $id && ! isset( $map[ $id ] ) ) {
			$map[ $id ] = $post->post_content;
		}
	}
	return $map;
}

function estudia_campus_article_links( $id ) {
	$subject = get_post_meta( $id, '_estudia_subject', true );
	$topic = get_post_meta( $id, '_estudia_topic_id', true );
	if ( $subject ) {
		echo '<p>Asignatura: <a href="' . esc_url( home_url( '/#temas' ) ) . '">' . esc_html( $subject ) . '</a></p>';
	}
	if ( $topic ) {
		echo '<p><a href="' . esc_url( home_url( '/#' . sanitize_title( $topic ) ) ) . '">Ver tema asociado</a></p>';
	}
	$module = strtolower( (string) get_post_meta( $id, '_estudia_ehep', true ) );
	if ( in_array( $module, array( 'sí', 'si' ), true ) && function_exists( 'estudiamed_ehep_url' ) && estudiamed_ehep_url() ) {
		echo '<p><a href="' . esc_url( estudiamed_ehep_url() ) . '">Examen de Ecuador (EHEP)</a></p>';
	}
}

add_filter( 'post_row_actions', static function ( $actions, $post ) {
	if ( ! current_user_can( 'manage_options' ) ) return $actions;
	if ( 'post' === $post->post_type && 'publish' === $post->post_status ) {
		$url = wp_nonce_url( add_query_arg( array( 'action' => 'estudia_archive_article', 'id' => $post->ID ), admin_url( 'admin-post.php' ) ), 'estudia_archive_' . $post->ID );
		$actions['estudia_archive'] = '<a href="' . esc_url( $url ) . '">Archivar</a>';
	}
	if ( 'estudia_notice' === $post->post_type ) {
		$url = wp_nonce_url( add_query_arg( 'estudia_preview_notice', $post->ID, home_url( '/' ) ), 'estudia_notice_' . $post->ID );
		$actions['estudia_notice_preview'] = '<a href="' . esc_url( $url ) . '">Previsualizar aviso</a>';
	}
	return $actions;
}, 10, 2 );

add_action( 'admin_post_estudia_archive_article', static function () {
	$id = absint( $_GET['id'] ?? 0 );
	if ( ! current_user_can( 'manage_options' ) || 'post' !== get_post_type( $id ) ) wp_die( 'Acceso denegado.', '', array( 'response' => 403 ) );
	check_admin_referer( 'estudia_archive_' . $id );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
	update_post_meta( $id, '_estudia_archived', '1' );
	wp_safe_redirect( admin_url( 'edit.php' ) );
	exit;
} );

add_filter( 'display_post_states', static function ( $states, $post ) {
	if ( 'draft' === $post->post_status && get_post_meta( $post->ID, '_estudia_archived', true ) ) {
		$states['estudia_archived'] = 'Archivado';
	}
	return $states;
}, 10, 2 );

add_action( 'template_redirect', static function () {
	if ( ! isset( $_GET['estudia_preview_notice'] ) ) return;
	$id = absint( $_GET['estudia_preview_notice'] );
	if ( ! current_user_can( 'manage_options' ) || 'estudia_notice' !== get_post_type( $id ) ) wp_die( 'Acceso denegado.', '', array( 'response' => 403 ) );
	check_admin_referer( 'estudia_notice_' . $id );
	$notice = get_post( $id );
	nocache_headers();
	status_header( 200 );
	get_header();
	echo '<main class="section wrap"><h1>Vista previa del aviso</h1><aside class="panel campus-notice"><h2>' . esc_html( $notice->post_title ) . '</h2>';
	echo get_the_post_thumbnail( $notice, 'medium_large' );
	echo wp_kses_post( wpautop( $notice->post_content ) ) . '</aside></main>';
	get_footer();
	exit;
}, 0 );
