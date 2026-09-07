<?php
$position = isset( $args['position'] ) && $args['position'] instanceof WP_Post
	? $args['position']
	: null;

if ( ! $position ) {
	return;
}

$meta = jlt_get_position_meta( $position->ID );
?>
<li class="position-card position-availability--<?php echo esc_attr( $meta['availability'] ); ?>">
	<h3 class="position-card__title">
		<a href="<?php echo esc_url( get_permalink( $position ) ); ?>">
			<?php echo esc_html( $position->post_title ); ?>
		</a>
	</h3>

	<p class="position-card__meta">
		<span class="badge badge--<?php echo esc_attr( $meta['availability'] ); ?>">
			<?php echo esc_html( ucfirst( $meta['availability'] ) ); ?>
		</span>
		<?php if ( $meta['location'] ) : ?>
			<span><?php echo esc_html( $meta['location'] ); ?></span>
		<?php endif; ?>
	</p>
</li>
