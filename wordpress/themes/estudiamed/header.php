<!DOCTYPE html>
<html lang="<?php echo esc_attr( estudiamed_language() ); ?>" <?php if ( is_rtl() ) { echo 'dir="rtl"'; } ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#conteudo"><?php echo esc_html( estudiamed_translate( 'Pular para o conteúdo', 'Saltar al contenido' ) ); ?></a>
<header class="site-header">
  <div class="wrap top">
    <a class="brand" href="<?php echo esc_url( estudiamed_home() ); ?>"><span class="mark" aria-hidden="true">+</span>EstudiaMed</a>
    <button class="menu-toggle" type="button" aria-controls="primary-nav" aria-expanded="false" hidden>Menú</button>
    <nav id="primary-nav" aria-label="Menú principal">
      <a href="<?php echo esc_url( estudiamed_home( '#exames' ) ); ?>">Exámenes</a>
      <?php $ehep_menu_url = function_exists( 'estudiamed_ehep_url' ) ? estudiamed_ehep_url() : ''; ?>
      <?php if ( $ehep_menu_url ) : ?>
        <a href="<?php echo esc_url( $ehep_menu_url ); ?>"<?php if ( is_page( 'ehep-ecuador' ) ) : ?> aria-current="page"<?php endif; ?>>Examen de Ecuador (EHEP)</a>
      <?php endif; ?>
      <a href="<?php echo esc_url( estudiamed_home( shortcode_exists( 'estudiamed_quiz' ) ? '#quiz-general' : '#praticar' ) ); ?>">Banco de preguntas</a>
      <?php if ( function_exists( 'estudia_academy_catalog' ) ) : ?>
        <a href="<?php echo esc_url( estudiamed_home( '#cursos' ) ); ?>">Cursos</a>
      <?php endif; ?>
      <a href="<?php echo esc_url( estudiamed_home( '#temas' ) ); ?>">Especialidades</a>
      <a href="<?php echo esc_url( estudiamed_home( '#casos-clinicos' ) ); ?>">Casos clínicos</a>
    </nav>
    <div class="header-tools">
    <?php if ( function_exists( 'estudia_campus_url' ) ) : ?>
      <?php if ( is_user_logged_in() ) : ?>
        <a class="account" href="<?php echo esc_url( estudia_campus_url( 'mi-panel' ) ); ?>">Ir a mi panel</a>
        <a class="account" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Cerrar sesión</a>
      <?php else : ?>
        <a class="account" href="<?php echo esc_url( estudia_campus_url( 'registro' ) ); ?>">Crear cuenta</a>
        <a class="account" href="<?php echo esc_url( estudia_campus_url( 'ingresar' ) ); ?>">Iniciar sesión</a>
      <?php endif; ?>
    <?php elseif ( is_user_logged_in() ) : ?>
      <a class="account" href="<?php echo esc_url( wp_logout_url( estudiamed_home() ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Sair', 'Salir' ) ); ?></a>
    <?php else : ?>
      <a class="account" href="<?php echo esc_url( wp_login_url( estudiamed_home() ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Entrar', 'Iniciar sesión' ) ); ?></a>
    <?php endif; ?>
    </div>
  </div>
</header>
