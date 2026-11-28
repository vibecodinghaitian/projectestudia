<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', static function () {
	register_post_type( 'estudia_curso', array(
		'labels' => array( 'name' => 'Cursos médicos', 'singular_name' => 'Curso médico' ),
		'public' => true,
		'rewrite' => false,
		'menu_icon' => 'dashicons-welcome-learn-more',
		'supports' => array( 'title', 'editor', 'excerpt', 'revisions', 'thumbnail' ),
		'show_in_rest' => true,
	) );
	register_post_type( 'estudia_leccion', array(
		'labels' => array( 'name' => 'Lecciones', 'singular_name' => 'Lección' ),
		'public' => true,
		'rewrite' => false,
		'show_in_menu' => 'edit.php?post_type=estudia_curso',
		'supports' => array( 'title', 'editor', 'excerpt', 'revisions', 'thumbnail' ),
		'show_in_rest' => true,
	) );
	register_taxonomy( 'estudia_area', array( 'estudia_curso', 'estudia_leccion' ), array(
		'label' => 'Áreas de estudio',
		'public' => false,
		'show_ui' => true,
		'show_in_rest' => true,
		'hierarchical' => true,
	) );
	register_post_meta( 'estudia_leccion', 'estudia_course_id', array(
		'type' => 'integer',
		'single' => true,
		'sanitize_callback' => 'absint',
	) );
} );

add_action( 'add_meta_boxes_estudia_leccion', static function () {
	add_meta_box( 'estudia_leccion_curso', 'Curso de la lección', static function ( $lesson ) {
		wp_nonce_field( 'estudia_lesson_course', 'estudia_course_nonce' );
		$selected = (int) get_post_meta( $lesson->ID, 'estudia_course_id', true );
		echo '<label for="estudia-course">Curso</label> <select id="estudia-course" name="estudia_course_id"><option value="0">Sin curso</option>';
		foreach ( get_posts( array( 'post_type' => 'estudia_curso', 'post_status' => array( 'publish', 'draft' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $course ) {
			echo '<option value="' . esc_attr( $course->ID ) . '"' . selected( $selected, $course->ID, false ) . '>' . esc_html( $course->post_title ) . '</option>';
		}
		echo '</select>';
	} );
} );

add_action( 'save_post_estudia_leccion', static function ( $id ) {
	if ( ! isset( $_POST['estudia_course_nonce'], $_POST['estudia_course_id'] ) || wp_is_post_revision( $id ) ||
		! current_user_can( 'edit_post', $id ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['estudia_course_nonce'] ) ), 'estudia_lesson_course' ) ) {
		return;
	}
	$course = absint( $_POST['estudia_course_id'] );
	if ( ! $course || 'estudia_curso' === get_post_type( $course ) ) {
		update_post_meta( $id, 'estudia_course_id', $course );
	}
} );

function estudia_academy_catalog() {
	$courses = get_posts( array( 'post_type' => 'estudia_curso', 'post_status' => 'publish', 'numberposts' => -1 ) );
	?>
	<section id="cursos" class="wrap section">
		<header class="section-heading"><h2>EstudiaMed Academy</h2><p>Estudia conceptos médicos y vuelve a practicar lo que necesitas reforzar.</p></header>
		<div class="cards academy-cards">
			<?php foreach ( $courses as $course ) : ?>
				<article class="card"><h3><a href="<?php echo esc_url( get_permalink( $course ) ); ?>"><?php echo esc_html( get_the_title( $course ) ); ?></a></h3>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $course ), 24 ) ); ?></p></article>
			<?php endforeach; ?>
			<article class="card"><h3>Lecturas de la biblioteca</h3><p>Consulta las fichas ampliadas con referencias médicas.</p>
				<a href="<?php echo esc_url( home_url( '/#anatomia-anatomia-del-torax' ) ); ?>">Anatomía del tórax</a><br>
				<a href="<?php echo esc_url( home_url( '/#cirugia-obstruccion-intestinal-mecanica' ) ); ?>">Obstrucción intestinal mecánica</a>
			</article>
		</div>
	</section>
	<?php
}

function estudia_academy_progress_key( $id ) {
	return '_estudia_lesson_completed_' . absint( $id );
}

add_action( 'admin_post_estudia_lesson_progress', static function () {
	$id = absint( $_POST['lesson'] ?? 0 );
	check_admin_referer( 'estudia_lesson_' . $id );
	if ( 'estudia_leccion' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) || post_password_required( $id ) ) {
		wp_die( 'Lección no disponible.', '', array( 'response' => 404 ) );
	}
	$key = estudia_academy_progress_key( $id );
	if ( 'complete' === ( $_POST['progress'] ?? '' ) ) {
		update_user_meta( get_current_user_id(), $key, time() );
	} else {
		delete_user_meta( get_current_user_id(), $key );
	}
	wp_safe_redirect( get_permalink( $id ), 303 );
	exit;
} );

