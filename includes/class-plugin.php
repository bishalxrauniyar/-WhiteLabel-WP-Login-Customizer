<?php
/**
 * Plugin bootstrap and hook registration.
 *
 * @package WhiteLabel\Login
 */

namespace WhiteLabel\Login;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Run on plugins_loaded.
	 */
	public static function boot() {
		// Kept for translations in distributions that are not hosted on WordPress.org.
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
		load_plugin_textdomain(
			'whitelabel-login-customizer',
			false,
			dirname( plugin_basename( WLWP_FILE ) ) . '/languages'
		);

		self::migrate_legacy_options();
		self::instance()->run();
	}

	/**
	 * Copy settings saved by the old wl_ prefix into the wlwp_ option.
	 */
	private static function migrate_legacy_options() {
		if ( false !== get_option( WLWP_OPTION, false ) ) {
			return;
		}

		$legacy = get_option( 'wl_login_options', false );
		if ( is_array( $legacy ) ) {
			update_option( WLWP_OPTION, $legacy, false );
		}
	}

	/**
	 * Get the singleton.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register runtime hooks.
	 */
	public function run() {
		( new Frontend() )->run();

		if ( is_admin() ) {
			( new Admin() )->run();
		}
	}

	/**
	 * Activation: seed defaults once. Deactivation intentionally keeps settings.
	 */
	public static function activate() {
		if ( ! is_array( get_option( WLWP_OPTION ) ) ) {
			update_option( WLWP_OPTION, Settings::get_defaults(), false );
		}
	}

	/**
	 * Deactivation hook (kept for future cleanup needs).
	 */
	public static function deactivate() {}
}
