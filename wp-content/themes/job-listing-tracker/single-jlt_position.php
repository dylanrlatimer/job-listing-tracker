<?php get_header(); ?>
<main id="main">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$meta = jlt_get_position_meta( get_the_ID() );
		?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<h1><?php the_title(); ?></h1>

		<dl class="position-details">
			<dt><?php esc_html_e( 'Availability', 'job-listing-tracker' ); ?></dt>
			<dd class="position-availability--<?php echo esc_attr( $meta['availability'] ); ?>">
				<?php echo esc_html( ucfirst( $meta['availability'] ) ); ?>
			</dd>

			<?php if ( $meta['location'] ) : ?>
				<dt><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></dt>
				<dd><?php echo esc_html( $meta['location'] ); ?></dd>
			<?php endif; ?>

			<?php if ( $meta['tech_stack'] ) : ?>
				<dt><?php esc_html_e( 'Technology Stack', 'job-listing-tracker' ); ?></dt>
				<dd><?php echo nl2br( esc_html( $meta['tech_stack'] ) ); ?></dd>
			<?php endif; ?>

			<?php
			$company = get_post( $meta['company_id'] );
			if ( $company ) :
				?>
				<dt><?php esc_html_e( 'Company', 'job-listing-tracker' ); ?></dt>
				<dd>
					<a href="<?php echo esc_url( get_permalink( $company ) ); ?>">
						<?php echo esc_html( $company->post_title ); ?>
					</a>
				</dd>
			<?php endif; ?>
		</dl>

		<?php if ( get_the_content() ) : ?>
			<div class="position-description"><?php the_content(); ?></div>
		<?php endif; ?>

		<?php if ( $meta['source_url'] ) : ?>
			<p class="position-source">
				<a href="<?php echo esc_url( $meta['source_url'] ); ?>"
				   target="_blank"
				   rel="noopener noreferrer">
					<?php esc_html_e( 'View Original Listing', 'job-listing-tracker' ); ?>
				</a>
			</p>
		<?php endif; ?>

	</article>

		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
