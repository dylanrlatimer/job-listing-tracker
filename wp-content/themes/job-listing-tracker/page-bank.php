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
<main id="main" class="wrap">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		if ( 'overview' === $mode ) {
			get_template_part( 'template-parts/bank-overview', null, $bank_view );
		} elseif ( 'company' === $mode ) {
			get_template_part( 'template-parts/bank-company', null, $bank_view );
		} elseif ( 'position' === $mode ) {
			get_template_part( 'template-parts/bank-position', null, $bank_view );
		} else {
			echo '<header class="page-header"><h1>';
			esc_html_e( 'Entry not found.', 'job-listing-tracker' );
			echo '</h1></header>';
			echo '<p class="back-link"><a href="' . esc_url( jlt_bank_url() ) . '">';
			esc_html_e( 'Back to bank', 'job-listing-tracker' );
			echo '</a></p>';
		}

	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
