<?php

/**
 * Plugin Name:       Blank Plugin
 * Description:       A WordPress plugin with a custom post type, meta fields, shortcode, and REST API settings.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Rabindra Tharu
 * Author URI:        https://github.com/rabindratharu
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blank-plugin
 *
 * @package blank-plugin
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define('BLANK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('BLANK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BLANK_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('BLANK_PLUGIN_BUILD_PATH', BLANK_PLUGIN_PATH . 'assets/build');
define('BLANK_PLUGIN_BUILD_PATH_URL', BLANK_PLUGIN_URL . 'assets/build');
define('BLANK_PLUGIN_NAME', 'blank-plugin');
define('BLANK_PLUGIN_OPTION_NAME', 'blank-plugin');

/**
 * Bootstrap the plugin.
 */
require_once BLANK_PLUGIN_PATH . 'includes/utils/autoloader.php';

use Blank_Plugin\Plugin;

// Check if the class exists and WordPress environment is valid
if (class_exists('Blank_Plugin\Plugin')) {
    // Instantiate the plugin
    $the_plugin = Plugin::get_instance();

    // Register activation and deactivation hooks
    register_activation_hook(__FILE__, [$the_plugin, 'activate']);
    register_deactivation_hook(__FILE__, [$the_plugin, 'deactivate']);
}
