<?php
/**
 * Query arguments of the post blocks.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Posts;

use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Turns the attributes of the post blocks into WP_Query arguments.
 *
 * Every value passes a positive list or absint(): the post type must be
 * viewable and not an attachment, the taxonomy must belong to the post type,
 * the order must be one of ORDER_BY, and the number of posts stays below the
 * maximum of the block and the setting post_lists_max_posts. Term, post and
 * parent IDs pass the WPML filter wpml_object_id, so a list shows the posts of
 * the language of the request. Only published posts are queried, sticky posts
 * keep their place, and only a paginated post list counts its rows. The class
 * registers no hooks; the filter creationell_wp_theme_post_query_args runs
 * last. The helpers ids(), choice() and flag() read the value shapes that
 * Blockstudio stores (ints, numeric strings, comma lists, option objects).
 *
 * @since 1.0.0
 */
final class Query_Args {

	/**
	 * Contexts of the blocks; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const CONTEXTS = array( 'post-list', 'post-accordion', 'post-slider', 'related-posts' );

	/**
	 * Filter of the query arguments.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_post_query_args';

	/**
	 * Allowed values of the attribute orderBy; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const ORDER_BY = array( 'date', 'title', 'menu_order', 'modified', 'rand', 'post__in' );

	/**
	 * Number of posts when the attribute postsPerPage is missing or invalid.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_PER_PAGE = 6;

	/**
	 * Highest number of posts per context, as the block attributes allow it.
	 *
	 * @since 1.0.0
	 */
	public const MAX_PER_PAGE = array(
		'post-list'      => 50,
		'post-accordion' => 20,
		'post-slider'    => 50,
		'related-posts'  => 12,
	);

	/**
	 * Setting with the highest number of posts of a list.
	 *
	 * @since 1.0.0
	 */
	public const MAX_POSTS_KEY = 'post_lists_max_posts';

	/**
	 * Value of post_lists_max_posts while the setting is not registered.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_MAX_POSTS = 24;

	/**
	 * Returns the WP_Query arguments for the attributes of a block.
	 *
	 * Example:
	 *
	 *     $args  = Query_Args::from_attributes( $a, 'post-list', get_the_ID() ?: 0 );
	 *     $query = new \WP_Query( $args );
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes      Block attributes (postType, taxonomy, terms, postIds, parentId, orderBy, order, postsPerPage, excludeCurrent, pagination, showCategories, showTags).
	 * @param string               $context         One of CONTEXTS; an unknown value counts as post-list.
	 * @param int                  $current_post_id Post that holds the block; 0 outside a post.
	 * @param int                  $page            Requested page of a paginated post list.
	 * @return array<string, mixed> WP_Query arguments.
	 */
	public static function from_attributes( array $attributes, string $context, int $current_post_id, int $page = 1 ): array {
		$context    = self::context( $context );
		$post_type  = self::post_type( $attributes['postType'] ?? 'post' );
		$pagination = 'post-list' === $context && self::flag( $attributes['pagination'] ?? null, false );
		$post_ids   = self::translate( self::ids( $attributes['postIds'] ?? array() ), $post_type );
		$order_by   = self::choice( $attributes['orderBy'] ?? '' );
		if ( ! in_array( $order_by, self::ORDER_BY, true ) || ( 'post__in' === $order_by && array() === $post_ids ) ) {
			$order_by = self::ORDER_BY[0];
		}
		$order = strtoupper( self::choice( $attributes['order'] ?? '' ) );

		$args = array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'no_found_rows'          => ! $pagination,
			'posts_per_page'         => self::per_page( $attributes['postsPerPage'] ?? null, $context ),
			'orderby'                => $order_by,
			'order'                  => 'ASC' === $order ? 'ASC' : 'DESC',
			'update_post_term_cache' => self::flag( $attributes['showCategories'] ?? null, true ) || self::flag( $attributes['showTags'] ?? null, false ),
		);
		if ( $pagination ) {
			$args['paged'] = max( 1, $page );
		}

