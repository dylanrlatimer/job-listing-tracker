<li class="company-card">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="company-card__logo">
			<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'thumbnail' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<h2 class="company-card__name">
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h2>

	<?php
	$meta = jlt_get_company_meta( get_the_ID() );
	?>

	<?php if ( $meta['type'] ) : ?>
		<p class="company-card__type"><?php echo esc_html( $meta['type'] ); ?></p>
	<?php endif; ?>

	<?php if ( $meta['location'] ) : ?>
		<p class="company-card__location"><?php echo esc_html( $meta['location'] ); ?></p>
	<?php endif; ?>
</li>
