<?php
/**
 * Switch of the blog templates home.php and author.php.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Blog;

use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells home.php and author.php whether to render themselves or the template of the older type.
 *
 * The blog page falls back to index.php and the author archive to
 * archive.php while the module blog is not active, for the author archive
 * also while the setting blog_author_pages is off, and whenever a child theme
 * has its own index.php or archive.php but not its own home.php or author.php.
 * The class registers no hooks.
 *
 * @since 1.0.0
 */
final class Blog_Templates {

	/**
	 * Template of the older type for each template of the switch.
	 *
	 * @since 1.0.0
	 */
	private const FALLBACKS = array(
		'home'   => 'index.php',
		'author' => 'archive.php',
	);

	/**
	 * Returns the template to require instead of the new template, or '' to render the new template.
	 *
	 * Example (the first statement of home.php):
	 *
	 *     $creationell_wp_theme_fallback = \Creationell\WpTheme\Blog\Blog_Templates::fallback( 'home' );
	 *     if ( '' !== $creationell_wp_theme_fallback ) {
	 *         require $creationell_wp_theme_fallback;
	 *         return;
	 *     }
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Template of the switch: home or author.
	 * @return string Absolute path of index.php (home) or archive.php (author) as located by WordPress; '' to render the new template.
	 */
	public static function fallback( string $template ): string {
		if ( ! isset( self::FALLBACKS[ $template ] ) ) {
			return '';
		}
		if ( 'active' !== creationell_wp_theme_module_state( 'blog' ) || ( 'author' === $template && ! self::author_pages() ) || self::child_overrides( $template ) ) {
			return locate_template( self::FALLBACKS[ $template ] );
		}
		return '';
	}

	/**
	 * Tells whether the child theme keeps the template of the older type in front of the new template.
	 *
	 * True while a child theme is active that has its own index.php (for
	 * home) or archive.php (for author) and not its own home.php or author.php.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Template of the switch: home or author.
	 * @return bool True when the template of the child wins.
	 */
	public static function child_overrides( string $template ): bool {
		if ( ! isset( self::FALLBACKS[ $template ] ) || ! is_child_theme() ) {
			return false;
		}
		$dir = get_stylesheet_directory(); // creationell-allow-stylesheet: a child template of the older type must keep its priority.
		return is_file( $dir . '/' . self::FALLBACKS[ $template ] ) && ! is_file( $dir . '/' . $template . '.php' );
	}

	/**
	 * Tells whether the setting blog_author_pages is on; false while it is not registered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when author pages are on.
	 */
	private static function author_pages(): bool {
		return Registry::instance()->has( 'blog_author_pages' ) && (bool) Settings::instance()->get( 'blog_author_pages' );
	}
}
