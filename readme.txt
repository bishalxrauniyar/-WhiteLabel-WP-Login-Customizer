=== WhiteLabel Login Customizer ===
Contributors: whitelabelwp
Author: White Label WP
Author URI: https://whitelabelwp.io/
Plugin URI: https://whitelabelwp.io/
Tags: login, branding, white label, logo, customization
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize the WordPress login screen with your own logo, colors, background, fonts and CSS.

== Description ==

WhiteLabel Login Customizer lets administrators replace the default WordPress login screen with branded styling — without touching theme files or writing code.

Features:

* Upload a custom login logo from the media library (or use any http(s) URL) and set its size, link and alt text
* Customize page background color and background image (repeat, position, size, attachment)
* Set colors for text, links, form, inputs and buttons (including hover, focus, placeholder and checkbox states)
* Apply a ready-made palette in one click: White Label WP, Midnight, Warm Sand, Forest or Plum
* Adjust form width, padding, border, corner radius, shadow, input sizes and typography
* Add a custom message above the form, footer text and a custom browser title
* Hide the "Go to site", "Register" and "Lost your password?" links
* Add your own additional CSS for advanced tweaks
* Live preview with desktop/mobile toggle in the settings page
* Show the complete standard WordPress login screen on your own page with the `[wlwp_login]` shortcode (aliases: `[whitelabel_login]`, `[wl_login]`) and redirect wp-login.php there
* Simple on/off switch and one-click reset to defaults

Settings live at **Settings → WhiteLabel Login Customizer**. Styling assets are loaded only on the login screen and the plugin's settings page.

Developed by [White Label WP](https://whitelabelwp.io/) — a white-label WordPress development agency for digital agencies. Need custom WordPress work? [Get a quote](https://whitelabelwp.io/contact-us/).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from Plugins → Add New.
2. Activate the plugin through the Plugins screen.
3. Go to Settings → WhiteLabel Login Customizer and configure your branding.
4. Click Save Changes, then open `wp-login.php` (the "View live login page" button) to see it.

== Frequently Asked Questions ==

= Will this change my theme? =

No. The plugin only adds CSS and filters to the WordPress login screen.

= Can I turn it off without losing my settings? =

Yes. Uncheck "Enable customization" on the settings page. Your settings are kept.

= What image should I use for the logo? =

A PNG or SVG with a transparent background works best. Around 300×90 px is a good starting point; set the exact width and height in the settings.

= Does it work on multisite? =

Yes. Each site stores and manages its own settings via the WordPress Options API.

= What happens when the plugin is deleted? =

The `wlwp_login_options` option and the legacy `wl_login_options` option are removed. No other data is touched.

== Changelog ==

= 1.0.1 =
* Renamed the plugin to avoid the WordPress.org restricted "WP" term.
* Namespace all CSS classes, JS functions and handlers, AJAX actions, hooks, constants and the options key with `wlwp` to prevent conflicts. Existing settings are migrated automatically; old shortcodes still work.
* Custom login page now serves the complete default WordPress login screen, including role-based redirects and logout handling.

= 1.0.0 =
* Initial release.
