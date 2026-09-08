<?php
defined( 'ABSPATH' ) || exit;

$post_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_ID();
if ( ! $post_id ) {
	return;
}

$meta = jlt_get_company_meta( $post_id );
$has  = $meta['type'] || $meta['location'] || $meta['address'] || $meta['website'];
if ( ! $has ) {
	return;
}
?>
<dl class="facts">
	<?php if ( $meta['type'] ) : ?>
		<dt><?php esc_html_e( 'Type', 'job-listing-tracker' ); ?></dt>
		<dd><?php echo esc_html( $meta['type'] ); ?></dd>
	<?php endif; ?>

	<?php if ( $meta['location'] ) : ?>
		<dt><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></dt>
		<dd><?php echo esc_html( $meta['location'] ); ?></dd>
	<?php endif; ?>

	<?php if ( $meta['address'] ) : ?>
		<dt><?php esc_html_e( 'Address', 'job-listing-tracker' ); ?></dt>
		<dd><?php echo esc_html( $meta['address'] ); ?></dd>
	<?php endif; ?>

	<?php if ( $meta['website'] ) : ?>
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
