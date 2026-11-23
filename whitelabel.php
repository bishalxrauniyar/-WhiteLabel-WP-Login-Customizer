<?php
/**
 * Plugin Name:       WhiteLabel WP Login Customizer
 * Description:       Replace the default WordPress login screen with your own logo, colors, background, typography and custom CSS — without writing code. Developed by White Label WP — https://whitelabelwp.io/
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            White Label WP
 * Author URI:        https://whitelabelwp.io/
 * Text Domain:       whitelabel
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WL_LOGIN_VERSION', '1.0.0' );
define( 'WL_LOGIN_FILE', __FILE__ );
define( 'WL_LOGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WL_LOGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WL_LOGIN_OPTION', 'wl_login_options' );

define( 'WL_LOGIN_SITE_NAME', 'White Label WP' );
define( 'WL_LOGIN_SITE_URL', 'https://whitelabelwp.io/' );
define( 'WL_LOGIN_BRAND_ICON', 'https://whitelabelwp.io/wp-content/uploads/2026/11/front-1-3.webp' );
define( 'WL_LOGIN_BRAND_LOGO', 'https://whitelabelwp.io/wp-content/uploads/2024/03/WP-Logo-Main-768x279.png' );

require_once WL_LOGIN_DIR . 'includes/class-settings.php';
require_once WL_LOGIN_DIR . 'includes/class-frontend.php';
require_once WL_LOGIN_DIR . 'includes/class-admin.php';
require_once WL_LOGIN_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( '\WhiteLabel\Login\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\WhiteLabel\Login\Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( '\WhiteLabel\Login\Plugin', 'boot' ) );
