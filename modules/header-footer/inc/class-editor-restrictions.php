<?php
/**
 * Site Editor for editors: menu entry, tidy Appearance menu, redirects, block locks and block list.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Core\Site_Editor_Lock;
use WP_Block_Editor_Context;
use WP_Block_Type_Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gives editors one entry "Header & Footer" and keeps the rest of the Site Editor out of sight.
 *
 * The menu entry needs the template parts capability; its subpages open the
 * editors of header and footer and, with the menu capability, the menu
 * screen. The remaining rules apply to users without the native capability
 * edit_theme_options only: the entries below Appearance go (the grant would
 * show them in context), except "Menus" with the menu capability;
 * site-editor.php leads every other path to the part list; the Site Editor
 * offers no block locking and no blocks of the deny list. The REST allow list
 * stays the real limit.
 *
 * @since 1.0.0
 */
final class Editor_Restrictions {

	/**
	 * Slug of the menu entry.
	 *
	 * @since 1.0.0
	 */
	public const SLUG = 'creationell-wp-theme-header-footer';

	/**
	 * Menu position, between Appearance (60) and the theme settings (62).
	 *
	 * @since 1.0.0
	 */
	public const POSITION = 61;

	/**
	 * Filter for the blocks editors may not insert in the Site Editor.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_DENIED_BLOCKS = 'creationell_wp_theme_header_footer_denied_blocks';

	/**
	 * Blocks editors may not insert in the Site Editor by default.
	 *
	 * @since 1.0.0
	 */
	public const DENIED_BLOCKS = array( 'core/html', 'core/freeform', 'core/shortcode', 'core/navigation', 'core/template-part', 'core/site-logo', 'core/site-title', 'core/site-tagline' );

	/**
	 * Name of the block editor context of the Site Editor.
	 */
	private const SITE_EDITOR_CONTEXT = 'core/edit-site';

	/**
	 * Registers the hooks; called when the module boots.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ), 10, 0 );
		add_action( 'admin_menu', array( self::class, 'prune_menu' ), PHP_INT_MAX, 0 );
		add_action( 'admin_init', array( self::class, 'redirect_menu_page' ), 10, 0 );
		add_action( 'load-site-editor.php', array( self::class, 'guard_site_editor' ), 1, 0 );
		add_filter( 'block_editor_settings_all', array( self::class, 'editor_settings' ), 10, 2 );
		add_filter( 'allowed_block_types_all', array( self::class, 'allowed_blocks' ), 10, 2 );
	}

	/**
	 * Adds the menu entry "Header & Footer" with its subpages; runs on admin_menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_menu_page(
			__( 'Header & Footer', 'creationell-wp-theme' ),
			__( 'Header & Footer', 'creationell-wp-theme' ),
			Capabilities::EDIT_TEMPLATE_PARTS,
			self::SLUG,
			array( self::class, 'render' ),
			'dashicons-layout',
			self::POSITION
		);
		add_submenu_page( self::SLUG, __( 'Header', 'creationell-wp-theme' ), __( 'Header', 'creationell-wp-theme' ), Capabilities::EDIT_TEMPLATE_PARTS, Access_Routes::part_editor_path( 'header' ) );
		add_submenu_page( self::SLUG, __( 'Footer', 'creationell-wp-theme' ), __( 'Footer', 'creationell-wp-theme' ), Capabilities::EDIT_TEMPLATE_PARTS, Access_Routes::part_editor_path( 'footer' ) );
		add_submenu_page( self::SLUG, __( 'Menus', 'creationell-wp-theme' ), __( 'Menus', 'creationell-wp-theme' ), Capabilities::EDIT_MENUS, 'nav-menus.php' );
	}

	/**
	 * Removes the entries below Appearance for users without the native capability; runs last on admin_menu.
	 *
	 * "Menus" stays with the template part and the menu capability
	 * (Template_Part_Access::can_edit_menus()); without both it also goes from
	 * "Header & Footer", where WordPress would otherwise show it in place of
	 * the closed main entry. Without any entry left, Appearance itself goes, so
	 * no link leads to a closed screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function prune_menu(): void {
		global $submenu;

		if ( Template_Part_Access::has_native_cap( get_current_user_id() ) ) {
			return;
		}
		$menus = Template_Part_Access::can_edit_menus();
		if ( ! $menus && isset( $submenu[ self::SLUG ] ) && is_array( $submenu[ self::SLUG ] ) ) {
			foreach ( $submenu[ self::SLUG ] as $index => $item ) {
				if ( is_array( $item ) && 'nav-menus.php' === ( $item[2] ?? null ) ) {
					unset( $submenu[ self::SLUG ][ $index ] );
				}
			}
		}
		if ( isset( $submenu['themes.php'] ) && is_array( $submenu['themes.php'] ) ) {
			foreach ( $submenu['themes.php'] as $index => $item ) {
				if ( ! $menus || ! is_array( $item ) || 'nav-menus.php' !== ( $item[2] ?? null ) ) {
					unset( $submenu['themes.php'][ $index ] );
				}
			}
			if ( array() !== $submenu['themes.php'] ) {
				return;
			}
			unset( $submenu['themes.php'] );
		}
		remove_menu_page( 'themes.php' );
	}

	/**
	 * Redirects the page of the menu entry to the part list; runs on admin_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function redirect_menu_page(): void {
		$pagenow     = $GLOBALS['pagenow'] ?? null;
		$plugin_page = $GLOBALS['plugin_page'] ?? null;
		if ( 'admin.php' !== $pagenow || self::SLUG !== $plugin_page ) {
			return;
		}
		wp_safe_redirect( Access_Routes::part_list_url() );
		exit;
	}

	/**
	 * Leads editors from every other Site Editor path to the part list; runs on load-site-editor.php with priority 1.
	 *
	 * Only for users with the template parts capability and without the native
	 * capability; others get the answer of WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function guard_site_editor(): void {
		if ( ! current_user_can( Capabilities::EDIT_TEMPLATE_PARTS ) || Template_Part_Access::has_native_cap( get_current_user_id() ) ) {
			return;
		}
		if ( Access_Routes::site_editor_path_allowed( Site_Editor_Lock::site_editor_path() ) ) {
			return;
		}
		wp_safe_redirect( Access_Routes::part_list_url() );
		exit;
	}

	/**
	 * Switches block locking off for editors in the Site Editor; runs on block_editor_settings_all.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed>    $settings Editor settings.
	 * @param WP_Block_Editor_Context $context  Editor context.
	 * @return array<string, mixed> Editor settings.
	 */
	public static function editor_settings( array $settings, WP_Block_Editor_Context $context ): array {
		if ( self::restricted( $context ) ) {
			$settings['canLockBlocks'] = false;
		}
		return $settings;
	}

