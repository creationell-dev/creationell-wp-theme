<?php
/**
 * Head and foot of a post: meta line, back link, terms and the category badge.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Blog\Blog_Url;
use WP_Post;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Supplies the meta line, the link back to the blog and the terms below a single post.
 *
 * The meta line (template-parts/blog/entry-meta.php) shows date, reading
 * time, author and comments, each switched by its setting. The back link
 * (template-parts/blog/back-link.php) opens the post header. The terms
 * (template-parts/blog/entry-terms.php) replace the tags below the post. The
 * setting blog_category_badge hides the badge above the title of the four
 * single post templates. While the module answers, the meta line and the
 * terms are never empty: a line break stands for "nothing", so the template
 * neither prints its own markup nor an empty paragraph. The callbacks read
 * the settings when they run.
 *
 * @since 1.0.0
 */
final class Entry_Parts {

	/**
	 * Contexts of the four single post templates.
	 *
	 * @since 1.0.0
	 */
	public const SINGLE_CONTEXTS = array( 'single', 'single-sidebar-left', 'single-sidebar-none', 'single-full-width-image' );

	/**
	 * Parts of the meta line in their order, with the setting that switches each.
	 *
	 * @since 1.0.0
	 */
	private const META_PARTS = array(
		'date'         => 'blog_meta_date',
		'reading_time' => 'blog_meta_reading_time',
		'author'       => 'blog_meta_author',
		'comments'     => 'blog_meta_comments',
	);

	/**
	 * Answer of the meta and terms filters for "nothing to print": not empty, so the template prints none of its own.
	 *
	 * @since 1.0.0
	 */
	private const NOTHING = "\n";

