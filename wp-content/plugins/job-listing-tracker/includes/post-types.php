<?php
defined( 'ABSPATH' ) || exit;

/**
 * Returns the allowed availability values for shared positions.
 *
 * @return string[]
 */
function jlt_availability_values() {
	return array( 'open', 'closed', 'unknown' );
}

/**
 * Returns the allowed status values for company entries.
 *
 * @return string[]
 */
function jlt_company_status_values() {
	return array( 'interested', 'contacted', 'not_pursuing' );
}

/**
 * Returns the primitive capabilities for company management.
 *
 * @return string[]
 */
function jlt_company_primitive_caps() {
	return array(
		'edit_jlt_companies',
		'edit_others_jlt_companies',
		'publish_jlt_companies',
		'read_private_jlt_companies',
		'delete_jlt_companies',
		'delete_private_jlt_companies',
		'delete_published_jlt_companies',
		'delete_others_jlt_companies',
		'edit_private_jlt_companies',
		'edit_published_jlt_companies',
	);
}

/**
 * Returns the primitive capabilities for position management.
 *
 * @return string[]
 */
function jlt_position_primitive_caps() {
	return array(
		'edit_jlt_positions',
		'edit_others_jlt_positions',
		'publish_jlt_positions',
		'read_private_jlt_positions',
		'delete_jlt_positions',
		'delete_private_jlt_positions',
		'delete_published_jlt_positions',
		'delete_others_jlt_positions',
		'edit_private_jlt_positions',
		'edit_published_jlt_positions',
	);
}

add_action( 'init', 'jlt_register_post_types' );

/**
 * Registers the shared company and position post types.
 */
function jlt_register_post_types() {
	register_post_type(
		'jlt_company',
		array(
			'labels'          => array(
				'name'               => __( 'Companies', 'job-listing-tracker' ),
				'singular_name'      => __( 'Company', 'job-listing-tracker' ),
				'add_new_item'       => __( 'Add New Company', 'job-listing-tracker' ),
				'edit_item'          => __( 'Edit Company', 'job-listing-tracker' ),
				'new_item'           => __( 'New Company', 'job-listing-tracker' ),
				'view_item'          => __( 'View Company', 'job-listing-tracker' ),
				'search_items'       => __( 'Search Companies', 'job-listing-tracker' ),
				'not_found'          => __( 'No companies found.', 'job-listing-tracker' ),
				'not_found_in_trash' => __( 'No companies found in Trash.', 'job-listing-tracker' ),
			),
			'public'          => true,
			'has_archive'     => true,
			'show_in_rest'    => true,
			'rewrite'         => array( 'slug' => 'companies' ),
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'capability_type' => array( 'jlt_company', 'jlt_companies' ),
			'map_meta_cap'    => true,
			'menu_icon'       => 'dashicons-building',
		)
	);

	register_post_type(
		'jlt_position',
		array(
			'labels'          => array(
				'name'               => __( 'Positions', 'job-listing-tracker' ),
				'singular_name'      => __( 'Position', 'job-listing-tracker' ),
				'add_new_item'       => __( 'Add New Position', 'job-listing-tracker' ),
				'edit_item'          => __( 'Edit Position', 'job-listing-tracker' ),
				'new_item'           => __( 'New Position', 'job-listing-tracker' ),
				'view_item'          => __( 'View Position', 'job-listing-tracker' ),
				'search_items'       => __( 'Search Positions', 'job-listing-tracker' ),
				'not_found'          => __( 'No positions found.', 'job-listing-tracker' ),
				'not_found_in_trash' => __( 'No positions found in Trash.', 'job-listing-tracker' ),
			),
			'public'          => true,
			'has_archive'     => false,
			'show_in_rest'    => true,
			'rewrite'         => array( 'slug' => 'positions' ),
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => array( 'jlt_position', 'jlt_positions' ),
			'map_meta_cap'    => true,
			'menu_icon'       => 'dashicons-portfolio',
		)
	);

	register_post_type(
		'jlt_company_entry',
		array(
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_rest'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'delete_with_user'    => true,
			'supports'            => array(),
		)
	);

	register_post_type(
		'jlt_position_entry',
		array(
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_rest'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'delete_with_user'    => true,
			'supports'            => array(),
		)
	);
}
