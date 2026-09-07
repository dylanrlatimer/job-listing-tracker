<?php get_header(); ?>
<main id="main" class="wrap">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$meta           = jlt_get_position_meta( get_the_ID() );
		$company        = $meta['company_id'] ? get_post( $meta['company_id'] ) : null;
		$company_entry  = is_user_logged_in()
			? jlt_get_company_entry( get_current_user_id(), $meta['company_id'] )
			: null;
		$position_entry = $company_entry
			? jlt_get_position_entry( get_current_user_id(), get_the_ID() )
			: null;
		?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<header class="page-header">
			<div class="page-header__main">
				<h1><?php the_title(); ?></h1>
			</div>
			<div class="page-header__action">
				<?php if ( ! is_user_logged_in() ) : ?>
					<a class="btn btn--primary" href="<?php echo esc_url( jlt_login_url( get_permalink() ) ); ?>">
						<?php esc_html_e( 'Log in to track', 'job-listing-tracker' ); ?>
					</a>
				<?php elseif ( ! $company_entry ) : ?>
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
					<a class="btn" href="<?php echo esc_url( jlt_bank_entry_url( 'position', $position_entry->ID ) ); ?>">
						<?php esc_html_e( 'Tracking this position', 'job-listing-tracker' ); ?>
					</a>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_track_position' ) ); ?>">
						<?php wp_nonce_field( 'jlt_track_position', 'jlt_nonce' ); ?>
						<input type="hidden" name="position_id" value="<?php echo absint( get_the_ID() ); ?>">
						<button class="btn btn--primary" type="submit">
							<?php esc_html_e( 'Track this position', 'job-listing-tracker' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</div>
		</header>

		<?php get_template_part( 'template-parts/position-facts', null, array( 'post_id' => get_the_ID() ) ); ?>

		<?php if ( get_the_content() ) : ?>
			<div class="section"><?php the_content(); ?></div>
		<?php endif; ?>

	</article>

		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
