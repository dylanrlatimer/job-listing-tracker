<?php
/**
 * Trash, restore, and permanent-delete cascade tests.
 */
class Test_Lifecycle extends WP_UnitTestCase {

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
	 * User A's position entry for $position_id.
	 *
	 * @var WP_Post
	 */
	protected $position_entry;

	/**
	 * WordPress core still calls PHPUnit 9's parseTestMethodAnnotations().
	 * These tests do not use @expectedDeprecated annotations.
	 */
	public function expectDeprecated() {}

	/**
	 * Creates two subscribers, one company, one position, and banks both for user A.
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

		$this->company_entry  = jlt_add_company_to_bank( $this->user_a, $this->company_id );
		$this->position_entry = jlt_track_position( $this->user_a, $this->position_id );
	}

	/**
	 * Permanently deleting a position deletes its position entries.
	 */
	public function test_deleting_position_permanently_deletes_its_entries() {
		wp_delete_post( $this->position_id, true );

		$this->assertNull( get_post( $this->position_entry->ID ) );
	}

	/**
	 * Permanently deleting a position leaves the company entry in place.
	 */
	public function test_deleting_position_does_not_touch_company_entry() {
		wp_delete_post( $this->position_id, true );

		$entry = jlt_get_company_entry( $this->user_a, $this->company_id );
		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( $this->company_entry->ID, $entry->ID );
	}

	/**
	 * Permanently deleting a position deletes entries for every user who tracked it.
	 */
	public function test_deleting_position_with_two_users_cleans_both_entries() {
		jlt_add_company_to_bank( $this->user_b, $this->company_id );
		$entry_b = jlt_track_position( $this->user_b, $this->position_id );

		wp_delete_post( $this->position_id, true );

		$this->assertNull( get_post( $this->position_entry->ID ) );
		$this->assertNull( get_post( $entry_b->ID ) );
	}

	/**
	 * Permanently deleting a position with no entries does not error.
	 */
	public function test_deleting_position_with_no_entries_does_not_error() {
		$orphan_position = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_position',
				'post_status' => 'publish',
				'post_title'  => 'Untracked Role',
				'meta_input'  => array(
					'jlt_company_id' => $this->company_id,
				),
			)
		);

		$result = wp_delete_post( $orphan_position, true );

		$this->assertInstanceOf( WP_Post::class, $result );
		$this->assertNull( get_post( $orphan_position ) );
	}

	/**
	 * Permanently deleting a company deletes its company entries.
	 */
	public function test_deleting_company_permanently_deletes_company_entries() {
		wp_delete_post( $this->company_id, true );

		$this->assertNull( get_post( $this->company_entry->ID ) );
	}

	/**
	 * Permanently deleting a company deletes its positions.
	 */
	public function test_deleting_company_permanently_deletes_positions() {
		wp_delete_post( $this->company_id, true );

		$this->assertNull( get_post( $this->position_id ) );
	}

	/**
	 * Permanently deleting a company deletes related position entries.
	 */
	public function test_deleting_company_permanently_deletes_position_entries() {
		wp_delete_post( $this->company_id, true );

		$this->assertNull( get_post( $this->position_entry->ID ) );
	}

	/**
	 * Permanently deleting a company deletes entries for every user who saved it.
	 */
	public function test_deleting_company_with_two_users_cleans_all_entries() {
		$company_entry_b  = jlt_add_company_to_bank( $this->user_b, $this->company_id );
		$position_entry_b = jlt_track_position( $this->user_b, $this->position_id );

		wp_delete_post( $this->company_id, true );

		$this->assertNull( get_post( $this->company_entry->ID ) );
		$this->assertNull( get_post( $this->position_entry->ID ) );
		$this->assertNull( get_post( $company_entry_b->ID ) );
		$this->assertNull( get_post( $position_entry_b->ID ) );
	}

	/**
	 * Permanently deleting a company with no positions does not error.
	 */
	public function test_deleting_company_with_no_positions_does_not_error() {
		$empty_company = self::factory()->post->create(
			array(
				'post_type'   => 'jlt_company',
				'post_status' => 'publish',
				'post_title'  => 'Empty Co',
			)
		);

		$result = wp_delete_post( $empty_company, true );

		$this->assertInstanceOf( WP_Post::class, $result );
		$this->assertNull( get_post( $empty_company ) );
	}

	/**
	 * Trashing a position leaves its position entry in place.
	 */
	public function test_trashing_position_preserves_position_entry() {
		wp_trash_post( $this->position_id );

		$entry = jlt_get_position_entry( $this->user_a, $this->position_id );
		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( $this->position_entry->ID, $entry->ID );
	}

	/**
	 * Restoring a trashed position leaves its position entry in place.
	 */
	public function test_restoring_position_preserves_position_entry() {
		wp_trash_post( $this->position_id );
		wp_untrash_post( $this->position_id );

		$entry = jlt_get_position_entry( $this->user_a, $this->position_id );
		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( $this->position_entry->ID, $entry->ID );
	}

	/**
	 * Trashing a company leaves its company entry in place.
	 */
	public function test_trashing_company_preserves_company_entry() {
		wp_trash_post( $this->company_id );

		$entry = jlt_get_company_entry( $this->user_a, $this->company_id );
		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( $this->company_entry->ID, $entry->ID );
	}

	/**
	 * Trashing a company leaves related position entries in place.
	 */
	public function test_trashing_company_preserves_position_entry() {
		wp_trash_post( $this->company_id );

		$entry = jlt_get_position_entry( $this->user_a, $this->position_id );
		$this->assertInstanceOf( WP_Post::class, $entry );
		$this->assertSame( $this->position_entry->ID, $entry->ID );
	}
}
