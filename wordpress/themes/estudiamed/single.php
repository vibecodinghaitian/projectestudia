<?php get_header(); ?>
<main id="conteudo" class="wrap section">
<?php while ( have_posts() ) : the_post(); ?>
	<article class="panel campus-article">
		<?php if ( function_exists( 'estudia_campus_url' ) ) : ?><a href="<?php echo esc_url( estudia_campus_url( 'blog' ) ); ?>">Volver al blog</a><?php endif; ?>
		<h1><?php the_title(); ?></h1>
		<p><?php the_author(); ?> · <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
		<?php the_post_thumbnail( 'large' ); ?>
		<?php the_content(); ?>
		<?php if ( function_exists( 'estudia_campus_article_links' ) ) { estudia_campus_article_links( get_the_ID() ); } ?>
	</article>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
