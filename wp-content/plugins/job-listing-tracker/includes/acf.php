<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_notices', 'jlt_acf_missing_notice' );

/**
 * Warn administrators when Advanced Custom Fields is not available.
 */
function jlt_acf_missing_notice() {
	if ( class_exists( 'ACF' ) ) {
		return;
	}

	echo '<div class="notice notice-warning is-dismissible"><p>';
	esc_html_e( 'Job Listing Tracker requires Advanced Custom Fields. Please install and activate the ACF plugin.', 'job-listing-tracker' );
	echo '</p></div>';
}
