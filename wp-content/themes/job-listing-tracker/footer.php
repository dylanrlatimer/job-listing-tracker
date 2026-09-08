<footer id="site-footer" role="contentinfo">
	<div class="wrap site-footer__inner">
		<div class="site-footer__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<p>
				<?php
				printf(
					/* translators: %s: author name linked to GitHub */
					esc_html__( 'An internal tool built by %s to demo some WordPress code.', 'job-listing-tracker' ),
					'<a href="' . esc_url( 'https://github.com/dylanrlatimer' ) . '">' . esc_html__( 'Dylan Latimer', 'job-listing-tracker' ) . '</a>'
				);
				?>
			</p>
		</div>
		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'job-listing-tracker' ); ?>">
			<ul>
				<?php
				$companies = get_post_type_archive_link( 'jlt_company' );
				if ( $companies ) :
					?>
					<li>
						<a href="<?php echo esc_url( $companies ); ?>">
							<?php esc_html_e( 'Companies', 'job-listing-tracker' ); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php if ( is_user_logged_in() ) : ?>
					<li>
						<a href="<?php echo esc_url( jlt_bank_url() ); ?>">
							<?php esc_html_e( 'My Bank', 'job-listing-tracker' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( jlt_settings_url() ); ?>">
							<?php esc_html_e( 'Settings', 'job-listing-tracker' ); ?>
						</a>
					</li>
				<?php else : ?>
					<li>
						<a href="<?php echo esc_url( wp_login_url( home_url( '/' ) ) ); ?>">
							<?php esc_html_e( 'Log in', 'job-listing-tracker' ); ?>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		</nav>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
