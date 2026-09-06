<?php
defined( 'ABSPATH' ) || exit;

/**
 * Finds a company entry for one user and company.
 *
 * @param int $user_id    Owner user ID.
 * @param int $company_id Shared company post ID.
 * @return WP_Post|null
 */
function jlt_get_company_entry( $user_id, $company_id ) {
	$user_id    = absint( $user_id );
	$company_id = absint( $company_id );

	if ( ! $user_id || ! $company_id ) {
		return null;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'jlt_company_entry',
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => 'jlt_company_id',
					'value' => $company_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	return $posts ? $posts[0] : null;
}

/**
 * Lists the current user's company entries, newest first.
 *
 * @param int $user_id Owner user ID.
 * @return WP_Post[]
 */
function jlt_get_company_entries( $user_id ) {
	$user_id = absint( $user_id );

	if ( ! $user_id ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'jlt_company_entry',
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

/**
 * Creates a company entry or returns the existing one.
 *
 * @param int $user_id    Owner user ID.
 * @param int $company_id Shared company post ID.
 * @return WP_Post|WP_Error
 */
function jlt_add_company_to_bank( $user_id, $company_id ) {
	$user_id    = absint( $user_id );
	$company_id = absint( $company_id );

	if ( ! $user_id || ! $company_id ) {
		return new WP_Error( 'invalid_args' );
	}

	if ( ! jlt_company_is_published( $company_id ) ) {
		return new WP_Error( 'invalid_company' );
	}

	$existing = jlt_get_company_entry( $user_id, $company_id );
	if ( $existing instanceof WP_Post ) {
		return $existing;
	}

	$entry_id = wp_insert_post(
		array(
			'post_type'    => 'jlt_company_entry',
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_title'   => sprintf( 'User %d — Company %d', $user_id, $company_id ),
			'post_content' => '',
			'meta_input'   => array(
				'jlt_company_id' => $company_id,
				'jlt_status'     => 'interested',
			),
		),
		true
	);

	if ( is_wp_error( $entry_id ) ) {
		return $entry_id;
	}

	return get_post( $entry_id );
}

/**
 * Validates and updates company status and notes.
 *
 * @param int    $entry_id Entry post ID.
 * @param int    $user_id  Owner user ID.
 * @param string $status   Allowed company status.
 * @param string $notes    Unslashed plain-text notes.
 * @return WP_Post|WP_Error
 */
function jlt_update_company_entry( $entry_id, $user_id, $status, $notes ) {
	$entry_id = absint( $entry_id );
	$user_id  = absint( $user_id );
	$entry    = get_post( $entry_id );

	if (
		! ( $entry instanceof WP_Post )
		|| 'jlt_company_entry' !== $entry->post_type
		|| (int) $entry->post_author !== $user_id
	) {
		return new WP_Error( 'unauthorized' );
	}

	if ( ! in_array( $status, jlt_company_status_values(), true ) ) {
		return new WP_Error( 'invalid_status' );
	}

	$notes = sanitize_textarea_field( $notes );

	$updated = wp_update_post(
		array(
			'ID'           => $entry_id,
			'post_content' => $notes,
		),
		true
	);

	if ( is_wp_error( $updated ) ) {
		return $updated;
	}

	update_post_meta( $entry_id, 'jlt_status', $status );

	return get_post( $entry_id );
}

/**
 * Returns true when the user has a position entry at the given company.
 *
 * @param int $company_id Shared company post ID.
 * @param int $user_id    Owner user ID.
 * @return bool
 */
function jlt_has_tracked_positions_at_company( $company_id, $user_id ) {
	$company_id = absint( $company_id );
	$user_id    = absint( $user_id );

	if ( ! $company_id || ! $user_id ) {
		return false;
	}

	$position_ids = get_posts(
		array(
			'post_type'      => 'jlt_position',
			'post_status'    => array( 'publish', 'pending', 'draft', 'private', 'trash' ),
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => 'jlt_company_id',
					'value' => $company_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	if ( ! $position_ids ) {
		return false;
	}

	foreach ( $position_ids as $position_id ) {
		$entries = get_posts(
			array(
				'post_type'      => 'jlt_position_entry',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => 'jlt_position_id',
						'value' => $position_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		if ( $entries ) {
			return true;
		}
	}

	return false;
}

/**
 * Removes an entry only when the user has no tracked positions at that company.
 *
 * @param int $entry_id Entry post ID.
 * @param int $user_id  Owner user ID.
 * @return true|WP_Error
 */
function jlt_remove_company_from_bank( $entry_id, $user_id ) {
	$entry_id = absint( $entry_id );
	$user_id  = absint( $user_id );
	$entry    = get_post( $entry_id );

	if (
		! ( $entry instanceof WP_Post )
		|| 'jlt_company_entry' !== $entry->post_type
		|| (int) $entry->post_author !== $user_id
	) {
		return new WP_Error( 'unauthorized' );
	}

	$company_id = absint( get_post_meta( $entry_id, 'jlt_company_id', true ) );

	if ( jlt_has_tracked_positions_at_company( $company_id, $user_id ) ) {
		return new WP_Error( 'has_positions' );
	}

	$deleted = wp_delete_post( $entry_id, true );

	if ( ! $deleted ) {
		return new WP_Error( 'delete_failed' );
	}

	return true;
}
