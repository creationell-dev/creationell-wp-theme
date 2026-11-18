<?php
/**
 * Search, popular sections and the latest posts on the 404 page.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Blog\Blog_Url;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints the parts of the module on the 404 page, after its widget area and before the link home.
 *
 * In this order: a search form, unless the setting blog_404_search is off
 * or the widget area 404-page already holds a search widget or a search
 * block; the menu of the location 404-links while a menu is assigned; the
 * latest posts (setting blog_404_posts, 0 shows none) while a published post
 * exists, with a link to the blog page below them when the front page is a
 * static page. The template part template-parts/blog/404-content.php prints
 * them; the status code of the page stays 404. The callbacks read the
 * settings when they run.
 *
 * @since 1.0.0
 */
final class Not_Found {

	/**
	 * Menu location of the popular sections, registered by Blog_Module::boot().
	 *
	 * @since 1.0.0
	 */
	public const MENU_LOCATION = '404-links';

	/**
	 * Widget area of the 404 page.
	 *
	 * @since 1.0.0
	 */
	public const AREA = '404-page';

	/**
	 * Adds the parts after the widget area of the 404 page; Blog_Module::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'creationell_wp_theme_404_after_content', array( self::class, 'render' ), 10, 1 );
	}

	/**
	 * Prints the parts of the 404 page; runs on creationell_wp_theme_404_after_content.
	 *
	 * Prints template-parts/blog/404-content.php with the arguments search
	 * (bool), menu (bool), posts (entries: title, url, datetime, date),
	 * blog_url (URL of the blog page or empty) and context. Nothing when no
	 * part shows.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $context Template, here 404; anything other than a string gives an empty context.
	 * @return void
	 */
	public static function render( mixed $context = '' ): void {
		$search   = true === Blog_Module::setting( 'blog_404_search' ) && ! self::area_has_search();
		$menu     = has_nav_menu( self::MENU_LOCATION );
		$posts    = self::posts();
		$blog_url = array() !== $posts && 'page' === get_option( 'show_on_front' ) ? Blog_Url::url() : '';
		if ( ! $search && ! $menu && array() === $posts ) {
			return;
		}
		get_template_part(
			'template-parts/blog/404-content',
			null,
			array(
				'search'   => $search,
				'menu'     => $menu,
				'posts'    => $posts,
				'blog_url' => $blog_url,
				'context'  => is_string( $context ) ? $context : '',
			)
		);
	}

	/**
	 * Tells whether the widget area of the 404 page holds a search widget or a search block, also inside another block.
	 *
	 * Reads the widgets of the area from the option sidebars_widgets and the
	 * content of the block widgets from the option widget_block.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with a search in the widget area.
	 */
	public static function area_has_search(): bool {
		$areas   = get_option( 'sidebars_widgets', array() );
		$widgets = is_array( $areas ) && is_array( $areas[ self::AREA ] ?? null ) ? $areas[ self::AREA ] : array();
		$blocks  = null;
		foreach ( $widgets as $widget ) {
			if ( ! is_string( $widget ) ) {
				continue;
			}
			if ( 1 === preg_match( '/^search-\d+$/', $widget ) ) {
				return true;
			}
			if ( 1 !== preg_match( '/^block-(\d+)$/', $widget, $match ) ) {
				continue;
			}
			$blocks  = $blocks ?? get_option( 'widget_block', array() );
			$content = is_array( $blocks ) && is_array( $blocks[ (int) $match[1] ] ?? null ) ? ( $blocks[ (int) $match[1] ]['content'] ?? null ) : null;
			if ( is_string( $content ) && self::holds_search( parse_blocks( $content ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Tells whether parsed blocks or their inner blocks contain the search block.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int|string, mixed> $blocks Blocks of parse_blocks().
	 * @return bool True with a search block.
	 */
	private static function holds_search( array $blocks ): bool {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( 'core/search' === ( $block['blockName'] ?? null ) ) {
				return true;
			}
			if ( is_array( $block['innerBlocks'] ?? null ) && self::holds_search( $block['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the latest published posts for the list, as many as the setting blog_404_posts says.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{title: string, url: string, datetime: string, date: string}> Entries; plain titles, empty for a post without a title.
	 */
	private static function posts(): array {
		$count = Blog_Module::setting( 'blog_404_posts' );
		if ( ! is_int( $count ) || $count < 1 || ! Blog_Url::has_posts() ) {
			return array();
		}
		$ids     = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => $count,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		$entries = array();
		foreach ( $ids as $id ) {
			$id   = (int) $id;
			$link = get_permalink( $id );
			if ( $id < 1 || ! is_string( $link ) || '' === $link ) {
				continue;
			}
			$entries[] = array(
				'title'    => trim( html_entity_decode( wp_strip_all_tags( get_the_title( $id ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ),
				'url'      => $link,
				'datetime' => (string) get_the_date( DATE_W3C, $id ),
				'date'     => (string) get_the_date( '', $id ),
			);
		}
		return $entries;
	}
}
