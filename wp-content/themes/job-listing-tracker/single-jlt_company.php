<?php get_header(); ?>
<main id="main">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$meta      = jlt_get_company_meta( get_the_ID() );
		$positions = jlt_get_company_positions( get_the_ID() );
		?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="company-logo"><?php the_post_thumbnail( 'medium' ); ?></div>
		<?php endif; ?>

		<h1><?php the_title(); ?></h1>

		<dl class="company-details">
			<?php if ( $meta['type'] ) : ?>
				<dt><?php esc_html_e( 'Type', 'job-listing-tracker' ); ?></dt>
				<dd><?php echo esc_html( $meta['type'] ); ?></dd>
			<?php endif; ?>

			<?php if ( $meta['location'] ) : ?>
				<dt><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></dt>
				<dd><?php echo esc_html( $meta['location'] ); ?></dd>
			<?php endif; ?>

			<?php if ( $meta['address'] ) : ?>
				<dt><?php esc_html_e( 'Address', 'job-listing-tracker' ); ?></dt>
				<dd><?php echo esc_html( $meta['address'] ); ?></dd>
			<?php endif; ?>

			<?php if ( $meta['website'] ) : ?>
				<dt><?php esc_html_e( 'Website', 'job-listing-tracker' ); ?></dt>
				<dd>
					<a href="<?php echo esc_url( $meta['website'] ); ?>"
					   target="_blank"
					   rel="noopener noreferrer">
						<?php echo esc_html( $meta['website'] ); ?>
					</a>
				</dd>
			<?php endif; ?>

			<?php if ( $meta['tech_stack'] ) : ?>
				<dt><?php esc_html_e( 'Technology Stack', 'job-listing-tracker' ); ?></dt>
				<dd><?php echo nl2br( esc_html( $meta['tech_stack'] ) ); ?></dd>
			<?php endif; ?>
		</dl>

		<?php if ( get_the_content() ) : ?>
			<div class="company-description"><?php the_content(); ?></div>
		<?php endif; ?>

		<section class="company-positions" aria-label="<?php esc_attr_e( 'Positions', 'job-listing-tracker' ); ?>">
			<h2><?php esc_html_e( 'Positions', 'job-listing-tracker' ); ?></h2>
			<?php if ( $positions ) : ?>
				<ul class="position-list">
					<?php foreach ( $positions as $position ) : ?>
						<?php
						get_template_part(
							'template-parts/position-card',
							null,
							array( 'position' => $position )
						);
						?>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'No positions at this time.', 'job-listing-tracker' ); ?></p>
			<?php endif; ?>
		</section>

	</article>

		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
