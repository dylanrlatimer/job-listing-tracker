<?php
$position = isset( $args['position'] ) && $args['position'] instanceof WP_Post
	? $args['position']
	: null;

if ( ! $position ) {
	return;
}

$meta = jlt_get_position_meta( $position->ID );
?>
<tr class="position-table__row position-availability--<?php echo esc_attr( $meta['availability'] ); ?>">
	<th scope="row" class="position-table__title">
		<a href="<?php echo esc_url( get_permalink( $position ) ); ?>">
			<?php echo esc_html( $position->post_title ); ?>
		</a>
	</th>
	<td><?php echo esc_html( ucfirst( $meta['availability'] ) ); ?></td>
	<td><?php echo $meta['location'] ? esc_html( $meta['location'] ) : '&mdash;'; ?></td>
</tr>
