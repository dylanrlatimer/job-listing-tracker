<?php
defined( 'ABSPATH' ) || exit;

$notice  = $args['notice'] ?? '';
$entries = jlt_get_company_entries( get_current_user_id() );

get_template_part( 'template-parts/notices', null, array( 'notice' => $notice ) );
?>
<section class="bank-overview">
	<?php if ( $entries ) : ?>
		<ul class="bank-company-list">
			<?php foreach ( $entries as $entry ) : ?>
				<?php
				$company_id = absint( get_post_meta( $entry->ID, 'jlt_company_id', true ) );
				$company    = $company_id ? get_post( $company_id ) : null;
				$status     = (string) get_post_meta( $entry->ID, 'jlt_status', true );
				$label      = $company instanceof WP_Post
					? $company->post_title
					: __( '(company unavailable)', 'job-listing-tracker' );
				$status_label = ucwords( str_replace( '_', ' ', $status ) );
				?>
				<li class="bank-company-list__item">
					<a href="<?php echo esc_url( jlt_bank_entry_url( 'company', $entry->ID ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
					<?php if ( $status_label ) : ?>
						<span class="bank-company-list__status"><?php echo esc_html( $status_label ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p><?php esc_html_e( 'You have not added any companies to your bank yet.', 'job-listing-tracker' ); ?></p>
		<p>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'jlt_company' ) ); ?>">
				<?php esc_html_e( 'Browse the company directory', 'job-listing-tracker' ); ?>
			</a>
		</p>
	<?php endif; ?>
</section>
