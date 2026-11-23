<?php
/**
 * Options storage, defaults and sanitization.
 *
 * @package WhiteLabel\Login
 */

namespace WhiteLabel\Login;

defined( 'ABSPATH' ) || exit;

final class Settings {

	/**
	 * Default option values.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_defaults() {
		return array(
			'enabled'            => 1,
			'logo_id'            => 0,
			'bg_image_id'        => 0,
			'logo_url'           => '',
			'logo_link'          => '',
			'logo_alt'           => '',
			'logo_width'         => 300,
			'logo_height'        => 90,
			'page_title'         => '',
			'login_message'      => '',
			'footer_text'        => '',
			'hide_backtoblog'    => 0,
			'hide_register'      => 0,
			'hide_lost_password' => 0,
			'custom_page_enabled' => 0,
			'custom_page_id'      => 0,
			/* Stock WordPress values: a fresh install looks unchanged until customized. */
			'bg_color'           => '#f1f1f1',
			'bg_image'           => '',
			'bg_repeat'          => 'no-repeat',
			'bg_position'        => 'center center',
			'bg_size'            => 'cover',
			'bg_attachment'      => 'fixed',
			'text_color'         => '#3c434a',
			'link_color'         => '#0073aa',
			'link_hover_color'   => '#005177',
			'form_bg_color'      => '#ffffff',
			'form_text_color'    => '#3c434a',
			'form_border_color'  => '#dcdcdc',
			'form_border_width'  => 1,
			'form_radius'        => 4,
			'form_width'         => 340,
			'form_padding'       => 28,
			'form_shadow'        => 'subtle',
			'field_bg_color'        => '#f6f7f7',
			'field_text_color'      => '#2c3338',
			'field_placeholder_color' => '#8c8f94',
			'field_border_color'    => '#8c8f94',
			'field_focus_color'     => '#0073aa',
			'field_radius'          => 4,
			'button_bg_color'       => '#0073aa',
			'button_hover_color'    => '#005177',
			'button_text_color'     => '#ffffff',
			'button_text_hover_color' => '#ffffff',
			'button_radius'         => 4,
			'checkbox_color'        => '#0073aa',
			'font_family'        => '',
			'font_size'          => 14,
			'custom_css'         => '',
		);
	}

	/**
	 * Read all options merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_options() {
		$saved = get_option( WLWP_OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();

		/**
		 * Filters the resolved plugin settings.
		 *
		 * @param array $options Settings merged with defaults.
		 */
		return apply_filters( 'wlwp_login_options', wp_parse_args( $saved, self::get_defaults() ) );
	}

	/**
	 * Read a single option.
	 *
	 * @param string $key      Option key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public static function get( $key, $fallback = '' ) {
		$options = self::get_options();

		return array_key_exists( $key, $options ) ? $options[ $key ] : $fallback;
	}

	/**
	 * Register the setting with the Settings API.
	 */
	public static function register() {
		register_setting(
			'wlwp_login_group',
			WLWP_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::get_defaults(),
			)
		);
	}

	/**
	 * Restore defaults.
	 */
	public static function reset() {
		update_option( WLWP_OPTION, self::get_defaults(), false );
	}

	/**
	 * Sanitize the whole options array.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ) {
		$input   = is_array( $input ) ? $input : array();
		$current = self::get_options();
		$output  = array();

		foreach ( $current as $key => $default ) {
			$raw = array_key_exists( $key, $input ) ? $input[ $key ] : $default;
			if ( is_string( $raw ) ) {
				$raw = wp_unslash( $raw );
			}

			$output[ $key ] = self::sanitize_field( $key, $raw, $default );
		}

		return $output;
	}

	/**
	 * Sanitize a single field based on its key.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $raw     Raw value.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private static function sanitize_field( $key, $raw, $default ) {
		switch ( $key ) {
			case 'enabled':
			case 'hide_backtoblog':
			case 'hide_register':
			case 'hide_lost_password':
			case 'custom_page_enabled':
				return empty( $raw ) ? 0 : 1;

			case 'custom_page_id':
			case 'logo_id':
			case 'bg_image_id':
			case 'logo_width':
			case 'logo_height':
			case 'form_width':
			case 'form_padding':
			case 'form_border_width':
			case 'form_radius':
			case 'field_radius':
			case 'button_radius':
			case 'font_size':
				return absint( $raw );

			case 'logo_url':
			case 'logo_link':
			case 'bg_image':
				return self::sanitize_url( $raw );

			case 'bg_repeat':
				return self::choice( $raw, array( 'repeat', 'no-repeat', 'repeat-x', 'repeat-y', 'space', 'round' ), $default );

			case 'bg_position':
				return self::choice( $raw, array( 'center center', 'top left', 'top center', 'top right', 'center left', 'center right', 'bottom left', 'bottom center', 'bottom right' ), $default );

			case 'bg_size':
				return self::choice( $raw, array( 'cover', 'contain', 'auto', '100% auto', 'auto 100%' ), $default );

			case 'bg_attachment':
				return self::choice( $raw, array( 'fixed', 'scroll' ), $default );

			case 'form_shadow':
				return self::choice( $raw, array( 'none', 'subtle', 'strong' ), $default );

			case 'font_family':
				return self::choice( $raw, array_keys( self::get_font_choices() ), $default );

			case 'logo_alt':
			case 'page_title':
				return sanitize_text_field( (string) $raw );

			case 'login_message':
			case 'footer_text':
				return wp_kses_post( (string) $raw );

			case 'custom_css':
				return self::sanitize_css( (string) $raw );

			default:
				if ( 0 === strpos( $key, 'color' ) || '_color' === substr( $key, -6 ) ) {
					$color = sanitize_hex_color( (string) $raw );

					return $color ? $color : $default;
				}

				return sanitize_text_field( (string) $raw );
		}
	}

	/**
	 * Limit a value to a whitelist.
	 *
	 * @param mixed  $raw     Raw value.
	 * @param array  $choices Allowed values.
	 * @param string $default Fallback.
	 * @return string
	 */
	private static function choice( $raw, array $choices, $default ) {
		$value = is_scalar( $raw ) ? (string) $raw : '';

		return in_array( $value, $choices, true ) ? $value : (string) $default;
	}

	/**
	 * Allow only safe absolute http(s) URLs.
	 *
	 * @param mixed $raw Raw value.
	 * @return string
	 */
	public static function sanitize_url( $raw ) {
		$url = trim( is_scalar( $raw ) ? (string) $raw : '' );
		if ( '' === $url ) {
			return '';
		}

		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * Render post-safe text with line breaks preserved.
	 *
	 * @param string $value Raw text.
	 * @return string
	 */
	public static function format_rich_text( $value ) {
		$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );

		return nl2br( wp_kses_post( $value ), false );
	}

	/**
	 * Make a URL safe to embed inside a CSS url() token.
	 *
	 * @param string $url Sanitized URL.
	 * @return string
	 */
	public static function css_url( $url ) {
		$url = self::sanitize_url( $url );
		if ( '' === $url ) {
			return '';
		}

		return str_replace( array( '"', "'", '\\', '(', ')', '<', '>', "\n", "\r" ), '', $url );
	}

	/**
	 * Strip HTML and dangerous CSS constructs from user CSS.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	private static function sanitize_css( $css ) {
		$css = wp_strip_all_tags( $css );
		$css = str_ireplace(
			array( '</style', '<style', 'javascript:', 'expression(', '@import', 'behavior:' ),
			'',
			$css
		);

		return trim( $css );
	}

	/**
	 * Font stack choices.
	 *
	 * @return array<string,string>
	 */
	public static function get_font_choices() {
		return array(
			'Rubik, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif' => __( 'Rubik (White Label WP)', 'whitelabel-login-customizer' ),
			''                                        => __( 'Default (system font stack)', 'whitelabel-login-customizer' ),
			'Arial, Helvetica, sans-serif'            => __( 'Arial / Helvetica', 'whitelabel-login-customizer' ),
			'Georgia, "Times New Roman", serif'       => __( 'Georgia / Times New Roman', 'whitelabel-login-customizer' ),
			'"Trebuchet MS", sans-serif'              => __( 'Trebuchet MS', 'whitelabel-login-customizer' ),
			'"Courier New", Courier, monospace'       => __( 'Courier New', 'whitelabel-login-customizer' ),
			'Verdana, Geneva, sans-serif'             => __( 'Verdana', 'whitelabel-login-customizer' ),
		);
	}

	/**
	 * Shadow choices mapped to real CSS values.
	 *
	 * @return array<string,string>
	 */
	public static function get_shadow_choices() {
		return array(
			'none'   => __( 'None', 'whitelabel-login-customizer' ),
			'subtle' => __( 'Subtle', 'whitelabel-login-customizer' ),
			'strong' => __( 'Strong', 'whitelabel-login-customizer' ),
		);
	}

	/**
	 * Ready-made color palettes for the settings screen.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_palettes() {
		return array(
			'brand' => array(
				'label'    => __( 'White Label WP', 'whitelabel-login-customizer' ),
				'swatch'   => array( '#000000', '#1e1e1e', '#ebf2ff', '#00aeef' ),
				'colors'   => array(
					'bg_color'                => '#ebf2ff',
					'text_color'              => '#1e1e1e',
					'link_color'              => '#000000',
					'link_hover_color'        => '#00688f',
					'form_bg_color'           => '#ffffff',
					'form_text_color'         => '#1e1e1e',
					'form_border_color'       => '#d9d9d9',
					'field_bg_color'          => '#ffffff',
					'field_text_color'        => '#1e1e1e',
					'field_placeholder_color' => '#646970',
					'field_border_color'      => '#8c8f94',
					'field_focus_color'       => '#00aeef',
					'button_bg_color'         => '#000000',
					'button_hover_color'      => '#36393b',
					'button_text_color'       => '#ffffff',
					'button_text_hover_color' => '#ffffff',
					'checkbox_color'          => '#000000',
					'form_radius'             => 8,
					'font_family'             => 'Rubik, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
				),
			),
			'midnight' => array(
				'label'    => __( 'Midnight', 'whitelabel-login-customizer' ),
				'swatch'   => array( '#12161c', '#1c222b', '#38bdf8', '#e6edf3' ),
				'colors'   => array(
					'bg_color'                => '#12161c',
					'text_color'              => '#e6edf3',
					'link_color'              => '#7dd3fc',
					'link_hover_color'        => '#bae6fd',
					'form_bg_color'           => '#1c222b',
					'form_text_color'         => '#e6edf3',
					'form_border_color'       => '#2f3a47',
					'field_bg_color'          => '#11161d',
					'field_text_color'        => '#e6edf3',
					'field_placeholder_color' => '#8b98a8',
					'field_border_color'      => '#3a4654',
					'field_focus_color'       => '#38bdf8',
					'button_bg_color'         => '#38bdf8',
					'button_hover_color'      => '#0ea5e9',
					'button_text_color'       => '#061018',
					'button_text_hover_color' => '#061018',
					'checkbox_color'          => '#38bdf8',
					'form_radius'             => 6,
					'font_family'             => 'Rubik, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
				),
			),
			'sand' => array(
				'label'    => __( 'Warm Sand', 'whitelabel-login-customizer' ),
				'swatch'   => array( '#f6f1ea', '#fffaf3', '#b76e3c', '#3b3128' ),
				'colors'   => array(
					'bg_color'                => '#f6f1ea',
					'text_color'              => '#3b3128',
					'link_color'              => '#9a5b33',
					'link_hover_color'        => '#6f3f20',
					'form_bg_color'           => '#fffaf3',
					'form_text_color'         => '#3b3128',
					'form_border_color'       => '#e2d3c1',
					'field_bg_color'          => '#fffdf9',
					'field_text_color'        => '#3b3128',
					'field_placeholder_color' => '#9a8b7a',
					'field_border_color'      => '#cbb9a4',
					'field_focus_color'       => '#b76e3c',
					'button_bg_color'         => '#b76e3c',
					'button_hover_color'      => '#8f5229',
					'button_text_color'       => '#ffffff',
					'button_text_hover_color' => '#ffffff',
					'checkbox_color'          => '#b76e3c',
					'form_radius'             => 6,
					'font_family'             => 'Georgia, "Times New Roman", serif',
				),
			),
			'forest' => array(
				'label'    => __( 'Forest', 'whitelabel-login-customizer' ),
				'swatch'   => array( '#eef5ef', '#ffffff', '#2f6b46', '#1f3327' ),
				'colors'   => array(
					'bg_color'                => '#eef5ef',
					'text_color'              => '#1f3327',
					'link_color'              => '#2f6b46',
					'link_hover_color'        => '#1e4a30',
					'form_bg_color'           => '#ffffff',
					'form_text_color'         => '#1f3327',
					'form_border_color'       => '#cfe0d1',
					'field_bg_color'          => '#f7fbf7',
					'field_text_color'        => '#1f3327',
					'field_placeholder_color' => '#7b9082',
					'field_border_color'      => '#a9c2ad',
					'field_focus_color'       => '#2f6b46',
					'button_bg_color'         => '#2f6b46',
					'button_hover_color'      => '#1e4a30',
					'button_text_color'       => '#ffffff',
					'button_text_hover_color' => '#ffffff',
					'checkbox_color'          => '#2f6b46',
					'form_radius'             => 8,
					'font_family'             => '',
				),
			),
			'plum' => array(
				'label'    => __( 'Plum', 'whitelabel-login-customizer' ),
				'swatch'   => array( '#f7f2fb', '#ffffff', '#7c3aed', '#2f1f3d' ),
				'colors'   => array(
					'bg_color'                => '#f7f2fb',
					'text_color'              => '#2f1f3d',
					'link_color'              => '#7c3aed',
					'link_hover_color'        => '#5b21b6',
					'form_bg_color'           => '#ffffff',
					'form_text_color'         => '#2f1f3d',
					'form_border_color'       => '#e4d5f2',
					'field_bg_color'          => '#fdfaff',
					'field_text_color'        => '#2f1f3d',
					'field_placeholder_color' => '#8f7fa0',
					'field_border_color'      => '#cbb6dd',
					'field_focus_color'       => '#7c3aed',
					'button_bg_color'         => '#7c3aed',
					'button_hover_color'      => '#5b21b6',
					'button_text_color'       => '#ffffff',
					'button_text_hover_color' => '#ffffff',
					'checkbox_color'          => '#7c3aed',
					'form_radius'             => 10,
					'font_family'             => 'Rubik, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
				),
			),
		);
	}
}
