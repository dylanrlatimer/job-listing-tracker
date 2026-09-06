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

add_filter( 'acf/settings/save_json', 'jlt_acf_json_save_path' );
add_filter( 'acf/settings/load_json', 'jlt_acf_json_load_paths' );
add_filter( 'acf/validate_value/name=jlt_website_url', 'jlt_acf_validate_url', 10, 4 );
add_filter( 'acf/validate_value/name=jlt_source_url', 'jlt_acf_validate_url', 10, 4 );
add_filter( 'acf/validate_value/name=jlt_availability', 'jlt_acf_validate_availability', 10, 4 );

/**
 * Directs ACF Local JSON saves into the plugin directory.
 *
 * @param string $path Default ACF JSON save path.
 * @return string
 */
function jlt_acf_json_save_path( $path ) {
	return JLT_PLUGIN_DIR . 'acf-json';
}

/**
 * Adds the plugin ACF JSON directory to the load path list.
 *
 * @param string[] $paths Existing ACF JSON load paths.
 * @return string[]
 */
function jlt_acf_json_load_paths( $paths ) {
	$paths[] = JLT_PLUGIN_DIR . 'acf-json';
	return $paths;
}

/**
 * Rejects non-empty URL values that are not HTTP or HTTPS.
 *
 * @param mixed  $valid      Current validation result.
 * @param mixed  $value      Submitted field value.
 * @param array  $field      ACF field array.
 * @param string $input_name Input name.
 * @return mixed
 */
function jlt_acf_validate_url( $valid, $value, $field, $input_name ) {
	if ( ! $valid || empty( $value ) ) {
		return $valid;
	}

	$parsed = wp_parse_url( $value );
	if ( empty( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
		return __( 'Please enter a valid HTTP or HTTPS URL.', 'job-listing-tracker' );
	}

	return $valid;
}

/**
 * Rejects availability values outside the allowed set.
 *
 * @param mixed  $valid      Current validation result.
 * @param mixed  $value      Submitted field value.
 * @param array  $field      ACF field array.
 * @param string $input_name Input name.
 * @return mixed
 */
function jlt_acf_validate_availability( $valid, $value, $field, $input_name ) {
	if ( ! $valid || empty( $value ) ) {
		return $valid;
	}

	if ( ! in_array( $value, jlt_availability_values(), true ) ) {
		return __( 'Availability must be open, closed, or unknown.', 'job-listing-tracker' );
	}

	return $valid;
}
