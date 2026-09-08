<?php
defined( 'ABSPATH' ) || exit;

$entry              = $args['entry'] ?? null;
$notice             = $args['notice'] ?? '';
$pos_id             = 0;
$position           = null;
$position_available = false;
$status             = 'interested';
$applied_on         = '';
$notes              = '';
$company_entry      = null;

if ( $entry instanceof WP_Post ) {
	$pos_id             = absint( get_post_meta( $entry->ID, 'jlt_position_id', true ) );
	$position           = $pos_id ? get_post( $pos_id ) : null;
	$position_available = $position instanceof WP_Post && 'publish' === $position->post_status;
	$status             = (string) get_post_meta( $entry->ID, 'jlt_status', true );
	$applied_on         = (string) get_post_meta( $entry->ID, 'jlt_applied_on', true );
	$notes              = $entry->post_content;

	$linked_company_id = $position instanceof WP_Post
		? absint( get_post_meta( $pos_id, 'jlt_company_id', true ) )
		: 0;
	if ( $linked_company_id ) {
		$company_entry = jlt_get_company_entry( (int) $entry->post_author, $linked_company_id );
	}
}

get_template_part( 'template-parts/notices', null, array( 'notice' => $notice ) );
?>
<section class="bank-position">
	<?php if ( $entry instanceof WP_Post ) : ?>
		<header class="page-header">
			<h1>
				<?php
				echo $position_available
					? esc_html( $position->post_title )
					: esc_html__( '(position unavailable)', 'job-listing-tracker' );
				?>
			</h1>
		</header>

		<?php if ( $position_available ) : ?>
			<?php get_template_part( 'template-parts/position-facts', null, array( 'post_id' => $pos_id ) ); ?>
		<?php endif; ?>

		<form class="form section" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_update_position' ) ); ?>">
			<?php wp_nonce_field( 'jlt_update_position', 'jlt_nonce' ); ?>
			<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">

			<div class="field">
				<label for="jlt-position-status"><?php esc_html_e( 'Status', 'job-listing-tracker' ); ?></label>
				<select id="jlt-position-status" name="status">
					<?php foreach ( jlt_position_status_values() as $value ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>>
							<?php echo esc_html( ucwords( str_replace( '_', ' ', $value ) ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="field">
				<label for="jlt-position-applied-on"><?php esc_html_e( 'Applied date', 'job-listing-tracker' ); ?></label>
				<input id="jlt-position-applied-on" type="date" name="applied_on" value="<?php echo esc_attr( $applied_on ); ?>">
			</div>

			<div class="field">
				<label for="jlt-position-notes"><?php esc_html_e( 'Notes', 'job-listing-tracker' ); ?></label>
				<textarea id="jlt-position-notes" name="notes" rows="6"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>

			<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Save changes', 'job-listing-tracker' ); ?></button>
		</form>

		<div class="bank-footer">
			<p class="back-link">
				<?php if ( $company_entry instanceof WP_Post ) : ?>
					<a href="<?php echo esc_url( jlt_bank_entry_url( 'company', $company_entry->ID ) ); ?>">
						<?php esc_html_e( 'Back to company', 'job-listing-tracker' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( jlt_bank_url() ); ?>">
						<?php esc_html_e( 'Back to bank', 'job-listing-tracker' ); ?>
					</a>
				<?php endif; ?>
			</p>
			<form class="bank-remove" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_remove_position' ) ); ?>">
				<?php wp_nonce_field( 'jlt_remove_position', 'jlt_nonce' ); ?>
				<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">
				<button class="btn btn--danger" type="submit"><?php esc_html_e( 'Stop tracking', 'job-listing-tracker' ); ?></button>
			</form>
		</div>
	<?php endif; ?>
</section>
