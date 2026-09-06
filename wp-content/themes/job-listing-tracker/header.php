<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header id="site-header" role="banner">
	<div class="site-branding">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
	</div>
	<nav id="site-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary', 'job-listing-tracker' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>
</header>
