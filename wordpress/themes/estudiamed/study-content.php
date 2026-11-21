<section class="hero" aria-labelledby="intro">
  <div class="wrap hero-layout">
    <div>
      <p class="eyebrow"><?php echo esc_html( estudiamed_translate( 'Preparação para exames médicos', 'Preparación para exámenes médicos' ) ); ?></p>
      <h1 id="intro"><?php echo esc_html( estudiamed_translate( 'Estude para o', 'Estudia para el' ) ); ?> <span>Revalida.</span></h1>
      <p><?php echo esc_html( estudiamed_translate( 'Pratique por especialidade, revise as explicações e acompanhe seu desempenho em cada sessão.', 'Practica por especialidad, revisa las explicaciones y sigue tu rendimiento en cada sesión.' ) ); ?></p>
      <div class="actions">
        <a class="btn btn-light" href="#praticar"><?php echo esc_html( estudiamed_translate( 'Começar a estudar', 'Empezar a estudiar' ) ); ?></a>
        <a class="btn btn-outline" href="#exames"><?php echo esc_html( estudiamed_translate( 'Ver exames', 'Ver exámenes' ) ); ?></a>
      </div>
    </div>
    <a href="#praticar" class="preview">
      <span class="pill"><?php echo esc_html( estudiamed_translate( 'Clínica Médica', 'Medicina interna' ) ); ?></span>
      <h2><?php echo esc_html( estudiamed_translate( 'Como você prefere estudar hoje?', '¿Cómo prefieres estudiar hoy?' ) ); ?></h2>
      <span class="sample"><?php echo esc_html( estudiamed_translate( 'Treino por especialidade', 'Práctica por especialidad' ) ); ?></span>
      <span class="sample"><?php echo esc_html( estudiamed_translate( 'Simulado com tempo', 'Simulacro cronometrado' ) ); ?></span>
      <span class="sample"><?php echo esc_html( estudiamed_translate( 'Revisão com gabarito comentado', 'Revisión con respuestas explicadas' ) ); ?></span>
    </a>
  </div>
</section>
<section id="exames" class="section wrap">
  <header><h2><?php echo esc_html( estudiamed_translate( 'Catálogo de exames', 'Catálogo de exámenes' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Consulte os exames e veja quais têm questões disponíveis.', 'Consulta los exámenes y comprueba cuáles tienen preguntas disponibles.' ) ); ?></p></header>
  <div id="catalogo" class="cards"></div>
  <?php if ( function_exists( 'estudiamed_ehep_url' ) && estudiamed_ehep_url() ) : ?>
    <p><a class="btn" href="<?php echo esc_url( estudiamed_ehep_url() ); ?>">EHEP Ecuador</a></p>
  <?php endif; ?>
</section>
<section id="praticar" class="section section-alt">
  <div class="wrap">
    <header><h2><?php echo esc_html( estudiamed_translate( 'Área de estudo', 'Área de estudio' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Selecione uma especialidade para treinar ou faça um simulado cronometrado com todas as questões do catálogo.', 'Selecciona una especialidad para practicar o realiza un simulacro cronometrado con todas las preguntas del catálogo.' ) ); ?></p></header>
    <noscript><?php echo esc_html( estudiamed_translate( 'Ative o JavaScript para acessar a área de estudo.', 'Activa JavaScript para acceder al área de estudio.' ) ); ?></noscript>
    <div class="panel">
      <form id="configurar" class="filters">
        <label><?php echo esc_html( estudiamed_translate( 'Especialidade', 'Especialidad' ) ); ?> <select id="especialidade"><option value=""><?php echo esc_html( estudiamed_translate( 'Todas as especialidades', 'Todas las especialidades' ) ); ?></option></select></label>
        <button class="btn" name="modo" value="treino"><?php echo esc_html( estudiamed_translate( 'Iniciar treino', 'Iniciar práctica' ) ); ?></button>
        <button class="btn btn-outline" name="modo" value="simulado"><?php echo esc_html( estudiamed_translate( 'Iniciar simulado', 'Iniciar simulacro' ) ); ?></button>
      </form>
      <p id="disponibilidade" class="muted" role="status"></p>
      <div id="sessao" hidden></div>
    </div>
  </div>
</section>
<section id="progresso" class="section wrap">
  <header><h2><?php echo esc_html( estudiamed_translate( 'Meu progresso', 'Mi progreso' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Seu histórico de sessões concluídas neste navegador.', 'Tu historial de sesiones completadas en este navegador.' ) ); ?></p></header>
  <div id="metricas" class="metrics"></div>
</section>
