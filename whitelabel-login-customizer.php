<?php
/**
 * Plugin Name:       WhiteLabel Login Customizer
 * Description:       Replace the default WordPress login screen with your own logo, colors, background, typography and custom CSS — without writing code. Developed by White Label WP — https://whitelabelwp.io/
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            White Label WP
 * Author URI:        https://whitelabelwp.io/
 * Text Domain:       whitelabel-login-customizer
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WLWP_VERSION', '1.0.1' );
define( 'WLWP_FILE', __FILE__ );
define( 'WLWP_DIR', plugin_dir_path( __FILE__ ) );
define( 'WLWP_URL', plugin_dir_url( __FILE__ ) );
define( 'WLWP_OPTION', 'wlwp_login_options' );

define( 'WLWP_SITE_NAME', 'White Label WP' );
define( 'WLWP_SITE_URL', 'https://whitelabelwp.io/' );
define( 'WLWP_BRAND_ICON', 'https://whitelabelwp.io/wp-content/uploads/2026/11/front-1-3.webp' );
define( 'WLWP_BRAND_LOGO', 'https://whitelabelwp.io/wp-content/uploads/2024/03/WP-Logo-Main-768x279.png' );

require_once WLWP_DIR . 'includes/class-settings.php';
require_once WLWP_DIR . 'includes/class-frontend.php';
require_once WLWP_DIR . 'includes/class-admin.php';
require_once WLWP_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( '\WhiteLabel\Login\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\WhiteLabel\Login\Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( '\WhiteLabel\Login\Plugin', 'boot' ) );
