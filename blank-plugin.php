<?php
/**
 * Plugin Name:       Blank Plugin
 * Description:       A WordPress plugin with a custom post type, meta fields, shortcode, and REST API settings.
 * Author:            Rabindra Tharu
 * Plugin URI:        https://github.com/rabindratharu
 * Author URI:        https://github.com/rabindratharu
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blank-plugin
 * Domain Path:       /languages
 * Version:           1.1.0-beta.1
 * Requires PHP:      8.0
 * Requires at least: 6.8
 * Tested up to:      6.9
 *
 * @package BlankPlugin
 */

declare (strict_types = 1);

namespace BlankPlugin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**
 * Define the plugin constants.
 */
function constants(): void {
	/**
	 * Version of the plugin.
	 */
	define( 'BLANK_PLUGIN_VERSION', '1.1.0-beta.1' );

	/**
	 * Root path to the plugin directory.
	 */
	define( 'BLANK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

	/**
	 * Root URL to the plugin directory.
	 */
	define( 'BLANK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	/**
	 * The plugin basename.
	 */
	define( 'BLANK_PLUGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

constants();

// If autoloader failed, we cannot proceed.
require_once __DIR__ . '/inc/Autoloader.php';
if ( ! \BlankPlugin\Autoloader::autoload() ) {
	return;
}

// Load the plugin.
if ( class_exists( '\BlankPlugin\Main' ) ) {
	\BlankPlugin\Main::instance();
}
