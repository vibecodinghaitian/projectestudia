<section id="temas" class="section wrap">
  <header class="section-heading">
    <h2>Biblioteca por temas</h2>
    <p>Explora las materias y los conceptos clave del material de estudio de EstudiaMed.</p>
  </header>
  <label class="topic-search">Buscar tema <input type="search" id="topic-query" placeholder="Por ejemplo, tórax"></label>
  <p id="topic-empty" role="status" hidden>No se encontraron temas.</p>
  <div class="topic-list" id="topic-list">
    <?php
    $subjects = json_decode( (string) file_get_contents( __DIR__ . '/assets/topics.json' ), true );
    foreach ( $subjects as $subject ) :
      ?>
      <section class="panel topic-subject">
        <h3><?php echo esc_html( $subject['subject'] ); ?></h3>
        <?php foreach ( $subject['topics'] as $topic ) : ?>
          <details class="topic-item">
            <summary><?php echo esc_html( $topic['name'] ); ?></summary>
            <p><?php echo esc_html( $topic['summary'] ); ?></p>
            <ul>
              <?php foreach ( $topic['concepts'] as $concept ) : ?>
                <li><?php echo esc_html( $concept ); ?></li>
              <?php endforeach; ?>
            </ul>
          </details>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
</section>
