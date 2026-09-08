<?php
/**
 * Plugin Name: Job Listing Tracker
 * Description: Organizes a job search around companies and their relevant positions.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Dylan Latimer
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: job-listing-tracker
 */

defined( 'ABSPATH' ) || exit;

define( 'JLT_VERSION', '0.1.0' );
define( 'JLT_PLUGIN_FILE', __FILE__ );
define( 'JLT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once JLT_PLUGIN_DIR . 'includes/post-types.php';
require_once JLT_PLUGIN_DIR . 'includes/meta.php';
require_once JLT_PLUGIN_DIR . 'includes/acf.php';
require_once JLT_PLUGIN_DIR . 'includes/queries.php';
require_once JLT_PLUGIN_DIR . 'includes/entries.php';
require_once JLT_PLUGIN_DIR . 'includes/actions.php';
require_once JLT_PLUGIN_DIR . 'includes/lifecycle.php';
require_once JLT_PLUGIN_DIR . 'includes/urls.php';
require_once JLT_PLUGIN_DIR . 'includes/import.php';

if ( is_admin() ) {
	require_once JLT_PLUGIN_DIR . 'includes/admin.php';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once JLT_PLUGIN_DIR . 'includes/cli.php';
}

/**
 * Uninstall cleanup is deferred. Do not add register_uninstall_hook() or
 * uninstall.php yet; premature deletion logic is a risk.
 */
register_activation_hook( JLT_PLUGIN_FILE, 'jlt_activate' );
register_deactivation_hook( JLT_PLUGIN_FILE, 'jlt_deactivate' );

/**
 * Registers post types, grants administrator capabilities, and flushes rewrites.
 * Does not create or modify application data.
 */
function jlt_activate() {
	jlt_register_post_types();

	$role = get_role( 'administrator' );
	if ( $role ) {
		$caps = array_merge(
			jlt_company_primitive_caps(),
			jlt_position_primitive_caps()
		);
		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}

	flush_rewrite_rules();
}

/**
 * Flush rewrite rules after deactivation. Does not delete data.
 */
function jlt_deactivate() {
	flush_rewrite_rules();
}
