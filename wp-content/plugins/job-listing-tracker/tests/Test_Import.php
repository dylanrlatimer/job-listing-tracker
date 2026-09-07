<?php
/**
 * CSV import create, update, dry-run, and validation tests.
 */
class Test_Import extends WP_UnitTestCase {

	/**
	 * WordPress core still calls PHPUnit 9's parseTestMethodAnnotations().
	 * These tests do not use @expectedDeprecated annotations.
	 */
	public function expectDeprecated() {}

	/**
	 * Writes a temporary CSV and returns its path.
	 *
	 * @param string[][] $lines Rows including header.
	 * @return string
	 */
	protected function write_csv( $lines ) {
		$path = wp_tempnam( 'jlt-import-' );
		$fh   = fopen( $path, 'wb' );
		foreach ( $lines as $line ) {
			fputcsv( $fh, $line );
		}
		fclose( $fh );
		return $path;
	}

	/**
	 * Parses a CSV written by write_csv().
	 *
	 * @param string[][] $lines Rows including header.
	 * @return array<int,array<string,string>>
	 */
	protected function rows_from( $lines ) {
		$path = $this->write_csv( $lines );
		$rows = jlt_parse_csv_file( $path );
		unlink( $path );
		$this->assertIsArray( $rows );
		return $rows;
	}

	public function test_create_company_and_position() {
		$companies = $this->rows_from(
			array(
				array( 'slug', 'name', 'type', 'website', 'location' ),
				array( 'acme', 'Acme Corp', 'Software', 'https://acme.example', 'Austin' ),
			)
		);
		$company_results = jlt_import_companies( $companies, false );
		$this->assertSame( 'created', $company_results[0]['action'] );

		$company = jlt_get_company_by_slug( 'acme' );
		$this->assertInstanceOf( WP_Post::class, $company );
		$this->assertSame( 'Acme Corp', $company->post_title );
		$this->assertSame( 'Software', get_post_meta( $company->ID, 'jlt_company_type', true ) );

		$positions = $this->rows_from(
			array(
				array( 'slug', 'company_slug', 'title', 'availability', 'location' ),
				array( 'acme-engineer', 'acme', 'Engineer', 'open', 'Remote' ),
			)
		);
		$position_results = jlt_import_positions( $positions, false );
		$this->assertSame( 'created', $position_results[0]['action'] );

		$position = jlt_get_position_by_slug( 'acme-engineer' );
		$this->assertInstanceOf( WP_Post::class, $position );
		$this->assertSame( 'Engineer', $position->post_title );
		$this->assertSame( (string) $company->ID, (string) get_post_meta( $position->ID, 'jlt_company_id', true ) );
		$this->assertSame( 'open', get_post_meta( $position->ID, 'jlt_availability', true ) );
	}

	public function test_reimport_updates_title_and_meta() {
		$first = $this->rows_from(
			array(
				array( 'slug', 'name', 'location' ),
				array( 'acme', 'Acme Corp', 'Austin' ),
			)
		);
		jlt_import_companies( $first, false );

		$second = $this->rows_from(
			array(
				array( 'slug', 'name', 'location' ),
				array( 'acme', 'Acme Incorporated', 'Dallas' ),
			)
		);
		$results = jlt_import_companies( $second, false );
		$this->assertSame( 'updated', $results[0]['action'] );

		$company = jlt_get_company_by_slug( 'acme' );
		$this->assertSame( 'Acme Incorporated', $company->post_title );
		$this->assertSame( 'Dallas', get_post_meta( $company->ID, 'jlt_location', true ) );
	}

	public function test_dry_run_writes_nothing() {
		$rows = $this->rows_from(
			array(
				array( 'slug', 'name' ),
				array( 'ghost', 'Ghost Co' ),
			)
		);
		$results = jlt_import_companies( $rows, true );
		$this->assertSame( 'created', $results[0]['action'] );
		$this->assertSame( 'dry-run', $results[0]['message'] );
		$this->assertNull( jlt_get_company_by_slug( 'ghost' ) );
	}

	public function test_bad_availability_is_error() {
		$companies = $this->rows_from(
			array(
				array( 'slug', 'name' ),
				array( 'acme', 'Acme' ),
			)
		);
		jlt_import_companies( $companies, false );

		$positions = $this->rows_from(
			array(
				array( 'slug', 'company_slug', 'title', 'availability' ),
				array( 'acme-role', 'acme', 'Role', 'hiring' ),
			)
		);
		$results = jlt_import_positions( $positions, false );
		$this->assertSame( 'error', $results[0]['action'] );
		$this->assertNull( jlt_get_position_by_slug( 'acme-role' ) );
	}

	public function test_bad_url_is_error() {
		$rows = $this->rows_from(
			array(
				array( 'slug', 'name', 'website' ),
				array( 'acme', 'Acme', 'ftp://acme.example' ),
			)
		);
		$results = jlt_import_companies( $rows, false );
		$this->assertSame( 'error', $results[0]['action'] );
		$this->assertNull( jlt_get_company_by_slug( 'acme' ) );
	}

	public function test_missing_company_slug_is_skipped() {
		$positions = $this->rows_from(
			array(
				array( 'slug', 'company_slug', 'title' ),
				array( 'orphan-role', '', 'Orphan' ),
			)
		);
		$results = jlt_import_positions( $positions, false );
		$this->assertSame( 'skipped', $results[0]['action'] );
		$this->assertNull( jlt_get_position_by_slug( 'orphan-role' ) );
	}

	public function test_empty_optional_cell_does_not_wipe() {
		$first = $this->rows_from(
			array(
				array( 'slug', 'name', 'address' ),
				array( 'acme', 'Acme', '1 Main St' ),
			)
		);
		jlt_import_companies( $first, false );
		$company = jlt_get_company_by_slug( 'acme' );
		$this->assertSame( '1 Main St', get_post_meta( $company->ID, 'jlt_address', true ) );

		$second = $this->rows_from(
			array(
				array( 'slug', 'name', 'address' ),
				array( 'acme', 'Acme', '' ),
			)
		);
		jlt_import_companies( $second, false );
		$company = jlt_get_company_by_slug( 'acme' );
		$this->assertSame( '1 Main St', get_post_meta( $company->ID, 'jlt_address', true ) );
	}

	public function test_slug_clash_with_other_post_type_fails() {
		self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Taken',
				'post_name'   => 'taken',
			)
		);

		$rows = $this->rows_from(
			array(
				array( 'slug', 'name' ),
				array( 'taken', 'Taken Co' ),
			)
		);
		$results = jlt_import_companies( $rows, false );
		$this->assertSame( 'error', $results[0]['action'] );
		$this->assertNull( jlt_get_company_by_slug( 'taken' ) );
	}
}
