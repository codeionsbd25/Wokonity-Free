<?php
/**
 * Plugin Name: WORKONITY
 * Plugin URI: https://workonity.com/
 * Description: The free WORKONITY workforce foundation for WordPress, with employee records, shifts, attendance, holidays, and optional Professional modules.
 * Version: 2.0.20
 * Author: Codeions
 * Author URI: https://codeions.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: workonity
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Workonity
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WORKONITY_VERSION', '2.0.20' );
define( 'WORKONITY_PLUGIN_FILE', __FILE__ );
define( 'WORKONITY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WORKONITY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WORKONITY_TEXT_DOMAIN', 'workonity' );
define( 'WORKONITY_DB_PREFIX', 'workonity' );

require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-legacy-migrator.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-schema.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-security.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-licensing.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-permissions.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-privacy.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/services/class-workonity-audit-service.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/services/class-workonity-scope-service.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/services/class-workonity-notification-service.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-activator.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-admin.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-rest.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-core-extended.php';
require_once WORKONITY_PLUGIN_DIR . 'includes/class-workonity-plugin.php';

register_activation_hook( __FILE__, array( 'WORKONITY_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WORKONITY_Activator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	function () {
		WORKONITY_Legacy_Migrator::maybe_migrate();
		load_plugin_textdomain( WORKONITY_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		WORKONITY_Licensing::init();
		WORKONITY_Plugin::instance()->init();
	}
);
