<?php
/**
 * Fired when the plugin is deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wlwp_login_options' );
delete_option( 'wl_login_options' );
