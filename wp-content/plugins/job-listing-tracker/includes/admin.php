<?php
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'jlt_setup_admin_menu', 99 );
add_filter( 'parent_file', 'jlt_admin_parent_file' );
add_filter( 'submenu_file', 'jlt_admin_submenu_file', 10, 2 );
add_filter( 'manage_jlt_position_posts_columns', 'jlt_position_list_columns' );
add_action( 'manage_jlt_position_posts_custom_column', 'jlt_position_list_column_content', 10, 2 );
add_action( 'restrict_manage_posts', 'jlt_positions_company_filter', 10, 2 );
add_action( 'pre_get_posts', 'jlt_apply_positions_company_filter' );
add_action( 'add_meta_boxes_jlt_company', 'jlt_add_company_positions_metabox' );
add_filter( 'acf/load_value/name=jlt_company_id', 'jlt_prefill_position_company', 10, 3 );

/**
 * Groups Companies and Positions under one Job Listings Admin menu.
 */
function jlt_setup_admin_menu() {
	global $menu;

	if ( is_array( $menu ) ) {
		foreach ( $menu as $position => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=jlt_company' === $item[2] ) {
				$menu[ $position ][0] = __( 'Job Listings', 'job-listing-tracker' );
				break;
			}
		}
	}

	add_submenu_page(
		'edit.php?post_type=jlt_company',
		__( 'Positions', 'job-listing-tracker' ),
		__( 'Positions', 'job-listing-tracker' ),
		'edit_jlt_positions',
		'edit.php?post_type=jlt_position'
	);

	remove_menu_page( 'edit.php?post_type=jlt_position' );
}

/**
 * Keeps Job Listings selected while viewing Position admin screens.
 *
 * @param string $parent_file Current parent file.
 * @return string
 */
function jlt_admin_parent_file( $parent_file ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'jlt_position' === $screen->post_type ) {
		return 'edit.php?post_type=jlt_company';
	}

	return $parent_file;
}

/**
 * Keeps the Positions submenu selected on Position list and edit screens.
 *
 * @param string $submenu_file Current submenu file.
 * @param string $parent_file  Current parent file.
 * @return string
 */
function jlt_admin_submenu_file( $submenu_file, $parent_file ) {
	unset( $parent_file );

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'jlt_position' === $screen->post_type ) {
		return 'edit.php?post_type=jlt_position';
	}

	return $submenu_file;
}

/**
 * Inserts Company and Availability columns after Title on the Positions list.
 *
 * @param array<string,string> $columns Existing list columns.
 * @return array<string,string>
 */
function jlt_position_list_columns( $columns ) {
	$rebuilt = array();

	foreach ( $columns as $key => $label ) {
		$rebuilt[ $key ] = $label;
		if ( 'title' === $key ) {
			$rebuilt['jlt_company']      = __( 'Company', 'job-listing-tracker' );
			$rebuilt['jlt_availability'] = __( 'Availability', 'job-listing-tracker' );
		}
	}

	return $rebuilt;
}

/**
 * Renders Company and Availability cells on the Positions list.
 *
 * @param string $column  Column key.
 * @param int    $post_id Position post ID.
 */
function jlt_position_list_column_content( $column, $post_id ) {
	if ( 'jlt_company' === $column ) {
		$company_id = absint( get_post_meta( $post_id, 'jlt_company_id', true ) );
		$company    = $company_id ? get_post( $company_id ) : null;

		if ( $company instanceof WP_Post && 'jlt_company' === $company->post_type ) {
			$edit_link = get_edit_post_link( $company_id );
			if ( $edit_link ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url( $edit_link ),
					esc_html( $company->post_title )
				);
			} else {
				echo esc_html( $company->post_title );
			}
			return;
		}

		echo '<span aria-hidden="true">—</span>';
		return;
	}

	if ( 'jlt_availability' === $column ) {
		$avail = get_post_meta( $post_id, 'jlt_availability', true ) ?: 'unknown';
		echo esc_html( ucfirst( $avail ) );
	}
}

/**
 * Adds a Company dropdown to the Positions list filter bar.
 *
 * @param string $post_type Current list post type.
 * @param string $which     Filter location: top or bottom.
 */
