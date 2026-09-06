<?php
defined( 'ABSPATH' ) || exit;

/**
 * Returns the canonical URL of the Bank page.
 * Falls back to /bank/ if the page has not been created yet.
 *
 * @return string
 */
function jlt_bank_url() {
	$page = get_page_by_path( 'bank' );
	return $page ? get_permalink( $page->ID ) : home_url( '/bank/' );
}

/**
 * Returns a Bank view URL for a specific entry.
 *
 * @param string $view     'company' or 'position'.
 * @param int    $entry_id Entry post ID.
 * @return string
 */
function jlt_bank_entry_url( $view, $entry_id ) {
	return add_query_arg(
		array(
			'view'  => sanitize_key( $view ),
			'entry' => absint( $entry_id ),
		),
		jlt_bank_url()
	);
}

/**
 * Returns a login URL that redirects back to $redirect_url after authentication.
 *
 * Theme My Login filters wp_login_url() to the front-end login page.
 *
 * @param string $redirect_url URL to return to after login.
 * @return string
 */
function jlt_login_url( $redirect_url = '' ) {
	return wp_login_url( $redirect_url );
}

/**
 * Resolves the current Bank page view from request parameters.
 *
 * Reads 'view', 'entry', and 'notice' from $_GET. If an entry ID is supplied,
 * loads the post and verifies that its post_type and post_author match the
 * current user. Returns a result array:
 *
 *   'mode'   string   'overview' | 'company' | 'position' | 'not_found'
 *   'entry'  WP_Post|null  the validated entry post, or null
 *   'notice' string   a short redirect notice code, or empty string
 *
 * Must only be called when is_user_logged_in() is true.
 *
 * @return array{mode:string,entry:WP_Post|null,notice:string}
 */
function jlt_resolve_bank_view() {
	$view     = sanitize_key( wp_unslash( $_GET['view'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$entry_id = absint( $_GET['entry'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$notice   = sanitize_key( wp_unslash( $_GET['notice'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $view ) {
		return array(
			'mode'   => 'overview',
			'entry'  => null,
			'notice' => $notice,
		);
	}

	if ( ! in_array( $view, array( 'company', 'position' ), true ) || ! $entry_id ) {
		return array(
			'mode'   => 'not_found',
			'entry'  => null,
			'notice' => '',
		);
	}

	$post_type = ( 'company' === $view ) ? 'jlt_company_entry' : 'jlt_position_entry';
	$entry     = get_post( $entry_id );

	if (
		! ( $entry instanceof WP_Post )
		|| $post_type !== $entry->post_type
		|| (int) get_current_user_id() !== (int) $entry->post_author
	) {
		return array(
			'mode'   => 'not_found',
			'entry'  => null,
			'notice' => '',
		);
	}

	return array(
		'mode'   => $view,
		'entry'  => $entry,
		'notice' => $notice,
	);
}