		$taxonomy = self::choice( $attributes['taxonomy'] ?? '' );
		$terms    = self::ids( $attributes['terms'] ?? array() );
		if ( '' !== $taxonomy && array() !== $terms && is_object_in_taxonomy( $post_type, $taxonomy ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => self::translate( $terms, $taxonomy ),
				),
			);
		}
		if ( array() !== $post_ids ) {
			$args['post__in'] = $post_ids;
		}
		$parent = self::translate( array_slice( self::ids( $attributes['parentId'] ?? 0 ), 0, 1 ), $post_type );
		if ( array() !== $parent ) {
			$args['post_parent'] = $parent[0];
		}
		if ( $current_post_id > 0 && self::flag( $attributes['excludeCurrent'] ?? null, true ) ) {
			$args['post__not_in'] = array( $current_post_id );
		}

		return self::filter( $args, $context, $attributes );
	}

	/**
	 * Runs the filter creationell_wp_theme_post_query_args; a value that is not an array keeps the arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args       WP_Query arguments.
	 * @param string               $context    Context.
	 * @param array<string, mixed> $attributes Block attributes, or the settings of the related posts.
	 * @return array<string, mixed> Filtered arguments.
	 */
	public static function filter( array $args, string $context, array $attributes ): array {
		/**
		 * Filters the WP_Query arguments of a post list, accordion, slider or of the related posts.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_post_query_args',
		 *         static function ( array $args, string $context ): array {
		 *             if ( 'post-slider' === $context ) {
		 *                 $args['orderby'] = 'rand';
		 *             }
		 *             return $args;
		 *         },
		 *         10,
		 *         2
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $args       WP_Query arguments.
		 * @param string               $context    post-list, post-accordion, post-slider or related-posts.
		 * @param array<string, mixed> $attributes Block attributes; for related-posts the settings of the module.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_post_query_args', $args, $context, $attributes );
		if ( ! is_array( $filtered ) ) {
			return $args;
		}
		$result = array();
		foreach ( $filtered as $key => $value ) {
			$result[ (string) $key ] = $value;
		}
		return $result;
	}

	/**
	 * Returns a known context; an unknown value counts as post-list.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context.
	 * @return string One of CONTEXTS.
	 */
	public static function context( string $context ): string {
		return in_array( $context, self::CONTEXTS, true ) ? $context : self::CONTEXTS[0];
	}

	/**
	 * Returns a viewable post type other than attachment; anything else gives post.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Attribute value: name or Blockstudio option.
	 * @return string Post type.
	 */
	public static function post_type( mixed $value ): string {
		$name = self::choice( $value );
		if ( '' === $name || 'attachment' === $name || ! is_post_type_viewable( $name ) ) {
			return 'post';
		}
		return $name;
	}

	/**
	 * Returns the highest number of posts of a list: the setting post_lists_max_posts, 1 to 50, or 24 while it is not registered.
	 *
	 * @since 1.0.0
	 *
	 * @return int Maximum.
	 */
	public static function max_posts(): int {
		if ( ! Registry::instance()->has( self::MAX_POSTS_KEY ) ) {
			return self::DEFAULT_MAX_POSTS;
		}
		$value = Settings::instance()->get( self::MAX_POSTS_KEY );
		if ( ! is_numeric( $value ) || (int) $value < 1 ) {
			return self::DEFAULT_MAX_POSTS;
		}
		return min( (int) $value, self::MAX_PER_PAGE['post-list'] );
	}

	/**
	 * Returns positive, unique IDs from the shapes Blockstudio stores.
	 *
	 * Accepts an int, a numeric string, a list separated by commas or white
	 * space, an option array with value, id, ID or term_id, a post or term
	 * object, and lists of these. Each ID passes absint(); zero is dropped.
	 * Empty values (false, null, "", true) give no IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Attribute value.
	 * @return list<int> IDs in the given order.
	 */
	public static function ids( mixed $value ): array {
		$ids = array();
		if ( is_int( $value ) ) {
			$ids[] = absint( $value );
		} elseif ( is_string( $value ) ) {
			$parts = preg_split( '~[\s,]+~', $value, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( false === $parts ? array() : $parts as $part ) {
				$ids[] = is_numeric( $part ) ? absint( $part ) : 0;
			}
		} elseif ( is_object( $value ) ) {
			$ids = self::ids( get_object_vars( $value ) );
		} elseif ( is_array( $value ) ) {
			foreach ( array( 'value', 'id', 'ID', 'term_id' ) as $key ) {
				if ( array_key_exists( $key, $value ) ) {
					return self::ids( $value[ $key ] );
				}
			}
			foreach ( $value as $item ) {
				$ids = array_merge( $ids, self::ids( $item ) );
			}
		}
		return array_values( array_unique( array_filter( $ids, static fn( int $id ): bool => $id > 0 ) ) );
	}

	/**
	 * Returns the post that holds a block: the postId of the block context first, then the post of the loop or the postId of the Blockstudio block data.
	 *
	 * The context names the post of a query loop around the block, in the
	 * editor also the edited post. Without it, a page request takes the post of
	 * the loop (get_the_ID()): Blockstudio fills $block['postId'] there with the
	 * queried object, which is a term on an archive or the page around a query
	 * loop. A REST request (the editor and the inserter preview of Blockstudio)
	 * takes $block['postId'] first, the post the editor names: its global post
	 * may still be the last post of another block's query.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block Template variable $block of Blockstudio: postId and context (with postId).
	 * @return int Post ID; 0 outside a post.
	 */
	public static function current_post( mixed $block ): int {
		$block   = is_array( $block ) ? $block : array();
		$context = is_array( $block['context'] ?? null ) ? $block['context'] : array();
		$data    = $block['postId'] ?? null;
		$loop    = get_the_ID();
		$order   = wp_is_serving_rest_request() ? array( $context['postId'] ?? null, $data, $loop ) : array( $context['postId'] ?? null, $loop, $data );
		foreach ( $order as $candidate ) {
			$id = is_int( $candidate ) || ( is_string( $candidate ) && ctype_digit( $candidate ) ) ? (int) $candidate : 0;
			if ( $id > 0 ) {
				return $id;
			}
		}
		return 0;
	}

	/**
	 * Returns the value of a select attribute as trimmed string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value String, int, or Blockstudio option array with "value".
	 * @return string Value; empty for anything else.
	 */
	public static function choice( mixed $value ): string {
		if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
			$value = $value['value'];
		}
		if ( is_string( $value ) ) {
			return trim( $value );
		}
		return is_int( $value ) ? (string) $value : '';
	}

	/**
	 * Returns the value of a toggle attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value         Bool, 0 or 1, or "1", "0", "true", "false", "on", "off", "yes", "no", "".
	 * @param bool  $default_value Value for anything else, also for null.
	 * @return bool Flag.
	 */
	public static function flag( mixed $value, bool $default_value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) && ( 0 === $value || 1 === $value ) ) {
			return 1 === $value;
		}
		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );
			if ( in_array( $value, array( '1', 'true', 'on', 'yes' ), true ) ) {
				return true;
			}
			if ( in_array( $value, array( '0', 'false', 'off', 'no', '' ), true ) ) {
				return false;
			}
		}
		return $default_value;
	}

	/**
	 * Returns the number of posts: the attribute, else 6, capped by the context and the setting.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value   Attribute postsPerPage.
	 * @param string $context Known context.
	 * @return int Number of posts.
	 */
	private static function per_page( mixed $value, string $context ): int {
		$number = is_numeric( $value ) ? (int) $value : 0;
		if ( $number < 1 ) {
			$number = self::DEFAULT_PER_PAGE;
		}
		return min( $number, self::MAX_PER_PAGE[ $context ] ?? self::MAX_PER_PAGE['post-list'], self::max_posts() );
	}

	/**
	 * Maps IDs to the language of the request with the WPML filter wpml_object_id.
	 *
	 * Without WPML the filter returns the ID unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, int> $ids  IDs.
	 * @param string          $type Post type or taxonomy of the IDs.
	 * @return list<int> Translated IDs; IDs without a positive answer are dropped.
	 */
	private static function translate( array $ids, string $type ): array {
		$result = array();
		foreach ( $ids as $id ) {
			$translated = apply_filters( 'wpml_object_id', $id, $type, true );
			$translated = is_numeric( $translated ) ? absint( $translated ) : 0;
			if ( $translated > 0 ) {
				$result[] = $translated;
			}
		}
		return array_values( array_unique( $result ) );
	}
}
