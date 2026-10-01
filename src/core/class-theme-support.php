<?php
/**
 * Theme supports, template mode and content width.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Declares the theme supports on after_setup_theme with priority 2.
 *
 * Template mode (M0): the theme renders its PHP templates and never block
 * templates. Because the theme has a theme.json, WordPress adds the support
 * "block-templates" on after_setup_theme with priority 1
 * (wp_enable_block_templates()); with it, a wp_template saved in the database
 * would replace a PHP template such as single.php, and the Site Editor would
 * offer templates without header and footer. setup() runs on priority 2 and
 * removes that support again. It adds "block-template-parts", so the header and
 * footer template part areas declared in theme.json stay editable in the Site
 * Editor. Core block styles ("wp-block-styles") stay off; Bootstrap styles the
 * blocks.
 *
 * @since 1.0.0
 */
final class Theme_Support {

	/**
	 * Markup types that WordPress outputs as HTML5.
	 *
	 * @since 1.0.0
	 */
	public const HTML5 = array( 'comment-form', 'comment-list', 'search-form', 'gallery', 'caption', 'script', 'style' );

	/**
	 * Content width in pixels unless the filter creationell_wp_theme_content_width changes it.
	 *
	 * @since 1.0.0
	 */
	public const CONTENT_WIDTH = 640;

	/**
	 * Removes block templates, adds the theme supports and sets the content width.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function setup(): void {
		remove_theme_support( 'block-templates' );
		add_theme_support( 'block-template-parts' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', self::HTML5 );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		self::set_content_width();
	}

	/**
	 * Sets the global $content_width, used by WordPress for embeds and image sizes.
	 *
	 * @since 1.0.0
	 *
	 * @global int $content_width
	 *
	 * @return void
	 */
	private static function set_content_width(): void {
		/**
		 * Filters the content width in pixels.
		 *
		 * @since 1.0.0
		 *
		 * @param int $width Content width in pixels.
		 */
		$width = apply_filters( 'creationell_wp_theme_content_width', self::CONTENT_WIDTH );

		$GLOBALS['content_width'] = is_int( $width ) ? $width : self::CONTENT_WIDTH;
	}
}
