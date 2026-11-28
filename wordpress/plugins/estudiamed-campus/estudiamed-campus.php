<?php
/**
 * Plugin Name: EstudiaMed Campus
 * Description: Acceso de estudiantes, blog y avisos del campus.
 * Version: 1.2.0
 */
defined( 'ABSPATH' ) || exit;

define( 'ESTUDIA_CAMPUS_FILE', __FILE__ );
require_once __DIR__ . '/editor.php';
require_once __DIR__ . '/academy.php';
require_once __DIR__ . '/welcome.php';
require_once __DIR__ . '/gemini.php';

function estudia_campus_url( $route ) {
	return home_url( '/' . trim( $route, '/' ) . '/' );
}

add_action( 'template_redirect', static function () {
	$path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
	$prefix = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' ) . '/';
	$route = 0 === strpos( $path, $prefix ) ? trim( substr( $path, strlen( $prefix ) ), '/' ) : '';
	if ( ! in_array( $route, array( 'blog', 'registro', 'ingresar', 'mi-panel', 'recuperar' ), true ) ) {
		return;
	}
	if ( 'mi-panel' === $route && ! is_user_logged_in() ) {
		wp_safe_redirect( estudia_campus_url( 'ingresar' ) );
		exit;
	}
	if ( in_array( $route, array( 'registro', 'ingresar' ), true ) && is_user_logged_in() ) {
		wp_safe_redirect( estudia_campus_url( 'mi-panel' ) );
		exit;
	}
	$message = '';
	$email = '';
	$name = '';
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && in_array( $route, array( 'registro', 'ingresar', 'recuperar' ), true ) ) {
		$nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name = sanitize_text_field( wp_unslash( $_POST['student_name'] ?? '' ) );
		list( $number, $consent, $invalid_number ) = estudia_welcome_form_values();
		$password = isset( $_POST['password'] ) && is_string( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'estudia_' . $route ) ) {
			$message = 'La sesión del formulario venció. Vuelve a intentarlo.';
		} elseif ( ! is_email( $email ) ) {
			$message = 'Introduce un correo válido.';
		} elseif ( 'recuperar' === $route ) {
			retrieve_password( $email );
			$message = 'Si existe una cuenta con ese correo, recibirás un enlace para restablecer la contraseña.';
		} elseif ( 'ingresar' === $route ) {
			$account = wp_signon( array( 'user_login' => $email, 'user_password' => $password, 'remember' => true ), is_ssl() );
			if ( ! is_wp_error( $account ) ) {
				wp_safe_redirect( estudia_campus_url( 'mi-panel' ) );
				exit;
			}
			$message = 'No se pudo iniciar sesión. Revisa el correo y la contraseña.';
		} elseif ( '' === $name || strlen( $name ) > 150 || strlen( $password ) < 12 || strlen( $password ) > 128 ) {
			$message = 'Introduce tu nombre y una contraseña de 12 a 128 caracteres.';
		} elseif ( $invalid_number ) {
			$message = 'Introduce un número de WhatsApp válido con código de país o deja el campo vacío y la casilla desmarcada.';
		} else {
			$account = wp_insert_user( array(
				'user_login' => 'estudiante-' . wp_generate_password( 15, false ), 'user_email' => $email,
				'user_pass' => $password, 'display_name' => $name, 'first_name' => $name, 'role' => 'subscriber',
			) );
			if ( ! is_wp_error( $account ) ) {
				estudia_welcome_save_consent( $account, $number, $consent );
				do_action( 'estudia_campus_account_activated', $account );
				wp_set_current_user( $account );
				wp_set_auth_cookie( $account, true, is_ssl() );
				wp_safe_redirect( estudia_campus_url( 'mi-panel' ) );
				exit;
			}
			$message = 'No se pudo crear la cuenta. Si ya tienes una, inicia sesión o recupera la contraseña.';
		}
	}
	global $wp_query;
	$wp_query->is_404 = false;
	if ( 'blog' !== $route ) {
		nocache_headers();
	}
	status_header( 200 );
	add_filter( 'pre_get_document_title', static function () use ( $route ) {
		$titles = array( 'blog' => 'Blog', 'registro' => 'Crear cuenta', 'ingresar' => 'Iniciar sesión', 'mi-panel' => 'Mi panel', 'recuperar' => 'Recuperar contraseña' );
		return $titles[ $route ] . ' · EstudiaMed';
	} );
	get_header();
	echo '<main id="conteudo" class="wrap section">';
	if ( 'blog' === $route ) {
		estudia_campus_blog();
	} elseif ( 'mi-panel' === $route ) {
		estudia_campus_panel();
	} else {
		estudia_campus_account_form( $route, $message, $email, $name );
	}
	echo '</main>';
	get_footer();
	exit;
}, 1 );

