<?php
/**
 * Plugin Main Class.
 *
 * @package blank-plugin
 * @since   1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Main Plugin Class.
 *
 * Handles plugin initialization, compatibility checks, activation/deactivation,
 * and version upgrades using the Singleton pattern.
 *
 * @since 1.0.0
 */
final class Plugin {

	use Singleton;

	/**
	 * Minimum required PHP version.
	 *
	 * @var string
	 */
	private $min_php_version = '7.4';

	/**
	 * Minimum required WordPress version.
	 *
	 * @var string
	 */
	private $min_wp_version = '6.1';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		if ( ! $this->can_boot() ) {
			return;
		}

		$this->init_components();
		$this->handle_version_upgrade();
	}

	/**
	 * Initialize all plugin components.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_components() {
		Utils::get_instance();
		Customizer::get_instance();
		Register_Block::get_instance();
		Register_Post_Types::get_instance();
		Register_Taxonomies::get_instance();
		Meta_Boxes::get_instance();
		Shortcode::get_instance();
		Rest_Endpoint::get_instance();
		if ( is_admin() ) {
			Dashboard::get_instance();
		}
		Assets::get_instance();
	}

	/**
	 * Check if the environment meets plugin requirements.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function can_boot() {
		if ( ! $this->is_php_compatible() ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		if ( ! $this->is_wp_compatible() ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check PHP version compatibility.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_php_compatible() {
		return version_compare( PHP_VERSION, $this->min_php_version, '>=' );
	}

	/**
	 * Check WordPress version compatibility.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_wp_compatible() {
		global $wp_version;
		return version_compare( $wp_version, $this->min_wp_version, '>=' );
	}

	/**
	 * Display PHP version incompatibility notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function php_version_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
					printf(
						/* translators: 1: Minimum required PHP version, 2: Current PHP version */
						esc_html__( 'Blank Plugin requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade your PHP version.', 'blank-plugin' ),
						'<strong>' . esc_html( $this->min_php_version ) . '</strong>',
						'<strong>' . esc_html( PHP_VERSION ) . '</strong>'
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display WordPress version incompatibility notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function wp_version_notice() {
		global $wp_version;

		?>
		<div class="notice notice-error">
			<p>
				<?php
					printf(
						/* translators: 1: Minimum required WordPress version, 2: Current WordPress version */
						esc_html__(
							'Blank Plugin requires WordPress %1$s or higher. You are running version %2$s. Please upgrade WordPress.',
							'blank-plugin'
						),
						'<strong>' . esc_html( $this->min_wp_version ) . '</strong>',
						'<strong>' . esc_html( $wp_version ) . '</strong>'
					);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Runs on plugin activation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		$this->register_post_types_and_taxonomies();
		flush_rewrite_rules();

		update_option( 'blank_plugin_version', BLANK_PLUGIN_VERSION );
		set_transient( 'blank_plugin_activation_redirect', true, 30 );
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		flush_rewrite_rules();
		delete_transient( 'blank_plugin_activation_redirect' );
	}

	/**
	 * Handle plugin version upgrades.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function handle_version_upgrade() {
		$current_version = get_option( 'blank_plugin_version', '0.0.0' );

		if ( version_compare( $current_version, BLANK_PLUGIN_VERSION, '>=' ) ) {
			return;
		}

		$this->register_post_types_and_taxonomies();
		flush_rewrite_rules();
		update_option( 'blank_plugin_version', BLANK_PLUGIN_VERSION );
	}

	/**
	 * Register custom post types and taxonomies.
	 *
	 * Used during activation and upgrades to ensure rewrite rules are current.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_post_types_and_taxonomies() {
		Register_Post_Types::get_instance();
		Register_Taxonomies::get_instance();
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Cloning instances of this class is not allowed.', 'blank-plugin' ),
			esc_html( BLANK_PLUGIN_VERSION ),
		);
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Unserializing instances of this class is not allowed.', 'blank-plugin' ),
			esc_html( BLANK_PLUGIN_VERSION ),
		);
	}
}
