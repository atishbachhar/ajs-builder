<?php
/**
 * Plugin Name:       AJS Builder
 * Plugin URI:        https://example.com/ajs-builder
 * Description:       A lightweight, dependency-free Elementor-style visual page builder. Drag & drop sections, columns and modules (heading, text, image, logo, navigation, search, cart, my account, compare, button, social icons, image slider, icon box, icon list, gallery, tabs, accordion, counter, Google Map, Posts, Loop Carousel, Author Box, WooCommerce product tabs, product slider and more) and design pages visually with per-device visibility controls. Includes global colors, typography, buttons and design presets, six responsive starter header/footer templates plus JSON template export and import.
 * Version:           1.11.6
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            AJS
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ajs-builder
 *
 * @package Ajs_Builder
 */

defined( 'ABSPATH' ) || exit;

define( 'AJS_BUILDER_VERSION', '1.11.6' );
define( 'AJS_BUILDER_FILE', __FILE__ );
define( 'AJS_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'AJS_BUILDER_URL', plugin_dir_url( __FILE__ ) );
define( 'AJS_BUILDER_META', '_ajs_builder_data' );
define( 'AJS_BUILDER_ENABLED_META', '_ajs_builder_enabled' );

require_once AJS_BUILDER_DIR . 'includes/helpers.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-settings.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-styles.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-icons.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-widgets.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-render.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-document.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-rest.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-templates.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-demo-templates.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-frontend.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-admin.php';
require_once AJS_BUILDER_DIR . 'includes/class-ajs-builder-plugin.php';

/**
 * Main plugin bootstrap.
 *
 * @return Ajs_Builder_Plugin
 */
function ajs_builder() {
	return Ajs_Builder_Plugin::instance();
}

ajs_builder();

register_activation_hook( __FILE__, array( 'Ajs_Builder_Plugin', 'on_activate' ) );

/**
 * Add plugin action links.
 *
 * @param array $links Existing links.
 * @return array
 */
function ajs_builder_plugin_action_links( $links ) {
	$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=ajs-builder-settings' ) ) . '">' . esc_html__( 'Settings', 'ajs-builder' ) . '</a>';
	$links[] = '<a href="' . esc_url( AJS_BUILDER_URL . 'docs/index.html' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Documentation', 'ajs-builder' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ajs_builder_plugin_action_links' );

