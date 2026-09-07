<?php
defined( 'ABSPATH' ) || exit;

$entry             = $args['entry'] ?? null;
$notice            = $args['notice'] ?? '';
$company           = null;
$company_id        = 0;
$company_available = false;
$status            = 'interested';
$notes             = '';
$has_pos           = false;

if ( $entry instanceof WP_Post ) {
	$company_id        = absint( get_post_meta( $entry->ID, 'jlt_company_id', true ) );
	$company           = $company_id ? get_post( $company_id ) : null;
	$company_available = $company instanceof WP_Post && 'publish' === $company->post_status;
	$status            = (string) get_post_meta( $entry->ID, 'jlt_status', true );
	$notes             = $entry->post_content;
	$has_pos           = jlt_has_tracked_positions_at_company( $company_id, (int) $entry->post_author );
}

get_template_part( 'template-parts/notices', null, array( 'notice' => $notice ) );
?>
<section class="bank-company">
	<?php if ( $entry instanceof WP_Post ) : ?>
		<header class="page-header">
			<h1>
				<?php
				echo $company_available
					? esc_html( $company->post_title )
					: esc_html__( '(company unavailable)', 'job-listing-tracker' );
				?>
			</h1>
		</header>

		<?php if ( $company_available ) : ?>
			<?php get_template_part( 'template-parts/company-facts', null, array( 'post_id' => $company_id ) ); ?>
		<?php endif; ?>

		<?php
		$tracked_entries     = jlt_get_position_entries_for_company_entry( $entry->ID, (int) $entry->post_author );
		$available_positions = array();
		if ( $company_available ) {
			$tracked_ids = array();
			foreach ( $tracked_entries as $pe ) {
				$tracked_ids[] = absint( get_post_meta( $pe->ID, 'jlt_position_id', true ) );
			}
			$all_positions = jlt_get_company_positions( $company_id );
			foreach ( $all_positions as $pos ) {
				if ( ! in_array( $pos->ID, $tracked_ids, true ) ) {
					$available_positions[] = $pos;
				}
			}
		}
		?>

		<?php if ( $tracked_entries ) : ?>
			<section class="section" aria-label="<?php esc_attr_e( 'Tracked positions', 'job-listing-tracker' ); ?>">
				<h2><?php esc_html_e( 'Tracked positions', 'job-listing-tracker' ); ?></h2>
				<ul class="item-list">
					<?php foreach ( $tracked_entries as $pe ) : ?>
						<?php
						$pe_pos_id = absint( get_post_meta( $pe->ID, 'jlt_position_id', true ) );
						$pe_pos    = $pe_pos_id ? get_post( $pe_pos_id ) : null;
						$pe_title  = ( $pe_pos instanceof WP_Post && 'publish' === $pe_pos->post_status )
							? $pe_pos->post_title
							: __( '(position unavailable)', 'job-listing-tracker' );
						$pe_status = (string) get_post_meta( $pe->ID, 'jlt_status', true );
						?>
						<li class="item-list__item">
							<a href="<?php echo esc_url( jlt_bank_entry_url( 'position', $pe->ID ) ); ?>">
								<?php echo esc_html( $pe_title ); ?>
							</a>
							<?php if ( $pe_status ) : ?>
								<span class="badge"><?php echo esc_html( ucwords( str_replace( '_', ' ', $pe_status ) ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $available_positions ) : ?>
			<section class="section" aria-label="<?php esc_attr_e( 'Other positions', 'job-listing-tracker' ); ?>">
				<h2><?php esc_html_e( 'Other positions at this company', 'job-listing-tracker' ); ?></h2>
				<ul class="item-list">
					<?php foreach ( $available_positions as $pos ) : ?>
						<li class="item-list__item">
							<a href="<?php echo esc_url( get_permalink( $pos ) ); ?>">
								<?php echo esc_html( $pos->post_title ); ?>
							</a>
							<form method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_track_position' ) ); ?>">
								<?php wp_nonce_field( 'jlt_track_position', 'jlt_nonce' ); ?>
								<input type="hidden" name="position_id" value="<?php echo absint( $pos->ID ); ?>">
								<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Track', 'job-listing-tracker' ); ?></button>
							</form>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<form class="form section" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_update_company' ) ); ?>">
			<?php wp_nonce_field( 'jlt_update_company', 'jlt_nonce' ); ?>
			<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">

			<div class="field">
				<label for="jlt-company-status"><?php esc_html_e( 'Status', 'job-listing-tracker' ); ?></label>
				<select id="jlt-company-status" name="status">
					<?php foreach ( jlt_company_status_values() as $value ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>>
							<?php echo esc_html( ucwords( str_replace( '_', ' ', $value ) ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="field">
				<label for="jlt-company-notes"><?php esc_html_e( 'Notes', 'job-listing-tracker' ); ?></label>
				<textarea id="jlt-company-notes" name="notes" rows="6"><?php echo esc_textarea( $notes ); ?></textarea>
			</div>

			<button class="btn btn--primary" type="submit"><?php esc_html_e( 'Save changes', 'job-listing-tracker' ); ?></button>
		</form>

		<?php if ( $has_pos ) : ?>
			<p><?php esc_html_e( 'Remove all tracked positions at this company first.', 'job-listing-tracker' ); ?></p>
		<?php else : ?>
			<form class="bank-remove" method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_remove_company' ) ); ?>">
				<?php wp_nonce_field( 'jlt_remove_company', 'jlt_nonce' ); ?>
				<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">
				<button class="btn btn--danger" type="submit"><?php esc_html_e( 'Remove from bank', 'job-listing-tracker' ); ?></button>
			</form>
		<?php endif; ?>

		<p class="back-link">
			<a href="<?php echo esc_url( jlt_bank_url() ); ?>">
				<?php esc_html_e( 'Back to bank', 'job-listing-tracker' ); ?>
			</a>
		</p>
	<?php endif; ?>
</section>