	/**
	 * Adds the filters and the action of the post head and foot; Blog_Module::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'creationell_wp_theme_entry_meta_html', array( self::class, 'meta_html' ), 10, 2 );
		add_filter( 'creationell_wp_theme_entry_terms_html', array( self::class, 'terms_html' ), 10, 2 );
		add_filter( 'creationell_wp_theme_show_category_badge', array( self::class, 'show_category_badge' ), 10, 2 );
		add_action( 'creationell_wp_theme_entry_header_open', array( self::class, 'back_link' ), 10, 1 );
	}

	/**
	 * Returns the meta line of a single post; runs on creationell_wp_theme_entry_meta_html.
	 *
	 * Prints template-parts/blog/entry-meta.php into a buffer with the
	 * arguments parts (enabled parts in order), minutes and comments_number.
	 * The author part needs the filter creationell_wp_theme_meta_author and a
	 * name; the reading time needs no password, so a protected post does not
	 * tell the length of its text; the comments part needs open comments or at
	 * least one comment, and no password.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $html    Markup so far; a non-empty markup stays.
	 * @param mixed $context Template, for example single or single-sidebar-left.
	 * @return mixed Markup of the meta line, a line break for none, or the markup so far outside a single post.
	 */
	public static function meta_html( mixed $html, mixed $context = '' ): mixed {
		if ( ( is_string( $html ) && '' !== $html ) || ! is_singular( 'post' ) ) {
			return $html;
		}
		$parts = array();
		foreach ( self::META_PARTS as $part => $key ) {
			if ( true === Blog_Module::setting( $key ) && self::meta_part_shows( $part ) ) {
				$parts[] = $part;
			}
		}
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			$parts = array_values( array_diff( $parts, array( 'reading_time' ) ) );
		}
		if ( array() === $parts ) {
			return self::NOTHING;
		}
		return self::buffer(
			'template-parts/blog/entry-meta',
			array(
				'parts'           => $parts,
				'minutes'         => $post instanceof WP_Post && in_array( 'reading_time', $parts, true ) ? creationell_wp_theme_blog_reading_time( $post ) : 1,
				'comments_number' => (int) get_comments_number(),
				'context'         => is_string( $context ) ? $context : '',
			)
		);
	}

	/**
	 * Returns the categories and tags below the queried single post; runs on creationell_wp_theme_entry_terms_html.
	 *
	 * Prints template-parts/blog/entry-terms.php into a buffer with the rows
	 * the setting blog_entry_terms selects; an empty row drops. The setting
	 * off, or no row with terms, gives a line break, so the old tag block does
	 * not come back.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $html    Markup so far; a non-empty markup stays.
	 * @param mixed $context Template, for example single or single-sidebar-left.
	 * @return mixed Markup of the terms, a line break for none, or the markup so far outside the queried post.
	 */
	public static function terms_html( mixed $html, mixed $context = '' ): mixed {
		if ( ( is_string( $html ) && '' !== $html ) || ! is_singular( 'post' ) || get_the_ID() !== get_queried_object_id() ) {
			return $html;
		}
		$setting = Blog_Module::setting( 'blog_entry_terms' );
		if ( ! in_array( $setting, array( 'both', 'categories', 'tags' ), true ) ) {
			return self::NOTHING;
		}
		$categories = 'tags' === $setting ? array() : self::categories();
		$tags       = 'categories' === $setting ? array() : self::tags();
		if ( array() === $categories && array() === $tags ) {
			return self::NOTHING;
		}
		return self::buffer(
			'template-parts/blog/entry-terms',
			array(
				'categories'       => $categories,
				'tags'             => $tags,
				'label_categories' => Blog_Module::text( 'blog_label_categories', __( 'Categories', 'creationell-wp-theme' ) ),
				'label_tags'       => Blog_Module::text( 'blog_label_tags', __( 'Tags', 'creationell-wp-theme' ) ),
				'context'          => is_string( $context ) ? $context : '',
			)
		);
	}

	/**
	 * Hides the category badge above the title of the single post templates when the setting says so; runs on creationell_wp_theme_show_category_badge.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $show    Answer so far.
	 * @param mixed $context Template; empty in cards and post lists.
	 * @return mixed False in a single post template with the setting off, else the answer so far.
	 */
	public static function show_category_badge( mixed $show, mixed $context = '' ): mixed {
		if ( in_array( $context, self::SINGLE_CONTEXTS, true ) && true !== Blog_Module::setting( 'blog_category_badge' ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * Prints the link back to the blog in the header of a single post; runs on creationell_wp_theme_entry_header_open.
	 *
	 * Prints template-parts/blog/back-link.php with the arguments url and
	 * text, or nothing with the setting off or without a blog page.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $context Template, for example single or single-sidebar-left.
	 * @return void
	 */
	public static function back_link( mixed $context = '' ): void {
		if ( ! is_singular( 'post' ) || true !== Blog_Module::setting( 'blog_back_link' ) ) {
			return;
		}
		$url = Blog_Url::url();
		if ( '' === $url ) {
			return;
		}
		get_template_part(
			'template-parts/blog/back-link',
			null,
			array(
				'url'     => $url,
				'text'    => self::back_link_text(),
				'context' => is_string( $context ) ? $context : '',
			)
		);
	}

	/**
	 * Returns the text of the back link: the own text of the setting with {title}, else "Back to {title}", else "Back to the blog".
	 *
	 * @since 1.0.0
	 *
	 * @return string Plain text.
	 */
	private static function back_link_text(): string {
		$title = Blog_Url::title();
		$text  = trim( str_replace( '{title}', $title, Blog_Module::text( 'blog_back_link_text', '' ) ) );
		if ( '' !== $text ) {
			return $text;
		}
		if ( '' !== $title ) {
			/* translators: %s: title of the blog page. */
			return sprintf( __( 'Back to %s', 'creationell-wp-theme' ), $title );
		}
		return __( 'Back to the blog', 'creationell-wp-theme' );
	}

	/**
	 * Tells whether a part of the meta line has something to show, apart from its setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $part One of the keys of META_PARTS.
	 * @return bool True when the part shows.
	 */
	private static function meta_part_shows( string $part ): bool {
		if ( 'author' === $part ) {
			/**
			 * Filters whether the post meta shows the author.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $show Whether to show the author.
			 */
			return (bool) apply_filters( 'creationell_wp_theme_meta_author', true ) && '' !== trim( (string) get_the_author() );
		}
		if ( 'reading_time' === $part ) {
			return ! post_password_required();
		}
		if ( 'comments' === $part ) {
			return ! post_password_required() && ( comments_open() || (int) get_comments_number() > 0 );
		}
		return true;
	}

	/**
	 * Returns the categories of the current post as name and link.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{name: string, url: string}> Categories; a category without a link is left out.
	 */
	private static function categories(): array {
		$items = array();
		foreach ( get_the_category() as $category ) {
			$url = get_category_link( (int) $category->term_id );
			if ( '' !== $url ) {
				$items[] = array(
					'name' => (string) $category->name,
					'url'  => $url,
				);
			}
		}
		return $items;
	}

	/**
	 * Returns the tags of the current post as name and link.
	 *
	 * @since 1.0.0
	 *
	 * @return list<array{name: string, url: string}> Tags; a tag without a link is left out.
	 */
	private static function tags(): array {
		$tags = get_the_tags();
		if ( ! is_array( $tags ) ) {
			return array();
		}
		$items = array();
		foreach ( $tags as $tag ) {
			$url = get_term_link( $tag, 'post_tag' );
			if ( is_string( $url ) ) {
				$items[] = array(
					'name' => $tag->name,
					'url'  => $url,
				);
			}
		}
		return $items;
	}

	/**
	 * Prints a template part into a buffer and returns it; a part that prints nothing gives a line break.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $slug Template part.
	 * @param array<string, mixed> $args Arguments of the part.
	 * @return string Markup.
	 */
	private static function buffer( string $slug, array $args ): string {
		ob_start();
		get_template_part( $slug, null, $args );
		$html = (string) ob_get_clean();
		return '' === $html ? self::NOTHING : $html;
	}
}
