<?php get_header(); ?>
<main id="main" class="wrap">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="page-header">
					<h1><?php the_title(); ?></h1>
				</header>
				<div class="section"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No content found.', 'job-listing-tracker' ); ?></p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
