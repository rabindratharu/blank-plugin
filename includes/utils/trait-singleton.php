<?php

/**
 * Singleton Trait for Blank Plugin plugin.
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Utils;

/**
 * Singleton Trait.
 *
 * Ensures a class has only one instance and provides a global point of access to it.
 *
 * @since 1.0.0
 */
trait Singleton
{
	/**
	 * The single instance of the class.
	 *
	 * @var static
	 */
	private static $instance = null;

	/**
	 * Get the instance of the class.
	 *
	 * @since 1.0.0
	 * @return static
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct()
	{
		// Prevent direct instantiation
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function __clone()
	{
		// Prevent cloning
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup()
	{
		// Prevent unserializing
		throw new \Exception('Cannot unserialize a singleton.');
	}
}
