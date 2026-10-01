<?php
/**
 * Front-end styles and scripts of the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Assets;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the compiled theme stylesheet, the icon font, the child stylesheet, the Bootstrap bundle and the theme script.
 *
 * The handles are public: plugins may order their assets against them.
 * - creationell-wp-theme-main: compiled stylesheet from Stylesheet_Locator::main() in the head;
 *   WordPress swaps in theme-rtl.min.css for right-to-left languages once the package ships it.
 * - creationell-wp-theme-icons: the vendored Bootstrap Icons font, only with CreaBootstrapBlocks
 *   or when the filter creationell_wp_theme_load_icon_font returns true.
 * - creationell-wp-theme-child-style: style.css of the active child theme, after the main stylesheet.
 * - creationell-wp-theme-bootstrap: the vendored Bootstrap bundle (sets window.bootstrap), footer, defer.
 * - creationell-wp-theme-script: assets/js/theme.js, footer, defer, after the bundle.
 *
 * @since 1.0.0
 */
final class Assets {

	/**
	 * Handle of the compiled theme stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_MAIN = 'creationell-wp-theme-main';

	/**
	 * Handle of the Bootstrap Icons font.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_ICONS = 'creationell-wp-theme-icons';

	/**
	 * Handle of the style.css of the child theme.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_CHILD_STYLE = 'creationell-wp-theme-child-style';

	/**
	 * Handle of the Bootstrap bundle.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_BOOTSTRAP = 'creationell-wp-theme-bootstrap';

	/**
	 * Handle of the theme script.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE_SCRIPT = 'creationell-wp-theme-script';

	/**
	 * Vendored Bootstrap bundle with Popper, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const BOOTSTRAP_BUNDLE = 'assets/vendor/bootstrap-5/js/bootstrap.bundle.min.js';

	/**
	 * File with the exact version of the vendored Bootstrap, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const BOOTSTRAP_VERSION_FILE = 'assets/vendor/bootstrap-5/VERSION';

	/**
	 * Stylesheet of the vendored Bootstrap Icons font, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const ICONS_STYLESHEET = 'assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css';

	/**
	 * File with the exact version of the vendored Bootstrap Icons, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const ICONS_VERSION_FILE = 'assets/vendor/bootstrap-icons/VERSION';

	/**
	 * Theme script, relative to the theme folder.
	 *
	 * @since 1.0.0
	 */
	public const THEME_SCRIPT = 'assets/js/theme.js';

	/**
	 * Enqueues the front-end assets; runs on wp_enqueue_scripts with priority 5, before plugins on 10.
	 *
	 * The stylesheet version is the theme version plus the fingerprint of the file,
	 * so a rebuilt stylesheet reaches browsers without a new theme version. Both
	 * scripts load deferred in the footer; the theme script depends on the bundle.
	 * The core comment reply script loads on singular views with open, threaded
	 * comments.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_frontend(): void {
		$uri    = get_template_directory_uri();
		$footer = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);

		self::enqueue_main_style();
		self::enqueue_icon_font();
		if ( is_child_theme() ) {
			$child_version = wp_get_theme()->get( 'Version' );
			wp_enqueue_style( self::HANDLE_CHILD_STYLE, get_stylesheet_uri(), array( self::HANDLE_MAIN ), is_string( $child_version ) ? $child_version : false );
		}
		wp_enqueue_script( self::HANDLE_BOOTSTRAP, $uri . '/' . self::BOOTSTRAP_BUNDLE, array(), self::vendor_version( self::BOOTSTRAP_VERSION_FILE ), $footer );
		wp_enqueue_script( self::HANDLE_SCRIPT, $uri . '/' . self::THEME_SCRIPT, array( self::HANDLE_BOOTSTRAP ), Stylesheet_Locator::theme_version(), $footer );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Enqueues the main stylesheet; used by the front end and by the editor canvas.
	 *
	 * When the right-to-left counterpart exists next to the stylesheet, WordPress
	 * loads it instead for right-to-left languages ("rtl" = "replace", suffix
	 * ".min"). Without that file the stylesheet carries no RTL data, so no
	 * request ends in a missing file.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_main_style(): void {
		$main = Stylesheet_Locator::main();
		wp_enqueue_style( self::HANDLE_MAIN, $main['url'], array(), $main['version'] );
		if ( is_readable( Stylesheet_Locator::rtl_path( $main['path'] ) ) ) {
			wp_style_add_data( self::HANDLE_MAIN, 'rtl', 'replace' );
			wp_style_add_data( self::HANDLE_MAIN, 'suffix', '.min' );
		}
	}

	/**
	 * Enqueues the Bootstrap Icons font when it is needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_icon_font(): void {
		if ( ! self::load_icon_font() ) {
			return;
		}
		wp_enqueue_style( self::HANDLE_ICONS, get_template_directory_uri() . '/' . self::ICONS_STYLESHEET, array(), self::vendor_version( self::ICONS_VERSION_FILE ) );
	}

	/**
	 * Tells whether the Bootstrap Icons font loads.
	 *
	 * The templates use inline SVG icons; the font serves the blocks of
	 * CreaBootstrapBlocks, which rely on the theme for it.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with CreaBootstrapBlocks active or when the filter returns true.
	 */
	public static function load_icon_font(): bool {
		/**
		 * Filters whether the theme loads the Bootstrap Icons font.
		 *
		 * Only the boolean true switches the font on.
		 *
		 * Example: add_filter( 'creationell_wp_theme_load_icon_font', '__return_true' );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $load True when CreaBootstrapBlocks is active.
		 */
		return true === apply_filters( 'creationell_wp_theme_load_icon_font', Bootstrap_Bridge::cbb_active() );
	}

	/**
	 * Returns the version of a vendored library from its VERSION file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative VERSION file relative to the theme folder.
	 * @return string Version, or an empty string when the file is not readable.
	 */
	public static function vendor_version( string $relative ): string {
		$file    = get_template_directory() . '/' . $relative;
		$content = is_readable( $file ) ? file_get_contents( get_template_directory() . '/' . $relative ) : false;
		return false === $content ? '' : trim( $content );
	}
}