function jlt_positions_company_filter( $post_type, $which ) {
	if ( 'jlt_position' !== $post_type || 'top' !== $which ) {
		return;
	}

	$companies = get_posts(
		array(
			'post_type'      => 'jlt_company',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	if ( ! $companies ) {
		return;
	}

	$selected = isset( $_GET['jlt_filter_company_id'] ) ? absint( $_GET['jlt_filter_company_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	echo '<select name="jlt_filter_company_id">';
	printf(
		'<option value="">%s</option>',
		esc_html__( 'All Companies', 'job-listing-tracker' )
	);

	foreach ( $companies as $company ) {
		printf(
			'<option value="%d"%s>%s</option>',
			(int) $company->ID,
			selected( $selected, $company->ID, false ),
			esc_html( $company->post_title )
		);
	}

	echo '</select>';
}

/**
 * Scopes the main Positions Admin query to a validated Company ID.
 *
 * @param WP_Query $query Current query.
 */
function jlt_apply_positions_company_filter( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'jlt_position' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! isset( $_GET['jlt_filter_company_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$company_id = absint( $_GET['jlt_filter_company_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $company_id ) {
		return;
	}

	$company = get_post( $company_id );
	if ( ! ( $company instanceof WP_Post ) || 'jlt_company' !== $company->post_type ) {
		return;
	}

	$existing   = $query->get( 'meta_query' ) ?: array();
	$existing[] = array(
		'key'   => 'jlt_company_id',
		'value' => $company_id,
		'type'  => 'NUMERIC',
	);
	$query->set( 'meta_query', $existing );
}

/**
 * Registers the related-Positions meta box on the Company edit screen.
 */
function jlt_add_company_positions_metabox() {
	add_meta_box(
		'jlt_company_positions',
		__( 'Positions at this Company', 'job-listing-tracker' ),
		'jlt_render_company_positions_metabox',
		'jlt_company',
		'normal',
		'low'
	);
}

/**
 * Lists non-trashed Positions for the current Company and an add action.
 *
 * @param WP_Post $post Company post.
 */
function jlt_render_company_positions_metabox( $post ) {
	$positions = get_posts(
		array(
			'post_type'      => 'jlt_position',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'   => 'jlt_company_id',
					'value' => $post->ID,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	if ( ! $positions ) {
		echo '<p>' . esc_html__( 'No positions yet.', 'job-listing-tracker' ) . '</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Title', 'job-listing-tracker' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'job-listing-tracker' ) . '</th>';
		echo '<th>' . esc_html__( 'Availability', 'job-listing-tracker' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $positions as $position ) {
			$status_object = get_post_status_object( $position->post_status );
			$status_label  = $status_object ? $status_object->label : $position->post_status;
			$availability  = get_post_meta( $position->ID, 'jlt_availability', true ) ?: 'unknown';
			$edit_link     = get_edit_post_link( $position->ID );

			echo '<tr>';
			echo '<td>';
			if ( $edit_link ) {
				printf(
					'<a href="%s">%s</a>',
					esc_url( $edit_link ),
					esc_html( $position->post_title )
				);
			} else {
				echo esc_html( $position->post_title );
			}
			echo '</td>';
			echo '<td>' . esc_html( $status_label ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $availability ) ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	$new_url = add_query_arg(
		array(
			'post_type'           => 'jlt_position',
			'jlt_prefill_company' => $post->ID,
		),
		admin_url( 'post-new.php' )
	);

	printf(
		'<p><a href="%s" class="button button-secondary">%s</a></p>',
		esc_url( $new_url ),
		esc_html__( 'Add Position for this Company', 'job-listing-tracker' )
	);
}

/**
 * Prefills the Company field on a new Position when a valid company ID is supplied.
 *
 * @param mixed $value   Current ACF field value.
 * @param int   $post_id Current post ID.
 * @param array $field   ACF field array.
 * @return mixed
 */
function jlt_prefill_position_company( $value, $post_id, $field ) {
	unset( $field );

	global $pagenow;
	if ( 'post-new.php' !== $pagenow ) {
		return $value;
	}

	if ( ! isset( $_GET['jlt_prefill_company'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $value;
	}

	if ( $value ) {
		return $value;
	}

	$current_post = get_post( $post_id );
	if (
		! ( $current_post instanceof WP_Post )
		|| 'jlt_position' !== $current_post->post_type
		|| 'auto-draft' !== $current_post->post_status
	) {
		return $value;
	}

	$company_id = absint( $_GET['jlt_prefill_company'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $company_id ) {
		return $value;
	}

	$company = get_post( $company_id );
	if ( ! ( $company instanceof WP_Post ) || 'jlt_company' !== $company->post_type ) {
		return $value;
	}

	if ( ! current_user_can( 'edit_post', $company_id ) ) {
		return $value;
	}

	return $company_id;
}
