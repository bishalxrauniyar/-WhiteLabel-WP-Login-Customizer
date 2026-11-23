<?php
/**
 * Settings screen, media pickers and live preview.
 *
 * @package WhiteLabel\Login
 */

namespace WhiteLabel\Login;

defined( 'ABSPATH' ) || exit;

final class Admin {

	/**
	 * Settings page slug.
	 */
	const PAGE = 'wlwp-login-customizer';

	/**
	 * Settings group.
	 */
	const GROUP = 'wlwp_login_group';

	/**
	 * Registered hook suffix.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Register admin hooks.
	 */
	public function run() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_wlwp_reset', array( $this, 'handle_reset' ) );
		add_action( 'wp_ajax_wlwp_create_page', array( $this, 'handle_create_page' ) );
		add_action( 'admin_notices', array( $this, 'show_reset_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WLWP_FILE ), array( $this, 'plugin_links' ) );
	}

	/**
	 * Add the settings page under Settings.
	 */
	public function add_menu_page() {
		$this->hook_suffix = add_options_page(
			__( 'WhiteLabel Login Customizer', 'whitelabel-login-customizer' ),
			__( 'WhiteLabel Login Customizer', 'whitelabel-login-customizer' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings, sections and fields.
	 */
	public function register_settings() {
		Settings::register();

		foreach ( $this->get_sections() as $id => $section ) {
			add_settings_section(
				'wl_' . $id,
				$section['title'],
				array( $this, 'render_section' ),
				self::PAGE
			);
		}

		foreach ( $this->get_fields() as $field ) {
			add_settings_field(
				'wl_' . $field['key'],
				$field['label'],
				array( $this, 'render_field' ),
				self::PAGE,
				'wl_' . $field['section'],
				$field
			);
		}
	}

	/**
	 * Settings sections with short, human-readable descriptions.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function get_sections() {
		return array(
			'general'  => array(
				'title' => __( 'General & Branding', 'whitelabel-login-customizer' ),
				'intro' => __( 'These options control the branding around the form on every login screen — Log In, Register, Lost Password and Reset Password.', 'whitelabel-login-customizer' ),
			),
			'page'     => array(
				'title' => __( 'Custom login page', 'whitelabel-login-customizer' ),
				'intro' => __( 'Show the complete standard WordPress login screen on one of your own pages, then send wp-login.php visitors there. Customizer branding stays on wp-login.php.', 'whitelabel-login-customizer' ),
			),
			'colors'   => array(
				'title' => __( 'Colors & Background', 'whitelabel-login-customizer' ),
				'intro' => __( 'The color scheme of the page, form, inputs and buttons — including hover, focus and placeholder states.', 'whitelabel-login-customizer' ),
			),
			'layout'   => array(
				'title' => __( 'Layout & Typography', 'whitelabel-login-customizer' ),
				'intro' => __( 'Sizes, spacing, rounded corners and fonts.', 'whitelabel-login-customizer' ),
			),
			'advanced' => array(
				'title' => __( 'Advanced', 'whitelabel-login-customizer' ),
				'intro' => __( 'Add your own CSS when the options above are not enough.', 'whitelabel-login-customizer' ),
			),
		);
	}

	/**
	 * Field configuration.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_fields() {
		return array(
			array(
				'key'            => 'enabled',
				'section'        => 'general',
				'label'          => __( 'Enable customization', 'whitelabel-login-customizer' ),
				'type'           => 'checkbox',
				'help'           => __( 'Master switch. Turn it off to show the stock WordPress login screen again — your settings are kept, not deleted.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login',
			),
			array(
				'key'            => 'logo_id',
				'section'        => 'general',
				'label'          => __( 'Login logo', 'whitelabel-login-customizer' ),
				'type'           => 'logo',
				'help'           => __( 'Replaces the WordPress logo above the form. A PNG or SVG with a transparent background works best (about 300 × 90 px). Leave empty to keep the WordPress logo.', 'whitelabel-login-customizer' ),
				'media_title'    => __( 'Choose a login logo', 'whitelabel-login-customizer' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'logo_width',
				'section'        => 'general',
				'label'          => __( 'Logo width / height', 'whitelabel-login-customizer' ),
				'type'           => 'dual_number',
				'help'           => __( 'The size of the logo box on the page. Your image is scaled to fit inside it without stretching, so use your image’s proportions for the sharpest result.', 'whitelabel-login-customizer' ),
				'fields'         => array(
					array( 'key' => 'logo_width', 'label' => __( 'Width (px)', 'whitelabel-login-customizer' ), 'var' => '--wlwp-logo-width', 'unit' => 'px', 'preview_target' => '#login h1' ),
					array( 'key' => 'logo_height', 'label' => __( 'Height (px)', 'whitelabel-login-customizer' ), 'var' => '--wlwp-logo-height', 'unit' => 'px', 'preview_target' => '#login h1' ),
				),
			),
			array(
				'key'            => 'logo_link',
				'section'        => 'general',
				'label'          => __( 'Logo link URL', 'whitelabel-login-customizer' ),
				'type'           => 'url',
				'placeholder'    => home_url( '/' ),
				'help'           => __( 'Where visitors go when they click the logo. Leave empty to link to your site’s home page.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'logo_alt',
				'section'        => 'general',
				'label'          => __( 'Logo alt text', 'whitelabel-login-customizer' ),
				'type'           => 'text',
				'placeholder'    => get_bloginfo( 'name' ),
				'help'           => __( 'Alternative text read aloud by screen readers and shown as the link’s tooltip. Leave empty to use your site name.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'page_title',
				'section'        => 'general',
				'label'          => __( 'Browser page title', 'whitelabel-login-customizer' ),
				'type'           => 'text',
				'placeholder'    => __( 'Log In', 'whitelabel-login-customizer' ),
				'help'           => __( 'The text in the browser tab or window title bar. Leave empty to keep WordPress’s default, e.g. “Log In ‹ Your Site”.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'login_message',
				'section'        => 'general',
				'label'          => __( 'Message above form', 'whitelabel-login-customizer' ),
				'type'           => 'textarea',
				'rows'           => 3,
				'help'           => __( 'A welcome note shown above the form on every login screen, including “Lost your password”. Basic HTML such as links and <strong> is allowed.', 'whitelabel-login-customizer' ),
				'preview_target' => '.wlwp-login-message',
			),
			array(
				'key'            => 'footer_text',
				'section'        => 'general',
				'label'          => __( 'Footer text', 'whitelabel-login-customizer' ),
				'type'           => 'textarea',
				'rows'           => 2,
				'help'           => __( 'Shown at the very bottom of the login page, below the navigation links. Useful for a support email or legal note. Basic HTML is allowed.', 'whitelabel-login-customizer' ),
				'preview_target' => '.wlwp-login-footer',
			),
			array(
				'key'    => 'hide_backtoblog',
				'section' => 'general',
				'label'  => __( 'Hide links', 'whitelabel-login-customizer' ),
				'type'   => 'checkbox_group',
				'help'   => __( 'These links sit below the form on the real login page. Hiding one removes it from every login screen; the feature itself still works if someone visits its URL directly.', 'whitelabel-login-customizer' ),
				'items'  => array(
					array( 'key' => 'hide_backtoblog', 'label' => __( 'Hide “Go to site” link', 'whitelabel-login-customizer' ), 'preview_hide' => '#backtoblog', 'preview_target' => '#backtoblog' ),
					array( 'key' => 'hide_register', 'label' => __( 'Hide “Register” link', 'whitelabel-login-customizer' ), 'preview_hide' => '.wp-login-register', 'preview_target' => '.wp-login-register' ),
					array( 'key' => 'hide_lost_password', 'label' => __( 'Hide “Lost your password?” link', 'whitelabel-login-customizer' ), 'preview_hide' => '.wp-login-lost-password', 'preview_target' => '.wp-login-lost-password' ),
				),
			),
			array(
				'key'    => 'custom_page_enabled',
				'section' => 'page',
				'label'  => __( 'Use a custom login page', 'whitelabel-login-customizer' ),
				'type'   => 'checkbox',
				'help'   => __( 'When enabled, wp-login.php visitors are sent to the selected page. The page shows the complete standard WordPress login screen, and logout also returns there.', 'whitelabel-login-customizer' ),
			),
			array(
				'key'    => 'custom_page_id',
				'section' => 'page',
				'label'  => __( 'Login page', 'whitelabel-login-customizer' ),
				'type'   => 'pages',
				'help'   => __( 'Choose the published page that contains the shortcode. The page itself must be published for the redirect to work.', 'whitelabel-login-customizer' ),
			),
			array(
				'key'    => 'custom_page_create',
				'section' => 'page',
				'label'  => __( 'Create a new login page', 'whitelabel-login-customizer' ),
				'type'   => 'create_page',
				'help'   => __( 'Creates a published page with the shortcode already in place, then selects it above. The page shows the complete standard WordPress login screen. You can rename it in Pages later.', 'whitelabel-login-customizer' ),
			),
			array(
				'key'    => 'custom_page_shortcode',
				'section' => 'page',
				'label'  => __( 'Shortcode for your page', 'whitelabel-login-customizer' ),
				'type'   => 'shortcode',
				'help'   => __( 'Paste this into a published page to show the complete standard WordPress login screen, including its styling, links and logged-in behaviour. Customizer styling is not applied here. [whitelabel_login] and [wl_login] also work.', 'whitelabel-login-customizer' ),
			),
			array(
				'key'  => 'bg_color',
				'section' => 'colors',
				'label' => __( 'Page background', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-bg',
			),
			array(
				'key'           => 'bg_image_id',
				'section'       => 'colors',
				'label'         => __( 'Background image', 'whitelabel-login-customizer' ),
				'type'          => 'image',
				'media_title'   => __( 'Choose a background image', 'whitelabel-login-customizer' ),
				'var'           => '--wlwp-bg-image',
				'css_image'     => true,
			),
			array(
				'key'  => 'bg_repeat',
				'section' => 'colors',
				'label' => __( 'Background repeat', 'whitelabel-login-customizer' ),
				'type' => 'select',
				'var'  => '--wlwp-bg-repeat',
				'choices' => array(
					'no-repeat' => __( 'No repeat', 'whitelabel-login-customizer' ),
					'repeat'    => __( 'Repeat', 'whitelabel-login-customizer' ),
					'repeat-x'  => __( 'Repeat horizontally', 'whitelabel-login-customizer' ),
					'repeat-y'  => __( 'Repeat vertically', 'whitelabel-login-customizer' ),
					'space'     => __( 'Space', 'whitelabel-login-customizer' ),
					'round'     => __( 'Round', 'whitelabel-login-customizer' ),
				),
			),
			array(
				'key'  => 'bg_position',
				'section' => 'colors',
				'label' => __( 'Background position', 'whitelabel-login-customizer' ),
				'type' => 'select',
				'var'  => '--wlwp-bg-position',
				'choices' => array(
					'center center' => __( 'Center', 'whitelabel-login-customizer' ),
					'top left'      => __( 'Top left', 'whitelabel-login-customizer' ),
					'top center'    => __( 'Top center', 'whitelabel-login-customizer' ),
					'top right'     => __( 'Top right', 'whitelabel-login-customizer' ),
					'center left'   => __( 'Center left', 'whitelabel-login-customizer' ),
					'center right'  => __( 'Center right', 'whitelabel-login-customizer' ),
					'bottom left'   => __( 'Bottom left', 'whitelabel-login-customizer' ),
					'bottom center' => __( 'Bottom center', 'whitelabel-login-customizer' ),
					'bottom right'  => __( 'Bottom right', 'whitelabel-login-customizer' ),
				),
			),
			array(
				'key'  => 'bg_size',
				'section' => 'colors',
				'label' => __( 'Background size', 'whitelabel-login-customizer' ),
				'type' => 'select',
				'var'  => '--wlwp-bg-size',
				'choices' => array(
					'cover'     => __( 'Cover', 'whitelabel-login-customizer' ),
					'contain'   => __( 'Contain', 'whitelabel-login-customizer' ),
					'auto'      => __( 'Original size', 'whitelabel-login-customizer' ),
					'100% auto' => __( 'Full width', 'whitelabel-login-customizer' ),
				),
			),
			array(
				'key'  => 'bg_attachment',
				'section' => 'colors',
				'label' => __( 'Background attachment', 'whitelabel-login-customizer' ),
				'type' => 'select',
				'var'  => '--wlwp-bg-attachment',
				'choices' => array(
					'fixed'  => __( 'Fixed', 'whitelabel-login-customizer' ),
					'scroll' => __( 'Scroll', 'whitelabel-login-customizer' ),
				),
			),
			array(
				'key'  => 'text_color',
				'section' => 'colors',
				'label' => __( 'Text color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-text',
			),
			array(
				'key'  => 'form_bg_color',
				'section' => 'colors',
				'label' => __( 'Form background', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-form-bg',
				'preview_target' => '#login form',
			),
			array(
				'key'  => 'form_text_color',
				'section' => 'colors',
				'label' => __( 'Form text color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-form-text',
				'help' => __( 'Labels and helper text inside the form. The page text color above still controls messages around it.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login form label',
			),
			array(
				'key'  => 'form_border_color',
				'section' => 'colors',
				'label' => __( 'Form border color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-form-border-color',
			),
			array(
				'key'  => 'field_bg_color',
				'section' => 'colors',
				'label' => __( 'Input background', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-field-bg',
			),
			array(
				'key'  => 'field_text_color',
				'section' => 'colors',
				'label' => __( 'Input text color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-field-text',
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'field_placeholder_color',
				'section' => 'colors',
				'label' => __( 'Placeholder color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-field-placeholder',
				'help' => __( 'The light sample text shown in empty input fields.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'field_border_color',
				'section' => 'colors',
				'label' => __( 'Input border color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-field-border',
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'field_focus_color',
				'section' => 'colors',
				'label' => __( 'Input focus color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-field-focus',
				'help' => __( 'Border and soft glow around an input when it is selected.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'button_bg_color',
				'section' => 'colors',
				'label' => __( 'Button background', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-button-bg',
			),
			array(
				'key'  => 'button_hover_color',
				'section' => 'colors',
				'label' => __( 'Button hover background', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-button-hover',
			),
			array(
				'key'  => 'button_text_color',
				'section' => 'colors',
				'label' => __( 'Button text color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-button-text',
				'preview_target' => '#login form input[type=submit]',
			),
			array(
				'key'  => 'button_text_hover_color',
				'section' => 'colors',
				'label' => __( 'Button text on hover', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-button-hover-text',
				'help' => __( 'Text color while the pointer is over the button.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login form input[type=submit]',
			),
			array(
				'key'  => 'checkbox_color',
				'section' => 'colors',
				'label' => __( 'Checkbox accent color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-accent',
				'help' => __( 'Colors the “Remember Me” checkbox and other native form controls.', 'whitelabel-login-customizer' ),
				'preview_target' => '#login form input[type=checkbox]',
			),
			array(
				'key'  => 'link_color',
				'section' => 'colors',
				'label' => __( 'Link color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-link',
			),
			array(
				'key'  => 'link_hover_color',
				'section' => 'colors',
				'label' => __( 'Link hover color', 'whitelabel-login-customizer' ),
				'type' => 'color',
				'var'  => '--wlwp-link-hover',
			),
			array(
				'key'  => 'form_width',
				'section' => 'layout',
				'label' => __( 'Form width (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-form-width',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_padding',
				'section' => 'layout',
				'label' => __( 'Form padding (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-form-padding',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_border_width',
				'section' => 'layout',
				'label' => __( 'Form border width (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-form-border-width',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_radius',
				'section' => 'layout',
				'label' => __( 'Form corner radius (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-form-radius',
				'unit' => 'px',
			),
			array(
				'key'  => 'field_radius',
				'section' => 'layout',
				'label' => __( 'Input corner radius (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-field-radius',
				'unit' => 'px',
			),
			array(
				'key'  => 'button_radius',
				'section' => 'layout',
				'label' => __( 'Button corner radius (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-button-radius',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_shadow',
				'section' => 'layout',
				'label' => __( 'Form shadow', 'whitelabel-login-customizer' ),
				'type' => 'select',
				'var'  => '--wlwp-form-shadow',
				'choices' => Settings::get_shadow_choices(),
				'map' => array(
					'none'   => 'none',
					'subtle' => '0 4px 16px rgba(0, 0, 0, 0.10)',
					'strong' => '0 12px 32px rgba(0, 0, 0, 0.22)',
				),
			),
			array(
				'key'  => 'font_family',
				'section' => 'layout',
				'label' => __( 'Font family', 'whitelabel-login-customizer' ),
				'type' => 'select',
				'var'  => '--wlwp-font',
				'help' => __( 'Rubik is the default — the same font used on whitelabelwp.io. It loads automatically when selected.', 'whitelabel-login-customizer' ),
				'choices' => Settings::get_font_choices(),
				'map' => array(
					'' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
				),
			),
			array(
				'key'  => 'font_size',
				'section' => 'layout',
				'label' => __( 'Font size (px)', 'whitelabel-login-customizer' ),
				'type' => 'number',
				'var'  => '--wlwp-font-size',
				'unit' => 'px',
			),
			array(
				'key'  => 'custom_css',
				'section' => 'advanced',
				'label' => __( 'Additional CSS', 'whitelabel-login-customizer' ),
				'type' => 'css',
				'help' => __( 'Applied to the real wp-login.php screen only. HTML tags and @import are stripped for safety.', 'whitelabel-login-customizer' ),
			),
		);
	}

	/**
	 * Section description callback.
	 *
	 * @param array $args Section args.
	 */
	public function render_section( $args ) {
		$id       = isset( $args['id'] ) ? (string) $args['id'] : '';
		$key      = 0 === strpos( $id, 'wl_' ) ? substr( $id, 3 ) : $id;
		$sections = $this->get_sections();

		if ( ! isset( $sections[ $key ] ) ) {
			return;
		}

		echo '<p class="wlwp-panel-intro">' . esc_html( $sections[ $key ]['intro'] ) . '</p>';

		if ( 'colors' === $key ) {
			echo '<div class="wlwp-palette-picker">';
			echo '<p class="wlwp-palette-picker-title">' . esc_html__( 'Quick palettes', 'whitelabel-login-customizer' ) . '</p>';
			echo '<div class="wlwp-palette-options">';
			foreach ( Settings::get_palettes() as $slug => $palette ) {
				echo '<button type="button" class="wlwp-palette-option" data-wlwp-preset="' . esc_attr( $slug ) . '">';
				echo '<span class="wlwp-palette-swatch" aria-hidden="true">';
				foreach ( $palette['swatch'] as $color ) {
					echo '<span style="background-color:' . esc_attr( $color ) . '"></span>';
				}
				echo '</span>';
				echo '<span class="wlwp-palette-name">' . esc_html( $palette['label'] ) . '</span>';
				echo '</button>';
			}
			echo '</div>';
			echo '</div>';
		}

		if ( 'general' !== $key ) {
			return;
		}

		$guide = array(
			__( 'General & Branding', 'whitelabel-login-customizer' ) => __( 'The logo, messages, footer text and helper links around the form.', 'whitelabel-login-customizer' ),
			__( 'Custom login page', 'whitelabel-login-customizer' )  => __( 'Show the complete standard WordPress login screen on your own page and redirect wp-login.php there.', 'whitelabel-login-customizer' ),
			__( 'Colors & Background', 'whitelabel-login-customizer' ) => __( 'The page background and the colors of the form, inputs and buttons.', 'whitelabel-login-customizer' ),
			__( 'Layout & Typography', 'whitelabel-login-customizer' )  => __( 'Widths, spacing, rounded corners and fonts.', 'whitelabel-login-customizer' ),
			__( 'Advanced', 'whitelabel-login-customizer' )             => __( 'Your own CSS for anything the options above cannot do.', 'whitelabel-login-customizer' ),
		);

		echo '<div class="wlwp-guide">';
		echo '<p class="wlwp-guide-title">' . esc_html__( 'What changes what', 'whitelabel-login-customizer' ) . '</p>';
		echo '<ul class="wlwp-guide-list">';
		foreach ( $guide as $title => $description ) {
			printf(
				'<li><strong>%1$s</strong><span>%2$s</span></li>',
				esc_html( $title ),
				esc_html( $description )
			);
		}
		echo '</ul>';
		echo '<p class="wlwp-guide-hint">' . esc_html__( 'Defaults match the stock WordPress login screen. Want a different look? Open Colors & Background and pick a quick palette. Tip: hover over a setting to highlight what it changes.', 'whitelabel-login-customizer' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Generic field renderer.
	 *
	 * @param array $field Field config.
	 */
	public function render_field( $field ) {
		$options = Settings::get_options();
		$key     = $field['key'];
		$value   = isset( $options[ $key ] ) ? $options[ $key ] : '';

		if ( ! empty( $field['preview_target'] ) ) {
			echo '<div class="wlwp-field" data-preview-target="' . esc_attr( $field['preview_target'] ) . '">';
		}

		switch ( $field['type'] ) {
			case 'checkbox':
				$this->render_checkbox( $field, $value );
				break;

			case 'checkbox_group':
				foreach ( $field['items'] as $item ) {
					$this->render_checkbox( $item, $options[ $item['key'] ] );
				}
				break;

			case 'dual_number':
				echo '<div class="wlwp-field-row">';
				foreach ( $field['fields'] as $sub ) {
					echo '<label class="wlwp-field-inline"';
					if ( ! empty( $sub['preview_target'] ) ) {
						echo ' data-preview-target="' . esc_attr( $sub['preview_target'] ) . '"';
					}
					echo '><span>' . esc_html( $sub['label'] ) . '</span>';
					$this->render_input(
						'number',
						$sub['key'],
						$options[ $sub['key'] ],
						array(
							'var'   => $sub['var'],
							'unit'  => $sub['unit'],
							'class' => 'small-text',
						)
					);
					echo '</label>';
				}
				echo '</div>';
				break;

			case 'color':
				$this->render_input( 'color', $key, $value, $field );
				break;

			case 'number':
			case 'text':
			case 'url':
				$this->render_input( $field['type'], $key, $value, $field );
				break;

			case 'select':
				$this->render_select( $field, $value );
				break;

			case 'pages':
				$field['choices'] = array( 0 => __( '— Select a page —', 'whitelabel-login-customizer' ) );
				$pages            = get_pages(
					array(
						'sort_column' => 'menu_order,post_title',
						'post_status' => 'publish',
					)
				);
				foreach ( $pages as $page ) {
					$field['choices'][ $page->ID ] = $page->post_title;
				}
				$this->render_select( $field, $value );
				break;

			case 'shortcode':
				$this->render_shortcode( $field );
				break;

			case 'create_page':
				$this->render_create_page();
				break;

			case 'textarea':
			case 'css':
				$this->render_textarea( $field, $value );
				break;

			case 'logo':
				$this->render_media( $field, Frontend::get_logo_url(), 'logo_id', 'logo_url' );
				break;

			case 'image':
				$this->render_media( $field, Frontend::get_background_url(), 'bg_image_id', 'bg_image' );
				break;
		}

		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description wlwp-field-help">' . esc_html( $field['help'] ) . '</p>';
		}

		if ( ! empty( $field['preview_target'] ) ) {
			echo '</div>';
		}
	}

	/**
	 * Render a text-like input.
	 *
	 * @param string $type  Input type.
	 * @param string $key   Option key.
	 * @param mixed  $value Current value.
	 * @param array  $field Field config.
	 */
	private function render_input( $type, $key, $value, $field ) {
		$class = isset( $field['class'] ) ? $field['class'] : ( 'color' === $type ? 'wlwp-color-field' : 'regular-text' );

		printf(
			'<input type="%1$s" name="wlwp_login_options[%2$s]" value="%3$s" class="%4$s"',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( (string) $value ),
			esc_attr( $class )
		);

		if ( ! empty( $field['placeholder'] ) ) {
			echo ' placeholder="' . esc_attr( $field['placeholder'] ) . '"';
		}
		if ( 'number' === $type ) {
			echo ' min="0" step="1"';
		}
		if ( isset( $field['var'] ) ) {
			echo ' data-css-var="' . esc_attr( $field['var'] ) . '"';
		}
		if ( isset( $field['unit'] ) ) {
			echo ' data-css-unit="' . esc_attr( $field['unit'] ) . '"';
		}
		if ( ! empty( $field['css_image'] ) ) {
			echo ' data-css-image="1"';
		}
		if ( isset( $field['map'] ) ) {
			echo ' data-css-map="' . esc_attr( wp_json_encode( $field['map'] ) ) . '"';
		}

		echo ' />';
	}

	/**
	 * Render a checkbox row.
	 *
	 * @param array $field Field config.
	 * @param mixed $value Current value.
	 */
	private function render_checkbox( $field, $value ) {
		echo '<label class="wlwp-checkbox"';
		if ( ! empty( $field['preview_target'] ) ) {
			echo ' data-preview-target="' . esc_attr( $field['preview_target'] ) . '"';
		}
		echo '>';
		echo '<input type="hidden" name="wlwp_login_options[' . esc_attr( $field['key'] ) . ']" value="0" />';
		echo '<input type="checkbox" name="wlwp_login_options[' . esc_attr( $field['key'] ) . ']" value="1"';
		checked( 1, (int) $value );
		if ( ! empty( $field['preview_hide'] ) ) {
			echo ' data-preview-hide="' . esc_attr( $field['preview_hide'] ) . '"';
		}
		echo ' />';
		echo ' <span>' . esc_html( $field['label'] ) . '</span>';
		echo '</label>';
	}

	/**
	 * Render a select field.
	 *
	 * @param array $field Field config.
	 * @param mixed $value Current value.
	 */
	private function render_select( $field, $value ) {
		echo '<select name="wlwp_login_options[' . esc_attr( $field['key'] ) . ']" class="regular-text"';
		if ( isset( $field['var'] ) ) {
			echo ' data-css-var="' . esc_attr( $field['var'] ) . '"';
		}
		if ( isset( $field['map'] ) ) {
			echo ' data-css-map="' . esc_attr( wp_json_encode( $field['map'] ) ) . '"';
		}
		echo '>';

		foreach ( $field['choices'] as $choice_value => $label ) {
			echo '<option value="' . esc_attr( $choice_value ) . '" ' . selected( (string) $choice_value, (string) $value, false ) . '>'
				. esc_html( $label ) . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Render the copyable shortcode field.
	 *
	 * @param array $field Field config.
	 */
	private function render_shortcode( $field ) {
		$code = isset( $field['code'] ) ? (string) $field['code'] : '[wlwp_login]';

		echo '<div class="wlwp-shortcode-field">';
		echo '<code class="wlwp-shortcode">' . esc_html( $code ) . '</code>';
		echo '<button type="button" class="button wlwp-copy-shortcode"'
			. ' data-wlwp-copy-shortcode="' . esc_attr( $code ) . '"'
			. ' data-copied-label="' . esc_attr__( 'Copied!', 'whitelabel-login-customizer' ) . '"'
			. ' data-copy-failed-label="' . esc_attr__( 'Copy failed', 'whitelabel-login-customizer' ) . '">'
			. esc_html__( 'Copy', 'whitelabel-login-customizer' )
			. '</button>';
		echo '</div>';
	}

	/**
	 * Render the create-page control.
	 */
	private function render_create_page() {
		echo '<div class="wlwp-create-page-field">';
		echo '<input type="text" class="regular-text wlwp-new-page-title"'
			. ' id="wlwp-new-login-page-title"'
			. ' placeholder="' . esc_attr__( 'Login', 'whitelabel-login-customizer' ) . '"'
			. ' aria-label="' . esc_attr__( 'New login page title', 'whitelabel-login-customizer' ) . '" />';
		echo '<button type="button" class="button wlwp-create-login-page">'
			. esc_html__( 'Create login page', 'whitelabel-login-customizer' )
			. '</button>';
		echo '<span class="wlwp-create-page-status" role="status" aria-live="polite"></span>';
		echo '</div>';
	}

	/**
	 * Render a textarea.
	 *
	 * @param array $field Field config.
	 * @param mixed $value Current value.
	 */
	private function render_textarea( $field, $value ) {
		printf(
			'<textarea name="wlwp_login_options[%1$s]" rows="%2$d" class="%3$s">%4$s</textarea>',
			esc_attr( $field['key'] ),
			absint( isset( $field['rows'] ) ? $field['rows'] : 4 ),
			esc_attr( 'css' === $field['type'] ? 'large-text code wlwp-css-field' : 'large-text' ),
			esc_textarea( (string) $value )
		);
	}

	/**
	 * Render an image picker with ID + URL storage.
	 *
	 * @param array  $field      Field config.
	 * @param string $url        Resolved image URL.
	 * @param string $id_key     Attachment ID option key.
	 * @param string $url_key    URL option key.
	 */
	private function render_media( $field, $url, $id_key, $url_key ) {
		$display = '' !== $url ? $url : '';

		echo '<div class="wlwp-media-field"';
		if ( isset( $field['var'] ) ) {
			echo ' data-media-var="' . esc_attr( $field['var'] ) . '"';
		}
		echo '>';

		echo '<input type="hidden" name="wlwp_login_options[' . esc_attr( $id_key ) . ']" value="'
			. absint( Settings::get( $id_key ) ) . '" data-media-id />';

		printf(
			'<input type="text" class="regular-text wlwp-media-url" name="wlwp_login_options[%1$s]" value="%2$s" placeholder="https://…" data-media-url',
			esc_attr( $url_key ),
			esc_attr( $display )
		);
		if ( isset( $field['var'] ) ) {
			echo ' data-css-var="' . esc_attr( $field['var'] ) . '" data-css-image="1"';
		}
		echo ' />';

		echo '<button type="button" class="button wlwp-media-choose" data-media-title="' . esc_attr( isset( $field['media_title'] ) ? $field['media_title'] : __( 'Choose image', 'whitelabel-login-customizer' ) ) . '">'
			. esc_html__( 'Choose from library', 'whitelabel-login-customizer' ) . '</button> ';
		echo '<button type="button" class="button-link wlwp-media-remove"' . ( '' === $display ? ' style="display:none"' : '' ) . '>'
			. esc_html__( 'Remove', 'whitelabel-login-customizer' ) . '</button>';

		echo '<img class="wlwp-media-preview" src="' . esc_url( $display ) . '" alt=""' . ( '' === $display ? ' style="display:none"' : '' ) . ' data-media-preview />';
		echo '</div>';
	}

	/**
	 * Enqueue assets only on the settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wlwp-rubik-font', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap', array(), '1.0' );
		wp_enqueue_style( 'wlwp-admin', WLWP_URL . 'admin/css/admin.css', array( 'dashicons', 'wlwp-rubik-font' ), WLWP_VERSION );
		wp_enqueue_script( 'wlwp-admin', WLWP_URL . 'admin/js/admin.js', array(), WLWP_VERSION, true );
		wp_localize_script(
			'wlwp-admin',
			'wlwpLoginAdmin',
			array(
				'confirmReset'   => __( 'Reset all WhiteLabel Login Customizer settings to their defaults?', 'whitelabel-login-customizer' ),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'createNonce'    => wp_create_nonce( 'wlwp_create_page' ),
				'createPage'     => array(
					'working' => __( 'Creating…', 'whitelabel-login-customizer' ),
					'create'  => __( 'Create login page', 'whitelabel-login-customizer' ),
					'done'    => __( 'Page created and selected.', 'whitelabel-login-customizer' ),
					'error'   => __( 'Could not create the page. Please try again.', 'whitelabel-login-customizer' ),
				),
				'palettes'       => Settings::get_palettes(),
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'whitelabel-login-customizer' ) );
		}

		$enabled      = (bool) Settings::get( 'enabled' );
		$status_class = $enabled ? 'wlwp-status-on' : 'wlwp-status-off';
		$status_text  = $enabled
			? __( 'Customization on', 'whitelabel-login-customizer' )
			: __( 'Using WordPress default', 'whitelabel-login-customizer' );
		$sections     = $this->get_sections();
		?>
		<div class="wrap wlwp-admin">
			<header class="wlwp-page-head">
				<a class="wlwp-mark-link" href="<?php echo esc_url( WLWP_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr__( 'Visit White Label WP', 'whitelabel-login-customizer' ); ?>">
					<img class="wlwp-mark" src="<?php echo esc_url( WLWP_BRAND_ICON ); ?>" alt="" />
				</a>
				<div class="wlwp-page-head-copy">
					<h1><?php esc_html_e( 'WhiteLabel Login Customizer', 'whitelabel-login-customizer' ); ?></h1>
					<p><?php esc_html_e( 'Make the WordPress login screen look like your product. No theme files, no code.', 'whitelabel-login-customizer' ); ?></p>
				</div>
				<div class="wlwp-page-head-actions">
					<span class="wlwp-status <?php echo esc_attr( $status_class ); ?>">
						<span class="wlwp-status-dot" aria-hidden="true"></span>
						<?php echo esc_html( $status_text ); ?>
					</span>
					<button type="submit" form="wlwp-settings-form" class="button button-primary wlwp-header-save">
						<?php esc_html_e( 'Save Changes', 'whitelabel-login-customizer' ); ?>
					</button>
				</div>
			</header>

			<?php /* WordPress core moves admin notices after this marker. */ ?>
			<div class="wp-header-end" aria-hidden="true"></div>

			<div class="wlwp-layout">
				<main class="wlwp-main">
					<form id="wlwp-settings-form" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
						<?php settings_fields( self::GROUP ); ?>

						<nav class="wlwp-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'WhiteLabel Login Customizer sections', 'whitelabel-login-customizer' ); ?>">
							<?php $first = true; ?>
							<?php foreach ( $sections as $id => $section ) : ?>
								<button type="button" role="tab" id="wlwp-tab-<?php echo esc_attr( $id ); ?>"
									class="wlwp-tab <?php echo $first ? 'is-active' : ''; ?>"
									aria-controls="wlwp-panel-<?php echo esc_attr( $id ); ?>"
									aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
									data-wlwp-tab="<?php echo esc_attr( $id ); ?>">
									<?php echo esc_html( $section['title'] ); ?>
								</button>
								<?php $first = false; ?>
							<?php endforeach; ?>
						</nav>

						<div class="wlwp-panels">
							<?php $first = true; ?>
							<?php foreach ( $sections as $id => $section ) : ?>
								<section role="tabpanel" class="wlwp-panel" id="wlwp-panel-<?php echo esc_attr( $id ); ?>"
									aria-labelledby="wlwp-tab-<?php echo esc_attr( $id ); ?>" tabindex="0"
									data-wlwp-panel="<?php echo esc_attr( $id ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
									<?php $this->render_section( array( 'id' => 'wl_' . $id ) ); ?>
									<table class="form-table" role="presentation">
										<tbody>
											<?php do_settings_fields( self::PAGE, 'wl_' . $id ); ?>
										</tbody>
									</table>
								</section>
								<?php $first = false; ?>
							<?php endforeach; ?>
						</div>

						<div class="wlwp-actions">
							<button type="submit" class="button button-primary wlwp-save">
								<?php esc_html_e( 'Save Changes', 'whitelabel-login-customizer' ); ?>
							</button>
							<span class="wlwp-actions-note">
								<?php esc_html_e( 'Changes appear on the real login page after saving.', 'whitelabel-login-customizer' ); ?>
							</span>
						</div>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wlwp-reset-form">
						<?php wp_nonce_field( 'wlwp_reset' ); ?>
						<input type="hidden" name="action" value="wlwp_reset" />
						<button type="submit" class="wlwp-reset-button"><?php esc_html_e( 'Reset all settings to defaults', 'whitelabel-login-customizer' ); ?></button>
					</form>
				</main>

				<aside class="wlwp-preview-wrap">
					<div class="wlwp-preview-header">
						<div>
							<strong><?php esc_html_e( 'Live preview', 'whitelabel-login-customizer' ); ?></strong>
							<span class="wlwp-preview-sub"><?php esc_html_e( 'Updates as you type', 'whitelabel-login-customizer' ); ?></span>
						</div>
						<div class="wlwp-device-toggle" role="group" aria-label="<?php echo esc_attr__( 'Preview size', 'whitelabel-login-customizer' ); ?>">
							<button type="button" class="button button-small active" data-wlwp-device="desktop"><?php esc_html_e( 'Desktop', 'whitelabel-login-customizer' ); ?></button>
							<button type="button" class="button button-small" data-wlwp-device="mobile"><?php esc_html_e( 'Mobile', 'whitelabel-login-customizer' ); ?></button>
						</div>
					</div>

					<div class="wlwp-preview-device wlwp-device-desktop">
						<?php $this->render_preview(); ?>
					</div>

					<?php $view_url = (bool) Settings::get( 'custom_page_enabled' ) ? Frontend::get_custom_login_url() : ''; ?>
					<?php $view_url = '' !== $view_url ? $view_url : wp_login_url(); ?>
					<a class="button wlwp-view-link" href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View live login page', 'whitelabel-login-customizer' ); ?>
						<span aria-hidden="true">↗</span>
					</a>
				</aside>
			</div>

			<footer class="wlwp-brand-footer">
				<a class="wlwp-brand-footer-logo" href="<?php echo esc_url( WLWP_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr__( 'Visit White Label WP', 'whitelabel-login-customizer' ); ?>">
					<img class="wlwp-brand-footer-mark" src="<?php echo esc_url( WLWP_BRAND_LOGO ); ?>" alt="" />
				</a>
				<div class="wlwp-brand-footer-copy">
					<p class="wlwp-brand-footer-title">
						<?php esc_html_e( 'Developed by', 'whitelabel-login-customizer' ); ?>
						<a href="<?php echo esc_url( WLWP_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( WLWP_SITE_NAME ); ?></a>
					</p>
					<p class="wlwp-brand-footer-desc">
						<?php esc_html_e( 'A white-label WordPress development agency for digital agencies — NDA-signed expert developers, ready to hire.', 'whitelabel-login-customizer' ); ?>
					</p>
				</div>
				<nav class="wlwp-brand-footer-links" aria-label="<?php echo esc_attr__( 'White Label WP links', 'whitelabel-login-customizer' ); ?>">
					<a href="<?php echo esc_url( WLWP_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Website', 'whitelabel-login-customizer' ); ?></a>
					<a href="<?php echo esc_url( trailingslashit( WLWP_SITE_URL ) . 'wordpress-outsourcing-services/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Services', 'whitelabel-login-customizer' ); ?></a>
					<a href="<?php echo esc_url( trailingslashit( WLWP_SITE_URL ) . 'blog/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Blog', 'whitelabel-login-customizer' ); ?></a>
					<a href="<?php echo esc_url( trailingslashit( WLWP_SITE_URL ) . 'contact-us/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a quote', 'whitelabel-login-customizer' ); ?></a>
				</nav>
			</footer>
		</div>
		<?php
	}

	/**
	 * Render the scoped preview markup.
	 */
	private function render_preview() {
		$logo_url = Frontend::get_logo_url();
		$message  = Settings::get( 'login_message' );
		$footer   = Settings::get( 'footer_text' );
		$options  = Settings::get_options();
		?>
		<style id="wlwp-preview-css">
			<?php echo Frontend::get_css( '.wlwp-login-preview', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is built and escaped by Frontend::get_css(). ?>
		</style>
		<div class="wlwp-login-preview" id="wlwp-login-preview">
			<div id="login">
				<h1 role="presentation" class="wp-login-logo"><a href="#"><?php echo esc_html( $logo_url ? '' : get_bloginfo( 'name' ) ); ?></a></h1>

				<?php if ( '' !== $message ) : ?>
					<p class="wlwp-login-message"><?php echo Settings::format_rich_text( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is sanitized by Settings::format_rich_text(). ?></p>
				<?php endif; ?>

				<form name="loginform" id="loginform" class="wlwp-preview-form">
					<p>
						<label for="wlwp-preview-user"><?php esc_html_e( 'Username or Email Address', 'whitelabel-login-customizer' ); ?></label>
						<input type="text" id="wlwp-preview-user" class="input" value="" size="20" autocomplete="off" />
					</p>
					<p>
						<label for="wlwp-preview-pass"><?php esc_html_e( 'Password', 'whitelabel-login-customizer' ); ?></label>
						<input type="password" id="wlwp-preview-pass" class="input" value="" size="20" autocomplete="off" />
					</p>
					<p class="forgetmenot">
						<label><input type="checkbox" id="wlwp-preview-remember" /> <?php esc_html_e( 'Remember Me', 'whitelabel-login-customizer' ); ?></label>
					</p>
					<p class="submit">
						<input type="submit" class="wp-core-ui button-primary" value="<?php echo esc_attr__( 'Log In', 'whitelabel-login-customizer' ); ?>" />
					</p>
				</form>

				<p id="nav">
					<?php if ( ! $options['hide_register'] ) : ?>
						<a class="wp-login-register" href="#"><?php esc_html_e( 'Register', 'whitelabel-login-customizer' ); ?></a>
						<?php if ( ! $options['hide_lost_password'] ) : ?><span class="wlwp-nav-sep"> | </span><?php endif; ?>
					<?php endif; ?>
					<?php if ( ! $options['hide_lost_password'] ) : ?>
						<a class="wp-login-lost-password" href="#"><?php esc_html_e( 'Lost your password?', 'whitelabel-login-customizer' ); ?></a>
					<?php endif; ?>
				</p>

				<?php if ( ! $options['hide_backtoblog'] ) : ?>
					<p id="backtoblog"><a href="#">&larr; <?php esc_html_e( 'Go to site', 'whitelabel-login-customizer' ); ?></a></p>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $footer ) : ?>
				<p class="wlwp-login-footer"><?php echo Settings::format_rich_text( $footer ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is sanitized by Settings::format_rich_text(). ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Create a published page containing the login shortcode.
	 */
	public function handle_create_page() {
		check_ajax_referer( 'wlwp_create_page', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to create pages.', 'whitelabel-login-customizer' ) ), 403 );
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			$title = __( 'Login', 'whitelabel-login-customizer' );
		}

		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_content'   => '[wlwp_login]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create the page. Please try again.', 'whitelabel-login-customizer' ) ), 500 );
		}

		$options                     = Settings::get_options();
		$options['custom_page_id']   = absint( $page_id );
		$options['custom_page_enabled'] = 1;
		update_option( WLWP_OPTION, $options, false );

		$edit_link = get_edit_post_link( $page_id );

		wp_send_json_success(
			array(
				'id'       => absint( $page_id ),
				'title'    => get_the_title( $page_id ),
				'url'      => get_permalink( $page_id ),
				'edit_url' => $edit_link ? $edit_link : '',
			)
		);
	}

	/**
	 * Handle the reset action.
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'whitelabel-login-customizer' ) );
		}

		check_admin_referer( 'wlwp_reset' );
		Settings::reset();

		// Show the confirmation once, without leaving a flag in the URL.
		set_transient( 'wlwp_reset_notice_' . get_current_user_id(), 1, MINUTE_IN_SECONDS );

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => self::PAGE ),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Show a confirmation notice after reset.
	 */
	public function show_reset_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_' . self::PAGE !== $screen->id ) {
			return;
		}

		$key = 'wlwp_reset_notice_' . get_current_user_id();
		if ( ! get_transient( $key ) ) {
			return;
		}
		delete_transient( $key );

		echo '<div class="notice notice-success is-dismissible wlwp-reset-notice"><p>'
			. esc_html__( 'WhiteLabel Login Customizer settings have been reset to the WordPress defaults.', 'whitelabel-login-customizer' )
			. '</p></div>';
	}

	/**
	 * Add quick links on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function plugin_links( $links ) {
		$settings = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">'
			. esc_html__( 'Settings', 'whitelabel-login-customizer' ) . '</a>';
		$login    = '<a href="' . esc_url( wp_login_url() ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Login page', 'whitelabel-login-customizer' ) . '</a>';
		$site     = '<a href="' . esc_url( WLWP_SITE_URL ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'White Label WP', 'whitelabel-login-customizer' ) . '</a>';
		$support  = '<a href="' . esc_url( trailingslashit( WLWP_SITE_URL ) . 'contact-us/' ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Support', 'whitelabel-login-customizer' ) . '</a>';

		return array_merge( array( $settings, $login, $site, $support ), $links );
	}
}
