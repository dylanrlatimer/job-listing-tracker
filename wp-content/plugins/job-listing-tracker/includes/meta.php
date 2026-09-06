<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes an availability value. Returns 'unknown' for any unrecognized input.
 *
 * @param mixed $value Raw availability value.
 * @return string
 */
function jlt_sanitize_availability( $value ) {
	return in_array( $value, jlt_availability_values(), true ) ? $value : 'unknown';
}

/**
 * Sanitizes a company-entry status. Returns 'interested' for any unrecognized input.
 *
 * @param mixed $value Raw status value.
 * @return string
 */
function jlt_sanitize_company_status( $value ) {
	return in_array( $value, jlt_company_status_values(), true ) ? $value : 'interested';
}

/**
 * Authorization callback for shared post metadata.
 *
 * @param bool   $allowed  Whether the user can add this meta.
 * @param string $meta_key Meta key.
 * @param int    $post_id  Post ID.
 * @param int    $user_id  User ID.
 * @return bool
 */
function jlt_meta_auth_callback( $allowed, $meta_key, $post_id, $user_id ) {
	return user_can( $user_id, 'edit_post', $post_id );
}

add_action( 'init', 'jlt_register_shared_meta' );

/**
 * Registers shared company and position metadata.
 */
function jlt_register_shared_meta() {
	$shared_args = array(
		'single'        => true,
		'show_in_rest'  => false,
		'auth_callback' => 'jlt_meta_auth_callback',
	);

	register_post_meta(
		'jlt_company',
		'jlt_company_type',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		)
	);

	register_post_meta(
		'jlt_company',
		'jlt_website_url',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			)
		)
	);

	register_post_meta(
		'jlt_company',
		'jlt_location',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		)
	);

	register_post_meta(
		'jlt_company',
		'jlt_address',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		)
	);

	register_post_meta(
		'jlt_company',
		'jlt_tech_stack',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		)
	);

	register_post_meta(
		'jlt_position',
		'jlt_company_id',
		array_merge(
			$shared_args,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			)
		)
	);

	register_post_meta(
		'jlt_position',
		'jlt_source_url',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
			)
		)
	);

	register_post_meta(
		'jlt_position',
		'jlt_location',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		)
	);

	register_post_meta(
		'jlt_position',
		'jlt_tech_stack',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		)
	);

	register_post_meta(
		'jlt_position',
		'jlt_availability',
		array_merge(
			$shared_args,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'jlt_sanitize_availability',
				'default'           => 'unknown',
			)
		)
	);

	register_post_meta(
		'jlt_company_entry',
		'jlt_company_id',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'absint',
			'auth_callback'     => '__return_false',
		)
	);

	register_post_meta(
		'jlt_company_entry',
		'jlt_status',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'jlt_sanitize_company_status',
			'auth_callback'     => '__return_false',
			'default'           => 'interested',
		)
	);
}
