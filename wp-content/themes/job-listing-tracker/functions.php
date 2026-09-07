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

add_filter( 'wp_nav_menu_items', 'jlt_prepend_companies_link', 10, 2 );

/**
 * Always include a Companies link at the start of the primary menu.
 *
 * @param string   $items Menu HTML.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @return string
 */
function jlt_prepend_companies_link( $items, $args ) {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	$archive = get_post_type_archive_link( 'jlt_company' );
	if ( ! $archive ) {
		return $items;
	}

	$link = sprintf(
		'<li class="menu-item"><a href="%s">%s</a></li>',
		esc_url( $archive ),
		esc_html__( 'Companies', 'job-listing-tracker' )
	);

	return $link . $items;
}

/**
 * Primary menu fallback when no menu is assigned.
 */
function jlt_primary_menu_fallback() {
	$archive = get_post_type_archive_link( 'jlt_company' );
	if ( ! $archive ) {
		return;
	}

	echo '<ul>';
	echo '<li class="menu-item"><a href="' . esc_url( $archive ) . '">';
	esc_html_e( 'Companies', 'job-listing-tracker' );
	echo '</a></li>';
	echo '</ul>';
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
