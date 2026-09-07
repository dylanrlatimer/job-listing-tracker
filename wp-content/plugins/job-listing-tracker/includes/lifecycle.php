<?php
defined( 'ABSPATH' ) || exit;

/**
 * Permanently deletes position entries that reference a deleted position.
 *
 * @param int     $post_id Post ID being deleted.
 * @param WP_Post $post    Post object being deleted.
 */
function jlt_on_before_delete_position( $post_id, $post ) {
	if ( 'jlt_position' !== $post->post_type ) {
		return;
	}

	$entries = get_posts(
		array(
			'post_type'      => 'jlt_position_entry',
			'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'trash' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'jlt_position_id',
					'value' => $post_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $entries as $entry_id ) {
		wp_delete_post( $entry_id, true );
	}
}

/**
 * Permanently deletes a company's positions, then its company entries.
 *
 * Position deletion re-fires before_delete_post and cleans up position entries.
 *
 * @param int     $post_id Post ID being deleted.
 * @param WP_Post $post    Post object being deleted.
 */
function jlt_on_before_delete_company( $post_id, $post ) {
	if ( 'jlt_company' !== $post->post_type ) {
		return;
	}

	$positions = get_posts(
		array(
			'post_type'      => 'jlt_position',
			'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'trash' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'jlt_company_id',
					'value' => $post_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $positions as $position_id ) {
		wp_delete_post( $position_id, true );
	}

	$company_entries = get_posts(
		array(
			'post_type'      => 'jlt_company_entry',
			'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'trash' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'jlt_company_id',
					'value' => $post_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $company_entries as $entry_id ) {
		wp_delete_post( $entry_id, true );
	}
}

add_action( 'before_delete_post', 'jlt_on_before_delete_position', 10, 2 );
add_action( 'before_delete_post', 'jlt_on_before_delete_company', 10, 2 );