	/**
	 * Removes the denied blocks for editors in the Site Editor; runs on allowed_block_types_all.
	 *
	 * @since 1.0.0
	 *
	 * @param bool|array<int, string> $allowed Allowed block names, true for all, false for none.
	 * @param WP_Block_Editor_Context $context Editor context.
	 * @return bool|array<int, string> Allowed block names.
	 */
	public static function allowed_blocks( bool|array $allowed, WP_Block_Editor_Context $context ): bool|array {
		if ( false === $allowed || ! self::restricted( $context ) ) {
			return $allowed;
		}
		$names = true === $allowed ? array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() ) : $allowed;
		return array_values( array_diff( $names, self::denied_blocks() ) );
	}

	/**
	 * Returns the blocks editors may not insert in the Site Editor.
	 *
	 * An invalid filter value falls back to the default and is reported with
	 * _doing_it_wrong(); entries that are no non-empty string are left out.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Block names.
	 */
	public static function denied_blocks(): array {
		/**
		 * Filters the blocks editors may not insert while editing header and footer in the Site Editor.
		 *
		 * Administrators with the native capability edit_theme_options are not
		 * affected. Add a block name to hide it, remove one to offer it.
		 *
		 * @since 1.0.0
		 *
		 * @param array<int, string> $denied Block names; default core/html, core/freeform, core/shortcode, core/navigation, core/template-part, core/site-logo, core/site-title, core/site-tagline.
		 */
		$denied = apply_filters( 'creationell_wp_theme_header_footer_denied_blocks', self::DENIED_BLOCKS );
		if ( ! is_array( $denied ) ) {
			self::report( 'The value must be a list of block names' );
			return self::DENIED_BLOCKS;
		}
		$names = array_values( array_filter( $denied, static fn( mixed $name ): bool => is_string( $name ) && '' !== $name ) );
		if ( count( $names ) !== count( $denied ) ) {
			self::report( 'Entries must be block names' );
		}
		return $names;
	}

	/**
	 * Prints the page of the menu entry, which is only reached when the redirect did not run.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		printf(
			'<div class="wrap"><h1>%1$s</h1><p><a href="%2$s">%3$s</a></p></div>' . "\n",
			esc_html__( 'Header & Footer', 'creationell-wp-theme' ),
			esc_url( Access_Routes::part_list_url() ),
			esc_html__( 'Open the template parts in the Site Editor', 'creationell-wp-theme' )
		);
	}

	/**
	 * Tells whether the rules for editors apply: Site Editor context and no native capability.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Block_Editor_Context $context Editor context.
	 * @return bool True for editors in the Site Editor.
	 */
	private static function restricted( WP_Block_Editor_Context $context ): bool {
		return self::SITE_EDITOR_CONTEXT === $context->name && ! Template_Part_Access::has_native_cap( get_current_user_id() );
	}

	/**
	 * Reports an invalid value of the filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $problem Problem, without the filter name.
	 * @return void
	 */
	private static function report( string $problem ): void {
		_doing_it_wrong(
			__CLASS__ . '::denied_blocks',
			esc_html( sprintf( 'Invalid value of the filter %1$s: %2$s.', self::FILTER_DENIED_BLOCKS, $problem ) ),
			'1.0.0'
		);
	}
}
