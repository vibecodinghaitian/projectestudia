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
    <nav aria-label="<?php echo esc_html( estudiamed_translate( 'Menu principal', 'Menú principal' ) ); ?>">
      <a href="<?php echo esc_url( estudiamed_home( '#exames' ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Exames', 'Exámenes' ) ); ?></a>
      <a href="<?php echo esc_url( estudiamed_home( '#praticar' ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Praticar', 'Practicar' ) ); ?></a>
      <a href="<?php echo esc_url( estudiamed_home( '#progresso' ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Progresso', 'Progreso' ) ); ?></a>
    </nav>
    <div class="header-tools">
      <nav class="languages" aria-label="<?php echo esc_attr( 'Idioma' ); ?>">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" lang="pt-BR" <?php if ( 'pt-BR' === estudiamed_language() ) { echo 'aria-current="true"'; } ?>>Português</a>
        <a href="<?php echo esc_url( add_query_arg( 'idioma', 'es', home_url( '/' ) ) ); ?>" lang="es" <?php if ( 'es' === estudiamed_language() ) { echo 'aria-current="true"'; } ?>>Español</a>
      </nav>
    <?php if ( is_user_logged_in() ) : ?>
      <a class="account" href="<?php echo esc_url( wp_logout_url( estudiamed_home() ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Sair', 'Salir' ) ); ?></a>
    <?php else : ?>
      <a class="account" href="<?php echo esc_url( wp_login_url( estudiamed_home() ) ); ?>"><?php echo esc_html( estudiamed_translate( 'Entrar', 'Iniciar sesión' ) ); ?></a>
    <?php endif; ?>
    </div>
  </div>
</header>
