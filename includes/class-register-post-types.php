<?php

/**
 * Register Custom Post Types
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Blank_Plugin\Utils\Singleton;

/**
 * Register Post Types class.
 *
 * Handles registration of custom post types for the current theme/plugin.
 *
 * @since 1.0.0
 */
class Register_Post_Types
{
    use Singleton;

    /**
     * Private constructor to prevent direct object creation.
     *
     * Sets up hooks for post type registration.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * Set up action hooks.
     *
     * @since 1.0.0
     * @return void
     */
    protected function setup_hooks()
    {
        add_action('init', [$this, 'register_post_types'], 5);
        // Flush rewrite rules on activation only
        register_activation_hook(__FILE__, [$this, 'flush_rewrite_rules']);
    }

    /**
     * Register custom post types.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_post_types()
    {
        if (!is_blog_installed()) {
            return;
        }

        $custom_post_types = self::get_post_type_args();

        foreach ($custom_post_types as $post_type => $args) {
            if (post_type_exists($post_type)) {
                continue;
            }

            $labels = $this->get_post_type_labels(
                $args['singular_name'],
                $args['general_name'],
                $args['menu_name']
            );

            $post_type_args = [
                'label'               => esc_html__($args['singular_name'], 'blank-plugin'),
                'description'         => esc_html__($args['singular_name'] . ' Post Type', 'blank-plugin'),
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
                'rewrite'             => [
                    'slug'       => 'reviews', // Changed to a simpler slug
                    'with_front' => false,
                    'pages'      => true,
                    'feeds'      => true,
                ],
            ];

            $result = register_post_type($post_type, $post_type_args);
            if (is_wp_error($result) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('Blank Plugin: Failed to register post type %s: %s', $post_type, $result->get_error_message()));
            }
        }
    }

    /**
     * Get labels for a custom post type.
     *
     * @since 1.0.0
     * @param string $singular_name Singular name of the post type.
     * @param string $general_name General name of the post type.
     * @param string $menu_name Menu name for the post type.
     * @return array Array of labels for the post type.
     */
    private function get_post_type_labels($singular_name, $general_name, $menu_name)
    {
        return [
            'name'                  => esc_html__($general_name, 'blank-plugin'),
            'singular_name'         => esc_html__($singular_name, 'blank-plugin'),
            'menu_name'             => esc_html__($menu_name, 'blank-plugin'),
            'name_admin_bar'        => esc_html__($singular_name, 'blank-plugin'),
            'archives'              => esc_html__($singular_name . ' Archives', 'blank-plugin'),
            'attributes'            => esc_html__($singular_name . ' Attributes', 'blank-plugin'),
            'parent_item_colon'     => esc_html__('Parent ' . $singular_name . ':', 'blank-plugin'),
            'all_items'             => esc_html__($general_name, 'blank-plugin'),
            'add_new_item'          => esc_html__('Add ' . $singular_name, 'blank-plugin'),
            'add_new'               => esc_html__('Add', 'blank-plugin'),
            'new_item'              => esc_html__('New ' . $singular_name, 'blank-plugin'),
            'edit_item'             => esc_html__('Edit ' . $singular_name, 'blank-plugin'),
            'update_item'           => esc_html__('Update ' . $singular_name, 'blank-plugin'),
            'view_item'             => esc_html__('View ' . $singular_name, 'blank-plugin'),
            'view_items'            => esc_html__('View ' . $general_name, 'blank-plugin'),
            'search_items'          => esc_html__('Search ' . $singular_name, 'blank-plugin'),
            'not_found'             => esc_html__('Not found', 'blank-plugin'),
            'not_found_in_trash'    => esc_html__('Not found in Trash', 'blank-plugin'),
            'featured_image'        => esc_html__('Featured Image', 'blank-plugin'),
            'set_featured_image'    => esc_html__('Set featured image', 'blank-plugin'),
            'remove_featured_image' => esc_html__('Remove featured image', 'blank-plugin'),
            'use_featured_image'    => esc_html__('Use as featured image', 'blank-plugin'),
            'insert_into_item'      => esc_html__('Insert into ' . $singular_name, 'blank-plugin'),
            'uploaded_to_this_item' => esc_html__('Uploaded to this ' . $singular_name, 'blank-plugin'),
            'items_list'            => esc_html__($general_name . ' list', 'blank-plugin'),
            'items_list_navigation' => esc_html__($general_name . ' list navigation', 'blank-plugin'),
            'filter_items_list'     => esc_html__('Filter ' . $general_name . ' list', 'blank-plugin'),
        ];
    }

    /**
     * Flush rewrite rules.
     *
     * Called on plugin/theme activation to update permalinks.
     *
     * @since 1.0.0
     * @return void
     */
    public static function flush_rewrite_rules()
    {
        flush_rewrite_rules();
    }

    /**
     * Get custom post type arguments.
     *
     * @since 1.0.0
     * @return array Array of post type arguments.
     */
    public static function get_post_type_args()
    {
        return [
            'review' => [
                'menu_name'           => esc_html__('Reviews', 'blank-plugin'),
                'singular_name'       => esc_html__('Review', 'blank-plugin'),
                'general_name'        => esc_html__('Reviews', 'blank-plugin'),
                'dashicon'            => 'dashicons-star-filled',
                'has_archive'         => true,
                'exclude_from_search' => false,
                'show_in_nav_menus'   => false,
                'show_in_menu'        => true,
                'capability_type'     => 'post',
                'supports'            => ['title', 'editor', 'revisions', 'thumbnail', 'custom-fields'],
            ],
        ];
    }
}
