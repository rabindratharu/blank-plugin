<?php
/**
 * Shared utilities functions for internal plugin use.
 *
 * Utilities are a domain antipattern - where ever possible, colocate methods with their related classes.
 *
 * @package BlankPlugin
 */

declare(strict_types = 1);

namespace BlankPlugin;

/**
 * Class - Utils
 */
class Utils {

	/**
	 * Normalize a URL by trimming whitespace and ensuring it ends with a trailing slash.
	 *
	 * @param string $url The URL to normalize.
	 */
	public static function normalize_url( string $url ): string {
		return (string) trailingslashit( trim( $url ) );
	}
}
