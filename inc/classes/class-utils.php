<?php

/**
 * Plugin Utils for Blank Plugin.
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
 * Utils class.
 *
 * Handles utility functions for the Blank Plugin plugin.
 *
 * @since 1.0.0
 */
class Utils
{
    use Singleton;

    /**
     * Get an array of posts.
     *
     * @since 1.0.0
     * @param array|string $args Arguments for WP_Query.
     * @return array Array of post IDs and titles, or empty array on failure.
     */
    public static function get_posts($args): array
    {
        // Normalize $args to an array
        if (is_string($args)) {
            $args = wp_parse_args($args, ['suppress_filters' => false]);
        } elseif (!is_array($args)) {
            return [];
        }

        // Set default query arguments
        $args = wp_parse_args($args, [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'suppress_filters' => false,
        ]);

        $query = new \WP_Query($args);
        $items = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items[get_the_ID()] = get_the_title() ?: __('(no title)', 'blank-plugin');
            }
        }

        wp_reset_postdata();
        return $items;
    }

    /**
     * Get default options.
     *
     * @since 1.0.0
     * @return array Default options.
     */
    public static function get_default_options(): array
    {
        $defaults = [
            'setting1'  => esc_html__('Default Setting 1', 'blank-plugin'),
            'setting2'  => esc_html__('Default Setting 2', 'blank-plugin'),
            'setting3'  => false,
            'setting4'  => true,
            'setting5'  => 'option-1',
            'deleteAll' => false,
        ];

        return apply_filters(BLANK_PLUGIN_NAME . '_get_default_options', $defaults);
    }

    /**
     * Get the plugin's saved options.
     *
     * @since 1.0.0
     * @param string $key Optional option key to retrieve a specific value.
     * @return mixed Array of all options or specific option value.
     */
    public static function get_options(string $key = '')
    {
        if (!defined('BLANK_PLUGIN_NAME')) {
            return $key ? false : [];
        }

        $options = get_option(BLANK_PLUGIN_NAME, []);
        $default_options = self::get_default_options();

        if (!is_array($options)) {
            $options = [];
        }

        if (!empty($key)) {
            return $options[$key] ?? ($default_options[$key] ?? false);
        }

        return array_merge($default_options, $options);
    }

    /**
     * Update the plugin options.
     *
     * @since 1.0.0
     * @param string|array $key_or_data Option key or array of options.
     * @param mixed       $val Value for the option key (if key is provided).
     * @return void
     */
    public static function update_options($key_or_data, $val = ''): void
    {
        if (!defined('BLANK_PLUGIN_NAME')) {
            return;
        }

        $options = self::get_options();
        $schema = self::get_settings_schema()['properties'];

        if (is_string($key_or_data) && !empty($key_or_data)) {
            // Sanitize based on schema type
            if (isset($schema[$key_or_data]['type'])) {
                $val = self::sanitize_option($val, $schema[$key_or_data]);
            }
            $options[$key_or_data] = $val;
        } elseif (is_array($key_or_data)) {
            foreach ($key_or_data as $key => $value) {
                if (isset($schema[$key]['type'])) {
                    $key_or_data[$key] = self::sanitize_option($value, $schema[$key]);
                }
            }
            $options = array_merge($options, $key_or_data);
        }

        update_option(BLANK_PLUGIN_NAME, $options);
    }

    /**
     * Initialize and return the WordPress filesystem object.
     *
     * @since 1.0.0
     * @return \WP_Filesystem_Base|WP_Error Filesystem object or WP_Error on failure.
     */
    public static function file_system()
    {
        global $wp_filesystem;

        if (!defined('ABSPATH')) {
            return new \WP_Error('filesystem_error', __('ABSPATH is not defined.', 'blank-plugin'));
        }

        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            if (!WP_Filesystem()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Product_Reviewer_Manager: Failed to initialize WP_Filesystem.');
                }
                return new \WP_Error('filesystem_error', __('Failed to initialize the WordPress filesystem.', 'blank-plugin'));
            }
        }

        return $wp_filesystem;
    }

    /**
     * Parse the changelog and return the changes.
     *
     * @since 1.0.0
     * @return string Sanitized changelog content.
     */
    public static function parse_changelog(): string
    {
        $wp_filesystem = self::file_system();

        if (is_wp_error($wp_filesystem)) {
            return '';
        }

        $changelog_file = apply_filters(BLANK_PLUGIN_NAME . '_changelog_file', defined('BLANK_PLUGIN_PATH') ? BLANK_PLUGIN_PATH . 'readme.txt' : '');

        if (empty($changelog_file) || !$wp_filesystem->exists($changelog_file) || !$wp_filesystem->is_readable($changelog_file)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Blank_Plugin: Changelog file not found or not readable at ' . $changelog_file);
            }
            return '';
        }

        $content = $wp_filesystem->get_contents($changelog_file);
        if (empty($content)) {
            return '';
        }

        $matches = [];
        $regexp = '~==\s*Changelog\s*==\s*(.*?)(?==\s*[^\s]+\s*==|\z)~Uis';
        $changelog = '';

        if (preg_match($regexp, $content, $matches)) {
            $changes = explode("\r\n", trim($matches[1]));
            foreach ($changes as $line) {
                $line = preg_replace('~=\s*Version\s*(\d+(?:\.\d+)+)\s*=~i', '', $line);
                $changelog .= trim($line) . "\n";
            }
        }

        return wp_kses_post(trim($changelog));
    }

    /**
     * Get settings schema.
     *
     * @since 1.0.0
     * @return array Settings schema conforming to JSON Schema (draft-04).
     */
    public static function get_settings_schema(): array
    {
        $defaults = self::get_default_options();
        $setting_properties = apply_filters(
            BLANK_PLUGIN_NAME . '_options_properties',
            [
                'setting1' => [
                    'type'        => 'string',
                    'description' => __('First text setting.', 'blank-plugin'),
                    'default'     => $defaults['setting1'],
                ],
                'setting2' => [
                    'type'        => 'string',
                    'description' => __('Second text setting.', 'blank-plugin'),
                    'default'     => $defaults['setting2'],
                ],
                'setting3' => [
                    'type'        => 'boolean',
                    'description' => __('First boolean setting.', 'blank-plugin'),
                    'default'     => $defaults['setting3'],
                ],
                'setting4' => [
                    'type'        => 'boolean',
                    'description' => __('Second boolean setting.', 'blank-plugin'),
                    'default'     => $defaults['setting4'],
                ],
                'setting5' => [
                    'type'        => 'string',
                    'description' => __('Option selection setting.', 'blank-plugin'),
                    'enum'        => ['option-1', 'option-2'],
                    'default'     => $defaults['setting5'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'deleteAll' => [
                    'type'        => 'boolean',
                    'description' => __('Delete all settings on plugin deactivation.', 'blank-plugin'),
                    'default'     => $defaults['deleteAll'],
                ],
            ]
        );

        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'type'       => 'object',
            'properties' => $setting_properties,
        ];
    }

    /**
     * Sanitize an option value based on its schema.
     *
     * @since 1.0.0
     * @param mixed $value Value to sanitize.
     * @param array $schema Schema for the option.
     * @return mixed Sanitized value.
     */
    private static function sanitize_option($value, array $schema)
    {
        switch ($schema['type']) {
            case 'string':
                $sanitize_callback = $schema['sanitize_callback'] ?? 'sanitize_text_field';
                $value = call_user_func($sanitize_callback, $value);
                if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) {
                    $value = $schema['default'] ?? '';
                }
                break;
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $schema['default'] ?? false;
                break;
            default:
                $value = $schema['default'] ?? $value;
        }
        return $value;
    }

    /**
     * Get white label options.
     *
     * @since 1.0.0
     * @param string $key Optional key to retrieve specific option.
     * @return mixed White label options or specific option value.
     */
    public static function white_label($key = '')
    {
        $plugin_name = apply_filters(
            'blank_plugin_white_label',
            esc_html__('Blank Plugin', 'blank-plugin')
        );

        $options = apply_filters(
            'blank_plugin_white_label_options',
            [
                'admin_menu_page' => [
                    'page_title' => esc_html__('Blank Plugin Page', 'blank-plugin'),
                    'menu_title' => esc_html__('Blank Plugin', 'blank-plugin'),
                    'menu_slug'  => BLANK_PLUGIN_NAME,
                    'icon_url'   => BLANK_PLUGIN_BUILD_PATH_URL . '/images/placeholder.png',
                    'position'   => null,
                ],
                'dashboard' => [
                    'logo'   => BLANK_PLUGIN_BUILD_PATH_URL . '/images/placeholder.png',
                    'notice' => sprintf(
                        /* translators: %s is the plugin name */
                        esc_html__('Congratulations on choosing the %s for creating your plugin. We recommend taking a few minutes to read the following information on how the plugin works. Please read it carefully to fully understand the capabilities of the plugin and how to use them effectively.', 'blank-plugin'),
                        $plugin_name
                    ),
                ],
                'landingPage' => [
                    'banner' => [
                        'heading'    => $plugin_name,
                        'leadText'   => sprintf(
                            /* translators: %s is the plugin name */
                            esc_html__('Congratulations! You have successfully installed %s and it is ready for customization. Feel free to add/edit any files.', 'blank-plugin'),
                            $plugin_name
                        ),
                        'normalText' => sprintf(
                            /* translators: %s is the plugin name */
                            esc_html__('If you have any questions or need assistance, please do not hesitate to contact us for support. The %s plugin caters to WordPress developers and designers seeking to quickly start developing WordPress plugins in a modern way using ReactJS, Rest API.', 'blank-plugin'),
                            $plugin_name
                        ),
                        'buttons' => [
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M11.251.068a.5.5 0 0 1 .227.58L9.677 6.5H13a.5.5 0 0 1 .364.843l-8 8.5a.5.5 0 0 1-.842-.49L6.323 9.5H3a.5.5 0 0 1-.364-.843l8-8.5a.5.5 0 0 1 .615-.09z"/></svg>',
                                'text'    => esc_html__('Get started', 'blank-plugin'),
                                'url'     => 'https://github.com/rabindratharu/blank-plugin',
                                'variant' => 'primary',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M13 0H6a2 2 0 0 0-2 2 2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2 2 2 0 0 0 2-2V2a2 2 0 0 0-2-2m0 13V4a2 2 0 0 0-2-2H5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1M3 4a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/></svg>',
                                'text'    => esc_html__('Documentation', 'blank-plugin'),
                                'url'     => 'https://github.com/rabindratharu/blank-plugin',
                                'variant' => 'outline-primary',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M3.1.7a.5.5 0 0 1 .4-.2h9a.5.5 0 0 1 .4.2l2.976 3.974c.149.185.156.45.01.644L8.4 15.3a.5.5 0 0 1-.8 0L.1 5.3a.5.5 0 0 1 0-.6zm11.386 3.785-1.806-2.41-.776 2.413zm-3.633.004.961-2.989H4.186l.963 2.995zM5.47 5.495 8 13.366l2.532-7.876zm-1.371-.999-.78-2.422-1.818 2.425zM1.499 5.5l5.113 6.817-2.192-6.82zm7.889 6.817 ferta
        5.123-6.83-2.928.002z"/></svg>',
                                'text'    => esc_html__('Get support', 'blank-plugin'),
                                'url'     => 'https://github.com/rabindratharu/blank-plugin',
                                'variant' => 'secondary',
                            ],
                        ],
                        'image' => BLANK_PLUGIN_BUILD_PATH_URL . '/images/placeholder.png',
                    ],
                    'identity' => [
                        'logo'    => BLANK_PLUGIN_BUILD_PATH_URL . '/images/placeholder.png',
                        'title'   => $plugin_name,
                        'buttons' => [
                            [
                                'text'    => esc_html__('Visit site', 'blank-plugin'),
                                'url'     => 'https://github.com/rabindratharu/blank-plugin',
                                'variant' => 'primary',
                            ],
                            [
                                'text'    => esc_html__('Get Support', 'blank-plugin'),
                                'url'     => 'https://github.com/rabindratharu/blank-plugin',
                                'variant' => 'light',
                            ],
                        ],
                    ],
                    'contact' => [
                        'title' => esc_html__('Contact Information', 'blank-plugin'),
                        'info'  => [
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/></svg>',
                                'title'   => esc_html__('Support', 'blank-plugin'),
                                'text'    => esc_html__('Get Support', 'blank-plugin'),
                                'url'     => 'https://github.com/rabindratharu/blank-plugin',
                                'variant' => 'link',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg>',
                                'title'   => esc_html__('Email', 'blank-plugin'),
                                'text'    => esc_html__('rabindra.tharu.np@gmail.com', 'blank-plugin'),
                                'url'     => 'mailto:rabindra.tharu.np@gmail.com',
                                'variant' => 'link',
                            ],
                            [
                                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/></svg>',
                                'title' => esc_html__('Location', 'blank-plugin'),
                                'text'  => esc_html__('Kathmandu, Nepal', 'blank-plugin'),
                            ],
                        ],
                        'social' => [
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M12.633 7.653c0-.848-.305-1.435-.566-1.892l-.08-.13c-.317-.51-.594-.958-.594-1.48 0-.63.478-1.218 1.152-1.218q.03 0 .058.003l.031.003A6.84 6.84 0 0 0 8 1.137 6.86 6.86 0 0 0 2.266 4.23c.16.005.313.009.442.009.717 0 1.828-.087 1.828-.087.37-.022.414.521.044.565 0 0-.371.044-.785.065l2.5 7.434 1.5-4.506-1.07-2.929c-.369-.022-.719-.065-.719-.065-.37-.022-.326-.588.043-.566 0 0 1.134.087 1.808.087.718 0 1.83-.087 1.83-.087.37-.022.413.522.043.566 0 0-.372.043-.785.065l2.48 7.377.684-2.287.054-.173c.27-.86.469-1.495.469-2.046zM1.137 8a6.86 6.86 0 0 0 3.868 6.176L1.73 5.206A6.8 6.8 0 0 0 1.137 8"/><path d="M6.061 14.583 8.121 8.6l2.109 5.78q.02.05.049.094a6.85 6.85 0 0 1-4.218.109m7.96-9.876q.046.328.047.706c0 .696-.13 1.479-.522 2.458l-2.096 6.06a6.86 6.86 0 0 0 2.572-9.224z"/><path fill-rule="evenodd" d="M0 8c0-4.411 3.589-8 8-8s8 3.589 8 8-3.59 8-8 8-8-3.589-8-8m.367 8c0 4.209 3.424 7.633 7.633 7.633S15.632 12.209 15.632 8C15.632 3.79 12.208.367 8 .367 3.79.367.367 3.79.367 8"/></svg>',
                                'url'     => 'https://github.com/rabindratharu/',
                                'variant' => 'outline-primary',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07o-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8"/></svg>',
                                'url'     => 'https://github.com/rabindratharu',
                                'variant' => 'outline-primary',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334q.002-.211-.006-.422A6.7 6.7 0 0 0 16 3.542a6.7 6.7 0 0 1-1.889.518 3.3 3.3 0 0 0 1.447-1.817 6.5 6.5 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.32 9.32 0 0 1-6.767-3.429 3.29 3.29 0 0 0 1.018 4.382A3.3 3.3 0 0 1 .64 6.575v.045a3.29 3.29 0 0 0 2.632 3.218 3.2 3.2 0 0 1-.865.115 3 3 0 0 1-.614-.057 3.28 3.28 0 0 0 3.067 2.277A6.6 6.6 0 0 1 .78 13.58a6 6 0 0 1-.78-.045A9.34 9.34 0 0 0 5.026 15"/></svg>',
                                'url'     => 'https://www.linkedin.com/in/rabindratharu/',
                                'variant' => 'outline-primary',
                            ],
                        ],
                    ],
                    'bannerColumns' => [
                        [
                            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="at-svg at-w at-h" viewBox="0 0 16 16"><path d="M5 3a5 5 0 0 0 0 10h6a5 5 0 0 0 0-10zm6 9a4 4 0 1 1 0-8 4 4 0 0 1 0 8"/></svg>',
                            'title' => esc_html__('Activate Plugin', 'blank-plugin'),
                        ],
                        [
                            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg at-w at-h" viewBox="0 0 16 16"><path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0m-9 8c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4m9.886-3.54c.18-.613 1.048-.613 1.229 0l.043.148a.64.64 0 0 0 .921.382l.136-.074c.561-.306 1.175.308.87.869l-.075.136a.64.64 0 0 0 .382.92l.149.045c.612.18.612 1.048 0 1.229l-.15.043a.64.64 0 0 0-.38.921l.074.136c.305.561-.309 1.175-.87.87l-.136-.075a.64.64 0 0 0-.92.382l-.045.149c-.18.612-1.048.612-1.229 0l-.043-.15a.64.64 0 0 0-.921-.38l-.136.074c-.561.305-1.175-.309-.87-.87l.075-.136a.64.64 0 0 0-.382-.92l-.148-.045c-.613-.18-.613-1.048 0-1.229l.148-.043a.64.64 0 0 0 .382-.921l-.074-.136c-.306-.561.308-1.175.869-.87l.136.075a.64.64 0 0 0 .92-.382zM14 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0"/></svg>',
                            'title' => esc_html__('Login settings', 'blank-plugin'),
                        ],
                        [
                            'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg at-w at-h" viewBox="0 0 16 16"><path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4m4-2.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5M9 8a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4A.5.5 0 0 1 9 8m1 2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5"/><path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H8.96q.04-.245.04-.5C9 10.567 7.21 9 5 9c-2.086 0-3.8 1.398-3.984 3.181A1 1 0 0 1 1 12z"/></svg>',
                            'title' => esc_html__('Dashboard settings', 'blank-plugin'),
                        ],
                    ],
                    'normalColumns' => [
                        [
                            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>',
                            'title'      => esc_html__('Knowledge base', 'blank-plugin'),
                            'content'    => esc_html__('The utilization of this plugin can be facilitated by perusing comprehensive and well-documented articles.', 'blank-plugin'),
                            'buttonText' => esc_html__('Visit knowledge base', 'blank-plugin'),
                            'buttonLink' => 'https://github.com/rabindratharu/blank-plugin',
                        ],
                        [
                            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/><path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/></svg>',
                            'title'      => esc_html__('Community', 'blank-plugin'),
                            'content'    => sprintf(
                                /* translators: %s is the plugin name */
                                esc_html__('Our objective is to enhance the customer experience, we invite you to join our community where you can receive immediate support.', 'blank-plugin'),
                                $plugin_name
                            ),
                            'buttonText' => esc_html__('Visit community page', 'blank-plugin'),
                            'buttonLink' => 'https://github.com/rabindratharu/blank-plugin',
                        ],
                        [
                            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/></svg>',
                            'title'      => esc_html__('24x7 support', 'blank-plugin'),
                            'content'    => sprintf(
                                /* translators: %s is the plugin name */
                                esc_html__('Our support team is available 24/7 to assist you in the event that you encounter any problems while utilizing this plugin.', 'blank-plugin'),
                                $plugin_name
                            ),
                            'buttonText' => esc_html__('Create a support thread', 'blank-plugin'),
                            'buttonLink' => 'https://github.com/rabindratharu/blank-plugin',
                        ],
                        [
                            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M0 12V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2m6.79-6.907A.5.5 0 0 0 6 5.5v5a.5.5 0 0 0 .79.407l3.5-2.5a.5.5 0 0 0 0-.814z"/></svg>',
                            'title'      => esc_html__('Video guide', 'blank-plugin'),
                            'content'    => sprintf(
                                /* translators: %s is the plugin name */
                                esc_html__('The plugin is accompanied by comprehensive video tutorials that provide practical demonstrations for most customization.', 'blank-plugin'),
                                $plugin_name
                            ),
                            'buttonText' => esc_html__('View video guide', 'blank-plugin'),
                            'buttonLink' => 'https://github.com/rabindratharu/blank-plugin',
                        ],
                    ],
                    'topicLinks' => [
                        'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/><path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 0 0-4.243-4.243z"/></svg>',
                        'title'   => esc_html__('Quick links to settings', 'blank-plugin'),
                        'columns' => [
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>',
                                'title'   => esc_html__('Settings 1', 'blank-plugin'),
                                'link'    => '#/settings/setting1',
                                'variant' => 'light',
                                'target'  => '_self',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>',
                                'title'   => esc_html__('Settings 2', 'blank-plugin'),
                                'link'    => '#/settings/setting2',
                                'variant' => 'light',
                                'target'  => '_self',
                            ],
                            [
                                'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>',
                                'title'   => esc_html__('Advanced settings', 'blank-plugin'),
                                'link'    => '#/settings/advanced',
                                'variant' => 'light',
                                'target'  => '_self',
                            ],
                        ],
                    ],
                    'changelog' => [
                        'icon'    => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="at-svg" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/></svg>',
                        'title'   => esc_html__('Changelog', 'blank-plugin'),
                        'content' => self::parse_changelog(),
                    ],
                ],
            ]
        );

        return !empty($key) ? (isset($options[$key]) ? $options[$key] : []) : $options;
    }
}
