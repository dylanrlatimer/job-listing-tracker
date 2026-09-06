<?php get_header(); ?>
<main id="main">

	<h1><?php post_type_archive_title(); ?></h1>

	<?php if ( have_posts() ) : ?>
		<ul class="company-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/company-card' );
			endwhile;
			?>
		</ul>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No companies found.', 'job-listing-tracker' ); ?></p>
	<?php endif; ?>

</main>
<?php get_footer(); ?>
