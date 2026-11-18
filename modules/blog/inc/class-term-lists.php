<?php
/**
 * Category and tag lists of the blog sidebar.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads the categories and tags with at least one published post for the part template-parts/blog/sidebar-terms.php.
 *
 * Each entry holds the term, its link, its number of posts (with the posts of
 * its children, so a parent used only for grouping does not show 0), its state
 * and its children: the state is "page" on the first archive page of the
 * queried term, "true" on its later pages and empty otherwise. A term whose
 * link fails is left out; its children move up a level. The values are raw;
 * the part escapes them.
 *
 * @phpstan-type Entry array{term: \WP_Term, url: string, count: int, current: string, children: list<array<string, mixed>>}
 *
 * @since 1.0.0
 */
final class Term_Lists {

	/**
	 * Deepest nesting level of the categories; deeper categories get no children.
	 *
	 * @since 1.0.0
	 */
	private const MAX_DEPTH = 20;

	/**
	 * Returns the categories with posts, sorted by name and nested by parent.
	 *
	 * The default category is left out when it is the only category.
	 *
	 * @since 1.0.0
	 *
	 * @return list<Entry> Entries of the top level.
	 */
	public static function categories(): array {
		$items = self::items( 'category' );
		if ( 1 === count( $items ) && (int) get_option( 'default_category' ) === (int) array_key_first( $items ) ) {
			return array();
		}
		return self::nest( $items );
	}

	/**
	 * Returns the tags with posts, sorted by name.
	 *
	 * @since 1.0.0
	 *
	 * @return list<Entry> Entries; children are always empty.
	 */
	public static function tags(): array {
		return array_values( self::items( 'post_tag' ) );
	}

	/**
	 * Reads the terms of a taxonomy with their links and states, in the order of get_terms().
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy: category or post_tag.
	 * @return array<int, Entry> Entries by term ID.
	 */
	private static function items( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'pad_counts' => true,
				'orderby'    => 'name',
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$current = self::current_id( $taxonomy );
		$state   = is_paged() ? 'true' : 'page';
		$items   = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$url = get_term_link( $term );
			if ( ! is_string( $url ) ) {
				continue;
			}
			$id           = (int) $term->term_id;
			$items[ $id ] = array(
				'term'     => $term,
				'url'      => $url,
				'count'    => (int) $term->count,
				'current'  => $id === $current ? $state : '',
				'children' => array(),
			);
		}
		return $items;
	}

	/**
	 * Returns the ID of the term whose archive the request shows, in the given taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy Taxonomy: category or post_tag.
	 * @return int Term ID; 0 outside an archive of the taxonomy.
	 */
	private static function current_id( string $taxonomy ): int {
		$archive = 'category' === $taxonomy ? is_category() : is_tag();
		$term    = $archive ? get_queried_object() : null;
		return $term instanceof \WP_Term && $taxonomy === $term->taxonomy ? (int) $term->term_id : 0;
	}

	/**
	 * Nests the entries by parent; an entry whose parent is missing goes to the top level.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, Entry> $items Entries by term ID, sorted.
	 * @return list<Entry> Entries of the top level.
	 */
	private static function nest( array $items ): array {
		$roots    = array();
		$children = array();
		foreach ( $items as $id => $item ) {
			$parent = (int) $item['term']->parent;
			if ( $parent > 0 && $parent !== $id && isset( $items[ $parent ] ) ) {
				$children[ $parent ][] = $id;
			} else {
				$roots[] = $id;
			}
		}
		return self::branch( $roots, $items, $children, 0 );
	}

	/**
	 * Builds one level of the tree with the levels below it.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, int>       $ids      Term IDs of the level, sorted.
	 * @param array<int, Entry>     $items    Entries by term ID.
	 * @param array<int, list<int>> $children Child IDs by parent ID.
	 * @param int                   $depth    Level, 0 for the top.
	 * @return list<Entry> Entries of the level.
	 */
	private static function branch( array $ids, array $items, array $children, int $depth ): array {
		$level = array();
		foreach ( $ids as $id ) {
			$item             = $items[ $id ];
			$item['children'] = $depth < self::MAX_DEPTH ? self::branch( $children[ $id ] ?? array(), $items, $children, $depth + 1 ) : array();
			$level[]          = $item;
		}
		return $level;
	}
}
