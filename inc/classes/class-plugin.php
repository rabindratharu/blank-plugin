<?php
/**
 * Plugin.
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Plugin Main Class
 *
 * @since 1.0.0
 */
final class Plugin {

	use Singleton;

	/**
	 * Minimum supported php version.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	public $php_version = '7.4';

	/**
	 * Minimum WordPress version.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	public $wp_version = '6.1';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		if ( ! $this->can_boot() ) {
			return;
		}

		// Load class.
		Assets::get_instance();
		Utils::get_instance();
		Register_Post_Types::get_instance();
		Meta_Boxes::get_instance();
		Reviews::get_instance();
		Rest_Endpoint::get_instance();
		Api_Settings::get_instance();
		Customizer::get_instance();

		if ( is_admin() ) {
			Dashboard::get_instance();
		}
	}

	/**
	 * Main condition that checks if plugin parts should continue loading.
	 *
	 * @return bool
	 */
	private function can_boot() {
		/**
		 * Checks
		 *  - PHP version
		 *  - WP Version
		 * If not then return.
		 */
		global $wp_version;

		return (
			version_compare( PHP_VERSION, $this->php_version, '>=' ) &&
			version_compare( $wp_version, $this->wp_version, '>=' )
		);
	}

	/**
	 * Method to execute tasks on plugin activation.
	 *
	 * This function is triggered when the plugin is activated.
	 * It can be used to set up default options, create necessary database tables,
	 * or perform any other initial setup required by the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		$current_version = get_option( 'blank_plugin_version', '0.0.0' );
		$new_version     = BLANK_PLUGIN_VERSION; // Replace with your plugin version.

		if ( version_compare( $current_version, $new_version, '<' ) ) {
			// Flush rewrite rules on update.
			flush_rewrite_rules();
			update_option( 'blank_plugin_version', $new_version );
		}
	}

	/**
	 * Method to execute tasks on plugin deactivation.
	 *
	 * This function is triggered when the plugin is deactivated.
	 * It can be used to clean up any resources or data associated with the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Prevent cloning of the plugin instance.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Cloning is forbidden.', 'blank-plugin' ),
			esc_html( BLANK_PLUGIN_VERSION )
		);
	}

	/**
	 * Prevent unserializing of the plugin instance.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Unserializing instances of this class is forbidden.', 'blank-plugin' ),
			esc_html( BLANK_PLUGIN_VERSION )
		);
	}
}
