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

/**
 * Finds a position entry for one user and position.
 *
 * @param int $user_id     Owner user ID.
 * @param int $position_id Shared position post ID.
 * @return WP_Post|null
 */
function jlt_get_position_entry( $user_id, $position_id ) {
	$user_id     = absint( $user_id );
	$position_id = absint( $position_id );

	if ( ! $user_id || ! $position_id ) {
		return null;
	}

	$posts = get_posts(
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

	return $posts ? $posts[0] : null;
}

/**
 * Lists tracked positions for an owned company entry.
 *
 * @param int $company_entry_id Company entry post ID.
 * @param int $user_id          Owner user ID.
 * @return WP_Post[]
 */
function jlt_get_position_entries_for_company_entry( $company_entry_id, $user_id ) {
	$company_entry_id = absint( $company_entry_id );
	$user_id          = absint( $user_id );

	if ( ! $company_entry_id || ! $user_id ) {
		return array();
	}

	$company_id = absint( get_post_meta( $company_entry_id, 'jlt_company_id', true ) );
	if ( ! $company_id ) {
		return array();
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
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'jlt_position_entry',
			'post_status'    => 'publish',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => 'jlt_position_id',
					'value'   => $position_ids,
					'compare' => 'IN',
					'type'    => 'NUMERIC',
				),
			),
		)
	);
}

/**
 * Creates a position entry only when the user has already saved the position's company.
 *
 * @param int $user_id     Owner user ID.
 * @param int $position_id Shared position post ID.
 * @return WP_Post|WP_Error
 */
function jlt_track_position( $user_id, $position_id ) {
	$user_id     = absint( $user_id );
	$position_id = absint( $position_id );

	if ( ! $user_id || ! $position_id ) {
		return new WP_Error( 'invalid_args' );
	}

	$position = get_post( $position_id );
	if ( ! ( $position instanceof WP_Post ) || 'jlt_position' !== $position->post_type ) {
		return new WP_Error( 'invalid_position' );
	}

	$company_id = absint( get_post_meta( $position_id, 'jlt_company_id', true ) );
	if ( ! $company_id ) {
		return new WP_Error( 'invalid_position' );
	}

	if ( ! jlt_get_company_entry( $user_id, $company_id ) ) {
		return new WP_Error( 'no_company_entry' );
	}

	$existing = jlt_get_position_entry( $user_id, $position_id );
	if ( $existing instanceof WP_Post ) {
		return $existing;
	}

	$entry_id = wp_insert_post(
		array(
			'post_type'    => 'jlt_position_entry',
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_title'   => sprintf( 'User %d — Position %d', $user_id, $position_id ),
			'post_content' => '',
			'meta_input'   => array(
				'jlt_position_id' => $position_id,
				'jlt_status'      => 'interested',
				'jlt_applied_on'  => '',
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
 * Validates and updates position status, applied date, and notes.
 *
 * @param int    $entry_id   Entry post ID.
 * @param int    $user_id    Owner user ID.
 * @param string $status     Allowed position status.
 * @param string $applied_on Unslashed date string.
 * @param string $notes      Unslashed plain-text notes.
 * @return WP_Post|WP_Error
 */
function jlt_update_position_entry( $entry_id, $user_id, $status, $applied_on, $notes ) {
	$entry_id = absint( $entry_id );
	$user_id  = absint( $user_id );
	$entry    = get_post( $entry_id );

	if (
		! ( $entry instanceof WP_Post )
		|| 'jlt_position_entry' !== $entry->post_type
		|| (int) $entry->post_author !== $user_id
	) {
		return new WP_Error( 'unauthorized' );
	}

	if ( ! in_array( $status, jlt_position_status_values(), true ) ) {
		return new WP_Error( 'invalid_status' );
	}

	$applied_on = jlt_sanitize_applied_date( $applied_on );
	$notes      = sanitize_textarea_field( $notes );

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
	update_post_meta( $entry_id, 'jlt_applied_on', $applied_on );

	return get_post( $entry_id );
}

/**
 * Permanently removes the user's position entry.
 *
 * @param int $entry_id Entry post ID.
 * @param int $user_id  Owner user ID.
 * @return true|WP_Error
 */
function jlt_remove_position_tracking( $entry_id, $user_id ) {
	$entry_id = absint( $entry_id );
	$user_id  = absint( $user_id );
	$entry    = get_post( $entry_id );

	if (
		! ( $entry instanceof WP_Post )
		|| 'jlt_position_entry' !== $entry->post_type
		|| (int) $entry->post_author !== $user_id
	) {
		return new WP_Error( 'unauthorized' );
	}

	$deleted = wp_delete_post( $entry_id, true );

	if ( ! $deleted ) {
		return new WP_Error( 'delete_failed' );
	}

	return true;
}
