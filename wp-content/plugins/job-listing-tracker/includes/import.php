<?php
defined( 'ABSPATH' ) || exit;

/**
 * Resolves an import path. Relative paths are joined to ABSPATH.
 *
 * @param string $path File path.
 * @return string
 */
function jlt_resolve_import_path( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return '';
	}

	if ( preg_match( '#^(/|[A-Za-z]:[\\\\/])#', $path ) ) {
		return $path;
	}

	return ABSPATH . ltrim( str_replace( '\\', '/', $path ), '/' );
}

/**
 * Parses a UTF-8 CSV file. First row is the header.
 *
 * @param string $path Absolute path.
 * @return array<int,array<string,string>>|WP_Error Rows keyed by column name. Each row has `_row` (1-based file line).
 */
function jlt_parse_csv_file( $path ) {
	if ( ! is_readable( $path ) ) {
		return new WP_Error( 'jlt_csv_unreadable', sprintf( 'Cannot read CSV: %s', $path ) );
	}

	$handle = fopen( $path, 'rb' );
	if ( ! $handle ) {
		return new WP_Error( 'jlt_csv_unreadable', sprintf( 'Cannot open CSV: %s', $path ) );
	}

	$header = fgetcsv( $handle );
	if ( ! is_array( $header ) || ! $header || array( null ) === $header ) {
		fclose( $handle );
		return new WP_Error( 'jlt_csv_header', 'CSV is empty or missing a header row.' );
	}

	$header = array_map(
		static function ( $name ) {
			return strtolower( trim( (string) $name ) );
		},
		$header
	);

	if ( in_array( '', $header, true ) || count( $header ) !== count( array_unique( $header ) ) ) {
		fclose( $handle );
		return new WP_Error( 'jlt_csv_header', 'CSV header has empty or duplicate column names.' );
	}

	$rows    = array();
	$line_no = 1;
	while ( ( $data = fgetcsv( $handle ) ) !== false ) {
		++$line_no;
		if ( array( null ) === $data ) {
			continue;
		}

		$row = array( '_row' => (string) $line_no );
		foreach ( $header as $index => $name ) {
			$row[ $name ] = isset( $data[ $index ] ) ? trim( (string) $data[ $index ] ) : '';
		}
		$rows[] = $row;
	}

	fclose( $handle );
	return $rows;
}

/**
 * Returns a post of any relevant type that uses this slug, or null.
 *
 * @param string $slug Sanitized slug.
 * @return WP_Post|null
 */
function jlt_get_post_by_slug( $slug ) {
	$posts = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => array( 'jlt_company', 'jlt_position', 'post', 'page' ),
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	return $posts ? $posts[0] : null;
}

/**
 * Finds a company by slug.
 *
 * @param string $slug Sanitized slug.
 * @return WP_Post|null
 */
function jlt_get_company_by_slug( $slug ) {
	$post = jlt_get_post_by_slug( $slug );
	if ( $post instanceof WP_Post && 'jlt_company' === $post->post_type ) {
		return $post;
	}
	return null;
}

/**
 * Finds a position by slug.
 *
 * @param string $slug Sanitized slug.
 * @return WP_Post|null
 */
function jlt_get_position_by_slug( $slug ) {
	$post = jlt_get_post_by_slug( $slug );
	if ( $post instanceof WP_Post && 'jlt_position' === $post->post_type ) {
		return $post;
	}
	return null;
}

/**
 * Builds a result row for the import report.
 *
 * @param string $action  created|updated|skipped|error.
 * @param int    $line    File line number.
 * @param string $slug    Record slug.
 * @param string $message Extra detail.
 * @return array{action:string,row:int,slug:string,message:string}
 */
function jlt_import_result( $action, $line, $slug, $message = '' ) {
	return array(
		'action'  => $action,
		'row'     => (int) $line,
		'slug'    => $slug,
		'message' => $message,
	);
}

/**
 * Validates an optional HTTP(S) URL. Empty is ok.
 *
 * @param string $value Raw URL.
 * @return true|WP_Error
 */
