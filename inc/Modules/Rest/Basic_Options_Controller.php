<?php

/**
 * REST API routes for plugin settings.
 *
 * @package BlankPlugin
 */

namespace BlankPlugin\Modules\Rest;

use BlankPlugin\Modules\Settings\Settings;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class Basic_Options_Controller
 */
class Basic_Options_Controller extends Abstract_REST_Controller
{
	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void
	{
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [$this, 'get_item'],
					'permission_callback' => [$this, 'get_item_permissions_check'],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [$this, 'update_item'],
					'permission_callback' => [$this, 'update_item_permissions_check'],
					'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
				],
			]
		);
	}

	/**
	 * Get collection parameters.
	 */
	public function get_collection_params(): array
	{
		return [
			'context' => [
				'default' => 'view',
				'type'    => 'string',
				'enum'    => ['view', 'edit'],
			],
		];
	}

	/**
	 * Check if a given request has access to get items.
	 */
	public function get_item_permissions_check($request)
	{
		if (!current_user_can('manage_options')) {
			return new WP_Error(
				'rest_forbidden',
				__('Sorry, you are not allowed to view these settings.', 'blank-plugin'),
				['status' => rest_authorization_required_code()]
			);
		}
		return true;
	}

	/**
	 * Check if a given request has access to update items.
	 */
	public function update_item_permissions_check($request)
	{
		if (!current_user_can('manage_options')) {
			return new WP_Error(
				'rest_forbidden',
				__('Sorry, you are not allowed to update these settings.', 'blank-plugin'),
				['status' => rest_authorization_required_code()]
			);
		}
		return true;
	}

	/**
	 * Retrieves a single setting item.
	 */
	public function get_item($request)
	{
		try {
			$saved_options = Settings::get_options();

			$prepared_item = $this->prepare_item_for_response($saved_options, $request);
			$response = rest_ensure_response($prepared_item);

			return $response;
		} catch (\Throwable $t) {
			error_log(
				sprintf(
					'[BlankPlugin] REST get_item error: %s in %s on line %d',
					$t->getMessage(),
					$t->getFile(),
					$t->getLine()
				)
			);

			return new WP_Error(
				'rest_server_error',
				__('An unexpected error occurred while retrieving settings.', 'blank-plugin'),
				['status' => 500]
			);
		}
	}

	/**
	 * Updates a single setting item.
	 */
	public function update_item($request)
	{
		try {
			
			// Get the raw JSON data
			$params = $request->get_json_params();

			if (empty($params)) {
				$params = $request->get_params();
			}

			// Check if any parameters are provided
			if (empty($params)) {
				return new WP_Error(
					'rest_no_valid_params',
					__('No valid settings provided.', 'blank-plugin'),
					['status' => 400]
				);
			}

			// Validate against schema
			$schema = $this->get_item_schema();
			$validation_result = rest_validate_value_from_schema($params, $schema, 'settings');

			if (is_wp_error($validation_result)) {
				return new WP_Error(
					'rest_invalid_param',
					__('Invalid parameter(s).', 'blank-plugin'),
					[
						'status' => 400,
						'details' => $validation_result->get_error_data(),
					]
				);
			}

			// Sanitize using schema
			$sanitized = rest_sanitize_value_from_schema($params, $schema, 'settings');

			if (is_wp_error($sanitized)) {
				return $sanitized;
			}

			// Update options directly using Settings::update_options
			$update_result = Settings::update_options($sanitized);

			if ($update_result === false) {
				return new WP_Error(
					'rest_update_failed',
					__('Failed to update settings.', 'blank-plugin'),
					['status' => 500]
				);
			}

			// Return the updated settings
			return $this->get_item($request);
		} catch (\Throwable $t) {
			error_log(
				sprintf(
					'[BlankPlugin] REST update_item error: %s in %s on line %d',
					$t->getMessage(),
					$t->getFile(),
					$t->getLine()
				)
			);

			return new WP_Error(
				'rest_server_error',
				__('An unexpected error occurred while saving settings.', 'blank-plugin'),
				['status' => 500]
			);
		}
	}

	/**
	 * Prepares a single setting output for response.
	 */
	public function prepare_item_for_response($item, $request)
	{
		$schema = $this->get_item_schema();
		$context = $request->get_param('context') ?: 'view';

		$prepared = [];
		foreach ($schema['properties'] as $property => $property_schema) {
			if (!isset($item[$property]) && isset($property_schema['default'])) {
				$prepared[$property] = $property_schema['default'];
				continue;
			}

			if (!isset($item[$property])) {
				continue;
			}

			$prepared[$property] = $item[$property];
		}

		return apply_filters('blank_plugin_prepare_setting_for_response', $prepared, $item, $request);
	}

	/**
	 * Retrieves the registered schema from Settings class.
	 */
	protected function get_registered_schema(): array
	{
		static $cached_schema = null;

		if (null !== $cached_schema) {
			return $cached_schema;
		}

		$schema = Settings::get_settings_schema();

		if (empty($schema['properties']) || !is_array($schema['properties'])) {
			$default_options = Settings::get_default_options();
			$schema['properties'] = [];
			foreach ($default_options as $key => $value) {
				$schema['properties'][$key] = [
					'type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string'),
					'default' => $value,
				];
			}
		}

		$cached_schema = $schema;
		return $schema;
	}

	/**
	 * Retrieves the site setting schema.
	 */
	public function get_item_schema(): array
	{
		$schema = $this->get_registered_schema();

		$schema['$schema'] = 'http://json-schema.org/draft-04/schema#';
		$schema['title'] = 'blank-plugin-settings';

		if (!isset($schema['type'])) {
			$schema['type'] = 'object';
		}

		if (!isset($schema['properties'])) {
			$schema['properties'] = [];
		}

		return apply_filters('blank_plugin_rest_settings_schema', $schema);
	}
}
