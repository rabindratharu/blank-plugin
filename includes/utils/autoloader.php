<?php

/**
 * Autoloader file for Blank Plugin plugin.
 *
 * @package blank-plugin
 * @since 1.0.0
 */

namespace Blank_Plugin\Utils;

/**
 * Autoloader class.
 *
 * Handles automatic loading of plugin classes and traits based on namespace mapping.
 *
 * @since 1.0.0
 */
class Autoloader
{
    /**
     * Namespace root for the plugin.
     *
     * @var string
     */
    private static $namespace_root = 'Blank_Plugin\\';

    /**
     * Directory mappings for namespaces.
     *
     * @var array
     */
    private static $namespace_map = [
        'admin'   => 'includes/admin',
        'utils'   => 'includes/utils',
        'api'     => 'includes/api',
        'widgets' => 'includes/widgets',
    ];

    /**
     * Autoload function.
     *
     * Maps namespaces to file paths and includes the required class or trait files.
     *
     * @since 1.0.0
     * @param string $resource The class or trait name including namespace.
     * @return void
     */
    public static function autoload($resource = '')
    {
        // Normalize resource by trimming namespace separators
        $resource = trim($resource, '\\');

        // Bail if resource is empty, not in our namespace, or lacks sub-namespace
        if (
            empty($resource) ||
            strpos($resource, '\\') === false ||
            strpos($resource, self::$namespace_root) !== 0
        ) {
            return;
        }

        // Remove root namespace
        $resource = str_replace(self::$namespace_root, '', $resource);

        // Convert namespace to path segments
        $path = explode('\\', str_replace('_', '-', strtolower($resource)));

        if (empty($path[0])) {
            return;
        }

        // Determine directory and file name
        $directory = isset(self::$namespace_map[$path[0]]) ? self::$namespace_map[$path[0]] : 'includes';
        $file_name = '';

        // Handle special case for traits in utils namespace
        if ($path[0] === 'utils' && !empty($path[1]) && stripos($path[1], 'singleton') !== false) {
            $file_name = 'trait-singleton';
        } else {
            $file_name = !empty($path[1]) ? sprintf('class-%s', trim(strtolower($path[1]))) : sprintf('class-%s', trim(strtolower($path[0])));
        }

        if (empty($file_name)) {
            return;
        }

        // Construct file path
        $base_path = defined('BLANK_PLUGIN_PATH') ? untrailingslashit(BLANK_PLUGIN_PATH) : untrailingslashit(plugin_dir_path(__DIR__));
        $resource_path = sprintf('%s/%s/%s.php', $base_path, $directory, $file_name);

        // Validate and include file
        if (file_exists($resource_path) && validate_file($resource_path) === 0) {
            require_once $resource_path;
        } elseif (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Blank_Plugin: Failed to load %s at %s. File %s.',
                stripos($file_name, 'trait-') === 0 ? 'trait' : 'class',
                $resource,
                file_exists($resource_path) ? 'is not readable or invalid' : 'does not exist'
            ));
        }
    }

    /**
     * Register the autoloader.
     *
     * @since 1.0.0
     * @return void
     */
    public static function register()
    {
        // Prevent duplicate registration
        if (!in_array([__CLASS__, 'autoload'], spl_autoload_functions(), true)) {
            spl_autoload_register([__CLASS__, 'autoload']);
        }
    }
}

// Register the autoloader
Autoloader::register();
