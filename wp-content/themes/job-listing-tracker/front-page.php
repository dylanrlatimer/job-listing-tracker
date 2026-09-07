<?php get_header(); ?>
<main id="main" class="wrap">
	<header class="page-header">
		<h1><?php bloginfo( 'name' ); ?></h1>
	</header>

	<div class="home-copy">
		<p>
			<?php
			printf(
				/* translators: %s: author name linked to GitHub */
				esc_html__( 'This is an internal tool built by %s to demo some WordPress code.', 'job-listing-tracker' ),
				'<a href="' . esc_url( 'https://github.com/dylanrlatimer' ) . '">' . esc_html__( 'Dylan Latimer', 'job-listing-tracker' ) . '</a>'
			);
			?>
		</p>
		<p>
			<?php esc_html_e( 'This is an admin-managed directory for job listings I\'m interested in as a developer, in my local area. This is not really useful to anyone but myself, but if you want, any user can make an account and add listings to their internal \'bank\' where you can keep track of your status with that company, personal notes, progress, anything like that. It\'s an organizational tool.', 'job-listing-tracker' ); ?>
		</p>
	</div>

	<p class="home-actions">
		<?php
		$companies = get_post_type_archive_link( 'jlt_company' );
		if ( $companies ) :
			?>
			<a class="btn btn--primary" href="<?php echo esc_url( $companies ); ?>">
				<?php esc_html_e( 'Browse companies', 'job-listing-tracker' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( is_user_logged_in() ) : ?>
			<a class="btn" href="<?php echo esc_url( jlt_bank_url() ); ?>">
				<?php esc_html_e( 'Open my bank', 'job-listing-tracker' ); ?>
			</a>
		<?php else : ?>
			<a href="<?php echo esc_url( wp_registration_url() ); ?>">
				<?php esc_html_e( 'Register', 'job-listing-tracker' ); ?>
			</a>
		<?php endif; ?>
	</p>
</main>
<?php get_footer(); ?>
