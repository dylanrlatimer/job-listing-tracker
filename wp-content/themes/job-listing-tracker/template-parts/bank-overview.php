<?php
defined( 'ABSPATH' ) || exit;

$notice = $args['notice'] ?? '';
get_template_part( 'template-parts/notices', null, array( 'notice' => $notice ) );
?>
<section class="bank-overview">
	<p><?php esc_html_e( 'You have not added any companies to your bank yet.', 'job-listing-tracker' ); ?></p>
	<p>
		<a href="<?php echo esc_url( get_post_type_archive_link( 'jlt_company' ) ); ?>">
			<?php esc_html_e( 'Browse the company directory', 'job-listing-tracker' ); ?>
		</a>
	</p>
</section>
