<?php get_header(); ?>
<main id="main" class="wrap">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$positions = jlt_get_company_positions( get_the_ID() );
		$entry     = is_user_logged_in()
			? jlt_get_company_entry( get_current_user_id(), get_the_ID() )
			: null;
		?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<header class="page-header">
			<div class="page-header__main">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="page-header__logo"><?php the_post_thumbnail( 'thumbnail' ); ?></div>
				<?php endif; ?>
				<h1><?php the_title(); ?></h1>
			</div>
			<div class="page-header__action">
				<?php if ( ! is_user_logged_in() ) : ?>
					<a class="btn btn--primary" href="<?php echo esc_url( jlt_login_url( get_permalink() ) ); ?>">
						<?php esc_html_e( 'Log in to add to bank', 'job-listing-tracker' ); ?>
					</a>
				<?php elseif ( $entry ) : ?>
					<a class="btn" href="<?php echo esc_url( jlt_bank_entry_url( 'company', $entry->ID ) ); ?>">
						<?php esc_html_e( 'Edit in bank', 'job-listing-tracker' ); ?>
					</a>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( jlt_action_url( 'jlt_add_company' ) ); ?>">
						<?php wp_nonce_field( 'jlt_add_company', 'jlt_nonce' ); ?>
						<input type="hidden" name="company_id" value="<?php echo absint( get_the_ID() ); ?>">
						<button class="btn btn--primary" type="submit">
							<?php esc_html_e( 'Add to bank', 'job-listing-tracker' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</div>
		</header>

		<?php get_template_part( 'template-parts/company-facts', null, array( 'post_id' => get_the_ID() ) ); ?>

		<?php if ( get_the_content() ) : ?>
			<div class="section"><?php the_content(); ?></div>
		<?php endif; ?>

		<section class="section" aria-label="<?php esc_attr_e( 'Positions', 'job-listing-tracker' ); ?>">
			<h2><?php esc_html_e( 'Positions', 'job-listing-tracker' ); ?></h2>
			<?php if ( $positions ) : ?>
				<div class="position-table-wrap">
					<table class="position-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Position', 'job-listing-tracker' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Status', 'job-listing-tracker' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Location', 'job-listing-tracker' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $positions as $position ) : ?>
								<?php
								get_template_part(
									'template-parts/position-card',
									null,
									array( 'position' => $position )
								);
								?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No positions at this time.', 'job-listing-tracker' ); ?></p>
			<?php endif; ?>
		</section>

	</article>

		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
