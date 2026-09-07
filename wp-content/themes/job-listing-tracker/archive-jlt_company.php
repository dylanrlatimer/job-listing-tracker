<?php get_header(); ?>
<main id="main" class="wrap">

	<header class="page-header">
		<h1><?php post_type_archive_title(); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<ul class="company-list">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/company-card' );
			endwhile;
			?>
		</ul>
		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 2,
				'end_size'  => 1,
				'prev_text' => __( 'Previous', 'job-listing-tracker' ),
				'next_text' => __( 'Next', 'job-listing-tracker' ),
			)
		);
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'No companies found.', 'job-listing-tracker' ); ?></p>
	<?php endif; ?>

</main>
<?php get_footer(); ?>
