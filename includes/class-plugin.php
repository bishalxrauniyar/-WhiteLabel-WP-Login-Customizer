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
			'whitelabel',
			false,
			dirname( plugin_basename( WL_LOGIN_FILE ) ) . '/languages'
		);

		self::instance()->run();
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
		if ( ! is_array( get_option( WL_LOGIN_OPTION ) ) ) {
			update_option( WL_LOGIN_OPTION, Settings::get_defaults(), false );
		}
	}

	/**
	 * Deactivation hook (kept for future cleanup needs).
	 */
	public static function deactivate() {}
}
