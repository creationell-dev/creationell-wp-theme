<?php
/**
 * Blog context of a request.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Blog;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells whether the request shows the blog.
 *
 * The blog page, a single post, a category, tag or date archive, and an
 * author archive while the main query asks for posts are blog views. Search
 * results are none, also when the search is limited to a category or an
 * author. The class registers no hooks.
 *
 * @since 1.0.0
 */
final class Blog_Context {

	/**
	 * Tells whether the request is a blog view.
	 *
	 * Example:
	 *
	 *     if ( Blog_Context::is_blog_view() ) {
	 *         // Show the categories in the sidebar.
	 *     }
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for a blog view.
	 */
	public static function is_blog_view(): bool {
		$is_blog_view = ! is_search() && (
			is_home()
			|| is_singular( 'post' )
			|| is_category()
			|| is_tag()
			|| is_date()
			|| ( is_author() && in_array( get_query_var( 'post_type' ), array( '', 'post' ), true ) )
		);

		/**
		 * Filters whether the request is a blog view.
		 *
		 * Only true counts; any other answer gives false.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_is_blog_view',
		 *         static fn( bool $is_blog_view ): bool => $is_blog_view || is_post_type_archive( 'podcast' )
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $is_blog_view True for the blog page, a single post, a category, tag, date or post author archive; false for search results.
		 */
		return true === apply_filters( 'creationell_wp_theme_is_blog_view', $is_blog_view );
	}
}
