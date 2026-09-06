<?php
defined( 'ABSPATH' ) || exit;

$entry      = $args['entry'] ?? null;
$notice     = $args['notice'] ?? '';
$company    = null;
$company_id = 0;
$meta       = array();
$status     = 'interested';
$notes      = '';
$has_pos    = false;

if ( $entry instanceof WP_Post ) {
	$company_id = absint( get_post_meta( $entry->ID, 'jlt_company_id', true ) );
	$company    = $company_id ? get_post( $company_id ) : null;
	$meta       = $company ? jlt_get_company_meta( $company_id ) : array();
	$status     = (string) get_post_meta( $entry->ID, 'jlt_status', true );
	$notes      = $entry->post_content;
	$has_pos    = jlt_has_tracked_positions_at_company( $company_id, (int) $entry->post_author );
}

get_template_part( 'template-parts/notices', null, array( 'notice' => $notice ) );
?>
<section class="bank-company">
	<?php if ( $entry instanceof WP_Post ) : ?>
		<h2>
			<?php
			echo $company instanceof WP_Post
				? esc_html( $company->post_title )
				: esc_html__( '(company unavailable)', 'job-listing-tracker' );
			?>
		</h2>

		<?php if ( $meta ) : ?>
			<dl class="company-details">
				<?php if ( ! empty( $meta['type'] ) ) : ?>
					<dt><?php esc_html_e( 'Type', 'job-listing-tracker' ); ?></dt>
					<dd><?php echo esc_html( $meta['type'] ); ?></dd>
				<?php endif; ?>

				<?php if ( ! empty( $meta['location'] ) ) : ?>
					<dt><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></dt>
					<dd><?php echo esc_html( $meta['location'] ); ?></dd>
				<?php endif; ?>

				<?php if ( ! empty( $meta['website'] ) ) : ?>
					<dt><?php esc_html_e( 'Website', 'job-listing-tracker' ); ?></dt>
					<dd>
						<a href="<?php echo esc_url( $meta['website'] ); ?>"
						   target="_blank"
						   rel="noopener noreferrer">
							<?php echo esc_html( $meta['website'] ); ?>
						</a>
					</dd>
				<?php endif; ?>
			</dl>
		<?php endif; ?>

		<form class="bank-entry-status-form" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_update_company' ) ); ?>">
			<?php wp_nonce_field( 'jlt_update_company', 'jlt_nonce' ); ?>
			<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">

			<label for="jlt-company-status"><?php esc_html_e( 'Status', 'job-listing-tracker' ); ?></label>
			<select id="jlt-company-status" name="status">
				<?php foreach ( jlt_company_status_values() as $value ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>>
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $value ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="jlt-company-notes"><?php esc_html_e( 'Notes', 'job-listing-tracker' ); ?></label>
			<textarea id="jlt-company-notes" name="notes" rows="6"><?php echo esc_textarea( $notes ); ?></textarea>

			<button type="submit"><?php esc_html_e( 'Save changes', 'job-listing-tracker' ); ?></button>
		</form>

		<?php if ( $has_pos ) : ?>
			<p><?php esc_html_e( 'Remove all tracked positions at this company first.', 'job-listing-tracker' ); ?></p>
		<?php else : ?>
			<form class="bank-remove-form" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_remove_company' ) ); ?>">
				<?php wp_nonce_field( 'jlt_remove_company', 'jlt_nonce' ); ?>
				<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">
				<button type="submit"><?php esc_html_e( 'Remove from bank', 'job-listing-tracker' ); ?></button>
			</form>
		<?php endif; ?>

		<p class="bank-entry-back">
			<a href="<?php echo esc_url( jlt_bank_url() ); ?>">
				<?php esc_html_e( 'Back to bank', 'job-listing-tracker' ); ?>
			</a>
		</p>
	<?php endif; ?>
</section>
