<?php

/**
 * Registers the Admin menu and settings screen.
 *
 * @package BlankPlugin\Modules\Settings
 */

declare(strict_types=1);

namespace BlankPlugin\Modules\Settings;

use BlankPlugin\Contracts\Interfaces\Registrable;
use BlankPlugin\Modules\Core\Assets;

/**
 * Class - Admin
 */
class Admin implements Registrable
{
	/**
	 * The menu slug for the admin menu.
	 *
	 * @todo replace with a cross-plugin menu.
	 */
	public const MENU_SLUG = 'blank-plugin';

	/**
	 * The screen ID for the settings page.
	 */
	public const SCREEN_ID = self::MENU_SLUG . '-settings';

	/**
	 * Path to the SVG logo for the menu.
	 *
	 * @todo Replace with actual logo.
	 * @var string
	 */
	private const SVG_LOGO_PATH = BLANK_PLUGIN_URL . 'assets/build/images/logo.svg';

	/**
	 * {@inheritDoc}
	 */
	public function register_hooks(): void
	{
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_menu', [$this, 'add_submenu'], 20); // 20 priority to make sure settings page respect its position.
		add_action('admin_menu', [$this, 'remove_default_submenu'], 999);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

		add_filter('plugin_action_links_' . BLANK_PLUGIN_PLUGIN_BASENAME, [$this, 'add_action_links'], 2);
		add_filter('admin_body_class', [$this, 'add_body_classes']);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu(): void
	{
		add_menu_page(
			esc_html__('Blank Plugin', 'blank-plugin'),
			esc_html__('Blank Plugin', 'blank-plugin'),
			'manage_options',
			self::MENU_SLUG,
			[$this, 'menu_callback'],
			self::SVG_LOGO_PATH,
			2
		);
	}

	/**
	 * Register the settings page.
	 */
	public function add_submenu(): void
	{
		// Add the dashboard submenu page.
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__('Dashboard', 'blank-plugin'),
			esc_html__('Dashboard', 'blank-plugin'),
			'manage_options',
			self::MENU_SLUG,
			[$this, 'menu_callback'],
			999
		);
		// Add the settings submenu page.
		add_submenu_page(
			self::MENU_SLUG,
			esc_html__('Settings', 'blank-plugin'),
			esc_html__('Settings', 'blank-plugin'),
			'manage_options',
			self::SCREEN_ID,
			[$this, 'screen_callback'],
			999
		);
	}

	/**
	 * Remove the default submenu added by WordPress.
	 */
	public function remove_default_submenu(): void
	{
		if (! empty(Settings::get_options())) {
			return;
		}
		remove_submenu_page(self::MENU_SLUG, self::MENU_SLUG);
	}

	/**
	 * Admin page content callback.
	 */
	public function menu_callback(): void
	{
		echo '<div class="wrap" id="blank-plugin-dashboard"></div>';
	}

	/**
	 * Admin page content callback.
	 */
	public function screen_callback(): void
	{
?>
		<div class="wrap">
			<h1><?php esc_html_e('Settings', 'blank-plugin'); ?></h1>
			<div id="blank-plugin-settings-page"></div>
		</div>
<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts(string $hook): void
	{

		if (strpos($hook, self::MENU_SLUG) !== false) {
			wp_enqueue_script(Assets::DASHBOARD_SCRIPT_HANDLE);
		}

		if (strpos($hook, self::SCREEN_ID) !== false) {
			wp_localize_script(Assets::SETTINGS_SCRIPT_HANDLE, 'BlankPluginSettings', Assets::get_localized_data());
			wp_enqueue_script(Assets::SETTINGS_SCRIPT_HANDLE);
		}
	}

	/**
	 * Add action links to the settings on the plugins page.
	 *
	 * @param string[] $links Existing links.
	 *
	 * @return string[]
	 */
	public function add_action_links($links): array
	{
		// Defense against other plugins.
		if (! is_array($links)) {
			_doing_it_wrong(__METHOD__, esc_html__('Expected an array.', 'blank-plugin'), '1.0.0');

			$links = [];
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url(admin_url(sprintf('admin.php?page=%s', self::SCREEN_ID))),
			__('Settings', 'blank-plugin')
		);

		return $links;
	}

	/**
	 * Add body classes for the admin area.
	 *
	 * @param string $classes Existing body classes.
	 */
	public function add_body_classes($classes): string
	{
		$current_screen = get_current_screen();

		if (! $current_screen) {
			return $classes;
		}

		$classes .= ' ' .  self::MENU_SLUG;

		// Cast to string in case it's null.
		$classes = $this->add_body_class_for_missing_sites((string) $classes);

		return $classes;
	}

	/**
	 * Add body class for missing sites.
	 *
	 * @param string $classes Existing body classes.
	 */
	private function add_body_class_for_missing_sites(string $classes): string
	{
		// Bail if the shared sites are already set.
		$shared_sites = Settings::get_options();
		if (! empty($shared_sites)) {
			return $classes;
		}

		$classes .= ' blank-plugin-missing-brand-sites ';
		return $classes;
	}
}
