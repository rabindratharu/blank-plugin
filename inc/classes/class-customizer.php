<?php

/**
 * Register Meta Boxes
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;
use Blank_Plugin\Inc\Utils;
use WP_Customize_Manager;
use Blank_Plugin\Inc\Controls\Text as BlankPluginTextControl;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Register meta boxes class.
 *
 * Handles registration of custom meta boxes for product reviews.
 *
 * @since 1.0.0
 */
class Customizer
{
    use Singleton;

    /**
     * WordPress Customizer Manager instance.
     *
     * @var WP_Customize_Manager
     */
    private $wp_customize;

    /**
     * Private constructor to prevent direct object creation.
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
        add_action('customize_register', [$this, 'customize_register_callback']);
    }

    public function customize_register_callback(WP_Customize_Manager $wp_customize)
    {

        $controls_dir = trailingslashit(BLANK_PLUGIN_PATH) . 'inc/controls/';

        if (!is_dir($controls_dir) || !is_readable($controls_dir)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Blank Plugin: Controls directory not found or not readable at ' . $controls_dir);
            }
            return;
        }

        $control_files = glob($controls_dir . 'class-*.php');
        if (empty($control_files)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Blank Plugin: No control files found in ' . $controls_dir);
            }
            return;
        }

        foreach ($control_files as $file) {
            try {

                // Get class name from file
                $base = sanitize_file_name(basename($file, '.php'));
                $class_slug = str_replace('class-', '', $base);
                $class_name = str_replace('-', '_', $class_slug);
                $full_class = __NAMESPACE__ . '\\Controls\\' . str_replace('-', '_', ucwords($class_slug, '-'));

                // Include and validate control file
                if (is_readable($file)) {
                    require_once $file;
                } else {
                    throw new \Exception('Unable to read control file: ' . $file);
                }
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Blank Plugin: Failed to register control ' . ($full_class ?? 'unknown') . ': ' . $e->getMessage());
                }
            }
        }

        // Add a section
        $wp_customize->add_section('blank_plugin_controls_section', array(
            'title'         => esc_html__('Blank Plugin', 'blank-plugin'),
            'priority'      => 120,
        ));

        // Add setting
        $wp_customize->add_setting('blank_plugin_text_control', array(
            'default'           => esc_html__('Default', 'blank-plugin'),
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        // Add control
        $wp_customize->add_control(new BlankPluginTextControl(
            $wp_customize,
            'blank_plugin_text_control',
            array(
                'label'         => esc_html__('Text Control', 'blank-plugin'),
                'description'   => esc_html__('Description for this control.', 'blank-plugin'),
                'section'       => 'blank_plugin_controls_section',
            )
        ));
    }
}
