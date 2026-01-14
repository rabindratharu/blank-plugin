<?php
/**
 * Plugin Settings Management
 *
 * @package BlankPlugin\Modules\Settings
 */

declare(strict_types=1);

namespace BlankPlugin\Modules\Settings;

use BlankPlugin\Contracts\Interfaces\Registrable;

/**
 * Manages plugin settings registration, sanitization and access
 */
class Settings implements Registrable
{
    private const SETTING_PREFIX = 'blank_plugin_';

    public const SETTING_GROUP   = self::SETTING_PREFIX . 'settings';
    public const OPTION_GENERAL  = self::SETTING_PREFIX . 'general';

    /**
     * {@inheritDoc}
     */
    public function register_hooks(): void
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('rest_api_init', [$this, 'register_settings']);
    }

    /**
     * Register plugin settings
     */
    public function register_settings(): void
    {
        register_setting(
            self::SETTING_GROUP,
            self::OPTION_GENERAL,
            [
                'type'              => 'object',
                'description'       => 'Blank Plugin global settings',
                'default'           => self::get_default_options(),
                'sanitize_callback' => [$this, 'sanitize_options'],
                'show_in_rest'      => [
                    'schema' => self::get_settings_schema(),
                ],
            ]
        );
    }

    /**
     * Sanitize options callback
     */
    public function sanitize_options($value): array
    {
        if (!is_array($value)) {
            return self::get_default_options();
        }

        $schema = self::get_settings_schema();
        $sanitized = [];

        foreach ($value as $key => $val) {
            if (isset($schema['properties'][$key])) {
                $property_schema = $schema['properties'][$key];
                $sanitized[$key] = self::sanitize_option($val, $property_schema);
            }
        }

        return array_merge(self::get_default_options(), $sanitized);
    }

    /**
     * Get default options
     */
    public static function get_default_options(): array
    {
        $defaults = [
            'setting1'  => esc_html__('Default Setting 1', 'blank-plugin'),
            'setting2'  => esc_html__('Default Setting 2', 'blank-plugin'),
            'setting3'  => false,
            'quiz'      => true,
            'setting5'  => 'option-1',
            'deleteAll' => false,
        ];

        return apply_filters(self::OPTION_GENERAL . '_defaults', $defaults);
    }

    /**
     * Get settings schema for REST API
     */
    public static function get_settings_schema(): array
    {
        $defaults = self::get_default_options();

        $properties = apply_filters(
            self::OPTION_GENERAL . '_schema_properties',
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
                'quiz' => [
                    'type'        => 'boolean',
                    'description' => __('Enable/disable quiz block.', 'blank-plugin'),
                    'default'     => $defaults['quiz'],
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
            'title'      => 'Blank Plugin General Settings',
            'type'       => 'object',
            'properties' => $properties,
        ];
    }

    /**
     * Get the plugin's saved options.
     */
    public static function get_options(string $key = ''): mixed
    {
        $options = get_option(self::OPTION_GENERAL, []);
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
     */
    public static function update_options($key_or_data, $val = ''): bool
    {
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

        return update_option(self::OPTION_GENERAL, $options);
    }

    /**
     * Sanitize an option value based on its schema.
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
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($value === null) {
                    $value = $schema['default'] ?? false;
                }
                break;
            default:
                $value = $schema['default'] ?? $value;
        }
        return $value;
    }
}
