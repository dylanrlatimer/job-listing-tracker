<?php
defined( 'ABSPATH' ) || exit;

$notice   = sanitize_key( $args['notice'] ?? '' );
$messages = array(
	/*
	 * Notice code => human-readable message.
	 * Populated in M5 (company actions) and M6 (position actions).
	 * Example:
	 *   'added'   => __( 'Company added to your bank.', 'job-listing-tracker' ),
	 *   'updated' => __( 'Changes saved.', 'job-listing-tracker' ),
	 *   'removed' => __( 'Company removed from your bank.', 'job-listing-tracker' ),
	 *   'error'   => __( 'Something went wrong. Please try again.', 'job-listing-tracker' ),
	 */
);

if ( $notice && isset( $messages[ $notice ] ) ) {
	echo '<div class="bank-notice" role="status">';
	echo esc_html( $messages[ $notice ] );
	echo '</div>';
}
