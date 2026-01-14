<?php

/**
 * Base REST controller class.
 *
 * Includes the shared namespace, version and hook registration.
 *
 * @package BlankPlugin\Modules\Rest
 */

declare(strict_types=1);

namespace BlankPlugin\Modules\Rest;

use BlankPlugin\Contracts\Interfaces\Registrable;
use BlankPlugin\Modules\Settings\Settings;
use BlankPlugin\Utils;
use WP_REST_Controller;

/**
 * Class - Abstract_REST_Controller
 */
abstract class Abstract_REST_Controller extends WP_REST_Controller implements Registrable
{
	/**
	 * The namespace for the REST API.
	 */
	public const NAMESPACE = 'blank-plugin/v1';

	/**
	 * {@inheritDoc}
	 *
	 * Reuses the namespace constant.
	 *
	 * @var string
	 */
	protected $namespace = self::NAMESPACE;

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	/**
	 * {@inheritDoc}
	 *
	 * We throw an exception here to force the child class to implement this method.
	 *
	 * @throws \Exception If method not implemented.
	 *
	 * @codeCoverageIgnore
	 */
	public function register_routes(): void
	{
		throw new \Exception(__FUNCTION__ . ' Method not implemented.');
	}

	/**
	 * Check if user is authenticated.
	 *
	 * @return bool|WP_Error
	 */
	public function logged_in_permission()
	{
		if (! is_user_logged_in()) {
			return new WP_Error(
				'rest_forbidden',
				__('You must be logged in to access this resource.', 'blank-plugin'),
				array('status' => 401)
			);
		}
		return true;
	}

	/**
	 * Check if user can read (subscriber level or higher).
	 *
	 * @return bool|WP_Error
	 */
	public function read_permission()
	{
		if (! current_user_can('read')) {
			return new WP_Error(
				'rest_forbidden',
				__('You do not have permission to read this resource.', 'blank-plugin'),
				array('status' => rest_authorization_required_code())
			);
		}
		return true;
	}

	/**
	 * Check if user has manage_options capability.
	 *
	 * @return bool|WP_Error
	 */
	public function manage_options_permission()
	{
		if (! current_user_can('manage_options')) {
			return new WP_Error(
				'rest_forbidden',
				__('Administrator permission required.', 'blank-plugin'),
				array('status' => rest_authorization_required_code())
			);
		}
		return true;
	}
}
