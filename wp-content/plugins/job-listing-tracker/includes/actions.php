<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_jlt_add_company', 'jlt_handle_add_company' );
add_action( 'admin_post_jlt_update_company', 'jlt_handle_update_company' );
add_action( 'admin_post_jlt_remove_company', 'jlt_handle_remove_company' );

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
