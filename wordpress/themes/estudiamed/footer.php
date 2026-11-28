<footer class="site-footer">
  <div class="wrap footer-row">
    <p><strong>EstudiaMed</strong> · <?php echo esc_html( estudiamed_translate( 'Preparação para exames médicos', 'Preparación para exámenes médicos' ) ); ?></p>
    <?php if ( function_exists( 'estudia_campus_url' ) ) : ?><a href="<?php echo esc_url( estudia_campus_url( 'blog' ) ); ?>">Blog</a><?php endif; ?>
    <?php if ( get_privacy_policy_url() ) : ?><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php echo esc_html( estudiamed_translate( 'Política de privacidade', 'Política de privacidad' ) ); ?></a><?php endif; ?>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