function estudia_campus_account_form( $route, $message, $email, $name ) {
	$titles = array( 'registro' => 'Crear cuenta', 'ingresar' => 'Iniciar sesión', 'recuperar' => 'Recuperar contraseña' );
	?>
	<div class="panel campus-account">
		<h1><?php echo esc_html( $titles[ $route ] ); ?></h1>
		<?php if ( $message ) : ?><p role="status"><?php echo esc_html( $message ); ?></p><?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'estudia_' . $route ); ?>
			<?php if ( 'registro' === $route ) : ?>
				<label>Nombre <input name="student_name" autocomplete="name" maxlength="150" required value="<?php echo esc_attr( $name ); ?>"></label>
			<?php endif; ?>
			<label>Correo electrónico <input type="email" name="email" autocomplete="email" required value="<?php echo esc_attr( $email ); ?>"></label>
			<?php if ( 'recuperar' !== $route ) : ?>
				<label>Contraseña <input type="password" name="password" required maxlength="128" <?php if ( 'registro' === $route ) : ?>minlength="12" autocomplete="new-password"<?php else : ?>autocomplete="current-password"<?php endif; ?>></label>
			<?php endif; ?>
			<?php if ( 'registro' === $route ) : ?>
				<?php estudia_welcome_fields( isset( $_POST['estudia_whatsapp'] ) && is_string( $_POST['estudia_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['estudia_whatsapp'] ) ) : '', '1' === ( $_POST['estudia_wa_consent'] ?? '' ) ); ?>
			<?php endif; ?>
			<button class="btn"><?php echo esc_html( 'recuperar' === $route ? 'Enviar enlace' : $titles[ $route ] ); ?></button>
		</form>
		<p><a href="<?php echo esc_url( estudia_campus_url( 'ingresar' === $route ? 'recuperar' : 'ingresar' ) ); ?>"><?php echo 'ingresar' === $route ? '¿Olvidaste tu contraseña?' : 'Iniciar sesión'; ?></a></p>
	</div>
	<?php
}

function estudia_campus_panel() {
	$ehep = function_exists( 'estudiamed_ehep_url' ) ? estudiamed_ehep_url() : '';
	$links = array( 'Cursos médicos' => home_url( '/#cursos' ), 'Temas' => home_url( '/#temas' ), 'Banco y práctica' => home_url( '/#quiz-general' ), 'Blog' => estudia_campus_url( 'blog' ) );
	if ( $ehep ) {
		$links = array( 'Continuar estudiando' => $ehep, 'Simulacros EHEP' => $ehep . '#ehep-config', 'Favoritas e historial' => $ehep . '#ehep-config' ) + $links;
	}
	echo '<h1>Mi panel</h1>';
	estudia_campus_announcements( 'panel' );
	echo '<div class="cards">';
	foreach ( $links as $label => $url ) {
		echo '<a class="card" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</div>';
	estudia_academy_panel();
	estudia_welcome_profile();
}

function estudia_campus_blog() {
	$term = sanitize_text_field( wp_unslash( $_GET['buscar'] ?? '' ) );
	$cat = absint( $_GET['categoria'] ?? 0 );
	$page = max( 1, absint( $_GET['pagina'] ?? 1 ) );
	$query = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish',
		's' => $term, 'cat' => $cat, 'posts_per_page' => 9, 'paged' => $page ) );
	?>
	<h1>Blog de EstudiaMed</h1>
	<form class="filters" method="get" action="<?php echo esc_url( estudia_campus_url( 'blog' ) ); ?>">
		<label>Buscar artículos <input type="search" name="buscar" value="<?php echo esc_attr( $term ); ?>"></label>
		<label>Categoría <?php wp_dropdown_categories( array( 'name' => 'categoria', 'show_option_all' => 'Todas', 'selected' => $cat ) ); ?></label>
		<button class="btn">Buscar</button>
	</form>
	<?php
	estudia_campus_posts( $query->posts );
	if ( $query->max_num_pages > 1 ) {
		echo wp_kses_post( paginate_links( array(
			'base' => add_query_arg( 'pagina', '%#%', estudia_campus_url( 'blog' ) ),
			'current' => $page, 'total' => $query->max_num_pages, 'add_args' => array( 'buscar' => $term, 'categoria' => $cat ),
		) ) );
	}
}

function estudia_campus_posts( $posts ) {
	if ( ! $posts ) {
		echo '<p>No se encontraron artículos publicados.</p>';
		return;
	}
	echo '<div class="cards">';
	foreach ( $posts as $post ) {
		echo '<article class="card"><h3><a href="' . esc_url( get_permalink( $post ) ) . '">' .
			esc_html( get_the_title( $post ) ) . '</a></h3><p>' . esc_html( get_the_date( '', $post ) ) . '</p><p>' .
			esc_html( wp_trim_words( get_the_excerpt( $post ), 21 ) ) . '</p></article>';
	}
	echo '</div>';
}
