<?php
/**
 * Register Block
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Blank_Plugin\Inc\Traits\Singleton;
use Blank_Plugin\Inc\Utils;

/**
 * Register block class.
 *
 * Handles registration of custom post types for the current theme/plugin.
 *
 * @since 1.0.0
 */
class Register_Block {

	use Singleton;

	/**
	 * Private constructor to prevent direct object creation.
	 *
	 * Sets up hooks for post type registration.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up action hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'init', array( $this, 'register_block_types' ) );
		add_action( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize_block_scripts' ) );
	}

	/**
	 * Register all block types from assets/build/block/directory/block.json.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_block_types() {
		$saved_options = Utils::get_options();

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Ensure the constant is defined.
		if ( ! defined( 'BLANK_PLUGIN_PATH' ) ) {
			return;
		}

		$block_path = BLANK_PLUGIN_PATH . 'assets/build/blocks/';
		if ( ! is_dir( $block_path ) ) {
			return;
		}

		$block_json_files = glob( $block_path . '*/block.json' );
		if ( empty( $block_json_files ) ) {
			return;
		}

		foreach ( $block_json_files as $filename ) {
			if ( ! is_readable( $filename ) ) {
				continue;
			}
			$block_folder = dirname( $filename );

			$block_dir  = basename( $block_folder ); // e.g. "quiz".
			$block_slug = str_replace( '-', '_', $block_dir ); // "quiz".

			try {
				if ( is_wp_error( $saved_options ) || empty( $saved_options[ $block_slug ] ) ) {
					return;
				}

				$result = register_block_type( $block_folder );
			} catch ( \Exception $e ) {
				// Log the error but don't break the execution for other blocks.
				error_log( sprintf( 'Failed to register block %s: %s', $block_slug, $e->getMessage() ) );
			}
		}
	}

	/**
	 * Register a custom block category.
	 *
	 * @since 1.0.0
	 * @param array                    $categories Existing block categories.
	 * @param \WP_Block_Editor_Context $context Block editor context.
	 * @return array Modified block categories.
	 */
	public function register_block_category( $categories, $context ) {
		$new_category = array(
			'slug'  => 'blank-plugin',
			'title' => esc_html__( 'Blank Plugin', 'blank-plugin' ),
			'icon'  => 'book-alt',
		);

		// Check if the category already exists to avoid duplicates.
		if ( ! in_array( $new_category['slug'], array_column( $categories, 'slug' ), true ) ) {
			$categories = array_merge( array( $new_category ), $categories );
		}

		return $categories;
	}

	/**
	 * Localize scripts for the quiz block.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function localize_block_scripts() {
		// Localize the view script for the quiz block.
		$handle = 'blank-plugin-quiz-block-view-script';
		wp_localize_script(
			$handle,
			'quizBlockData',
			array(
				'restUrl' => esc_url_raw( rest_url( 'quiz/v1/submit' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
