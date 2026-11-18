<?php
/**
 * URL and title of the blog page.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Blog;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Finds the blog page from the reading options.
 *
 * With a static front page ("show_on_front" = "page") the blog is the posts
 * page of "page_for_posts", mapped to the language of the request with the
 * WPML filter wpml_object_id; with posts on the front page it is the home
 * URL. Without a posts page there is no blog page. has_posts() asks once per
 * request whether a published post exists. The class registers no hooks.
 *
 * @since 1.0.0
 */
final class Blog_Url {

	/**
	 * Answer of has_posts() for this request, null before the first call.
	 *
	 * @since 1.0.0
	 *
	 * @var bool|null
	 */
	private static ?bool $has_posts = null;

	/**
	 * Returns the URL of the blog page.
	 *
	 * Example:
	 *
	 *     $url = Blog_Url::url(); // "https://example.com/blog/" or ""
	 *
	 * @since 1.0.0
	 *
	 * @return string URL; empty without a blog page.
	 */
	public static function url(): string {
		$url = '';
		if ( 'posts' === get_option( 'show_on_front' ) ) {
			$url = home_url( '/' );
		} else {
			$id = self::page_id();
			if ( $id > 0 ) {
				$link = get_permalink( $id );
				$url  = is_string( $link ) ? $link : '';
			}
		}

		/**
		 * Filters the URL of the blog page, used by the link back to the blog.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_blog_url',
		 *         static fn( string $url ): string => home_url( '/news/' )
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param string $url URL of the posts page, the home URL with posts on the front page, or an empty string for none.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_blog_url', $url );
		return is_string( $filtered ) ? $filtered : '';
	}

	/**
	 * Returns the title of the posts page as plain text.
	 *
	 * Tags are removed and character references decoded, so the caller
	 * escapes the title for its output.
	 *
	 * @since 1.0.0
	 *
	 * @return string Title; empty without a posts page and with posts on the front page.
	 */
	public static function title(): string {
		$id = self::page_id();
		if ( $id < 1 ) {
			return '';
		}
		return trim( html_entity_decode( wp_strip_all_tags( get_the_title( $id ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Tells whether at least one post is published; asked once per request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when a published post exists.
	 */
	public static function has_posts(): bool {
		if ( null === self::$has_posts ) {
			$posts           = get_posts(
				array(
					'post_type'        => 'post',
					'post_status'      => 'publish',
					'numberposts'      => 1,
					'fields'           => 'ids',
					'suppress_filters' => false,
				)
			);
			self::$has_posts = array() !== $posts;
		}
		return self::$has_posts;
	}

	/**
	 * Forgets the answer of has_posts(); for tests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$has_posts = null;
	}

	/**
	 * Returns the ID of the posts page in the language of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return int Page ID; 0 without a static front page or without a posts page.
	 */
	private static function page_id(): int {
		if ( 'page' !== get_option( 'show_on_front' ) ) {
			return 0;
		}
		$id = absint( get_option( 'page_for_posts' ) );
		if ( $id < 1 ) {
			return 0;
		}
		$translated = apply_filters( 'wpml_object_id', $id, 'page', true );
		return is_numeric( $translated ) ? absint( $translated ) : 0;
	}
}