function jlt_import_validate_url( $value ) {
	if ( '' === $value ) {
		return true;
	}

	$parsed = wp_parse_url( $value );
	if ( empty( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
		return new WP_Error( 'jlt_import_url', 'URL must be HTTP or HTTPS.' );
	}

	return true;
}

/**
 * Validates optional post_status for import. Empty is ok.
 *
 * @param string $value Raw status.
 * @return true|WP_Error
 */
function jlt_import_validate_post_status( $value ) {
	if ( '' === $value ) {
		return true;
	}

	if ( ! in_array( $value, array( 'publish', 'draft' ), true ) ) {
		return new WP_Error( 'jlt_import_status', 'status must be publish or draft.' );
	}

	return true;
}

/**
 * Imports company rows.
 *
 * @param array<int,array<string,string>> $rows    Parsed CSV rows.
 * @param bool                            $dry_run If true, do not write.
 * @return array<int,array{action:string,row:int,slug:string,message:string}>
 */
function jlt_import_companies( $rows, $dry_run = false ) {
	$results = array();

	foreach ( $rows as $row ) {
		$line = absint( $row['_row'] ?? 0 );
		$slug = sanitize_title( $row['slug'] ?? '' );
		$name = trim( $row['name'] ?? '' );

		if ( '' === $slug ) {
			$results[] = jlt_import_result( 'error', $line, '', 'Missing or invalid slug.' );
			continue;
		}

		if ( '' === $name ) {
			$results[] = jlt_import_result( 'error', $line, $slug, 'Missing name.' );
			continue;
		}

		$status    = trim( $row['status'] ?? '' );
		$website   = trim( $row['website'] ?? '' );
		$status_ok = jlt_import_validate_post_status( $status );
		$url_ok    = jlt_import_validate_url( $website );
		if ( is_wp_error( $status_ok ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, $status_ok->get_error_message() );
			continue;
		}
		if ( is_wp_error( $url_ok ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, $url_ok->get_error_message() );
			continue;
		}

		$existing = jlt_get_post_by_slug( $slug );
		if ( $existing instanceof WP_Post && 'jlt_company' !== $existing->post_type ) {
			$results[] = jlt_import_result( 'error', $line, $slug, 'Slug is already used by another post type.' );
			continue;
		}

		$is_update = $existing instanceof WP_Post;
		if ( $dry_run ) {
			$results[] = jlt_import_result( $is_update ? 'updated' : 'created', $line, $slug, 'dry-run' );
			continue;
		}

		$postarr = array(
			'post_type'  => 'jlt_company',
			'post_title' => $name,
			'post_name'  => $slug,
		);

		$description = $row['description'] ?? '';
		if ( '' !== $description ) {
			$postarr['post_content'] = wp_kses_post( $description );
		}

		if ( '' !== $status ) {
			$postarr['post_status'] = $status;
		} elseif ( ! $is_update ) {
			$postarr['post_status'] = 'publish';
		}

		if ( $is_update ) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			if ( ! isset( $postarr['post_content'] ) ) {
				$postarr['post_content'] = '';
			}
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, $post_id->get_error_message() );
			continue;
		}

		$meta_map = array(
			'type'     => array( 'jlt_company_type', 'sanitize_text_field' ),
			'location' => array( 'jlt_location', 'sanitize_text_field' ),
			'address'  => array( 'jlt_address', 'sanitize_text_field' ),
		);
		foreach ( $meta_map as $column => $spec ) {
			$value = trim( $row[ $column ] ?? '' );
			if ( '' === $value ) {
				continue;
			}
			update_post_meta( $post_id, $spec[0], call_user_func( $spec[1], $value ) );
		}

		if ( '' !== $website ) {
			update_post_meta( $post_id, 'jlt_website_url', esc_url_raw( $website ) );
		}

		$results[] = jlt_import_result( $is_update ? 'updated' : 'created', $line, $slug );
	}

	return $results;
}

/**
 * Imports position rows.
 *
 * @param array<int,array<string,string>> $rows    Parsed CSV rows.
 * @param bool                            $dry_run If true, do not write.
 * @return array<int,array{action:string,row:int,slug:string,message:string}>
 */
