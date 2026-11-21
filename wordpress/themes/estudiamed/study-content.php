<?php $ehep_url = function_exists( 'estudiamed_ehep_url' ) ? estudiamed_ehep_url() : ''; ?>
<div class="landing-top wrap">
  <section class="hero" aria-labelledby="intro">
    <div class="hero-copy">
      <p class="eyebrow"><?php echo esc_html( estudiamed_translate( 'Preparação para exames médicos', 'Preparación para exámenes médicos' ) ); ?></p>
      <h1 id="intro"><?php echo esc_html( estudiamed_translate( 'Estude medicina.', 'Estudia medicina.' ) ); ?><br><span><?php echo esc_html( estudiamed_translate( 'Prepare-se para a prova.', 'Prepárate para tu examen.' ) ); ?></span></h1>
      <p><?php echo esc_html( estudiamed_translate( 'Resolva questões, pratique com simulados e acompanhe sua evolução no EHEP Equador e no Revalida Brasil.', 'Resuelve preguntas, practica con simulacros y sigue tu progreso en el EHEP Ecuador y el Revalida Brasil.' ) ); ?></p>
      <div class="actions">
        <a class="btn btn-light" href="#exames"><?php echo esc_html( estudiamed_translate( 'Escolher exame', 'Elegir examen' ) ); ?></a>
        <a class="btn btn-outline" href="#praticar"><?php echo esc_html( estudiamed_translate( 'Começar a praticar', 'Empezar a practicar' ) ); ?></a>
      </div>
    </div>
  </section>
  <section id="exames" class="section">
    <header class="section-heading">
      <h2><?php echo esc_html( estudiamed_translate( 'Qual exame você vai prestar?', '¿Qué examen vas a rendir?' ) ); ?></h2>
      <p><?php echo esc_html( estudiamed_translate( 'Escolha o programa correspondente à sua prova.', 'Elige el programa que corresponde a tu examen.' ) ); ?></p>
    </header>
    <div class="exam-featured">
      <?php if ( $ehep_url ) : ?>
        <article class="exam-card exam-ehep">
          <span class="exam-country"><span class="country-code" aria-hidden="true">EC</span><?php echo esc_html( estudiamed_translate( 'Equador', 'Ecuador' ) ); ?></span>
          <h3>EHEP Ecuador</h3>
          <p><?php echo esc_html( estudiamed_translate( 'Banco de questões, simulados cronometrados e histórico de desempenho no seu perfil.', 'Banco de preguntas, simulacros cronometrados e historial de rendimiento en tu cuenta.' ) ); ?></p>
          <a class="btn" href="<?php echo esc_url( $ehep_url ); ?>"><?php echo esc_html( estudiamed_translate( 'Acessar EHEP', 'Entrar a EHEP' ) ); ?></a>
        </article>
      <?php endif; ?>
      <article class="exam-card exam-revalida">
        <span class="exam-country"><span class="country-code" aria-hidden="true">BR</span>Brasil</span>
        <h3>Revalida Brasil</h3>
        <p><?php echo esc_html( estudiamed_translate( 'Treine por especialidade e revise o gabarito comentado.', 'Practica por especialidad y revisa las respuestas comentadas.' ) ); ?></p>
        <a class="btn" href="#praticar"><?php echo esc_html( estudiamed_translate( 'Praticar Revalida', 'Practicar Revalida' ) ); ?></a>
      </article>
    </div>
    <details class="exam-other">
      <summary><?php echo esc_html( estudiamed_translate( 'Outros exames no catálogo', 'Otros exámenes del catálogo' ) ); ?></summary>
      <div id="catalogo" class="cards"></div>
    </details>
  </section>
</div>
<?php if ( shortcode_exists( 'estudiamed_quiz' ) ) : ?>
<section id="quiz-general" class="section wrap">
  <h2><?php echo esc_html( estudiamed_translate( 'Quiz Geral', 'Quiz General' ) ); ?></h2>
  <?php echo do_shortcode( '[estudiamed_quiz]' ); ?>
</section>
<?php endif; ?>
<section id="praticar" class="section section-alt">
  <div class="wrap">
    <header><h2><?php echo esc_html( estudiamed_translate( 'Prática Revalida', 'Práctica Revalida' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Selecione uma especialidade para treinar ou faça um simulado cronometrado com todas as questões do catálogo.', 'Selecciona una especialidad para practicar o realiza un simulacro cronometrado con todas las preguntas del catálogo.' ) ); ?></p></header>
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
  <header><h2><?php echo esc_html( estudiamed_translate( 'Meu progresso no Revalida', 'Mi progreso en Revalida' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Seu histórico de sessões concluídas neste navegador.', 'Tu historial de sesiones completadas en este navegador.' ) ); ?></p></header>
  <div id="metricas" class="metrics"></div>
</section>
