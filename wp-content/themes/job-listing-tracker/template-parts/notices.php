<?php
defined( 'ABSPATH' ) || exit;

$notice   = sanitize_key( $args['notice'] ?? '' );
$messages = array(
	'added'            => __( 'Company added to your bank.', 'job-listing-tracker' ),
	'updated'          => __( 'Changes saved.', 'job-listing-tracker' ),
	'removed'          => __( 'Company removed from your bank.', 'job-listing-tracker' ),
	'has_positions'    => __( 'Remove all tracked positions at this company first.', 'job-listing-tracker' ),
	'tracked'          => __( 'Position added to your tracking.', 'job-listing-tracker' ),
	'position_updated' => __( 'Changes saved.', 'job-listing-tracker' ),
	'position_removed' => __( 'Position removed from tracking.', 'job-listing-tracker' ),
	'no_company_entry' => __( 'Add this company to your bank before tracking its positions.', 'job-listing-tracker' ),
	'error'            => __( 'Something went wrong. Please try again.', 'job-listing-tracker' ),
);

$warn_notices = array( 'error', 'has_positions', 'no_company_entry' );

if ( $notice && isset( $messages[ $notice ] ) ) {
	$class = in_array( $notice, $warn_notices, true ) ? 'notice notice--warn' : 'notice';
	echo '<div class="' . esc_attr( $class ) . '" role="status">';
	echo esc_html( $messages[ $notice ] );
	echo '</div>';
}
