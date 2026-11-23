<?php
/**
 * Login screen output and generated CSS.
 *
 * @package WhiteLabel\Login
 */

namespace WhiteLabel\Login;

defined( 'ABSPATH' ) || exit;

final class Frontend {

	/**
	 * Whether the current request is serving the stock login screen on a page.
	 *
	 * @var bool
	 */
	private $rendering_default_login = false;

	/**
	 * Register login-page hooks.
	 */
	public function run() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render_default_login_page' ), 0 );

		if ( ! (bool) Settings::get( 'enabled' ) ) {
			return;
		}

		if ( $this->should_use_custom_page() ) {
			add_action( 'login_init', array( $this, 'redirect_login_to_page' ) );
			add_filter( 'wp_login_errors', array( $this, 'redirect_failed_login' ), 10, 2 );
			add_filter( 'logout_redirect', array( $this, 'filter_logout_redirect' ), 10, 3 );
		}

		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'login_footer', array( $this, 'render_footer' ) );
		add_filter( 'login_body_class', array( $this, 'add_body_class' ) );
		add_filter( 'login_title', array( $this, 'filter_title' ) );
		add_filter( 'login_headerurl', array( $this, 'filter_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'filter_logo_text' ) );
		add_filter( 'login_message', array( $this, 'filter_message' ) );
		add_filter( 'login_site_html_link', array( $this, 'filter_back_to_blog' ) );
		add_filter( 'register', array( $this, 'filter_register_link' ) );
		add_filter( 'lost_password_html_link', array( $this, 'filter_lost_password_link' ) );
		add_filter( 'login_link_separator', array( $this, 'filter_link_separator' ) );
		add_filter( 'wp_login_errors', array( $this, 'restore_custom_page_login_error' ), 11, 2 );
	}

	/**
	 * Register the shortcode used to place the form on any WordPress page.
	 */
	public function register_shortcodes() {
		add_shortcode( 'whitelabel_login', array( $this, 'render_login_shortcode' ) );
		add_shortcode( 'wl_login', array( $this, 'render_login_shortcode' ) );
	}

	/**
	 * Serve the stock WordPress login screen on pages using the shortcode.
	 *
	 * The shortcode is a marker: before the theme renders, the request is handed
	 * to wp-login.php so every default behaviour (styling, logged-in redirects,
	 * registration, lost password, "Go to site", etc.) is preserved exactly.
	 */
	public function maybe_render_default_login_page() {
		if ( is_admin() || ! is_singular( 'page' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$content = (string) $post->post_content;
		if ( ! has_shortcode( $content, 'whitelabel_login' ) && ! has_shortcode( $content, 'wl_login' ) ) {
			return;
		}

		$this->rendering_default_login = true;
		$GLOBALS['pagenow']            = 'wp-login.php';

		wl_login_render_default_login_page();
		exit;
	}

	/**
	 * Whether the current request is serving the stock login screen on a page.
	 *
	 * @return bool
	 */
	private function is_rendering_default_login() {
		return $this->rendering_default_login;
	}

	/**
	 * Re-apply the failed-login message when wp-login.php is served on a page.
	 *
	 * @param \WP_Error $errors      Login errors.
	 * @param string    $redirect_to Requested destination.
	 * @return \WP_Error
	 */
	public function restore_custom_page_login_error( $errors, $redirect_to ) {
		if ( ! $this->is_rendering_default_login() || ! is_wp_error( $errors ) ) {
			return $errors;
		}

		if ( ! $errors->has_errors() ) {
			$status = isset( $_GET['login'] ) ? sanitize_key( wp_unslash( $_GET['login'] ) ) : '';
			if ( 'failed' === $status ) {
				$errors->add(
					'incorrect_password',
					__( '<strong>Error:</strong> The username or password you entered is incorrect.', 'whitelabel' )
				);
			}

			if ( ! empty( $_GET['pwdreset'] ) ) {
				$errors->add( 'pwdreset', __( 'Check your email for the password reset link.', 'whitelabel' ), 'message' );
			}
		}

		return $errors;
	}

	/**
	 * Whether wp-login.php requests should go to the selected page.
	 *
	 * @return bool
	 */
	private function should_use_custom_page() {
		return (bool) Settings::get( 'enabled' )
			&& (bool) Settings::get( 'custom_page_enabled' )
			&& absint( Settings::get( 'custom_page_id' ) ) > 0;
	}

	/**
	 * Is the current request the stock login screen?
	 *
	 * @return bool
	 */
	private function is_wp_login_request() {
		$pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';

		return 'wp-login.php' === $pagenow;
	}

	/**
	 * Is the current request a form submit on the stock login screen?
	 *
	 * @return bool
	 */
	private function is_login_post() {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';

		return $this->is_wp_login_request() && 'POST' === $method;
	}

	/**
	 * Send visitors to the custom login page instead of wp-login.php.
	 */
	public function redirect_login_to_page() {
		if ( $this->is_rendering_default_login() || ! $this->is_wp_login_request() || $this->is_login_post() ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';
		$actions = array(
			'login',
			'logout',
			'lostpassword',
			'retrievepassword',
			'resetpass',
			'rp',
			'register',
			'checkemail',
			'confirm_admin_email',
			'confirmaction',
			'entered_recovery_mode',
		);
		if ( ! in_array( $action, $actions, true ) ) {
			return;
		}

		$args = array();
		if ( 'login' !== $action ) {
			$args['action'] = $action;
		}

		foreach ( $_GET as $key => $value ) {
			if ( ! is_string( $value ) || 'action' === $key ) {
				continue;
			}

			$args[ $key ] = 'redirect_to' === $key
				? esc_url_raw( wp_unslash( $value ) )
				: sanitize_text_field( wp_unslash( $value ) );
		}

		$url = self::get_custom_login_url( $args );
		if ( '' !== $url ) {
			wp_safe_redirect( $url, 302 );
			exit;
		}
	}

	/**
	 * After a failed login, send the user back to the custom page with an error.
	 *
	 * @param \WP_Error $errors      Login errors.
	 * @param string    $redirect_to Requested destination.
	 * @return \WP_Error
	 */
	public function redirect_failed_login( $errors, $redirect_to ) {
		if ( $this->is_rendering_default_login() || ! $this->is_login_post() || ! is_wp_error( $errors ) || ! $errors->has_errors() ) {
			return $errors;
		}

		$args = array( 'login' => 'failed' );
		if ( is_string( $redirect_to ) && '' !== $redirect_to ) {
			$args['redirect_to'] = $redirect_to;
		}

		$url = self::get_custom_login_url( $args );
		if ( '' !== $url ) {
			wp_safe_redirect( $url, 302 );
			exit;
		}

		return $errors;
	}

	/**
	 * Send the after-logout destination through the custom login page.
	 *
	 * @param string  $redirect_to           Default redirect URL.
	 * @param string  $requested_redirect_to Explicit redirect_to value, if any.
	 * @param \WP_User $user                 User that was logged out.
	 * @return string
	 */
	public function filter_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
		$args = array( 'loggedout' => 'true' );
		$lang = isset( $_REQUEST['wp_lang'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wp_lang'] ) ) : '';
		if ( '' === $lang && $user instanceof \WP_User ) {
			$lang = get_user_locale( $user );
		}
		if ( '' !== $lang ) {
			$args['wp_lang'] = $lang;
		}

		$url = self::get_custom_login_url( $args );

		return '' !== $url ? $url : $redirect_to;
	}

	/**
	 * URL of the selected custom login page.
	 *
	 * @param array<string,string> $args Optional query args.
	 * @return string Empty string when unavailable.
	 */
	public static function get_custom_login_url( $args = array() ) {
		$page_id = absint( Settings::get( 'custom_page_id' ) );
		if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
			return '';
		}

		$url = get_permalink( $page_id );
		if ( ! $url ) {
			return '';
		}

		if ( is_array( $args ) && $args ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	/**
	 * Shortcode callback. The whole request is handled earlier by
	 * maybe_render_default_login_page(), so no inline markup is needed.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_login_shortcode( $atts ) {
		return '';
	}

	/**
	 * Enqueue the base stylesheet plus generated settings CSS.
	 */
	public function enqueue_styles() {
		if ( $this->is_rendering_default_login() ) {
			return;
		}

		if ( false !== strpos( (string) Settings::get( 'font_family' ), 'Rubik' ) ) {
			wp_enqueue_style( 'wl-rubik-font', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap', array(), '1.0' );
		}

		wp_enqueue_style(
			'wl-login',
			WL_LOGIN_URL . 'public/css/login.css',
			array( 'login' ),
			WL_LOGIN_VERSION
		);

		wp_add_inline_style( 'wl-login', self::get_css( 'body.login' ) );
	}

	/**
	 * Add a helper class for additional theme/plugin overrides.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function add_body_class( $classes ) {
		if ( $this->is_rendering_default_login() ) {
			return $classes;
		}

		$classes[] = 'wl-login-customized';

		return $classes;
	}

	/**
	 * Custom browser/page title.
	 *
	 * @param string $title Default title.
	 * @return string
	 */
	public function filter_title( $title ) {
		if ( $this->is_rendering_default_login() ) {
			return $title;
		}

		$custom = Settings::get( 'page_title' );

		return '' !== $custom ? esc_html( $custom ) : $title;
	}

	/**
	 * Where the logo links to.
	 *
	 * @param string $url Default URL.
	 * @return string
	 */
	public function filter_logo_url( $url ) {
		if ( $this->is_rendering_default_login() ) {
			return $url;
		}

		$custom = Settings::get( 'logo_link' );

		return '' !== $custom ? esc_url( $custom ) : home_url( '/' );
	}

	/**
	 * Accessible text for the logo link (core echoes this raw).
	 *
	 * @param string $text Default text.
	 * @return string
	 */
	public function filter_logo_text( $text ) {
		if ( $this->is_rendering_default_login() ) {
			return $text;
		}

		$custom = Settings::get( 'logo_alt' );
		if ( '' !== $custom ) {
			return esc_html( $custom );
		}

		return esc_html( get_bloginfo( 'name' ) );
	}

	/**
	 * Custom message above the form.
	 *
	 * @param string $message Existing message.
	 * @return string
	 */
	public function filter_message( $message ) {
		if ( $this->is_rendering_default_login() ) {
			return $message;
		}

		$custom = Settings::get( 'login_message' );
		if ( '' === $custom ) {
			return $message;
		}

		return '<p class="wl-login-message">' . Settings::format_rich_text( $custom ) . '</p>' . $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is sanitized by Settings::format_rich_text().
	}

	/**
	 * Custom footer text.
	 */
	public function render_footer() {
		if ( $this->is_rendering_default_login() ) {
			return;
		}

		$custom = Settings::get( 'footer_text' );
		if ( '' === $custom ) {
			return;
		}

		echo '<p class="wl-login-footer">' . Settings::format_rich_text( $custom ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is sanitized by Settings::format_rich_text().
	}

	/**
	 * Hide the "Go to site" link.
	 *
	 * @param string $link Default link HTML.
	 * @return string
	 */
	public function filter_back_to_blog( $link ) {
		if ( $this->is_rendering_default_login() ) {
			return $link;
		}

		return (bool) Settings::get( 'hide_backtoblog' ) ? '' : $link;
	}

	/**
	 * Hide the registration link.
	 *
	 * @param string $link Default link HTML.
	 * @return string
	 */
	public function filter_register_link( $link ) {
		if ( $this->is_rendering_default_login() ) {
			return $link;
		}

		return (bool) Settings::get( 'hide_register' ) ? '' : $link;
	}

	/**
	 * Hide the "Lost your password?" link.
	 *
	 * @param string $link Default link HTML.
	 * @return string
	 */
	public function filter_lost_password_link( $link ) {
		if ( $this->is_rendering_default_login() ) {
			return $link;
		}

		return (bool) Settings::get( 'hide_lost_password' ) ? '' : $link;
	}

	/**
	 * Drop the separator when one of the nav links is hidden.
	 *
	 * @param string $separator Default separator.
	 * @return string
	 */
	public function filter_link_separator( $separator ) {
		if ( $this->is_rendering_default_login() ) {
			return $separator;
		}

		if ( Settings::get( 'hide_register' ) || Settings::get( 'hide_lost_password' ) ) {
			return '';
		}

		return $separator;
	}

	/**
	 * Resolve the effective logo URL (attachment first, then custom URL).
	 *
	 * @return string
	 */
	public static function get_logo_url() {
		$attachment_id = absint( Settings::get( 'logo_id' ) );
		if ( $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'full' );
			if ( $url ) {
				return Settings::sanitize_url( $url );
			}
		}

		return Settings::sanitize_url( Settings::get( 'logo_url' ) );
	}

	/**
	 * Resolve the effective background image URL.
	 *
	 * @return string
	 */
	public static function get_background_url() {
		$attachment_id = absint( Settings::get( 'bg_image_id' ) );
		if ( $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'full' );
			if ( $url ) {
				return Settings::sanitize_url( $url );
			}
		}

		return Settings::sanitize_url( Settings::get( 'bg_image' ) );
	}

	/**
	 * Build the CSS for the login page (or for the admin preview).
	 *
	 * All values are sanitized in Settings; output still uses a strict scope
	 * allow-list because the selector is interpolated directly into CSS.
	 *
	 * @param string $scope          Root selector, e.g. "body.login".
	 * @param bool   $include_custom Whether to append the user's custom CSS.
	 * @return string
	 */
	public static function get_css( $scope = 'body.login', $include_custom = true ) {
		$scope = trim( (string) $scope );
		if ( ! preg_match( '/^[a-zA-Z0-9\s.#>_:-]+$/', $scope ) ) {
			$scope = 'body.login';
		}

		$logo_url = self::get_logo_url();
		$bg_url   = self::get_background_url();
		$vars     = self::get_css_vars( $logo_url, $bg_url );

		$css = $scope . " {\n";
		foreach ( $vars as $name => $value ) {
			$css .= "\t" . $name . ': ' . $value . ";\n";
		}
		$css .= "}\n\n";

		$s = $scope . ' ';

		$css .= $scope . " {\n";
		$css .= "\tbackground-color: var(--wl-bg);\n";
		$css .= "\tbackground-image: var(--wl-bg-image);\n";
		$css .= "\tbackground-repeat: var(--wl-bg-repeat);\n";
		$css .= "\tbackground-position: var(--wl-bg-position);\n";
		$css .= "\tbackground-size: var(--wl-bg-size);\n";
		$css .= "\tbackground-attachment: var(--wl-bg-attachment);\n";
		$css .= "\tcolor: var(--wl-text);\n";
		$css .= "\tfont-family: var(--wl-font);\n";
		$css .= "\tfont-size: var(--wl-font-size);\n";
		$css .= "}\n\n";

		$css .= $s . "#login {\n\twidth: var(--wl-form-width);\n\tmax-width: 96%;\n}\n\n";

		$css .= $s . "#login h1 {\n\tmargin: 0 0 24px;\n}\n\n";

		if ( '' !== $logo_url ) {
			$css .= $s . "#login h1 a {\n";
			$css .= "\tbackground-image: var(--wl-logo-image);\n";
			$css .= "\tbackground-position: center center;\n";
			$css .= "\tbackground-repeat: no-repeat;\n";
			$css .= "\tbackground-size: contain;\n";
			$css .= "\twidth: var(--wl-logo-width);\n";
			$css .= "\theight: var(--wl-logo-height);\n";
			$css .= "\tmax-width: 100%;\n";
			$css .= "\ttext-indent: -9999px;\n";
			$css .= "}\n\n";
		}

		$css .= $s . "#login form {\n";
		$css .= "\tbackground-color: var(--wl-form-bg);\n";
		$css .= "\tborder: var(--wl-form-border-width) solid var(--wl-form-border-color);\n";
		$css .= "\tborder-radius: var(--wl-form-radius);\n";
		$css .= "\tbox-shadow: var(--wl-form-shadow);\n";
		$css .= "\tpadding: var(--wl-form-padding);\n";
		$css .= "}\n\n";

		$css .= $s . "#login form .input,\n" . $s . "#login form input[type=text],\n" . $s . "#login form input[type=password],\n" . $s . "#login form input[type=email],\n" . $s . "#login form input[type=number] {\n";
		$css .= "\tbackground-color: var(--wl-field-bg);\n";
		$css .= "\tcolor: var(--wl-field-text);\n";
		$css .= "\tborder: 1px solid var(--wl-field-border);\n";
		$css .= "\tborder-radius: var(--wl-field-radius);\n";
		$css .= "\tfont-size: var(--wl-font-size);\n";
		$css .= "\tbox-shadow: none;\n";
		$css .= "}\n\n";

		$css .= $s . "#login form .input::placeholder {\n";
		$css .= "\tcolor: var(--wl-field-placeholder);\n";
		$css .= "\topacity: 1;\n";
		$css .= "}\n\n";

		$css .= $s . "#login form .input:focus {\n";
		$css .= "\tborder-color: var(--wl-field-focus);\n";
		$css .= "\tbox-shadow: 0 0 0 3px var(--wl-field-focus-ring);\n";
		$css .= "\toutline: 2px solid transparent;\n";
		$css .= "}\n\n";

		$css .= $s . "#login form input[type=submit],\n" . $s . "#login form .button-primary,\n" . $s . "#login .wp-core-ui .button-primary {\n";
		$css .= "\tbackground-color: var(--wl-button-bg);\n";
		$css .= "\tborder-color: var(--wl-button-bg);\n";
		$css .= "\tborder-radius: var(--wl-button-radius);\n";
		$css .= "\tcolor: var(--wl-button-text);\n";
		$css .= "\tfont-size: var(--wl-font-size);\n";
		$css .= "\ttext-shadow: none;\n";
		$css .= "\tbox-shadow: none;\n";
		$css .= "}\n\n";

		$css .= $s . "#login form input[type=submit]:hover,\n" . $s . "#login form .button-primary:hover,\n" . $s . "#login .wp-core-ui .button-primary:hover {\n";
		$css .= "\tbackground-color: var(--wl-button-hover);\n";
		$css .= "\tborder-color: var(--wl-button-hover);\n";
		$css .= "\tcolor: var(--wl-button-hover-text);\n";
		$css .= "}\n\n";

		$css .= $s . "#login label,\n" . $s . "#login .forgetmenot label {\n\tcolor: var(--wl-form-text);\n}\n\n";
		$css .= $s . "#login input[type=checkbox] {\n\taccent-color: var(--wl-accent);\n}\n\n";
		$css .= $s . "#login #nav a,\n" . $s . "#login #backtoblog a,\n" . $scope . " .message a {\n\tcolor: var(--wl-link);\n}\n\n";
		$css .= $s . "#login #nav a:hover,\n" . $s . "#login #backtoblog a:hover,\n" . $scope . " .message a:hover {\n\tcolor: var(--wl-link-hover);\n\ttext-decoration: underline;\n\ttext-underline-offset: 3px;\n}\n\n";
		$css .= $s . "#login .wl-login-message,\n" . $scope . " .wl-login-footer,\n" . $scope . " .message {\n\tcolor: var(--wl-text);\n}\n\n";

		if ( Settings::get( 'hide_register' ) ) {
			$css .= $s . "a.wp-login-register { display: none !important; }\n";
		}
		if ( Settings::get( 'hide_lost_password' ) ) {
			$css .= $s . "a.wp-login-lost-password { display: none !important; }\n";
		}
		if ( Settings::get( 'hide_backtoblog' ) ) {
			$css .= $s . "#backtoblog { display: none !important; }\n";
		}

		if ( $include_custom ) {
			$custom_css = Settings::get( 'custom_css' );
			if ( '' !== $custom_css ) {
				$css .= "\n/* WhiteLabel custom CSS */\n" . $custom_css . "\n";
			}
		}

		return $css;
	}

	/**
	 * Convert a sanitized hex color to an rgba() string.
	 *
	 * @param string $hex   Hex color, with or without a leading hash.
	 * @param float  $alpha Opacity from 0 to 1.
	 * @return string
	 */
	private static function rgba( $hex, $alpha ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return 'rgba(0, 115, 170, ' . $alpha . ')';
		}

		return sprintf(
			'rgba(%d, %d, %d, %s)',
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
			$alpha
		);
	}

	/**
	 * Build the CSS custom properties used by the stylesheet.
	 *
	 * @param string $logo_url Logo URL.
	 * @param string $bg_url   Background URL.
	 * @return array<string,string>
	 */
	private static function get_css_vars( $logo_url, $bg_url ) {
		$logo_css = '' !== $logo_url ? 'url("' . Settings::css_url( $logo_url ) . '")' : 'none';
		$bg_css   = '' !== $bg_url ? 'url("' . Settings::css_url( $bg_url ) . '")' : 'none';

		$shadows = array(
			'none'   => 'none',
			'subtle' => '0 4px 16px rgba(0, 0, 0, 0.10)',
			'strong' => '0 12px 32px rgba(0, 0, 0, 0.22)',
		);
		$shadow  = isset( $shadows[ Settings::get( 'form_shadow' ) ] )
			? $shadows[ Settings::get( 'form_shadow' ) ]
			: $shadows['subtle'];

		// All values have been sanitized by Settings; absint prevents unit injection.
		return array(
			'--wl-bg'               => Settings::get( 'bg_color' ),
			'--wl-logo-image'       => $logo_css,
			'--wl-logo-width'       => max( 1, absint( Settings::get( 'logo_width' ) ) ) . 'px',
			'--wl-logo-height'      => max( 1, absint( Settings::get( 'logo_height' ) ) ) . 'px',
			'--wl-bg-image'         => $bg_css,
			'--wl-bg-repeat'        => Settings::get( 'bg_repeat' ),
			'--wl-bg-position'      => Settings::get( 'bg_position' ),
			'--wl-bg-size'          => Settings::get( 'bg_size' ),
			'--wl-bg-attachment'    => Settings::get( 'bg_attachment' ),
			'--wl-font'             => '' !== Settings::get( 'font_family' )
				? Settings::get( 'font_family' )
				: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
			'--wl-font-size'        => max( 8, absint( Settings::get( 'font_size' ) ) ) . 'px',
			'--wl-form-width'       => max( 200, absint( Settings::get( 'form_width' ) ) ) . 'px',
			'--wl-form-padding'     => absint( Settings::get( 'form_padding' ) ) . 'px',
			'--wl-form-bg'          => Settings::get( 'form_bg_color' ),
			'--wl-form-text'        => Settings::get( 'form_text_color' ),
			'--wl-form-border-color'=> Settings::get( 'form_border_color' ),
			'--wl-form-border-width'=> absint( Settings::get( 'form_border_width' ) ) . 'px',
			'--wl-form-radius'      => absint( Settings::get( 'form_radius' ) ) . 'px',
			'--wl-form-shadow'      => $shadow,
			'--wl-field-bg'         => Settings::get( 'field_bg_color' ),
			'--wl-field-text'       => Settings::get( 'field_text_color' ),
			'--wl-field-placeholder'=> Settings::get( 'field_placeholder_color' ),
			'--wl-field-border'     => Settings::get( 'field_border_color' ),
			'--wl-field-focus'      => Settings::get( 'field_focus_color' ),
			'--wl-field-focus-ring' => self::rgba( Settings::get( 'field_focus_color' ), 0.25 ),
			'--wl-field-radius'     => absint( Settings::get( 'field_radius' ) ) . 'px',
			'--wl-button-bg'        => Settings::get( 'button_bg_color' ),
			'--wl-button-hover'     => Settings::get( 'button_hover_color' ),
			'--wl-button-text'      => Settings::get( 'button_text_color' ),
			'--wl-button-hover-text'=> Settings::get( 'button_text_hover_color' ),
			'--wl-button-radius'    => absint( Settings::get( 'button_radius' ) ) . 'px',
			'--wl-accent'           => Settings::get( 'checkbox_color' ),
			'--wl-text'             => Settings::get( 'text_color' ),
			'--wl-link'             => Settings::get( 'link_color' ),
			'--wl-link-hover'       => Settings::get( 'link_hover_color' ),
		);
	}
}

if ( ! function_exists( __NAMESPACE__ . '\\wl_login_render_default_login_page' ) ) {
	/**
	 * Include wp-login.php with the variables it expects in the global scope.
	 *
	 * The include normally happens inside a class method, so the globals are
	 * imported explicitly to avoid undefined-variable notices on the stock screen.
	 */
	function wl_login_render_default_login_page() {
		global $action, $error, $errors, $interim_login, $user_login, $user_email;
		global $user, $redirect_to, $requested_redirect_to, $secure_cookie;
		global $customize_login, $reauth, $login_link_separator, $rememberme;
		global $aria_describedby, $has_errors, $login_script, $rp_cookie;
		global $rp_login, $rp_key, $rp_path, $message, $msg;

		require ABSPATH . 'wp-login.php';
	}
}
