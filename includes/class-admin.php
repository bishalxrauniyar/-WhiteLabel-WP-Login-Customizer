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
	const PAGE = 'wl-login-customizer';

	/**
	 * Settings group.
	 */
	const GROUP = 'wl_login_group';

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
		add_action( 'admin_post_wl_login_reset', array( $this, 'handle_reset' ) );
		add_action( 'wp_ajax_wl_login_create_page', array( $this, 'handle_create_page' ) );
		add_action( 'admin_notices', array( $this, 'show_reset_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WL_LOGIN_FILE ), array( $this, 'plugin_links' ) );
	}

	/**
	 * Add the settings page under Settings.
	 */
	public function add_menu_page() {
		$this->hook_suffix = add_options_page(
			__( 'WhiteLabel WP Login Customizer', 'whitelabel' ),
			__( 'WhiteLabel WP Login Customizer', 'whitelabel' ),
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
				'title' => __( 'General & Branding', 'whitelabel' ),
				'intro' => __( 'These options control the branding around the form on every login screen — Log In, Register, Lost Password and Reset Password.', 'whitelabel' ),
			),
			'page'     => array(
				'title' => __( 'Custom login page', 'whitelabel' ),
				'intro' => __( 'Show the complete standard WordPress login screen on one of your own pages, then send wp-login.php visitors there. Customizer branding stays on wp-login.php.', 'whitelabel' ),
			),
			'colors'   => array(
				'title' => __( 'Colors & Background', 'whitelabel' ),
				'intro' => __( 'The color scheme of the page, form, inputs and buttons — including hover, focus and placeholder states.', 'whitelabel' ),
			),
			'layout'   => array(
				'title' => __( 'Layout & Typography', 'whitelabel' ),
				'intro' => __( 'Sizes, spacing, rounded corners and fonts.', 'whitelabel' ),
			),
			'advanced' => array(
				'title' => __( 'Advanced', 'whitelabel' ),
				'intro' => __( 'Add your own CSS when the options above are not enough.', 'whitelabel' ),
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
				'label'          => __( 'Enable customization', 'whitelabel' ),
				'type'           => 'checkbox',
				'help'           => __( 'Master switch. Turn it off to show the stock WordPress login screen again — your settings are kept, not deleted.', 'whitelabel' ),
				'preview_target' => '#login',
			),
			array(
				'key'            => 'logo_id',
				'section'        => 'general',
				'label'          => __( 'Login logo', 'whitelabel' ),
				'type'           => 'logo',
				'help'           => __( 'Replaces the WordPress logo above the form. A PNG or SVG with a transparent background works best (about 300 × 90 px). Leave empty to keep the WordPress logo.', 'whitelabel' ),
				'media_title'    => __( 'Choose a login logo', 'whitelabel' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'logo_width',
				'section'        => 'general',
				'label'          => __( 'Logo width / height', 'whitelabel' ),
				'type'           => 'dual_number',
				'help'           => __( 'The size of the logo box on the page. Your image is scaled to fit inside it without stretching, so use your image’s proportions for the sharpest result.', 'whitelabel' ),
				'fields'         => array(
					array( 'key' => 'logo_width', 'label' => __( 'Width (px)', 'whitelabel' ), 'var' => '--wl-logo-width', 'unit' => 'px', 'preview_target' => '#login h1' ),
					array( 'key' => 'logo_height', 'label' => __( 'Height (px)', 'whitelabel' ), 'var' => '--wl-logo-height', 'unit' => 'px', 'preview_target' => '#login h1' ),
				),
			),
			array(
				'key'            => 'logo_link',
				'section'        => 'general',
				'label'          => __( 'Logo link URL', 'whitelabel' ),
				'type'           => 'url',
				'placeholder'    => home_url( '/' ),
				'help'           => __( 'Where visitors go when they click the logo. Leave empty to link to your site’s home page.', 'whitelabel' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'logo_alt',
				'section'        => 'general',
				'label'          => __( 'Logo alt text', 'whitelabel' ),
				'type'           => 'text',
				'placeholder'    => get_bloginfo( 'name' ),
				'help'           => __( 'Alternative text read aloud by screen readers and shown as the link’s tooltip. Leave empty to use your site name.', 'whitelabel' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'page_title',
				'section'        => 'general',
				'label'          => __( 'Browser page title', 'whitelabel' ),
				'type'           => 'text',
				'placeholder'    => __( 'Log In', 'whitelabel' ),
				'help'           => __( 'The text in the browser tab or window title bar. Leave empty to keep WordPress’s default, e.g. “Log In ‹ Your Site”.', 'whitelabel' ),
				'preview_target' => '#login h1',
			),
			array(
				'key'            => 'login_message',
				'section'        => 'general',
				'label'          => __( 'Message above form', 'whitelabel' ),
				'type'           => 'textarea',
				'rows'           => 3,
				'help'           => __( 'A welcome note shown above the form on every login screen, including “Lost your password”. Basic HTML such as links and <strong> is allowed.', 'whitelabel' ),
				'preview_target' => '.wl-login-message',
			),
			array(
				'key'            => 'footer_text',
				'section'        => 'general',
				'label'          => __( 'Footer text', 'whitelabel' ),
				'type'           => 'textarea',
				'rows'           => 2,
				'help'           => __( 'Shown at the very bottom of the login page, below the navigation links. Useful for a support email or legal note. Basic HTML is allowed.', 'whitelabel' ),
				'preview_target' => '.wl-login-footer',
			),
			array(
				'key'    => 'hide_backtoblog',
				'section' => 'general',
				'label'  => __( 'Hide links', 'whitelabel' ),
				'type'   => 'checkbox_group',
				'help'   => __( 'These links sit below the form on the real login page. Hiding one removes it from every login screen; the feature itself still works if someone visits its URL directly.', 'whitelabel' ),
				'items'  => array(
					array( 'key' => 'hide_backtoblog', 'label' => __( 'Hide “Go to site” link', 'whitelabel' ), 'preview_hide' => '#backtoblog', 'preview_target' => '#backtoblog' ),
					array( 'key' => 'hide_register', 'label' => __( 'Hide “Register” link', 'whitelabel' ), 'preview_hide' => '.wp-login-register', 'preview_target' => '.wp-login-register' ),
					array( 'key' => 'hide_lost_password', 'label' => __( 'Hide “Lost your password?” link', 'whitelabel' ), 'preview_hide' => '.wp-login-lost-password', 'preview_target' => '.wp-login-lost-password' ),
				),
			),
			array(
				'key'    => 'custom_page_enabled',
				'section' => 'page',
				'label'  => __( 'Use a custom login page', 'whitelabel' ),
				'type'   => 'checkbox',
				'help'   => __( 'When enabled, wp-login.php visitors are sent to the selected page. The page shows the complete standard WordPress login screen, and logout also returns there.', 'whitelabel' ),
			),
			array(
				'key'    => 'custom_page_id',
				'section' => 'page',
				'label'  => __( 'Login page', 'whitelabel' ),
				'type'   => 'pages',
				'help'   => __( 'Choose the published page that contains the shortcode. The page itself must be published for the redirect to work.', 'whitelabel' ),
			),
			array(
				'key'    => 'custom_page_create',
				'section' => 'page',
				'label'  => __( 'Create a new login page', 'whitelabel' ),
				'type'   => 'create_page',
				'help'   => __( 'Creates a published page with the shortcode already in place, then selects it above. The page shows the complete standard WordPress login screen. You can rename it in Pages later.', 'whitelabel' ),
			),
			array(
				'key'    => 'custom_page_shortcode',
				'section' => 'page',
				'label'  => __( 'Shortcode for your page', 'whitelabel' ),
				'type'   => 'shortcode',
				'help'   => __( 'Paste this into a published page to show the complete standard WordPress login screen, including its styling, links and logged-in behaviour. Customizer styling is not applied here. You can also use [wl_login].', 'whitelabel' ),
			),
			array(
				'key'  => 'bg_color',
				'section' => 'colors',
				'label' => __( 'Page background', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-bg',
			),
			array(
				'key'           => 'bg_image_id',
				'section'       => 'colors',
				'label'         => __( 'Background image', 'whitelabel' ),
				'type'          => 'image',
				'media_title'   => __( 'Choose a background image', 'whitelabel' ),
				'var'           => '--wl-bg-image',
				'css_image'     => true,
			),
			array(
				'key'  => 'bg_repeat',
				'section' => 'colors',
				'label' => __( 'Background repeat', 'whitelabel' ),
				'type' => 'select',
				'var'  => '--wl-bg-repeat',
				'choices' => array(
					'no-repeat' => __( 'No repeat', 'whitelabel' ),
					'repeat'    => __( 'Repeat', 'whitelabel' ),
					'repeat-x'  => __( 'Repeat horizontally', 'whitelabel' ),
					'repeat-y'  => __( 'Repeat vertically', 'whitelabel' ),
					'space'     => __( 'Space', 'whitelabel' ),
					'round'     => __( 'Round', 'whitelabel' ),
				),
			),
			array(
				'key'  => 'bg_position',
				'section' => 'colors',
				'label' => __( 'Background position', 'whitelabel' ),
				'type' => 'select',
				'var'  => '--wl-bg-position',
				'choices' => array(
					'center center' => __( 'Center', 'whitelabel' ),
					'top left'      => __( 'Top left', 'whitelabel' ),
					'top center'    => __( 'Top center', 'whitelabel' ),
					'top right'     => __( 'Top right', 'whitelabel' ),
					'center left'   => __( 'Center left', 'whitelabel' ),
					'center right'  => __( 'Center right', 'whitelabel' ),
					'bottom left'   => __( 'Bottom left', 'whitelabel' ),
					'bottom center' => __( 'Bottom center', 'whitelabel' ),
					'bottom right'  => __( 'Bottom right', 'whitelabel' ),
				),
			),
			array(
				'key'  => 'bg_size',
				'section' => 'colors',
				'label' => __( 'Background size', 'whitelabel' ),
				'type' => 'select',
				'var'  => '--wl-bg-size',
				'choices' => array(
					'cover'     => __( 'Cover', 'whitelabel' ),
					'contain'   => __( 'Contain', 'whitelabel' ),
					'auto'      => __( 'Original size', 'whitelabel' ),
					'100% auto' => __( 'Full width', 'whitelabel' ),
				),
			),
			array(
				'key'  => 'bg_attachment',
				'section' => 'colors',
				'label' => __( 'Background attachment', 'whitelabel' ),
				'type' => 'select',
				'var'  => '--wl-bg-attachment',
				'choices' => array(
					'fixed'  => __( 'Fixed', 'whitelabel' ),
					'scroll' => __( 'Scroll', 'whitelabel' ),
				),
			),
			array(
				'key'  => 'text_color',
				'section' => 'colors',
				'label' => __( 'Text color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-text',
			),
			array(
				'key'  => 'form_bg_color',
				'section' => 'colors',
				'label' => __( 'Form background', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-form-bg',
				'preview_target' => '#login form',
			),
			array(
				'key'  => 'form_text_color',
				'section' => 'colors',
				'label' => __( 'Form text color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-form-text',
				'help' => __( 'Labels and helper text inside the form. The page text color above still controls messages around it.', 'whitelabel' ),
				'preview_target' => '#login form label',
			),
			array(
				'key'  => 'form_border_color',
				'section' => 'colors',
				'label' => __( 'Form border color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-form-border-color',
			),
			array(
				'key'  => 'field_bg_color',
				'section' => 'colors',
				'label' => __( 'Input background', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-field-bg',
			),
			array(
				'key'  => 'field_text_color',
				'section' => 'colors',
				'label' => __( 'Input text color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-field-text',
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'field_placeholder_color',
				'section' => 'colors',
				'label' => __( 'Placeholder color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-field-placeholder',
				'help' => __( 'The light sample text shown in empty input fields.', 'whitelabel' ),
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'field_border_color',
				'section' => 'colors',
				'label' => __( 'Input border color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-field-border',
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'field_focus_color',
				'section' => 'colors',
				'label' => __( 'Input focus color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-field-focus',
				'help' => __( 'Border and soft glow around an input when it is selected.', 'whitelabel' ),
				'preview_target' => '#login form .input',
			),
			array(
				'key'  => 'button_bg_color',
				'section' => 'colors',
				'label' => __( 'Button background', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-button-bg',
			),
			array(
				'key'  => 'button_hover_color',
				'section' => 'colors',
				'label' => __( 'Button hover background', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-button-hover',
			),
			array(
				'key'  => 'button_text_color',
				'section' => 'colors',
				'label' => __( 'Button text color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-button-text',
				'preview_target' => '#login form input[type=submit]',
			),
			array(
				'key'  => 'button_text_hover_color',
				'section' => 'colors',
				'label' => __( 'Button text on hover', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-button-hover-text',
				'help' => __( 'Text color while the pointer is over the button.', 'whitelabel' ),
				'preview_target' => '#login form input[type=submit]',
			),
			array(
				'key'  => 'checkbox_color',
				'section' => 'colors',
				'label' => __( 'Checkbox accent color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-accent',
				'help' => __( 'Colors the “Remember Me” checkbox and other native form controls.', 'whitelabel' ),
				'preview_target' => '#login form input[type=checkbox]',
			),
			array(
				'key'  => 'link_color',
				'section' => 'colors',
				'label' => __( 'Link color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-link',
			),
			array(
				'key'  => 'link_hover_color',
				'section' => 'colors',
				'label' => __( 'Link hover color', 'whitelabel' ),
				'type' => 'color',
				'var'  => '--wl-link-hover',
			),
			array(
				'key'  => 'form_width',
				'section' => 'layout',
				'label' => __( 'Form width (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-form-width',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_padding',
				'section' => 'layout',
				'label' => __( 'Form padding (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-form-padding',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_border_width',
				'section' => 'layout',
				'label' => __( 'Form border width (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-form-border-width',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_radius',
				'section' => 'layout',
				'label' => __( 'Form corner radius (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-form-radius',
				'unit' => 'px',
			),
			array(
				'key'  => 'field_radius',
				'section' => 'layout',
				'label' => __( 'Input corner radius (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-field-radius',
				'unit' => 'px',
			),
			array(
				'key'  => 'button_radius',
				'section' => 'layout',
				'label' => __( 'Button corner radius (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-button-radius',
				'unit' => 'px',
			),
			array(
				'key'  => 'form_shadow',
				'section' => 'layout',
				'label' => __( 'Form shadow', 'whitelabel' ),
				'type' => 'select',
				'var'  => '--wl-form-shadow',
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
				'label' => __( 'Font family', 'whitelabel' ),
				'type' => 'select',
				'var'  => '--wl-font',
				'help' => __( 'Rubik is the default — the same font used on whitelabelwp.io. It loads automatically when selected.', 'whitelabel' ),
				'choices' => Settings::get_font_choices(),
				'map' => array(
					'' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
				),
			),
			array(
				'key'  => 'font_size',
				'section' => 'layout',
				'label' => __( 'Font size (px)', 'whitelabel' ),
				'type' => 'number',
				'var'  => '--wl-font-size',
				'unit' => 'px',
			),
			array(
				'key'  => 'custom_css',
				'section' => 'advanced',
				'label' => __( 'Additional CSS', 'whitelabel' ),
				'type' => 'css',
				'help' => __( 'Applied to the real wp-login.php screen only. HTML tags and @import are stripped for safety.', 'whitelabel' ),
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

		echo '<p class="wl-panel-intro">' . esc_html( $sections[ $key ]['intro'] ) . '</p>';

		if ( 'colors' === $key ) {
			echo '<div class="wl-palette-picker">';
			echo '<p class="wl-palette-picker-title">' . esc_html__( 'Quick palettes', 'whitelabel' ) . '</p>';
			echo '<div class="wl-palette-options">';
			foreach ( Settings::get_palettes() as $slug => $palette ) {
				echo '<button type="button" class="wl-palette-option" data-wl-preset="' . esc_attr( $slug ) . '">';
				echo '<span class="wl-palette-swatch" aria-hidden="true">';
				foreach ( $palette['swatch'] as $color ) {
					echo '<span style="background-color:' . esc_attr( $color ) . '"></span>';
				}
				echo '</span>';
				echo '<span class="wl-palette-name">' . esc_html( $palette['label'] ) . '</span>';
				echo '</button>';
			}
			echo '</div>';
			echo '</div>';
		}

		if ( 'general' !== $key ) {
			return;
		}

		$guide = array(
			__( 'General & Branding', 'whitelabel' ) => __( 'The logo, messages, footer text and helper links around the form.', 'whitelabel' ),
			__( 'Custom login page', 'whitelabel' )  => __( 'Show the complete standard WordPress login screen on your own page and redirect wp-login.php there.', 'whitelabel' ),
			__( 'Colors & Background', 'whitelabel' ) => __( 'The page background and the colors of the form, inputs and buttons.', 'whitelabel' ),
			__( 'Layout & Typography', 'whitelabel' )  => __( 'Widths, spacing, rounded corners and fonts.', 'whitelabel' ),
			__( 'Advanced', 'whitelabel' )             => __( 'Your own CSS for anything the options above cannot do.', 'whitelabel' ),
		);

		echo '<div class="wl-guide">';
		echo '<p class="wl-guide-title">' . esc_html__( 'What changes what', 'whitelabel' ) . '</p>';
		echo '<ul class="wl-guide-list">';
		foreach ( $guide as $title => $description ) {
			printf(
				'<li><strong>%1$s</strong><span>%2$s</span></li>',
				esc_html( $title ),
				esc_html( $description )
			);
		}
		echo '</ul>';
		echo '<p class="wl-guide-hint">' . esc_html__( 'Defaults match the stock WordPress login screen. Want a different look? Open Colors & Background and pick a quick palette. Tip: hover over a setting to highlight what it changes.', 'whitelabel' ) . '</p>';
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
			echo '<div class="wl-field" data-preview-target="' . esc_attr( $field['preview_target'] ) . '">';
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
				echo '<div class="wl-field-row">';
				foreach ( $field['fields'] as $sub ) {
					echo '<label class="wl-field-inline"';
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
				$field['choices'] = array( 0 => __( '— Select a page —', 'whitelabel' ) );
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
			echo '<p class="description wl-field-help">' . esc_html( $field['help'] ) . '</p>';
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
		$class = isset( $field['class'] ) ? $field['class'] : ( 'color' === $type ? 'wl-color-field' : 'regular-text' );

		printf(
			'<input type="%1$s" name="wl_login_options[%2$s]" value="%3$s" class="%4$s"',
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
		echo '<label class="wl-checkbox"';
		if ( ! empty( $field['preview_target'] ) ) {
			echo ' data-preview-target="' . esc_attr( $field['preview_target'] ) . '"';
		}
		echo '>';
		echo '<input type="hidden" name="wl_login_options[' . esc_attr( $field['key'] ) . ']" value="0" />';
		echo '<input type="checkbox" name="wl_login_options[' . esc_attr( $field['key'] ) . ']" value="1"';
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
		echo '<select name="wl_login_options[' . esc_attr( $field['key'] ) . ']" class="regular-text"';
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
		$code = isset( $field['code'] ) ? (string) $field['code'] : '[whitelabel_login]';

		echo '<div class="wl-shortcode-field">';
		echo '<code class="wl-shortcode">' . esc_html( $code ) . '</code>';
		echo '<button type="button" class="button wl-copy-shortcode"'
			. ' data-wl-copy-shortcode="' . esc_attr( $code ) . '"'
			. ' data-copied-label="' . esc_attr__( 'Copied!', 'whitelabel' ) . '"'
			. ' data-copy-failed-label="' . esc_attr__( 'Copy failed', 'whitelabel' ) . '">'
			. esc_html__( 'Copy', 'whitelabel' )
			. '</button>';
		echo '</div>';
	}

	/**
	 * Render the create-page control.
	 */
	private function render_create_page() {
		echo '<div class="wl-create-page-field">';
		echo '<input type="text" class="regular-text wl-new-page-title"'
			. ' id="wl-new-login-page-title"'
			. ' placeholder="' . esc_attr__( 'Login', 'whitelabel' ) . '"'
			. ' aria-label="' . esc_attr__( 'New login page title', 'whitelabel' ) . '" />';
		echo '<button type="button" class="button wl-create-login-page">'
			. esc_html__( 'Create login page', 'whitelabel' )
			. '</button>';
		echo '<span class="wl-create-page-status" role="status" aria-live="polite"></span>';
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
			'<textarea name="wl_login_options[%1$s]" rows="%2$d" class="%3$s">%4$s</textarea>',
			esc_attr( $field['key'] ),
			absint( isset( $field['rows'] ) ? $field['rows'] : 4 ),
			esc_attr( 'css' === $field['type'] ? 'large-text code wl-css-field' : 'large-text' ),
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

		echo '<div class="wl-media-field"';
		if ( isset( $field['var'] ) ) {
			echo ' data-media-var="' . esc_attr( $field['var'] ) . '"';
		}
		echo '>';

		echo '<input type="hidden" name="wl_login_options[' . esc_attr( $id_key ) . ']" value="'
			. absint( Settings::get( $id_key ) ) . '" data-media-id />';

		printf(
			'<input type="text" class="regular-text wl-media-url" name="wl_login_options[%1$s]" value="%2$s" placeholder="https://…" data-media-url',
			esc_attr( $url_key ),
			esc_attr( $display )
		);
		if ( isset( $field['var'] ) ) {
			echo ' data-css-var="' . esc_attr( $field['var'] ) . '" data-css-image="1"';
		}
		echo ' />';

		echo '<button type="button" class="button wl-media-choose" data-media-title="' . esc_attr( isset( $field['media_title'] ) ? $field['media_title'] : __( 'Choose image', 'whitelabel' ) ) . '">'
			. esc_html__( 'Choose from library', 'whitelabel' ) . '</button> ';
		echo '<button type="button" class="button-link wl-media-remove"' . ( '' === $display ? ' style="display:none"' : '' ) . '>'
			. esc_html__( 'Remove', 'whitelabel' ) . '</button>';

		echo '<img class="wl-media-preview" src="' . esc_url( $display ) . '" alt=""' . ( '' === $display ? ' style="display:none"' : '' ) . ' data-media-preview />';
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
		wp_enqueue_style( 'wl-rubik-font', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap', array(), '1.0' );
		wp_enqueue_style( 'wl-admin', WL_LOGIN_URL . 'admin/css/admin.css', array( 'dashicons', 'wl-rubik-font' ), WL_LOGIN_VERSION );
		wp_enqueue_script( 'wl-admin', WL_LOGIN_URL . 'admin/js/admin.js', array(), WL_LOGIN_VERSION, true );
		wp_localize_script(
			'wl-admin',
			'wlLoginAdmin',
			array(
				'confirmReset'   => __( 'Reset all WhiteLabel WP Login Customizer settings to their defaults?', 'whitelabel' ),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'createNonce'    => wp_create_nonce( 'wl_login_create_page' ),
				'createPage'     => array(
					'working' => __( 'Creating…', 'whitelabel' ),
					'create'  => __( 'Create login page', 'whitelabel' ),
					'done'    => __( 'Page created and selected.', 'whitelabel' ),
					'error'   => __( 'Could not create the page. Please try again.', 'whitelabel' ),
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
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'whitelabel' ) );
		}

		$enabled      = (bool) Settings::get( 'enabled' );
		$status_class = $enabled ? 'wl-status-on' : 'wl-status-off';
		$status_text  = $enabled
			? __( 'Customization on', 'whitelabel' )
			: __( 'Using WordPress default', 'whitelabel' );
		$sections     = $this->get_sections();
		?>
		<div class="wrap wl-admin">
			<header class="wl-page-head">
				<a class="wl-mark-link" href="<?php echo esc_url( WL_LOGIN_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr__( 'Visit White Label WP', 'whitelabel' ); ?>">
					<img class="wl-mark" src="<?php echo esc_url( WL_LOGIN_BRAND_ICON ); ?>" alt="" />
				</a>
				<div class="wl-page-head-copy">
					<h1><?php esc_html_e( 'WhiteLabel WP Login Customizer', 'whitelabel' ); ?></h1>
					<p><?php esc_html_e( 'Make the WordPress login screen look like your product. No theme files, no code.', 'whitelabel' ); ?></p>
				</div>
				<div class="wl-page-head-actions">
					<span class="wl-status <?php echo esc_attr( $status_class ); ?>">
						<span class="wl-status-dot" aria-hidden="true"></span>
						<?php echo esc_html( $status_text ); ?>
					</span>
					<button type="submit" form="wl-settings-form" class="button button-primary wl-header-save">
						<?php esc_html_e( 'Save Changes', 'whitelabel' ); ?>
					</button>
				</div>
			</header>

			<?php /* WordPress core moves admin notices after this marker. */ ?>
			<div class="wp-header-end" aria-hidden="true"></div>

			<div class="wl-layout">
				<main class="wl-main">
					<form id="wl-settings-form" method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
						<?php settings_fields( self::GROUP ); ?>

						<nav class="wl-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'WhiteLabel WP Login Customizer sections', 'whitelabel' ); ?>">
							<?php $first = true; ?>
							<?php foreach ( $sections as $id => $section ) : ?>
								<button type="button" role="tab" id="wl-tab-<?php echo esc_attr( $id ); ?>"
									class="wl-tab <?php echo $first ? 'is-active' : ''; ?>"
									aria-controls="wl-panel-<?php echo esc_attr( $id ); ?>"
									aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
									data-wl-tab="<?php echo esc_attr( $id ); ?>">
									<?php echo esc_html( $section['title'] ); ?>
								</button>
								<?php $first = false; ?>
							<?php endforeach; ?>
						</nav>

						<div class="wl-panels">
							<?php $first = true; ?>
							<?php foreach ( $sections as $id => $section ) : ?>
								<section role="tabpanel" class="wl-panel" id="wl-panel-<?php echo esc_attr( $id ); ?>"
									aria-labelledby="wl-tab-<?php echo esc_attr( $id ); ?>" tabindex="0"
									data-wl-panel="<?php echo esc_attr( $id ); ?>" <?php echo $first ? '' : 'hidden'; ?>>
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

						<div class="wl-actions">
							<button type="submit" class="button button-primary wl-save">
								<?php esc_html_e( 'Save Changes', 'whitelabel' ); ?>
							</button>
							<span class="wl-actions-note">
								<?php esc_html_e( 'Changes appear on the real login page after saving.', 'whitelabel' ); ?>
							</span>
						</div>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wl-reset-form">
						<?php wp_nonce_field( 'wl_login_reset' ); ?>
						<input type="hidden" name="action" value="wl_login_reset" />
						<button type="submit" class="wl-reset-button"><?php esc_html_e( 'Reset all settings to defaults', 'whitelabel' ); ?></button>
					</form>
				</main>

				<aside class="wl-preview-wrap">
					<div class="wl-preview-header">
						<div>
							<strong><?php esc_html_e( 'Live preview', 'whitelabel' ); ?></strong>
							<span class="wl-preview-sub"><?php esc_html_e( 'Updates as you type', 'whitelabel' ); ?></span>
						</div>
						<div class="wl-device-toggle" role="group" aria-label="<?php echo esc_attr__( 'Preview size', 'whitelabel' ); ?>">
							<button type="button" class="button button-small active" data-wl-device="desktop"><?php esc_html_e( 'Desktop', 'whitelabel' ); ?></button>
							<button type="button" class="button button-small" data-wl-device="mobile"><?php esc_html_e( 'Mobile', 'whitelabel' ); ?></button>
						</div>
					</div>

					<div class="wl-preview-device wl-device-desktop">
						<?php $this->render_preview(); ?>
					</div>

					<?php $view_url = (bool) Settings::get( 'custom_page_enabled' ) ? Frontend::get_custom_login_url() : ''; ?>
					<?php $view_url = '' !== $view_url ? $view_url : wp_login_url(); ?>
					<a class="button wl-view-link" href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View live login page', 'whitelabel' ); ?>
						<span aria-hidden="true">↗</span>
					</a>
				</aside>
			</div>

			<footer class="wl-brand-footer">
				<a class="wl-brand-footer-logo" href="<?php echo esc_url( WL_LOGIN_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr__( 'Visit White Label WP', 'whitelabel' ); ?>">
					<img class="wl-brand-footer-mark" src="<?php echo esc_url( WL_LOGIN_BRAND_LOGO ); ?>" alt="" />
				</a>
				<div class="wl-brand-footer-copy">
					<p class="wl-brand-footer-title">
						<?php esc_html_e( 'Developed by', 'whitelabel' ); ?>
						<a href="<?php echo esc_url( WL_LOGIN_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( WL_LOGIN_SITE_NAME ); ?></a>
					</p>
					<p class="wl-brand-footer-desc">
						<?php esc_html_e( 'A white-label WordPress development agency for digital agencies — NDA-signed expert developers, ready to hire.', 'whitelabel' ); ?>
					</p>
				</div>
				<nav class="wl-brand-footer-links" aria-label="<?php echo esc_attr__( 'White Label WP links', 'whitelabel' ); ?>">
					<a href="<?php echo esc_url( WL_LOGIN_SITE_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Website', 'whitelabel' ); ?></a>
					<a href="<?php echo esc_url( trailingslashit( WL_LOGIN_SITE_URL ) . 'wordpress-outsourcing-services/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Services', 'whitelabel' ); ?></a>
					<a href="<?php echo esc_url( trailingslashit( WL_LOGIN_SITE_URL ) . 'blog/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Blog', 'whitelabel' ); ?></a>
					<a href="<?php echo esc_url( trailingslashit( WL_LOGIN_SITE_URL ) . 'contact-us/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a quote', 'whitelabel' ); ?></a>
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
		<style id="wl-preview-css">
			<?php echo Frontend::get_css( '.wl-login-preview', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is built and escaped by Frontend::get_css(). ?>
		</style>
		<div class="wl-login-preview" id="wl-login-preview">
			<div id="login">
				<h1 role="presentation" class="wp-login-logo"><a href="#"><?php echo esc_html( $logo_url ? '' : get_bloginfo( 'name' ) ); ?></a></h1>

				<?php if ( '' !== $message ) : ?>
					<p class="wl-login-message"><?php echo Settings::format_rich_text( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is sanitized by Settings::format_rich_text(). ?></p>
				<?php endif; ?>

				<form name="loginform" id="loginform" class="wl-preview-form">
					<p>
						<label for="wl-preview-user"><?php esc_html_e( 'Username or Email Address', 'whitelabel' ); ?></label>
						<input type="text" id="wl-preview-user" class="input" value="" size="20" autocomplete="off" />
					</p>
					<p>
						<label for="wl-preview-pass"><?php esc_html_e( 'Password', 'whitelabel' ); ?></label>
						<input type="password" id="wl-preview-pass" class="input" value="" size="20" autocomplete="off" />
					</p>
					<p class="forgetmenot">
						<label><input type="checkbox" id="wl-preview-remember" /> <?php esc_html_e( 'Remember Me', 'whitelabel' ); ?></label>
					</p>
					<p class="submit">
						<input type="submit" class="wp-core-ui button-primary" value="<?php echo esc_attr__( 'Log In', 'whitelabel' ); ?>" />
					</p>
				</form>

				<p id="nav">
					<?php if ( ! $options['hide_register'] ) : ?>
						<a class="wp-login-register" href="#"><?php esc_html_e( 'Register', 'whitelabel' ); ?></a>
						<?php if ( ! $options['hide_lost_password'] ) : ?><span class="wl-nav-sep"> | </span><?php endif; ?>
					<?php endif; ?>
					<?php if ( ! $options['hide_lost_password'] ) : ?>
						<a class="wp-login-lost-password" href="#"><?php esc_html_e( 'Lost your password?', 'whitelabel' ); ?></a>
					<?php endif; ?>
				</p>

				<?php if ( ! $options['hide_backtoblog'] ) : ?>
					<p id="backtoblog"><a href="#">&larr; <?php esc_html_e( 'Go to site', 'whitelabel' ); ?></a></p>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $footer ) : ?>
				<p class="wl-login-footer"><?php echo Settings::format_rich_text( $footer ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is sanitized by Settings::format_rich_text(). ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Create a published page containing the login shortcode.
	 */
	public function handle_create_page() {
		check_ajax_referer( 'wl_login_create_page', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'publish_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to create pages.', 'whitelabel' ) ), 403 );
		}

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( '' === $title ) {
			$title = __( 'Login', 'whitelabel' );
		}

		$page_id = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $title,
				'post_content'   => '[whitelabel_login]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			),
			true
		);

		if ( is_wp_error( $page_id ) || ! $page_id ) {
			wp_send_json_error( array( 'message' => __( 'Could not create the page. Please try again.', 'whitelabel' ) ), 500 );
		}

		$options                     = Settings::get_options();
		$options['custom_page_id']   = absint( $page_id );
		$options['custom_page_enabled'] = 1;
		update_option( WL_LOGIN_OPTION, $options, false );

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
			wp_die( esc_html__( 'You are not allowed to change these settings.', 'whitelabel' ) );
		}

		check_admin_referer( 'wl_login_reset' );
		Settings::reset();

		// Show the confirmation once, without leaving a flag in the URL.
		set_transient( 'wl_login_reset_notice_' . get_current_user_id(), 1, MINUTE_IN_SECONDS );

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

		$key = 'wl_login_reset_notice_' . get_current_user_id();
		if ( ! get_transient( $key ) ) {
			return;
		}
		delete_transient( $key );

		echo '<div class="notice notice-success is-dismissible wl-reset-notice"><p>'
			. esc_html__( 'WhiteLabel WP Login Customizer settings have been reset to the WordPress defaults.', 'whitelabel' )
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
			. esc_html__( 'Settings', 'whitelabel' ) . '</a>';
		$login    = '<a href="' . esc_url( wp_login_url() ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Login page', 'whitelabel' ) . '</a>';
		$site     = '<a href="' . esc_url( WL_LOGIN_SITE_URL ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'White Label WP', 'whitelabel' ) . '</a>';
		$support  = '<a href="' . esc_url( trailingslashit( WL_LOGIN_SITE_URL ) . 'contact-us/' ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Support', 'whitelabel' ) . '</a>';

		return array_merge( array( $settings, $login, $site, $support ), $links );
	}
}
