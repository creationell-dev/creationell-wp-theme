<?php
/**
 * Hooks and settings of the module blog.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Blog\Blog_Context;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;
use WP_Post;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds the blog parts to the posts of the site: sidebar, meta line, back link, terms, share links, 404, author pages and their profile fields.
 *
 * The file bootstrap.php calls boot() once, while the module is active. The
 * module gate runs it on after_setup_theme (priority 20), before the settings
 * registry loads on init (priority 10): setting() is valid from init 10 on, so
 * boot() registers its hooks unconditionally and the callbacks read the
 * settings when they run.
 *
 * @since 1.0.0
 */
final class Blog_Module {

	/**
	 * Handle of the stylesheet and prefix of the script handles of the module.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-blog';

	/**
	 * Stylesheet of the module, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const STYLESHEET = 'assets/blog.css';

	/**
	 * Script of the copy and share buttons of the own share links, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const SHARE_SCRIPT = 'assets/share.js';

	/**
	 * Handle of the compiled theme stylesheet, loaded before the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const THEME_STYLE_HANDLE = 'creationell-wp-theme-main';

	/**
	 * Settings of the module with the values used while they are not registered.
	 *
	 * @since 1.0.0
	 */
	public const SETTINGS = array(
		'blog_sidebar_terms'     => 'both',
		'blog_sidebar_counts'    => true,
		'blog_sidebar_mobile'    => 'below',
		'blog_category_badge'    => true,
		'blog_meta_date'         => true,
		'blog_meta_reading_time' => true,
		'blog_meta_author'       => true,
		'blog_meta_comments'     => true,
		'blog_reading_wpm'       => 200,
		'blog_back_link'         => true,
		'blog_entry_terms'       => 'both',
		'blog_share'             => 'auto',
		'blog_share_networks'    => 'linkedin,facebook,whatsapp,email,copy',
		'blog_404_posts'         => 3,
		'blog_404_search'        => true,
		'blog_author_pages'      => false,
		'blog_label_categories'  => '',
		'blog_label_tags'        => '',
		'blog_back_link_text'    => '',
		'blog_share_title'       => '',
	);

	/**
	 * Adds the hooks of the module; bootstrap.php calls it once with the effective state.
	 *
	 * The hooks are added whatever the settings say; their callbacks read the
	 * settings, which are valid from init 10 on. The menu location 404-links
	 * and the doctor section blog exist only while the module is active.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state Effective state of the module: active.
	 * @return void
	 */
	public static function boot( string $state ): void {
		if ( 'active' !== $state ) {
			return;
		}
		Blog_Home::register();
		Blog_Sidebar::register();
		Entry_Parts::register();
		Share_Links::register();
		register_nav_menu( Not_Found::MENU_LOCATION, __( '404 page links', 'creationell-wp-theme' ) );
		Not_Found::register();
		Author_Pages::register();
		Author_Profile_Fields::register();
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_front' ), 20, 0 );
		add_action( 'cli_init', array( Blog_Doctor::class, 'add_doctor_section' ), 10, 0 );
	}

	/**
	 * Enqueues the module stylesheet after the theme stylesheet in the blog view and on the 404 page, and the share script on a single post with own share links; runs on wp_enqueue_scripts (priority 20).
	 *
	 * The share script loads deferred in the footer, without dependencies,
	 * only while Share_Links::mode() gives links for the queried post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_front(): void {
		if ( is_admin() ) {
			return;
		}
		if ( Blog_Context::is_blog_view() || is_404() ) {
			wp_enqueue_style( self::HANDLE, CREATIONELL_WP_THEME_URL . '/modules/blog/' . self::STYLESHEET, array( self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
		}
		$post = is_singular( 'post' ) ? get_queried_object() : null;
		if ( $post instanceof WP_Post && 'links' === Share_Links::mode( $post ) ) {
			wp_enqueue_script(
				self::HANDLE . '-share',
				CREATIONELL_WP_THEME_URL . '/modules/blog/' . self::SHARE_SCRIPT,
				array(),
				CREATIONELL_WP_THEME_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	/**
	 * Returns a setting of the module, or its default while the setting is not registered.
	 *
	 * Valid from init 10 on; before that the registry does not know the
	 * settings and the default of SETTINGS comes back.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key One of the keys of SETTINGS.
	 * @return mixed Value; null for a key the module does not know.
	 */
	public static function setting( string $key ): mixed {
		if ( ! Registry::instance()->has( $key ) ) {
			return self::SETTINGS[ $key ] ?? null;
		}
		return Settings::instance()->get( $key );
	}

	/**
	 * Returns a text setting of the module, trimmed, or the fallback when it is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key      Text setting, for example blog_label_tags.
	 * @param string $fallback Translated default text.
	 * @return string Text.
	 */
	public static function text( string $key, string $fallback ): string {
		$text = self::setting( $key );
		$text = is_string( $text ) ? trim( $text ) : '';
		return '' === $text ? $fallback : $text;
	}
}
