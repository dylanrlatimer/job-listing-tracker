<?php
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

$bank_view = jlt_resolve_bank_view();
$mode      = $bank_view['mode'];

if ( 'not_found' === $mode ) {
	status_header( 404 );
	nocache_headers();
}

get_header();
?>
<main id="main">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>

	<h1><?php the_title(); ?></h1>

	<?php if ( 'overview' === $mode ) : ?>
		<?php get_template_part( 'template-parts/bank-overview', null, $bank_view ); ?>
	<?php elseif ( 'company' === $mode ) : ?>
		<?php get_template_part( 'template-parts/bank-company', null, $bank_view ); ?>
	<?php elseif ( 'position' === $mode ) : ?>
		<?php get_template_part( 'template-parts/bank-position', null, $bank_view ); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Entry not found.', 'job-listing-tracker' ); ?></p>
	<?php endif; ?>

		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