function jlt_import_positions( $rows, $dry_run = false ) {
	$results = array();

	foreach ( $rows as $row ) {
		$line         = absint( $row['_row'] ?? 0 );
		$slug         = sanitize_title( $row['slug'] ?? '' );
		$title        = trim( $row['title'] ?? '' );
		$company_slug = sanitize_title( $row['company_slug'] ?? '' );

		if ( '' === $slug ) {
			$results[] = jlt_import_result( 'error', $line, '', 'Missing or invalid slug.' );
			continue;
		}

		if ( '' === $title ) {
			$results[] = jlt_import_result( 'error', $line, $slug, 'Missing title.' );
			continue;
		}

		if ( '' === $company_slug ) {
			$results[] = jlt_import_result( 'skipped', $line, $slug, 'Missing company_slug.' );
			continue;
		}

		$company = jlt_get_company_by_slug( $company_slug );
		if ( ! $company instanceof WP_Post ) {
			$results[] = jlt_import_result( 'skipped', $line, $slug, 'Company slug not found: ' . $company_slug );
			continue;
		}

		$status       = trim( $row['status'] ?? '' );
		$source_url   = trim( $row['source_url'] ?? '' );
		$availability = trim( $row['availability'] ?? '' );

		$status_ok = jlt_import_validate_post_status( $status );
		$url_ok    = jlt_import_validate_url( $source_url );
		if ( is_wp_error( $status_ok ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, $status_ok->get_error_message() );
			continue;
		}
		if ( is_wp_error( $url_ok ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, $url_ok->get_error_message() );
			continue;
		}
		if ( '' !== $availability && ! in_array( $availability, jlt_availability_values(), true ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, 'availability must be open, closed, or unknown.' );
			continue;
		}

		$existing = jlt_get_post_by_slug( $slug );
		if ( $existing instanceof WP_Post && 'jlt_position' !== $existing->post_type ) {
			$results[] = jlt_import_result( 'error', $line, $slug, 'Slug is already used by another post type.' );
			continue;
		}

		$is_update = $existing instanceof WP_Post;
		if ( $dry_run ) {
			$results[] = jlt_import_result( $is_update ? 'updated' : 'created', $line, $slug, 'dry-run' );
			continue;
		}

		$postarr = array(
			'post_type'  => 'jlt_position',
			'post_title' => $title,
			'post_name'  => $slug,
		);

		$description = $row['description'] ?? '';
		if ( '' !== $description ) {
			$postarr['post_content'] = wp_kses_post( $description );
		}

		if ( '' !== $status ) {
			$postarr['post_status'] = $status;
		} elseif ( ! $is_update ) {
			$postarr['post_status'] = 'publish';
		}

		if ( $is_update ) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			if ( ! isset( $postarr['post_content'] ) ) {
				$postarr['post_content'] = '';
			}
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			$results[] = jlt_import_result( 'error', $line, $slug, $post_id->get_error_message() );
			continue;
		}

		update_post_meta( $post_id, 'jlt_company_id', $company->ID );

		$location = trim( $row['location'] ?? '' );
		if ( '' !== $location ) {
			update_post_meta( $post_id, 'jlt_location', sanitize_text_field( $location ) );
		}

		$tech_stack = trim( $row['tech_stack'] ?? '' );
		if ( '' !== $tech_stack ) {
			update_post_meta( $post_id, 'jlt_tech_stack', sanitize_textarea_field( $tech_stack ) );
		}

		if ( '' !== $source_url ) {
			update_post_meta( $post_id, 'jlt_source_url', esc_url_raw( $source_url ) );
		}

		if ( '' !== $availability ) {
			update_post_meta( $post_id, 'jlt_availability', jlt_sanitize_availability( $availability ) );
		} elseif ( ! $is_update ) {
			update_post_meta( $post_id, 'jlt_availability', 'unknown' );
		}

		$results[] = jlt_import_result( $is_update ? 'updated' : 'created', $line, $slug );
	}

	return $results;
}
