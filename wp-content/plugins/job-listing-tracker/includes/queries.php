<?php
defined( 'ABSPATH' ) || exit;

/**
 * Returns view-ready company custom field values.
 *
 * @param int $post_id Company post ID.
 * @return array{type:string,website:string,location:string,address:string}
 */
function jlt_get_company_meta( $post_id ) {
	$post_id = absint( $post_id );
	return array(
		'type'     => (string) get_post_meta( $post_id, 'jlt_company_type', true ),
		'website'  => (string) get_post_meta( $post_id, 'jlt_website_url', true ),
		'location' => (string) get_post_meta( $post_id, 'jlt_location', true ),
		'address'  => (string) get_post_meta( $post_id, 'jlt_address', true ),
	);
}

/**
 * Returns view-ready position custom field values.
 *
 * @param int $post_id Position post ID.
 * @return array{company_id:int,availability:string,source_url:string,location:string,tech_stack:string}
 */
function jlt_get_position_meta( $post_id ) {
	$post_id = absint( $post_id );
	$avail   = (string) get_post_meta( $post_id, 'jlt_availability', true );
	return array(
		'company_id'   => absint( get_post_meta( $post_id, 'jlt_company_id', true ) ),
		'availability' => in_array( $avail, jlt_availability_values(), true ) ? $avail : 'unknown',
		'source_url'   => (string) get_post_meta( $post_id, 'jlt_source_url', true ),
		'location'     => (string) get_post_meta( $post_id, 'jlt_location', true ),
		'tech_stack'   => (string) get_post_meta( $post_id, 'jlt_tech_stack', true ),
	);
}

/**
 * Returns true when a published company exists for the given ID.
 *
 * @param int $company_id Company post ID.
 * @return bool
 */
function jlt_company_is_published( $company_id ) {
	$company_id = absint( $company_id );
	if ( ! $company_id ) {
		return false;
	}

	$post = get_post( $company_id );
	return $post instanceof WP_Post
		&& 'jlt_company' === $post->post_type
		&& 'publish' === $post->post_status;
}

/**
 * Comparison callback for sorting positions by availability, then title.
 *
 * @param WP_Post $a First position.
 * @param WP_Post $b Second position.
 * @return int
 */
function jlt_sort_positions_by_availability( $a, $b ) {
	$order = array(
		'open'    => 0,
		'closed'  => 1,
		'unknown' => 2,
	);
	$a_val = (string) get_post_meta( $a->ID, 'jlt_availability', true );
	$b_val = (string) get_post_meta( $b->ID, 'jlt_availability', true );
	$a_ord = $order[ $a_val ] ?? 2;
	$b_ord = $order[ $b_val ] ?? 2;
	if ( $a_ord === $b_ord ) {
		return strcmp( $a->post_title, $b->post_title );
	}
	return $a_ord - $b_ord;
}

/**
 * Returns published positions for a company, sorted by availability.
 *
 * @param int $company_id Company post ID.
 * @return WP_Post[]
 */
function jlt_get_company_positions( $company_id ) {
	$company_id = absint( $company_id );
	if ( ! $company_id ) {
		return array();
	}

	$positions = get_posts(
		array(
			'post_type'      => 'jlt_position',
			'post_status'    => 'publish',
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

	if ( ! $positions ) {
		return array();
	}

	usort( $positions, 'jlt_sort_positions_by_availability' );

	return $positions;
}

add_action( 'template_redirect', 'jlt_enforce_position_company_visibility' );

/**
 * Returns a 404 when a position's referenced company is not published.
 */
function jlt_enforce_position_company_visibility() {
	if ( ! is_singular( 'jlt_position' ) ) {
		return;
	}

	$company_id = absint( get_post_meta( get_queried_object_id(), 'jlt_company_id', true ) );

	if ( jlt_company_is_published( $company_id ) ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
