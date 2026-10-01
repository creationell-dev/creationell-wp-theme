<?php
/**
 * Query arguments of the related posts.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Posts;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the WP_Query arguments for the posts related to a post.
 *
 * Related posts have the same post type and share at least one term of the
 * chosen taxonomies (OR); the post itself is left out, the newest come first.
 * A post without terms, or of a type outside the setting
 * related_posts_post_types, has no related posts. The class registers no
 * hooks; the filter creationell_wp_theme_post_query_args runs last with the
 * context related-posts.
 *
 * @since 1.0.0
 */
final class Related_Query {

	/**
	 * Taxonomies per value of the setting related_posts_taxonomies; the first key is the default.
	 *
	 * @since 1.0.0
	 */
	public const TAXONOMIES = array(
		'category' => array( 'category' ),
		'post_tag' => array( 'post_tag' ),
		'both'     => array( 'category', 'post_tag' ),
	);

	/**
	 * Number of related posts when the setting is missing or invalid.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_COUNT = 3;

	/**
	 * Highest number of related posts.
	 *
	 * @since 1.0.0
	 */
	public const MAX_COUNT = 12;

	/**
	 * Returns the WP_Query arguments of the posts related to a post, or null when there are none to look for.
	 *
	 * Example:
	 *
	 *     $args = Related_Query::args( get_the_ID(), array(
	 *         'related_posts_post_types' => 'post',
	 *         'related_posts_taxonomies' => 'both',
	 *         'related_posts_count'      => 3,
	 *     ) );
	 *
	 * @since 1.0.0
	 *
	 * @param int                  $post_id  Post ID.
	 * @param array<string, mixed> $settings related_posts_post_types (comma list or array), related_posts_taxonomies (category, post_tag or both), related_posts_count (1 to 12).
	 * @return array<string, mixed>|null WP_Query arguments; null without terms, for an unknown post or a type that is not enabled.
	 */
	public static function args( int $post_id, array $settings ): ?array {
		$type = get_post_type( $post_id );
		if ( ! is_string( $type ) || ! in_array( $type, self::post_types( $settings['related_posts_post_types'] ?? 'post' ), true ) ) {
			return null;
		}
		$mode       = Query_Args::choice( $settings['related_posts_taxonomies'] ?? '' );
		$taxonomies = self::TAXONOMIES[ $mode ] ?? self::TAXONOMIES['category'];
		$tax_query  = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! is_object_in_taxonomy( $type, $taxonomy ) ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $taxonomy );
			$ids   = is_array( $terms ) ? Query_Args::ids( array_map( static fn( $term ): int => (int) $term->term_id, $terms ) ) : array();
			if ( array() !== $ids ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $ids,
				);
			}
		}
		if ( array() === $tax_query ) {
			return null;
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query = array_merge( array( 'relation' => 'OR' ), $tax_query );
		}
		$count = is_numeric( $settings['related_posts_count'] ?? null ) ? (int) $settings['related_posts_count'] : 0;
		$count = $count < 1 ? self::DEFAULT_COUNT : min( $count, self::MAX_COUNT );

		$args = array(
			'post_type'           => $type,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
			'no_found_rows'       => true,
			'posts_per_page'      => $count,
			'post__not_in'        => array( $post_id ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'tax_query'           => $tax_query,
		);
		return Query_Args::filter( $args, 'related-posts', $settings );
	}

	/**
	 * Returns the enabled post types: viewable, not attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Comma list or list of names.
	 * @return list<string> Post types.
	 */
	private static function post_types( mixed $value ): array {
		$names = is_array( $value ) ? $value : explode( ',', is_string( $value ) ? $value : '' );
		$types = array();
		foreach ( $names as $name ) {
			$name = is_string( $name ) ? trim( $name ) : '';
			if ( '' !== $name && 'attachment' !== $name && is_post_type_viewable( $name ) ) {
				$types[] = $name;
			}
		}
		return $types;
	}
}
