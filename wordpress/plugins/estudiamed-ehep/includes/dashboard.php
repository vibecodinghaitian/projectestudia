<?php
defined( 'ABSPATH' ) || exit;

function estudiamed_ehep_url() {
	$page = get_page_by_path( 'ehep-ecuador' );
	return $page && 'publish' === $page->post_status && has_shortcode( $page->post_content, 'estudiamed_ehep' ) ? get_permalink( $page ) : '';
}

function estudiamed_ehep_dashboard() {
	if ( ! is_user_logged_in() ) {
		$login = wp_login_url( get_permalink() ?: home_url( '/' ) );
		return '<section lang="es"><h2>EHEP Ecuador · Medicina</h2><p>Inicia sesión para practicar y guardar tu progreso.</p><p><a href="' .
			esc_url( $login ) . '">Iniciar sesión</a> · <a href="https://www.caces.gob.ec/examen-de-habilitacion-para-el-ejercicio-profesional/">Información oficial de CACES</a></p></section>';
	}
	wp_enqueue_style( 'estudiamed-ehep', plugins_url( 'assets/ehep.css', ESTUDIAMED_EHEP_FILE ), array(), '1.3.0' );
	wp_enqueue_script( 'estudiamed-ehep', plugins_url( 'assets/ehep.js', ESTUDIAMED_EHEP_FILE ), array(), '1.3.0', true );
	ob_start();
	?>
	<section class="ehep" lang="es" data-ehep-editor="<?php echo current_user_can( 'edit_pages' ) ? '1' : '0'; ?>" data-ehep-gemini="<?php echo estudiamed_ehep_gemini_key() ? '1' : '0'; ?>" data-ehep-url="<?php echo esc_url( rest_url( 'estudiamed/v1/ehep' ) ); ?>" data-ehep-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
		<header class="ehep-hero">
			<p>EstudiaMed · Medicina</p>
			<h2>Examen de Ecuador (EHEP)</h2>
			<p>Practica por especialidad y revisa tu desempeño antes del Examen de Habilitación para el Ejercicio Profesional.</p>
		</header>
		<p>Consulta el reglamento, las convocatorias y los requisitos vigentes en <a href="https://www.caces.gob.ec/examen-de-habilitacion-para-el-ejercicio-profesional/">Información oficial de CACES</a>. Las preguntas y los simulacros de esta página son ejercicios educativos de EstudiaMed.</p>
		<p class="ehep-status" role="status"></p>
		<div class="ehep-dashboard" hidden>
			<p class="ehep-storage">Tus intentos, progreso y favoritos se guardan en tu cuenta.</p>
			<section class="ehep-card">
				<h3>Banco de preguntas y simulacros</h3>
				<form class="ehep-form">
					<label>Especialidad <select name="subject"><option value="">Todas las especialidades</option></select></label>
					<label>Tema <select name="topic"><option value="">Todos los temas</option></select></label>
					<label class="ehep-check"><input type="checkbox" name="favorites"> Solo favoritas</label>
					<label>Preguntas <input type="number" name="count" min="1" max="50" value="3" required></label>
					<label>Minutos del simulacro <input type="number" name="minutes" min="1" max="120" value="10" required></label>
					<div class="ehep-actions">
						<button type="submit" value="practice">Practicar</button>
						<button type="submit" value="mock">Iniciar simulacro</button>
					</div>
				</form>
				<p class="ehep-count"></p>
				<div class="ehep-resume"></div>
			</section>
			<div class="ehep-grid">
				<section class="ehep-card"><h3>Mi desempeño</h3><div class="ehep-stats"></div></section>
				<section class="ehep-card"><h3>Qué repasar</h3><div class="ehep-revision"></div></section>
			</div>
			<section class="ehep-card"><h3>Historial de intentos</h3><div class="ehep-history"></div></section>
		</div>
		<div class="ehep-session" hidden></div>
		<noscript>Activa JavaScript para iniciar una sesión de estudio.</noscript>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'estudiamed_ehep', 'estudiamed_ehep_dashboard' );
