<?php
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

$user = wp_get_current_user();

get_header();
?>
<main id="main" class="wrap">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
	<header class="page-header">
		<h1><?php the_title(); ?></h1>
	</header>

	<div class="field">
		<label for="jlt-account-email"><?php esc_html_e( 'Email', 'job-listing-tracker' ); ?></label>
		<div class="field-locked">
			<input
				id="jlt-account-email"
				type="email"
				value="<?php echo esc_attr( $user->user_email ); ?>"
				readonly
				disabled
			>
		</div>
	</div>

	<div class="settings-actions">
		<a class="btn" href="<?php echo esc_url( wp_lostpassword_url( jlt_settings_url() ) ); ?>">
			<?php esc_html_e( 'Reset password', 'job-listing-tracker' ); ?>
		</a>
		<a class="btn btn--danger" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
			<?php esc_html_e( 'Log out', 'job-listing-tracker' ); ?>
		</a>
	</div>
		<?php
	endwhile;
endif;
?>
</main>
<?php get_footer(); ?>
