<?php
/**
 * Access, byline link, sitemap and header data of the author pages.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Settings\Registry;
use WP_Query;
use WP_User;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lets author archives through only as public author pages and keeps the logins of other users private.
 *
 * While the setting blog_author_pages is off, every author archive (also
 * ?author=N, later pages and the feed) becomes a 404 page, the byline shows
 * the name without a link and WordPress drops its users sitemap. While it is
 * on, the same happens for every user who is not public: a public author has
 * at least one published post. The guard runs on template_redirect with
 * priority 1, before redirect_canonical() (priority 10) could redirect
 * ?author=N to the address with the nice name, and already on wp with
 * priority 1, before SEO plugins pick their page type there (Rank Math on wp
 * with priority 10) and would print the name or the nice name of the user in
 * the head of the 404 page. The callbacks read the settings when they run.
 *
 * @since 1.0.0
 */
final class Author_Pages {

	/**
	 * User meta with the attachment ID of the profile image.
	 *
	 * @since 1.0.0
	 */
	public const META_IMAGE = 'creationell_wp_theme_author_image';

	/**
	 * User meta with the job title.
	 *
	 * @since 1.0.0
	 */
	public const META_JOB_TITLE = 'creationell_wp_theme_author_job_title';

	/**
	 * User meta with the external profiles, one URL per line.
	 *
	 * @since 1.0.0
	 */
	public const META_PROFILES = 'creationell_wp_theme_author_profiles';

	/**
	 * Adds the guard, the byline filter and the sitemap filters; Blog_Module::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp', array( self::class, 'guard' ), 1, 0 );
		add_action( 'template_redirect', array( self::class, 'guard' ), 1, 0 );
		add_filter( 'creationell_wp_theme_meta_author_link', array( self::class, 'link' ), 10, 2 );
		add_filter( 'wp_sitemaps_add_provider', array( self::class, 'sitemap_provider' ), 10, 2 );
		add_filter( 'wp_sitemaps_users_query_args', array( self::class, 'sitemap_users_query_args' ), 10, 1 );
	}

	/**
	 * Tells whether the author pages are on (setting blog_author_pages).
	 *
	 * Boots the settings registry first while it does not know the setting
	 * yet: WordPress builds its sitemap providers on init 10 before the
	 * theme boots the registry there (without ACF Pro nothing boots it
	 * earlier), and the default would drop the users sitemap of a site with
	 * author pages on. Registry::boot() runs once per request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True while the author pages are on.
	 */
	public static function enabled(): bool {
		if ( ! Registry::instance()->has( 'blog_author_pages' ) ) {
			Registry::boot();
		}
		return true === Blog_Module::setting( 'blog_author_pages' );
	}

	/**
	 * Tells whether a user has a public author page: at least one published post, unless the filter says otherwise.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user User.
	 * @return bool True for a public author.
	 */
	public static function is_public( WP_User $user ): bool {
		/**
		 * Filters whether a user counts as a public author with an author page.
		 *
		 * Without the filter a user with at least one published post is
		 * public; any other user gets the 404 page on the author address.
		 *
		 * @since 1.0.0
		 *
		 * @param bool    $is_public Whether the user has a published post.
		 * @param WP_User $user      User.
		 */
		return (bool) apply_filters( 'creationell_wp_theme_author_is_public', (int) count_user_posts( $user->ID, 'post', true ) > 0, $user );
	}

	/**
	 * Turns an author archive into a 404 page while the pages are off or the user is not public; runs on wp and on template_redirect (priority 1 each).
	 *
	 * Sets the main query to 404 (is_author() is false afterwards, so
	 * redirect_canonical() does not redirect, a second run does nothing and an
	 * SEO plugin that looks at the query on wp later sees a 404 page instead of
	 * the author archive), forgets the user as queried object, so that nothing
	 * in the head tells the user from a missing one, sends the status 404 and the
	 * headers against caching. For a feed it also clears is_feed, so that the
	 * template loader renders the 404 template, and replaces the feed content
	 * type that WP::send_headers() sent with text/html.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function guard(): void {
		// In the admin wp_edit_posts_query() runs wp() as well; edit.php?author=N stays a post list.
		if ( is_admin() || ! is_author() ) {
			return;
		}
		$user = get_queried_object();
		if ( self::enabled() && $user instanceof WP_User && self::is_public( $user ) ) {
			return;
		}
		$query = $GLOBALS['wp_query'] ?? null;
		if ( ! $query instanceof WP_Query ) {
			return;
		}
		$feed = is_feed();
		$query->set_404();
		// set_404() keeps the user as queried object; plugins in the head (WPML hreflang) would tell the user from a missing one.
		$query->queried_object    = null;
		$query->queried_object_id = null;
		status_header( 404 );
		nocache_headers();
		if ( ! $feed ) {
			return;
		}
		$query->is_feed = false;
		if ( ! headers_sent() ) {
			$charset = get_option( 'blog_charset' );
			header( 'Content-Type: text/html; charset=' . ( is_string( $charset ) && '' !== $charset ? $charset : 'UTF-8' ) );
		}
	}

	/**
	 * Keeps the link of the byline only for a public author page; runs on creationell_wp_theme_meta_author_link.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $link    Whether to link the author name.
	 * @param mixed $user_id ID of the author; anything other than an ID gives no link.
	 * @return bool True when the name links to the author page.
	 */
	public static function link( mixed $link, mixed $user_id = 0 ): bool {
		$user_id = is_int( $user_id ) || ( is_string( $user_id ) && ctype_digit( $user_id ) ) ? (int) $user_id : 0;
		if ( ! $link || ! self::enabled() ) {
			return false;
		}
		$user = get_userdata( $user_id );
		return $user instanceof WP_User && self::is_public( $user );
	}

