<?php

/**
 * Enqueue assets.
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
            $js_dependencies,
            $version,
            true
        );
    }
}
