<?php
defined( 'ABSPATH' ) || exit;

$post_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_ID();
if ( ! $post_id ) {
	return;
}

$meta    = jlt_get_position_meta( $post_id );
$company = $meta['company_id'] ? get_post( $meta['company_id'] ) : null;
?>
<dl class="facts">
	<dt><?php esc_html_e( 'Availability', 'job-listing-tracker' ); ?></dt>
	<dd>
		<span class="badge badge--<?php echo esc_attr( $meta['availability'] ); ?>">
			<?php echo esc_html( ucfirst( $meta['availability'] ) ); ?>
		</span>
	</dd>

	<?php if ( $company instanceof WP_Post ) : ?>
		<dt><?php esc_html_e( 'Company', 'job-listing-tracker' ); ?></dt>
		<dd>
			<a href="<?php echo esc_url( get_permalink( $company ) ); ?>">
				<?php echo esc_html( $company->post_title ); ?>
			</a>
		</dd>
	<?php endif; ?>

	<?php if ( $meta['location'] ) : ?>
		<dt><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></dt>
		<dd><?php echo esc_html( $meta['location'] ); ?></dd>
	<?php endif; ?>

	<?php if ( $meta['tech_stack'] ) : ?>
		<dt><?php esc_html_e( 'Technology stack', 'job-listing-tracker' ); ?></dt>
		<dd><?php echo nl2br( esc_html( $meta['tech_stack'] ) ); ?></dd>
	<?php endif; ?>

	<?php if ( $meta['source_url'] ) : ?>
		<dt><?php esc_html_e( 'Source', 'job-listing-tracker' ); ?></dt>
		<dd>
			<a href="<?php echo esc_url( $meta['source_url'] ); ?>"
			   target="_blank"
			   rel="noopener noreferrer">
				<?php esc_html_e( 'View original listing', 'job-listing-tracker' ); ?>
			</a>
		</dd>
	<?php endif; ?>
</dl>
