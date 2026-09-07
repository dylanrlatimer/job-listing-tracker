<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers WP-CLI commands.
 */
function jlt_register_cli_commands() {
	WP_CLI::add_command( 'jlt import', 'jlt_cli_import' );
}

/**
 * Imports companies and/or positions from CSV files.
 *
 * ## OPTIONS
 *
 * [--companies=<path>]
 * : Path to companies.csv, relative to the WordPress root or absolute.
 *
 * [--positions=<path>]
 * : Path to positions.csv, relative to the WordPress root or absolute.
 *
 * [--dry-run]
 * : Validate and report without writing.
 *
 * ## EXAMPLES
 *
 *     wp jlt import --companies=wp-content/jlt-data/companies.csv --positions=wp-content/jlt-data/positions.csv
 *     wp jlt import --companies=wp-content/jlt-data/companies.csv --dry-run
 *
 * @param string[]             $args       Positional args.
 * @param array<string,string> $assoc_args Associative args.
 */
function jlt_cli_import( $args, $assoc_args ) {
	unset( $args );

	$dry_run        = ! empty( $assoc_args['dry-run'] );
	$companies_path = jlt_resolve_import_path( $assoc_args['companies'] ?? '' );
	$positions_path = jlt_resolve_import_path( $assoc_args['positions'] ?? '' );

	if ( '' === $companies_path && '' === $positions_path ) {
		WP_CLI::error( 'Pass --companies and/or --positions.' );
	}

	$failed = 0;

	if ( '' !== $companies_path ) {
		$parsed = jlt_parse_csv_file( $companies_path );
		if ( is_wp_error( $parsed ) ) {
			WP_CLI::error( $parsed->get_error_message() );
		}
		$results = jlt_import_companies( $parsed, $dry_run );
		$failed += jlt_cli_print_import_results( 'companies', $results );
	}

	if ( '' !== $positions_path ) {
		$parsed = jlt_parse_csv_file( $positions_path );
		if ( is_wp_error( $parsed ) ) {
			WP_CLI::error( $parsed->get_error_message() );
		}
		$results = jlt_import_positions( $parsed, $dry_run );
		$failed += jlt_cli_print_import_results( 'positions', $results );
	}

	if ( $failed ) {
		WP_CLI::error( sprintf( '%d row(s) failed.', $failed ) );
	}

	WP_CLI::success( $dry_run ? 'Dry run finished.' : 'Import finished.' );
}

/**
 * Prints import results and returns the number of error rows.
 *
 * @param string                                                                                 $label   companies|positions.
 * @param array<int,array{action:string,row:int,slug:string,message:string}> $results Import results.
 * @return int
 */
function jlt_cli_print_import_results( $label, $results ) {
	$counts = array(
		'created' => 0,
		'updated' => 0,
		'skipped' => 0,
		'error'   => 0,
	);

	foreach ( $results as $result ) {
		$action = $result['action'];
		if ( isset( $counts[ $action ] ) ) {
			++$counts[ $action ];
		}

		$line = sprintf(
			'[%s] row %d %s %s',
			$label,
			$result['row'],
			$action,
			$result['slug']
		);
		if ( '' !== $result['message'] ) {
			$line .= ': ' . $result['message'];
		}

		if ( 'error' === $action ) {
			WP_CLI::warning( $line );
		} else {
			WP_CLI::log( $line );
		}
	}

	WP_CLI::log(
		sprintf(
			'[%s] created=%d updated=%d skipped=%d error=%d',
			$label,
			$counts['created'],
			$counts['updated'],
			$counts['skipped'],
			$counts['error']
		)
	);

	return $counts['error'];
}

jlt_register_cli_commands();
