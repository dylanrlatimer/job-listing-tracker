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

		<section class="bank-action" aria-label="<?php esc_attr_e( 'Track', 'job-listing-tracker' ); ?>">
			<?php if ( ! is_user_logged_in() ) : ?>
				<p>
					<a href="<?php echo esc_url( jlt_login_url( get_permalink() ) ); ?>">
						<?php esc_html_e( 'Log in to track this position', 'job-listing-tracker' ); ?>
					</a>
				</p>
			<?php else : ?>
				<?php
				$company_entry  = jlt_get_company_entry( get_current_user_id(), $meta['company_id'] );
				$position_entry = $company_entry
					? jlt_get_position_entry( get_current_user_id(), get_the_ID() )
					: null;
				?>
				<?php if ( ! $company_entry ) : ?>
					<p>
						<?php
						$company_link = $company instanceof WP_Post
							? sprintf(
								'<a href="%s">%s</a>',
								esc_url( get_permalink( $company ) ),
								esc_html( $company->post_title )
							)
							: esc_html__( 'this company', 'job-listing-tracker' );
						printf(
							/* translators: %s: company name linked to the company page */
							esc_html__( 'Add %s to your bank before tracking this position.', 'job-listing-tracker' ),
							$company_link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						);
						?>
					</p>
				<?php elseif ( $position_entry ) : ?>
					<p>
						<a href="<?php echo esc_url( jlt_bank_entry_url( 'position', $position_entry->ID ) ); ?>">
							<?php esc_html_e( 'Tracking this position', 'job-listing-tracker' ); ?>
						</a>
					</p>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_track_position' ) ); ?>">
						<?php wp_nonce_field( 'jlt_track_position', 'jlt_nonce' ); ?>
						<input type="hidden" name="position_id" value="<?php echo absint( get_the_ID() ); ?>">
						<button type="submit">
							<?php esc_html_e( 'Track this position', 'job-listing-tracker' ); ?>
						</button>
					</form>
				<?php endif; ?>
			<?php endif; ?>
		</section>

	</article>

		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