	/**
	 * Drops the users sitemap of WordPress while the author pages are off; runs on wp_sitemaps_add_provider.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $provider Sitemap provider.
	 * @param string $name     Name of the provider.
	 * @return mixed False for the users provider while the pages are off, else the provider.
	 */
	public static function sitemap_provider( mixed $provider, string $name ): mixed {
		return 'users' === $name && ! self::enabled() ? false : $provider;
	}

	/**
	 * Limits the users sitemap to users with published posts of the type post while the author pages are on; runs on wp_sitemaps_users_query_args.
	 *
	 * WordPress lists every user with a published post of any public type,
	 * so a user with only pages would appear with an address that gives 404.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args Arguments of the user query.
	 * @return mixed Arguments.
	 */
	public static function sitemap_users_query_args( mixed $args ): mixed {
		if ( ! is_array( $args ) || ! self::enabled() ) {
			return $args;
		}
		$args['has_published_posts'] = array( 'post' );
		return $args;
	}

	/**
	 * Returns the arguments of the header part template-parts/blog/author-header.php for a user.
	 *
	 * Keys: name (display name), image_id (attachment ID of the profile image,
	 * 0 for none), image_size (filter creationell_wp_theme_author_image_size,
	 * default medium), job_title, bio (the biographical info as WordPress
	 * returns it, raw), links (entries with url and text: the website first,
	 * then the external profiles; only http and https, each URL once) and
	 * context (author).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user Author.
	 * @return array{name: string, image_id: int, image_size: string|array<int, int>, job_title: string, bio: string, links: list<array{url: string, text: string}>, context: string} Arguments.
	 */
	public static function header_args( WP_User $user ): array {
		$id    = (int) $user->ID;
		$image = get_user_meta( $id, self::META_IMAGE, true );
		$job   = get_user_meta( $id, self::META_JOB_TITLE, true );
		$bio   = get_the_author_meta( 'description', $id );

		/**
		 * Filters the image size of the profile image on the author page.
		 *
		 * @since 1.0.0
		 *
		 * @param string|array<int, int> $size Registered image size or width and height; default medium.
		 * @param WP_User                $user Author.
		 */
		$size = apply_filters( 'creationell_wp_theme_author_image_size', 'medium', $user );

		return array(
			'name'       => (string) $user->display_name,
			'image_id'   => is_numeric( $image ) ? absint( $image ) : 0,
			'image_size' => ( is_string( $size ) && '' !== $size ) || self::is_size( $size ) ? $size : 'medium',
			'job_title'  => is_string( $job ) ? trim( $job ) : '',
			'bio'        => trim( $bio ),
			'links'      => self::links( $user ),
			'context'    => 'author',
		);
	}

	/**
	 * Tells whether a value is an image size given as width and height.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $size Value.
	 * @return bool True for a list of two integers.
	 *
	 * @phpstan-assert-if-true array<int, int> $size
	 */
	private static function is_size( mixed $size ): bool {
		return is_array( $size ) && 2 === count( $size ) && array_is_list( $size ) && is_int( $size[0] ) && is_int( $size[1] );
	}

	/**
	 * Returns the website and the external profiles of a user as links: http and https only, each URL once, the text host and path without scheme and www.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user User.
	 * @return list<array{url: string, text: string}> Links.
	 */
	private static function links( WP_User $user ): array {
		$profiles = get_user_meta( (int) $user->ID, self::META_PROFILES, true );
		$urls     = array_merge( array( (string) $user->user_url ), is_string( $profiles ) ? explode( "\n", $profiles ) : array() );
		$links    = array();
		$seen     = array();
		foreach ( $urls as $url ) {
			$url   = trim( $url );
			$parts = '' === $url || isset( $seen[ $url ] ) ? false : wp_parse_url( $url );
			if ( ! is_array( $parts ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) || '' === ( $parts['host'] ?? '' ) ) {
				continue;
			}
			$seen[ $url ] = true;
			$links[]      = array(
				'url'  => $url,
				'text' => rtrim( (string) preg_replace( '~^www\.~i', '', $parts['host'] ) . ( $parts['path'] ?? '' ), '/' ),
			);
		}
		return $links;
	}
}
