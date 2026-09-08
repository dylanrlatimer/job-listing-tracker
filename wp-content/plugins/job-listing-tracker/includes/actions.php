<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_jlt_add_company', 'jlt_handle_add_company' );
add_action( 'admin_post_jlt_update_company', 'jlt_handle_update_company' );
add_action( 'admin_post_jlt_remove_company', 'jlt_handle_remove_company' );
add_action( 'admin_post_jlt_track_position', 'jlt_handle_track_position' );
add_action( 'admin_post_jlt_update_position', 'jlt_handle_update_position' );
add_action( 'admin_post_jlt_remove_position', 'jlt_handle_remove_position' );

/**
 * Handles adding a published company to the current user's bank.
 */
function jlt_handle_add_company() {
	if ( ! wp_verify_nonce( wp_unslash( $_POST['jlt_nonce'] ?? '' ), 'jlt_add_company' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'job-listing-tracker' ) );
	}

	$company_id = absint( wp_unslash( $_POST['company_id'] ?? 0 ) );
	$entry      = jlt_add_company_to_bank( get_current_user_id(), $company_id );

	if ( is_wp_error( $entry ) ) {
		wp_safe_redirect( add_query_arg( 'notice', 'error', jlt_bank_url() ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'notice', 'added', jlt_bank_entry_url( 'company', $entry->ID ) ) );
	exit;
}

/**
 * Handles updating a company entry's status and notes.
 */
function jlt_handle_update_company() {
	if ( ! wp_verify_nonce( wp_unslash( $_POST['jlt_nonce'] ?? '' ), 'jlt_update_company' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'job-listing-tracker' ) );
	}

	$entry_id = absint( wp_unslash( $_POST['entry_id'] ?? 0 ) );
	$status   = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
	$notes    = wp_unslash( $_POST['notes'] ?? '' );
	$result   = jlt_update_company_entry( $entry_id, get_current_user_id(), $status, $notes );
	$notice   = is_wp_error( $result ) ? 'error' : 'updated';

	wp_safe_redirect( add_query_arg( 'notice', $notice, jlt_bank_entry_url( 'company', $entry_id ) ) );
	exit;
}

/**
 * Handles removing a company entry when no dependent positions remain.
 */
function jlt_handle_remove_company() {
	if ( ! wp_verify_nonce( wp_unslash( $_POST['jlt_nonce'] ?? '' ), 'jlt_remove_company' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'job-listing-tracker' ) );
	}

	$entry_id = absint( wp_unslash( $_POST['entry_id'] ?? 0 ) );
	$result   = jlt_remove_company_from_bank( $entry_id, get_current_user_id() );

	if ( true === $result ) {
		wp_safe_redirect( add_query_arg( 'notice', 'removed', jlt_bank_url() ) );
		exit;
	}

	$notice = ( $result instanceof WP_Error && 'has_positions' === $result->get_error_code() )
		? 'has_positions'
		: 'error';

	wp_safe_redirect( add_query_arg( 'notice', $notice, jlt_bank_entry_url( 'company', $entry_id ) ) );
	exit;
}

/**
 * Handles tracking a published position for the current user.
 */
function jlt_handle_track_position() {
	if ( ! wp_verify_nonce( wp_unslash( $_POST['jlt_nonce'] ?? '' ), 'jlt_track_position' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'job-listing-tracker' ) );
	}

	$position_id = absint( wp_unslash( $_POST['position_id'] ?? 0 ) );
	$entry       = jlt_track_position( get_current_user_id(), $position_id );

	if ( is_wp_error( $entry ) ) {
		$notice = ( 'no_company_entry' === $entry->get_error_code() ) ? 'no_company_entry' : 'error';
		wp_safe_redirect( add_query_arg( 'notice', $notice, jlt_bank_url() ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'notice', 'tracked', jlt_bank_entry_url( 'position', $entry->ID ) ) );
	exit;
}

/**
 * Handles updating a position entry's status, applied date, and notes.
 */
function jlt_handle_update_position() {
	if ( ! wp_verify_nonce( wp_unslash( $_POST['jlt_nonce'] ?? '' ), 'jlt_update_position' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'job-listing-tracker' ) );
	}

	$entry_id   = absint( wp_unslash( $_POST['entry_id'] ?? 0 ) );
	$status     = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
	$applied_on = wp_unslash( $_POST['applied_on'] ?? '' );
	$notes      = wp_unslash( $_POST['notes'] ?? '' );
	$result     = jlt_update_position_entry( $entry_id, get_current_user_id(), $status, $applied_on, $notes );
	$notice     = is_wp_error( $result ) ? 'error' : 'position_updated';

	wp_safe_redirect( add_query_arg( 'notice', $notice, jlt_bank_entry_url( 'position', $entry_id ) ) );
	exit;
}

/**
 * Handles permanently removing a position entry.
 */
function jlt_handle_remove_position() {
	if ( ! wp_verify_nonce( wp_unslash( $_POST['jlt_nonce'] ?? '' ), 'jlt_remove_position' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'job-listing-tracker' ) );
	}

	$entry_id      = absint( wp_unslash( $_POST['entry_id'] ?? 0 ) );
	$user_id       = get_current_user_id();
	$company_entry = null;

	$entry = get_post( $entry_id );
	if ( $entry instanceof WP_Post && 'jlt_position_entry' === $entry->post_type ) {
		$position_id = absint( get_post_meta( $entry_id, 'jlt_position_id', true ) );
		$company_id  = $position_id ? absint( get_post_meta( $position_id, 'jlt_company_id', true ) ) : 0;
		if ( $company_id ) {
			$company_entry = jlt_get_company_entry( $user_id, $company_id );
		}
	}

	$result = jlt_remove_position_tracking( $entry_id, $user_id );

	if ( true === $result && $company_entry instanceof WP_Post ) {
		wp_safe_redirect( add_query_arg( 'notice', 'position_removed', jlt_bank_entry_url( 'company', $company_entry->ID ) ) );
		exit;
	}

	$notice = ( true === $result ) ? 'position_removed' : 'error';
	wp_safe_redirect( add_query_arg( 'notice', $notice, jlt_bank_url() ) );
	exit;
}
