<?php
/**
 * Plugin Name:       Frontman
 * Plugin URI:        https://frontman.sh
 * Description:       AI-powered frontend editing for WordPress. Lets an AI agent see your site and edit posts, blocks, menus, templates, and options through a conversational UI.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Frontman AI
 * Author URI:        https://frontman.sh
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       frontman
 * Domain Path:       /languages
 */

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FRONTMAN_VERSION', '0.1.0' );
define( 'FRONTMAN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRONTMAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FRONTMAN_PLUGIN_FILE', __FILE__ );

// Autoload plugin classes.
require_once FRONTMAN_PLUGIN_DIR . 'includes/class-frontman-tools.php';
require_once FRONTMAN_PLUGIN_DIR . 'includes/class-frontman-proxy.php';
require_once FRONTMAN_PLUGIN_DIR . 'includes/class-frontman-router.php';
require_once FRONTMAN_PLUGIN_DIR . 'includes/class-frontman-ui.php';
require_once FRONTMAN_PLUGIN_DIR . 'includes/class-frontman-settings.php';

// Load tool implementations.
require_once FRONTMAN_PLUGIN_DIR . 'tools/class-tool-posts.php';
require_once FRONTMAN_PLUGIN_DIR . 'tools/class-tool-blocks.php';
require_once FRONTMAN_PLUGIN_DIR . 'tools/class-tool-menus.php';
require_once FRONTMAN_PLUGIN_DIR . 'tools/class-tool-options.php';
require_once FRONTMAN_PLUGIN_DIR . 'tools/class-tool-templates.php';
require_once FRONTMAN_PLUGIN_DIR . 'tools/class-tool-widgets.php';

/**
 * Main plugin bootstrap.
 */
function frontman_init(): void {
	// Register settings page.
	$settings = new Frontman_Settings();
	$settings->register();

	// Register all WP tools.
	$tools = Frontman_Tools::instance();
	( new Frontman_Tool_Posts() )->register( $tools );
	( new Frontman_Tool_Blocks() )->register( $tools );
	( new Frontman_Tool_Menus() )->register( $tools );
	( new Frontman_Tool_Options() )->register( $tools );
	( new Frontman_Tool_Templates() )->register( $tools );
	( new Frontman_Tool_Widgets() )->register( $tools );

	// Register the REST API router.
	$proxy  = new Frontman_Proxy( $settings );
	$router = new Frontman_Router( $tools, $proxy, $settings );
	$router->register();

	// Register the UI page.
	$ui = new Frontman_UI( $settings );
	$ui->register();
}
add_action( 'init', 'frontman_init' );
