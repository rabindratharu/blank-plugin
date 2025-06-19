<?php

/**
 * REST API settings for Blank Plugin plugin.
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Inc;

use Blank_Plugin\Inc\Traits\Singleton;
use Blank_Plugin\Inc\Utils;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Api_Settings
 *
 * Handles REST API settings for the Blank Plugin plugin.
 *
 * @since 1.0.0
 */
class Api_Settings
{
    use Singleton;

    /**
     * API version.
     *
     * @var string
     */
    private const VERSION = 'v1';

    /**
     * API namespace.
     *
     * @var string
     */
    private const NAMESPACE = BLANK_PLUGIN_NAME;

    /**
     * API endpoint.
     *
     * @var string
     */
    private const ENDPOINT = 'settings';

    /**
     * Initializes the class and sets up hooks.
     *
     * @since 1.0.0
     */
    protected function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers REST API routes.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes(): void
    {
        register_rest_route(
            self::NAMESPACE . '/' . self::VERSION,
            '/' . self::ENDPOINT,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args'                => [
                        'context' => [
                            'default' => 'view',
                            'type'    => 'string',
                            'enum'    => ['view', 'edit'],
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'update_item'],
                    'permission_callback' => [$this, 'get_item_permissions_check'],
                    'args' => rest_get_endpoint_args_for_schema($this->get_item_schema(), WP_REST_Server::EDITABLE),
                ],
            ]
        );
    }

    /**
     * Checks if a given request has access to read and manage settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error True if the request has access, WP_Error otherwise.
     */
    public function get_item_permissions_check(WP_REST_Request $request)
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('You do not have permission to access this resource.', 'blank-plugin'),
                ['status' => rest_authorization_required_code()]
            );
        }
        return true;
    }

    /**
     * Retrieves the settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function get_item(WP_REST_Request $request)
    {
        $saved_options = Utils::get_options();
        $schema = $this->get_registered_schema();

        $prepared_value = $this->prepare_value($saved_options, $schema);

        if (is_wp_error($prepared_value)) {
            return $prepared_value;
        }

        return new WP_REST_Response($prepared_value, 200);
    }

    /**
     * Updates settings.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error on failure.
     */
    public function update_item(WP_REST_Request $request)
    {
        $schema = $this->get_registered_schema();
        $params = $request->get_params();

        // Validate the input against the schema
        $validation = rest_validate_value_from_schema($params, $schema);
        if (is_wp_error($validation)) {
            return new WP_Error(
                'rest_invalid_params',
                __('Invalid settings data provided.', 'blank-plugin'),
                ['status' => 400, 'errors' => $validation->get_error_messages()]
            );
        }

        // Sanitize the input
        $sanitized_options = $this->prepare_value($params, $schema);
        if (is_wp_error($sanitized_options)) {
            return $sanitized_options;
        }

        // Update options
        Utils::update_options($sanitized_options);

        // Return the updated settings
        return $this->get_item($request);
    }

    /**
     * Retrieves all registered options for the Settings API.
     *
     * @since 1.0.0
     * @return array Schema array, or default schema if not available.
     */
    protected function get_registered_schema(): array
    {
        static $cached_schema = null;

        if (null !== $cached_schema) {
            return $cached_schema;
        }

        // Try to fetch schema from Utils class
        if (method_exists(Utils::class, 'get_settings_schema')) {
            $schema = Utils::get_settings_schema();
        } else {
            // Fallback schema if Utils::get_settings_schema is not defined
            $schema = [
                'type'       => 'object',
                'properties' => Utils::get_default_options(),
            ];
        }

        // Ensure properties are defined
        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            $schema['properties'] = Utils::get_default_options();
        }

        $cached_schema = $schema;
        return $schema;
    }

    /**
     * Retrieves the site setting schema, conforming to JSON Schema.
     *
     * @since 1.0.0
     * @return array Item schema data.
     */
    public function get_item_schema(): array
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => self::NAMESPACE,
            'type'       => 'object',
            'properties' => $this->get_registered_schema()['properties'],
        ];

        /**
         * Filters the item's schema.
         *
         * @since 1.0.0
         * @param array $schema Item schema data.
         */
        $schema = apply_filters('rest_' . self::NAMESPACE . '_item_schema', $schema);

        return $schema;
    }

    /**
     * Prepares a value for output based on a schema.
     *
     * @since 1.0.0
     * @param mixed $value  Value to prepare.
     * @param array $schema Schema to match.
     * @return mixed|WP_Error Prepared value or WP_Error on failure.
     */
    protected function prepare_value($value, array $schema)
    {
        $sanitized_value = rest_sanitize_value_from_schema($value, $schema);

        if (is_null($sanitized_value)) {
            return new WP_Error(
                'rest_invalid_stored_value',
                __('The settings data could not be sanitized.', 'blank-plugin'),
                ['status' => 400]
            );
        }

        return $sanitized_value;
    }
}
