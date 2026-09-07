<?php
/**
 * Position entry ownership, uniqueness, validation, and removal tests.
 */
class Test_Position_Entries extends WP_UnitTestCase {

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
	 * Published position post ID at $company_id.
	 *
	 * @var int
	 */
	protected $position_id;

	/**
	 * User A's company entry for $company_id.
	 *
	 * @var WP_Post
	 */
	protected $company_entry;

	/**
	 * WordPress core still calls PHPUnit 9's parseTestMethodAnnotations().
	 * These tests do not use @expectedDeprecated annotations.
	 */
	public function expectDeprecated() {}

	/**
	 * Creates two subscribers, one company, one position, and banks the company for user A.
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
		$this->position_id = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_position',
				'post_status' => 'publish',
				'post_title'  => 'Engineer',
				'meta_input'  => array(
					'jlt_company_id' => $this->company_id,
				),
			)
		);

		$this->company_entry = jlt_add_company_to_bank( $this->user_a, $this->company_id );
	}

	/**
	 * Lookup before tracking returns null.
	 */
	public function test_get_entry_returns_null_when_none() {
		$this->assertNull( jlt_get_position_entry( $this->user_a, $this->position_id ) );
	}

	/**
	 * Tracking creates an entry with the expected meta.
	 */
	public function test_track_creates_entry_with_correct_meta() {
		$entry = jlt_track_position( $this->user_a, $this->position_id );

		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( 'jlt_position_entry', $entry->post_type );
		$this->assertSame( $this->user_a, (int) $entry->post_author );
		$this->assertSame( $this->position_id, absint( get_post_meta( $entry->ID, 'jlt_position_id', true ) ) );
		$this->assertSame( 'interested', get_post_meta( $entry->ID, 'jlt_status', true ) );
	}

	/**
	 * A second track returns the existing post and does not insert a duplicate.
	 */
	public function test_track_is_idempotent() {
		$first  = jlt_track_position( $this->user_a, $this->position_id );
		$second = jlt_track_position( $this->user_a, $this->position_id );

		$this->assertInstanceOf( WP_Post::class, $first );
		$this->assertInstanceOf( WP_Post::class, $second );
		$this->assertSame( $first->ID, $second->ID );

		$all = get_posts(
			array(
				'post_type'      => 'jlt_position_entry',
				'post_status'    => 'publish',
				'author'         => $this->user_a,
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => 'jlt_position_id',
						'value' => $this->position_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		$this->assertCount( 1, $all );
	}

	/**
	 * Tracking without a company entry is rejected.
	 */
	public function test_track_requires_company_entry() {
		$result = jlt_track_position( $this->user_b, $this->position_id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'no_company_entry', $result->get_error_code() );
	}

	/**
	 * Tracking a position at a company the user has not saved is rejected.
	 */
	public function test_track_requires_matching_company_entry() {
		$other_company = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_company',
				'post_status' => 'publish',
				'post_title'  => 'Other Co',
			)
		);
		$other_position = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_position',
				'post_status' => 'publish',
				'post_title'  => 'Other Role',
				'meta_input'  => array(
					'jlt_company_id' => $other_company,
				),
			)
		);

		$result = jlt_track_position( $this->user_a, $other_position );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'no_company_entry', $result->get_error_code() );
	}

	/**
	 * Update persists status, notes, and applied date.
	 */
	public function test_update_changes_status_notes_and_date() {
		$entry  = jlt_track_position( $this->user_a, $this->position_id );
		$result = jlt_update_position_entry( $entry->ID, $this->user_a, 'applied', '2024-03-15', 'Sent resume.' );

		$this->assertInstanceOf( WP_Post::class, $result );
		$refetched = get_post( $entry->ID );
		$this->assertSame( 'applied', get_post_meta( $entry->ID, 'jlt_status', true ) );
		$this->assertSame( '2024-03-15', get_post_meta( $entry->ID, 'jlt_applied_on', true ) );
		$this->assertSame( 'Sent resume.', $refetched->post_content );
	}

	/**
	 * An unknown status string is rejected.
	 */
	public function test_update_rejects_invalid_status() {
		$entry  = jlt_track_position( $this->user_a, $this->position_id );
		$result = jlt_update_position_entry( $entry->ID, $this->user_a, 'hired', '', 'Nope' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_status', $result->get_error_code() );
	}

	/**
	 * User B cannot update User A's entry.
	 */
	public function test_update_rejects_cross_user_access() {
		$entry  = jlt_track_position( $this->user_a, $this->position_id );
		$result = jlt_update_position_entry( $entry->ID, $this->user_b, 'applied', '2024-03-15', 'Stolen notes' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'unauthorized', $result->get_error_code() );
		$this->assertSame( 'interested', get_post_meta( $entry->ID, 'jlt_status', true ) );
	}

	/**
	 * A real calendar date is stored as-is.
	 */
	public function test_valid_applied_date_is_stored() {
		$entry = jlt_track_position( $this->user_a, $this->position_id );
		jlt_update_position_entry( $entry->ID, $this->user_a, 'applied', '2024-03-15', '' );

		$this->assertSame( '2024-03-15', get_post_meta( $entry->ID, 'jlt_applied_on', true ) );
	}

	/**
	 * A non-date string is stored as empty.
	 */
	public function test_invalid_date_format_is_stored_empty() {
		$entry = jlt_track_position( $this->user_a, $this->position_id );
		jlt_update_position_entry( $entry->ID, $this->user_a, 'interested', 'not-a-date', '' );

		$this->assertSame( '', get_post_meta( $entry->ID, 'jlt_applied_on', true ) );
	}

	/**
	 * An impossible calendar date is stored as empty.
	 */
	public function test_nonexistent_date_is_stored_empty() {
		$entry = jlt_track_position( $this->user_a, $this->position_id );
		jlt_update_position_entry( $entry->ID, $this->user_a, 'interested', '2024-02-30', '' );

		$this->assertSame( '', get_post_meta( $entry->ID, 'jlt_applied_on', true ) );
	}

	/**
	 * Removal succeeds and deletes the post.
	 */
	public function test_remove_succeeds() {
		$entry  = jlt_track_position( $this->user_a, $this->position_id );
		$result = jlt_remove_position_tracking( $entry->ID, $this->user_a );

		$this->assertTrue( $result );
		$this->assertNull( get_post( $entry->ID ) );
	}

	/**
	 * Removing a position entry unblocks company removal.
	 */
	public function test_removing_entry_unblocks_company_removal() {
		$position_entry = jlt_track_position( $this->user_a, $this->position_id );
		$blocked        = jlt_remove_company_from_bank( $this->company_entry->ID, $this->user_a );

		$this->assertInstanceOf( WP_Error::class, $blocked );
		$this->assertSame( 'has_positions', $blocked->get_error_code() );

		jlt_remove_position_tracking( $position_entry->ID, $this->user_a );
		$result = jlt_remove_company_from_bank( $this->company_entry->ID, $this->user_a );

		$this->assertTrue( $result );
		$this->assertNull( get_post( $this->company_entry->ID ) );
	}
}
