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
    $expanded = function_exists( 'estudia_campus_topic_content' ) ? estudia_campus_topic_content() : array();
    $references = require __DIR__ . '/topic-expansions.php';
    foreach ( $subjects as $subject ) :
      ?>
      <section class="panel topic-subject">
        <h3><?php echo esc_html( $subject['subject'] ); ?></h3>
        <?php foreach ( $subject['topics'] as $topic ) :
          $topic_id = sanitize_title( $subject['subject'] . '-' . $topic['name'] );
          ?>
          <details class="topic-item" id="<?php echo esc_attr( $topic_id ); ?>">
            <summary><?php echo esc_html( $topic['name'] ); ?></summary>
            <p><?php echo esc_html( $topic['summary'] ); ?></p>
            <ul>
              <?php foreach ( $topic['concepts'] as $concept ) : ?>
                <li><?php echo esc_html( $concept ); ?></li>
              <?php endforeach; ?>
            </ul>
            <?php if ( isset( $expanded[ $topic_id ] ) ) { echo wp_kses_post( wpautop( $expanded[ $topic_id ] ) ); } ?>
            <?php if ( isset( $references[ $topic_id ] ) ) :
              $reference = $references[ $topic_id ];
              foreach ( $reference['sections'] as $heading => $paragraph ) :
                ?>
                <h4><?php echo esc_html( $heading ); ?></h4><p><?php echo esc_html( $paragraph ); ?></p>
              <?php endforeach; ?>
              <p><a href="<?php echo esc_url( $reference['url'] ); ?>"><?php echo esc_html( $reference['source'] ); ?></a></p>
              <?php if ( ! empty( $reference['practice'] ) && function_exists( 'estudiamed_ehep_url' ) && estudiamed_ehep_url() ) : ?>
                <p><a href="<?php echo esc_url( add_query_arg( 'tema', $reference['practice'], estudiamed_ehep_url() ) ); ?>">Practicar este tema en EHEP</a></p>
              <?php endif; ?>
            <?php elseif ( ! isset( $expanded[ $topic_id ] ) ) : ?>
              <p class="muted">Desarrollo y referencias pendientes de revisión editorial.</p>
            <?php endif; ?>
            <p><small>ID: <?php echo esc_html( $topic_id ); ?></small></p>
          </details>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
</section>
