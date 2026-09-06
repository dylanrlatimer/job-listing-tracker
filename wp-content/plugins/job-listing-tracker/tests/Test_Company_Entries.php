<?php
/**
 * Company entry ownership, uniqueness, validation, and removal tests.
 */
class Test_Company_Entries extends WP_UnitTestCase {

	/**
	 * Subscriber user A.
	 *
	 * @var int
	 */
	protected $user_a;

	/**
	 * Subscriber user B.
	 *
	 * @var int
	 */
	protected $user_b;

	/**
	 * Published company post ID.
	 *
	 * @var int
	 */
	protected $company_id;

	/**
	 * WordPress core still calls PHPUnit 9's parseTestMethodAnnotations().
	 * These tests do not use @expectedDeprecated annotations.
	 */
	public function expectDeprecated() {}

	/**
	 * Creates two subscribers and one published company.
	 */
	public function set_up() {
		parent::set_up();

		$this->user_a     = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->user_b     = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->company_id = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_company',
				'post_status' => 'publish',
				'post_title'  => 'Acme Corp',
			)
		);
	}

	/**
	 * jlt_get_company_entry returns null before any entry exists.
	 */
	public function test_get_entry_returns_null_when_none() {
		$this->assertNull( jlt_get_company_entry( $this->user_a, $this->company_id ) );
	}

	/**
	 * Adding a company creates an entry with the expected meta.
	 */
	public function test_add_creates_entry_with_correct_meta() {
		$entry = jlt_add_company_to_bank( $this->user_a, $this->company_id );

		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( 'jlt_company_entry', $entry->post_type );
		$this->assertSame( $this->user_a, (int) $entry->post_author );
		$this->assertSame( $this->company_id, absint( get_post_meta( $entry->ID, 'jlt_company_id', true ) ) );
		$this->assertSame( 'interested', get_post_meta( $entry->ID, 'jlt_status', true ) );
	}

	/**
	 * A second add returns the existing post and does not insert a duplicate.
	 */
	public function test_add_is_idempotent() {
		$first  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$second = jlt_add_company_to_bank( $this->user_a, $this->company_id );

		$this->assertInstanceOf( WP_Post::class, $first );
		$this->assertInstanceOf( WP_Post::class, $second );
		$this->assertSame( $first->ID, $second->ID );

		$all = get_posts(
			array(
				'post_type'      => 'jlt_company_entry',
				'post_status'    => 'publish',
				'author'         => $this->user_a,
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => 'jlt_company_id',
						'value' => $this->company_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		$this->assertCount( 1, $all );
	}

	/**
	 * Two users receive independent entries for the same company.
	 */
	public function test_two_users_get_independent_entries() {
		$entry_a = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$entry_b = jlt_add_company_to_bank( $this->user_b, $this->company_id );

		$this->assertInstanceOf( WP_Post::class, $entry_a );
		$this->assertInstanceOf( WP_Post::class, $entry_b );
		$this->assertNotSame( $entry_a->ID, $entry_b->ID );
		$this->assertSame( $this->user_a, (int) $entry_a->post_author );
		$this->assertSame( $this->user_b, (int) $entry_b->post_author );
	}

	/**
	 * Update persists a new status and notes.
	 */
	public function test_update_changes_status_and_notes() {
		$entry  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$result = jlt_update_company_entry( $entry->ID, $this->user_a, 'contacted', 'Called hiring manager.' );

		$this->assertInstanceOf( WP_Post::class, $result );
		$refetched = get_post( $entry->ID );
		$this->assertSame( 'contacted', get_post_meta( $entry->ID, 'jlt_status', true ) );
		$this->assertSame( 'Called hiring manager.', $refetched->post_content );
	}

	/**
	 * An unknown status string is rejected.
	 */
	public function test_update_rejects_invalid_status() {
		$entry  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$result = jlt_update_company_entry( $entry->ID, $this->user_a, 'hired', 'Nope' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_status', $result->get_error_code() );
	}

	/**
	 * User B cannot update User A's entry.
	 */
	public function test_update_rejects_cross_user_access() {
		$entry  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$result = jlt_update_company_entry( $entry->ID, $this->user_b, 'contacted', 'Stolen notes' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'unauthorized', $result->get_error_code() );
		$this->assertSame( 'interested', get_post_meta( $entry->ID, 'jlt_status', true ) );
	}

	/**
	 * Listing entries returns only the requesting user's records.
	 */
	public function test_get_entries_returns_only_own() {
		jlt_add_company_to_bank( $this->user_a, $this->company_id );
		jlt_add_company_to_bank( $this->user_b, $this->company_id );

		$entries_b = jlt_get_company_entries( $this->user_b );

		$this->assertCount( 1, $entries_b );
		$this->assertSame( $this->user_b, (int) $entries_b[0]->post_author );
	}

	/**
	 * HTML is stripped from notes; remaining plain text is stored.
	 */
	public function test_notes_stripped_of_html() {
		$entry  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$result = jlt_update_company_entry(
			$entry->ID,
			$this->user_a,
			'interested',
			'<script>alert(1)</script>Follow up next week'
		);

		$this->assertInstanceOf( WP_Post::class, $result );
		$refetched = get_post( $entry->ID );
		$this->assertStringNotContainsString( '<script>', $refetched->post_content );
		$this->assertStringContainsString( 'Follow up next week', $refetched->post_content );
	}

	/**
	 * Removal succeeds when the user has no tracked positions.
	 */
	public function test_remove_succeeds_without_positions() {
		$entry  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$result = jlt_remove_company_from_bank( $entry->ID, $this->user_a );

		$this->assertTrue( $result );
		$this->assertNull( get_post( $entry->ID ) );
	}

	/**
	 * Removal is blocked when a position entry exists at that company.
	 */
	public function test_remove_blocked_with_position_entry() {
		$entry = jlt_add_company_to_bank( $this->user_a, $this->company_id );

		$position_id = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_position',
				'post_status' => 'publish',
				'post_title'  => 'Engineer',
				'meta_input'  => array(
					'jlt_company_id' => $this->company_id,
				),
			)
		);

		wp_insert_post(
			array(
				'post_type'   => 'jlt_position_entry',
				'post_status' => 'publish',
				'post_author' => $this->user_a,
				'post_title'  => sprintf( 'User %d — Position %d', $this->user_a, $position_id ),
				'meta_input'  => array(
					'jlt_position_id' => $position_id,
				),
			)
		);

		$result = jlt_remove_company_from_bank( $entry->ID, $this->user_a );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'has_positions', $result->get_error_code() );
		$this->assertInstanceOf( WP_Post::class, get_post( $entry->ID ) );
	}

	/**
	 * An unpublished company cannot be added to the bank.
	 */
	public function test_add_rejects_unpublished_company() {
		$draft_id = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_company',
				'post_status' => 'draft',
				'post_title'  => 'Draft Co',
			)
		);

		$result = jlt_add_company_to_bank( $this->user_a, $draft_id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_company', $result->get_error_code() );
	}
}
