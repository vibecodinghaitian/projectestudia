<?php $ehep_url = function_exists( 'estudiamed_ehep_url' ) ? estudiamed_ehep_url() : ''; ?>
<div class="landing-top wrap">
  <?php if ( function_exists( 'estudia_campus_announcements' ) ) { estudia_campus_announcements( 'home' ); } ?>
  <section class="hero" aria-labelledby="intro">
    <div class="hero-copy">
      <h1 id="intro">Estudia medicina.<br><span>Prepárate para tu examen.</span></h1>
      <p>Aprende por especialidad y practica para EHEP Ecuador o Revalida Brasil.</p>
      <div class="actions">
        <a class="btn btn-light" href="#exames">Elegir examen</a>
        <?php if ( function_exists( 'estudia_academy_catalog' ) ) : ?>
          <a class="btn btn-outline" href="#cursos">Explorar cursos</a>
        <?php else : ?>
          <a class="btn btn-outline" href="#temas">Explorar especialidades</a>
        <?php endif; ?>
      </div>
    </div>
    <img class="hero-visual" src="<?php echo esc_url( get_template_directory_uri() . '/assets/medical-lab.webp' ); ?>" alt="" fetchpriority="high">
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
<?php if ( function_exists( 'estudia_academy_catalog' ) ) : ?>
  <?php estudia_academy_catalog(); ?>
<?php endif; ?>
<?php get_template_part( 'topics' ); ?>
<?php if ( shortcode_exists( 'estudiamed_quiz' ) ) : ?>
<section id="quiz-general" class="section wrap">
  <h2><?php echo esc_html( estudiamed_translate( 'Quiz Geral', 'Quiz General' ) ); ?></h2>
  <?php echo do_shortcode( '[estudiamed_quiz]' ); ?>
</section>
<?php endif; ?>
<section id="praticar" class="section section-alt">
  <div class="wrap">
    <header><h2><?php echo esc_html( estudiamed_translate( 'Prática Revalida', 'Práctica Revalida' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Selecione uma especialidade para treinar ou faça um simulado cronometrado com todas as questões do catálogo.', 'Selecciona una especialidad para practicar o realiza un simulacro cronometrado con todas las preguntas del catálogo.' ) ); ?></p></header>
    <p><a href="https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/revalida">Información oficial del Inep</a></p>
    <noscript><?php echo esc_html( estudiamed_translate( 'Ative o JavaScript para acessar a área de estudo.', 'Activa JavaScript para acceder al área de estudio.' ) ); ?></noscript>
    <div class="panel">
      <form id="configurar" class="filters">
        <label>Idioma del contenido <select id="revalida-language"><option value="es">Español</option><option value="pt-BR">Português (Brasil)</option></select></label>
        <label><?php echo esc_html( estudiamed_translate( 'Especialidade', 'Especialidad' ) ); ?> <select id="especialidade"><option value=""><?php echo esc_html( estudiamed_translate( 'Todas as especialidades', 'Todas las especialidades' ) ); ?></option></select></label>
        <button class="btn" name="modo" value="treino"><?php echo esc_html( estudiamed_translate( 'Iniciar treino', 'Iniciar práctica' ) ); ?></button>
        <button class="btn btn-outline" name="modo" value="simulado"><?php echo esc_html( estudiamed_translate( 'Iniciar simulado', 'Iniciar simulacro' ) ); ?></button>
      </form>
      <p class="muted">Preguntas de muestra. El historial se guarda en este navegador.</p>
      <p id="disponibilidade" class="muted" role="status"></p>
      <div id="sessao" hidden></div>
    </div>
  </div>
</section>
<section id="progresso" class="section wrap">
  <header><h2><?php echo esc_html( estudiamed_translate( 'Meu progresso no Revalida', 'Mi progreso en Revalida' ) ); ?></h2><p><?php echo esc_html( estudiamed_translate( 'Seu histórico de sessões concluídas neste navegador.', 'Tu historial de sesiones completadas en este navegador.' ) ); ?></p></header>
  <div id="metricas" class="metrics"></div>
</section>

<section id="casos-clinicos" class="section wrap">
  <h2>Casos clínicos y ejercicios</h2>
  <?php if ( shortcode_exists( 'estudiamed_quiz' ) ) : ?>
    <p>En el banco, selecciona «Caso clínico» para practicar con un escenario.</p>
    <p><a href="#quiz-general">Abrir banco de preguntas</a></p>
  <?php endif; ?>
  <details class="panel">
    <summary>Un estudio sigue a 200 personas inicialmente sanas. En un año aparecen 20 casos. ¿Cuál es el riesgo acumulado?</summary>
    <p><strong>Respuesta: 10 %.</strong> Se divide el número de casos nuevos (20) por las personas en riesgo al inicio (200). El resultado, 0,10, equivale al 10 %.</p>
    <p>Muestra original de EstudiaMed.</p>
  </details>
</section>
<?php if ( function_exists( 'estudia_campus_posts' ) ) : ?>
<section class="section wrap">
  <h2>Artículos recientes</h2>
  <?php estudia_campus_posts( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 3 ) ) ); ?>
  <p><a href="<?php echo esc_url( estudia_campus_url( 'blog' ) ); ?>">Ver todos los artículos</a></p>
</section>
<?php endif; ?>
