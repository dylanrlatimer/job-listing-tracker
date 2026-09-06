<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'jlt_theme_setup' );
add_action( 'wp_enqueue_scripts', 'jlt_enqueue_assets' );

/**
 * Register theme supports and the primary navigation menu.
 */
function jlt_theme_setup() {
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
	register_nav_menus(
		array(
			'primary' => __( 'Primary Navigation', 'job-listing-tracker' ),
		)
	);
}

/**
 * Enqueue the theme stylesheet.
 */
function jlt_enqueue_assets() {
	wp_enqueue_style(
		'jlt-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
