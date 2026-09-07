<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="skip-link screen-reader-text" href="#main">
	<?php esc_html_e( 'Skip to content', 'job-listing-tracker' ); ?>
</a>
<?php wp_body_open(); ?>
<header id="site-header" role="banner">
	<div class="wrap site-header__inner">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		</div>
		<nav id="site-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary', 'job-listing-tracker' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'fallback_cb'    => 'jlt_primary_menu_fallback',
				)
			);
			?>
		</nav>
		<div class="site-auth-links">
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( jlt_bank_url() ); ?>">
					<?php esc_html_e( 'My Bank', 'job-listing-tracker' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					<?php esc_html_e( 'Log out', 'job-listing-tracker' ); ?>
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
					<?php esc_html_e( 'Log in', 'job-listing-tracker' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
