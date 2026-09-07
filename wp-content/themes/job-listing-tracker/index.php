<?php get_header(); ?>
<main id="main">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No content found.', 'job-listing-tracker' ); ?></p>
	<?php endif; ?>

	<?php
	$companies = get_post_type_archive_link( 'jlt_company' );
	if ( $companies ) :
		?>
		<p>
			<a href="<?php echo esc_url( $companies ); ?>">
				<?php esc_html_e( 'Browse the company directory', 'job-listing-tracker' ); ?>
			</a>
		</p>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
