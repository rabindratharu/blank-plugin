<?php

/**
 * Enqueue assets.
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Assets
 */
class Assets
{
    use Singleton;

    /**
     * Construct method.
     *
     * Initializes the class and sets up necessary hooks.
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * Set up hooks for the class.
     *
     * @return void
     */
    protected function setup_hooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_styles']);
        add_action('enqueue_block_editor_assets', [$this, 'register_block_editor_assets']);
        add_action('customize_controls_enqueue_scripts', [$this, 'register_customizer_assets']);
    }

    /**
     * Register and enqueue styles for the theme.
     *
     * @return void
     */
    public function register_styles()
    {
        $suffix = is_rtl() ? '-rtl' : '';
        // Register styles.
        wp_register_style('blank-plugin-main', BLANK_PLUGIN_BUILD_PATH_URL . "/main/index{$suffix}.css", [], filemtime(BLANK_PLUGIN_BUILD_PATH . "/main/index{$suffix}.css"), 'all');
        // Enqueue Styles.
        wp_enqueue_style('blank-plugin-main');
    }

    /**
     * Registers and enqueues editor styles.
     *
     * @return void
     */
    public function register_block_editor_assets()
    {
        $asset_config_file = sprintf('%s/editor/index.asset.php', BLANK_PLUGIN_BUILD_PATH);

        if (! file_exists($asset_config_file)) {
            return;
        }

        $editor_asset   = include_once $asset_config_file;
        $js_dependencies = (! empty($editor_asset['dependencies'])) ? $editor_asset['dependencies'] : [];
        $version         = (! empty($editor_asset['version'])) ? $editor_asset['version'] : filemtime($asset_config_file);

        // Theme Gutenberg blocks editor JS.
        wp_enqueue_script(
            'blank-plugin-editor',
            BLANK_PLUGIN_BUILD_PATH_URL . '/editor/index.js',
            array_unique(array_merge($js_dependencies, [])),
            $version,
            true
        );
    }

    /**
     * Registers and enqueues customizer assets.
     *
     * @return void
     */
    public function register_customizer_assets()
    {
        $asset_config_file = sprintf('%s/customizer/index.asset.php', BLANK_PLUGIN_BUILD_PATH);

        if (! file_exists($asset_config_file)) {
            return;
        }

        $editor_asset   = include_once $asset_config_file;
        $js_dependencies = (! empty($editor_asset['dependencies'])) ? $editor_asset['dependencies'] : [];
        $version         = (! empty($editor_asset['version'])) ? $editor_asset['version'] : filemtime($asset_config_file);

        // Customizer JS.
        wp_enqueue_script(
            'blank-plugin-customizer',
            BLANK_PLUGIN_BUILD_PATH_URL . '/customizer/index.js',
            array_unique(array_merge($js_dependencies, [
                'wp-element',
                'wp-components',
                'wp-i18n',
                'customize-controls'
            ])),
            $version,
            true
        );
    }
}
