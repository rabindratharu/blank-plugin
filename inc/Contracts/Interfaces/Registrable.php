<?php
/**
 * Interface for Registrable classes.
 *
 * Registrable classes are those that register hooks (actions/filters) with WordPress.
 *
 * @package BlankPlugin\Contracts\Interfaces
 */

declare( strict_types = 1 );

namespace BlankPlugin\Contracts\Interfaces;

/**
 * Interface - Registrable
 */
interface Registrable {

	/**
	 * Registers class methods to WordPress.
	 *
	 * WordPress actions/filters should be included here.
	 */
	public function register_hooks(): void;
}
