<?php
defined( 'ABSPATH' ) || exit;

$entry  = $args['entry'] ?? null;
$notice = $args['notice'] ?? '';
get_template_part( 'template-parts/notices', null, array( 'notice' => $notice ) );
?>
<section class="bank-position">
	<?php if ( $entry instanceof WP_Post ) : ?>
		<p><?php
		printf(
			/* translators: %d: entry post ID */
			esc_html__( 'Position entry %d — details coming in a later milestone.', 'job-listing-tracker' ),
			(int) $entry->ID
		);
		?></p>
	<?php endif; ?>
</section>
