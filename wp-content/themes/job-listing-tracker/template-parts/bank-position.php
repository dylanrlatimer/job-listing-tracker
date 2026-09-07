<?php
defined( 'ABSPATH' ) || exit;

$entry              = $args['entry'] ?? null;
$notice             = $args['notice'] ?? '';
$pos_id             = 0;
$position           = null;
$position_available = false;
$pos_meta           = array();
$status             = 'interested';
$applied_on         = '';
$notes              = '';
$company_entry      = null;

if ( $entry instanceof WP_Post ) {
	$pos_id             = absint( get_post_meta( $entry->ID, 'jlt_position_id', true ) );
	$position           = $pos_id ? get_post( $pos_id ) : null;
	$position_available = $position instanceof WP_Post && 'publish' === $position->post_status;
	$pos_meta           = $position_available ? jlt_get_position_meta( $pos_id ) : array();
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
		<h2>
			<?php
			echo $position_available
				? esc_html( $position->post_title )
				: esc_html__( '(position unavailable)', 'job-listing-tracker' );
			?>
		</h2>

		<?php if ( $pos_meta ) : ?>
			<dl class="position-details">
				<?php if ( ! empty( $pos_meta['availability'] ) ) : ?>
					<dt><?php esc_html_e( 'Availability', 'job-listing-tracker' ); ?></dt>
					<dd class="position-availability--<?php echo esc_attr( $pos_meta['availability'] ); ?>">
						<?php echo esc_html( ucfirst( $pos_meta['availability'] ) ); ?>
					</dd>
				<?php endif; ?>

				<?php if ( ! empty( $pos_meta['location'] ) ) : ?>
					<dt><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></dt>
					<dd><?php echo esc_html( $pos_meta['location'] ); ?></dd>
				<?php endif; ?>

				<?php if ( ! empty( $pos_meta['source_url'] ) ) : ?>
					<dt><?php esc_html_e( 'Source', 'job-listing-tracker' ); ?></dt>
					<dd>
						<a href="<?php echo esc_url( $pos_meta['source_url'] ); ?>"
						   target="_blank"
						   rel="noopener noreferrer">
							<?php esc_html_e( 'View Original Listing', 'job-listing-tracker' ); ?>
						</a>
					</dd>
				<?php endif; ?>
			</dl>
		<?php endif; ?>

		<form class="bank-entry-status-form" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_update_position' ) ); ?>">
			<?php wp_nonce_field( 'jlt_update_position', 'jlt_nonce' ); ?>
			<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">

			<label for="jlt-position-status"><?php esc_html_e( 'Status', 'job-listing-tracker' ); ?></label>
			<select id="jlt-position-status" name="status">
				<?php foreach ( jlt_position_status_values() as $value ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>>
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $value ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="jlt-position-applied-on"><?php esc_html_e( 'Applied date', 'job-listing-tracker' ); ?></label>
			<input id="jlt-position-applied-on" type="date" name="applied_on" value="<?php echo esc_attr( $applied_on ); ?>">

			<label for="jlt-position-notes"><?php esc_html_e( 'Notes', 'job-listing-tracker' ); ?></label>
			<textarea id="jlt-position-notes" name="notes" rows="6"><?php echo esc_textarea( $notes ); ?></textarea>

			<button type="submit"><?php esc_html_e( 'Save changes', 'job-listing-tracker' ); ?></button>
		</form>

		<form class="bank-remove-form" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_remove_position' ) ); ?>">
			<?php wp_nonce_field( 'jlt_remove_position', 'jlt_nonce' ); ?>
			<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">
			<button type="submit"><?php esc_html_e( 'Stop tracking', 'job-listing-tracker' ); ?></button>
		</form>

		<p class="bank-entry-back">
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
	<?php endif; ?>
</section>
