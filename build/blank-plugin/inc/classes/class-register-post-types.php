<?php
/**
 * Register Custom Post Types
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Register Post Types class.
 *
 * Handles registration of custom post types for the current theme/plugin.
 *
 * @since 1.0.0
 */
class Register_Post_Types {

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
	 * Registers the action hooks for post type registration and
	 * flushing rewrite rules on plugin activation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		// Flush rewrite rules on activation only.
		register_activation_hook( __FILE__, array( $this, 'flush_rewrite_rules' ) );
	}

	/**
	 * Register custom post types.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_post_types() {
		if ( ! is_blog_installed() ) {
			return;
		}

		$custom_post_types = self::get_post_type_args();

		foreach ( $custom_post_types as $post_type => $args ) {
			if ( post_type_exists( $post_type ) ) {
				continue;
			}

			$labels = $this->get_post_type_labels( $post_type );

			$post_type_args = array(
				'label'               => $args['label'],
				'description'         => $args['description'],
				'labels'              => $labels,
				'supports'            => $args['supports'],
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => $args['show_in_menu'],
				'show_in_rest'        => true,
				'menu_icon'           => $args['dashicon'],
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => $args['show_in_nav_menus'],
				'can_export'          => true,
				'has_archive'         => $args['has_archive'],
				'exclude_from_search' => $args['exclude_from_search'],
				'publicly_queryable'  => true,
				'capability_type'     => $args['capability_type'],
				'rewrite'             => array(
					'slug'       => $args['rewrite_slug'],
					'with_front' => false,
					'pages'      => true,
					'feeds'      => true,
				),
			);

			$result = register_post_type( $post_type, $post_type_args );
			if ( is_wp_error( $result ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf( 'Blank Plugin: Failed to register post type %s: %s', $post_type, $result->get_error_message() ) );
			}
		}
	}

	/**
	 * Get labels for a custom post type.
	 *
	 * @since 1.0.0
	 * @param string $post_type The post type slug.
	 * @return array Array of labels for the post type.
	 */
	private function get_post_type_labels( $post_type ) {
		$labels = array();

		switch ( $post_type ) {
			case 'review':
				$labels = array(
					'name'                  => _x( 'Reviews', 'Post Type General Name', 'blank-plugin' ),
					'singular_name'         => _x( 'Review', 'Post Type Singular Name', 'blank-plugin' ),
					'menu_name'             => __( 'Reviews', 'blank-plugin' ),
					'name_admin_bar'        => __( 'Review', 'blank-plugin' ),
					'archives'              => __( 'Review Archives', 'blank-plugin' ),
					'attributes'            => __( 'Review Attributes', 'blank-plugin' ),
					'parent_item_colon'     => __( 'Parent Review:', 'blank-plugin' ),
					'all_items'             => __( 'All Reviews', 'blank-plugin' ),
					'add_new_item'          => __( 'Add New Review', 'blank-plugin' ),
					'add_new'               => __( 'Add New', 'blank-plugin' ),
					'new_item'              => __( 'New Review', 'blank-plugin' ),
					'edit_item'             => __( 'Edit Review', 'blank-plugin' ),
					'update_item'           => __( 'Update Review', 'blank-plugin' ),
					'view_item'             => __( 'View Review', 'blank-plugin' ),
					'view_items'            => __( 'View Reviews', 'blank-plugin' ),
					'search_items'          => __( 'Search Review', 'blank-plugin' ),
					'not_found'             => __( 'Not found', 'blank-plugin' ),
					'not_found_in_trash'    => __( 'Not found in Trash', 'blank-plugin' ),
					'featured_image'        => __( 'Featured Image', 'blank-plugin' ),
					'set_featured_image'    => __( 'Set featured image', 'blank-plugin' ),
					'remove_featured_image' => __( 'Remove featured image', 'blank-plugin' ),
					'use_featured_image'    => __( 'Use as featured image', 'blank-plugin' ),
					'insert_into_item'      => __( 'Insert into review', 'blank-plugin' ),
					'uploaded_to_this_item' => __( 'Uploaded to this review', 'blank-plugin' ),
					'items_list'            => __( 'Reviews list', 'blank-plugin' ),
					'items_list_navigation' => __( 'Reviews list navigation', 'blank-plugin' ),
					'filter_items_list'     => __( 'Filter reviews list', 'blank-plugin' ),
				);
				break;
		}

		return $labels;
	}

	/**
	 * Flush rewrite rules.
	 *
	 * Called on plugin/theme activation to update permalinks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}

	/**
	 * Get custom post type arguments.
	 *
	 * @since 1.0.0
	 * @return array Array of post type arguments.
	 */
	public static function get_post_type_args() {
		return array(
			'review' => array(
				'label'               => __( 'Review', 'blank-plugin' ),
				'description'         => __( 'Customer reviews and testimonials', 'blank-plugin' ),
				'dashicon'            => 'dashicons-star-filled',
				'has_archive'         => true,
				'exclude_from_search' => false,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => true,
				'capability_type'     => 'post',
				'rewrite_slug'        => 'reviews',
				'supports'            => array( 'title', 'editor', 'revisions', 'thumbnail', 'custom-fields' ),
			),
		);
	}
}
