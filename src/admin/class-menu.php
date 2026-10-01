<?php
/**
 * Main admin menu of the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Admin;

use Creationell\WpTheme\Core\Capabilities;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds the main menu "Theme settings", the parent of all settings pages of the theme.
 *
 * The menu needs no ACF. Subpages (texts, design, modules, import and export)
 * use the parent slug SLUG and bring their own capabilities. WordPress links
 * the menu to the first entry of the submenu without checking its capability,
 * so collect() moves the first subpage the user may open to the front. Opening
 * the main page itself redirects there. Without such a subpage the main page
 * says so.
 *
 * @since 1.0.0
 */
final class Menu {

	/**
	 * Slug of the main menu; parent slug of the subpages.
	 *
	 * @since 1.0.0
	 */
	public const SLUG = 'creationell-wp-theme';

	/**
	 * Position below "Appearance" and "Header & Footer".
	 *
	 * @since 1.0.0
	 */
	public const POSITION = 62;

	/**
	 * URL of the first subpage the current user may open, null without one.
	 *
	 * @var string|null
	 */
	private static ?string $target = null;

	/**
	 * Adds the main menu; runs on admin_menu with priority 9, before the subpages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_menu_page(
			__( 'creationell Theme settings', 'creationell-wp-theme' ),
			__( 'Theme settings', 'creationell-wp-theme' ),
			Capabilities::MANAGE_BASIC,
			self::SLUG,
			array( self::class, 'render' ),
			'dashicons-admin-generic',
			self::POSITION
		);
	}

	/**
	 * Moves the first subpage the current user may open to the front and remembers it; runs last on admin_menu.
	 *
	 * All subpages exist only once admin_menu has run. WordPress links the main
	 * menu to the first submenu entry, even when the user may not open it, and
	 * checks the capabilities of the subpages before admin_menu only. Entries
	 * are reordered, never removed: without its entry, user_can_access_admin_page()
	 * would check a subpage against the capability of the main menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function collect(): void {
		global $submenu;

		self::$target = null;
		if ( ! isset( $submenu[ self::SLUG ] ) || ! is_array( $submenu[ self::SLUG ] ) ) {
			return;
		}
		foreach ( $submenu[ self::SLUG ] as $key => $item ) {
			if ( ! is_array( $item ) || ! isset( $item[1], $item[2] ) || ! is_string( $item[1] ) || ! is_string( $item[2] ) ) {
				continue;
			}
			if ( self::SLUG === $item[2] || ! current_user_can( $item[1] ) ) {
				continue;
			}
			self::$target = str_contains( $item[2], '.php' ) ? admin_url( $item[2] ) : admin_url( 'admin.php?page=' . $item[2] );

			// Same entry, new place; array_unshift() renumbers the keys from 0.
			unset( $submenu[ self::SLUG ][ $key ] );
			array_unshift( $submenu[ self::SLUG ], $item );
			return;
		}
	}

	/**
	 * Returns the URL of the first subpage the current user may open.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null URL, null without such a subpage.
	 */
	public static function target(): ?string {
		return self::$target;
	}

	/**
	 * Redirects a request for the main page to the first allowed subpage; runs on admin_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function redirect(): void {
		if ( null === self::$target ) {
			return;
		}
		$pagenow     = $GLOBALS['pagenow'] ?? null;
		$plugin_page = $GLOBALS['plugin_page'] ?? null;
		if ( 'admin.php' !== $pagenow || self::SLUG !== $plugin_page ) {
			return;
		}
		wp_safe_redirect( self::$target );
		exit;
	}

	/**
	 * Prints the main page, which is only reached without an allowed subpage.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		printf(
			'<div class="wrap"><h1>%1$s</h1><p>%2$s</p></div>' . "\n",
			esc_html__( 'creationell Theme settings', 'creationell-wp-theme' ),
			esc_html__( 'No theme settings page is available to you.', 'creationell-wp-theme' )
		);
	}
}