add_filter( 'the_content', static function ( $content ) {
	if ( ! is_singular( array( 'estudia_curso', 'estudia_leccion' ) ) || ! in_the_loop() || ! is_main_query() || post_password_required() ) {
		return $content;
	}
	$id = get_the_ID();
	if ( 'estudia_curso' === get_post_type( $id ) ) {
		$lessons = get_posts( array( 'post_type' => 'estudia_leccion', 'post_status' => 'publish', 'numberposts' => -1, 'has_password' => false,
			'meta_key' => 'estudia_course_id', 'meta_value' => $id, 'orderby' => 'ID', 'order' => 'ASC' ) );
		$list = '';
		foreach ( $lessons as $lesson ) {
			$done = is_user_logged_in() && get_user_meta( get_current_user_id(), estudia_academy_progress_key( $lesson->ID ), true );
			$list .= '<li><a href="' . esc_url( get_permalink( $lesson ) ) . '">' . esc_html( get_the_title( $lesson ) ) . '</a>' . ( $done ? ' · Completada' : '' ) . '</li>';
		}
		return $content . ( $list ? '<h2>Lecciones</h2><ol>' . $list . '</ol>' : '<p>Este curso aún no tiene lecciones publicadas.</p>' );
	}
	if ( ! is_user_logged_in() || 'publish' !== get_post_status( $id ) ) {
		return $content;
	}
	$done = get_user_meta( get_current_user_id(), estudia_academy_progress_key( $id ), true );
	return $content . '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">' .
		wp_nonce_field( 'estudia_lesson_' . $id, '_wpnonce', true, false ) .
		'<input type="hidden" name="action" value="estudia_lesson_progress"><input type="hidden" name="lesson" value="' . esc_attr( $id ) .
		'"><button class="btn" name="progress" value="' . ( $done ? 'undo' : 'complete' ) . '">' .
		( $done ? 'Quitar marca de completada' : 'Marcar lección como completada' ) . '</button></form>';
} );

function estudia_academy_recommendation( $subject ) {
	$slug = sanitize_title( $subject );
	$lessons = get_posts( array(
		'post_type' => 'estudia_leccion', 'post_status' => 'publish', 'numberposts' => 1, 'has_password' => false,
		'tax_query' => array( array( 'taxonomy' => 'estudia_area', 'field' => 'slug', 'terms' => $slug ) ),
	) );
	if ( $lessons ) {
		return array( 'label' => get_the_title( $lessons[0] ), 'url' => get_permalink( $lessons[0] ) );
	}
	$readings = array(
		'anatomia' => array( 'Anatomía del tórax', 'anatomia-anatomia-del-torax' ),
		'obstruccion-intestinal-mecanica' => array( 'Obstrucción intestinal mecánica', 'cirugia-obstruccion-intestinal-mecanica' ),
		'cirugia' => array( 'Obstrucción intestinal mecánica', 'cirugia-obstruccion-intestinal-mecanica' ),
		'cirugia-general' => array( 'Obstrucción intestinal mecánica', 'cirugia-obstruccion-intestinal-mecanica' ),
	);
	if ( isset( $readings[ $slug ] ) && 'estudiamed' === get_template() ) {
		list( $label, $anchor ) = $readings[ $slug ];
		return array( 'label' => $label, 'url' => home_url( '/#' . $anchor ) );
	}
	return null;
}

function estudia_academy_panel() {
	$completed = array();
	foreach ( get_user_meta( get_current_user_id() ) as $key => $values ) {
		if ( preg_match( '/^_estudia_lesson_completed_([1-9][0-9]*)$/D', $key, $match ) ) {
			$completed[ (int) $match[1] ] = (int) $values[0];
		}
	}
	arsort( $completed );
	$lessons = $completed ? get_posts( array(
		'post_type' => 'estudia_leccion', 'post_status' => 'publish', 'has_password' => false,
		'numberposts' => 5, 'post__in' => array_keys( $completed ), 'orderby' => 'post__in',
	) ) : array();
	echo '<section class="panel"><h2>Mi avance en Academy</h2>';
	if ( $lessons ) {
		echo '<h3>Últimas lecciones completadas</h3><ul>';
		foreach ( $lessons as $lesson ) {
			echo '<li><a href="' . esc_url( get_permalink( $lesson ) ) . '">' . esc_html( get_the_title( $lesson ) ) . '</a></li>';
		}
		echo '</ul>';
	} else {
		echo '<p>Tus lecciones completadas aparecerán aquí.</p>';
	}
	echo '<a href="' . esc_url( home_url( '/#cursos' ) ) . '">Explorar cursos</a></section>';
}
