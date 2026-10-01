<?php
/**
 * Guard against recursive post content in the post blocks.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Posts;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lets a block print the full content of one other post at a time.
 *
 * The accordion with contentMode "content" prints the content of the listed
 * posts. A post that contains the block itself would print it again, without
 * end. enter() refuses the queried post, a post that is already open and a
 * second level; the caller then shows the excerpt. leave() closes the post
 * after its content was printed. The class registers no hooks.
 *
 * @since 1.0.0
 */
final class Content_Guard {

	/**
	 * Posts whose content may be open at the same time.
	 *
	 * @since 1.0.0
	 */
	public const MAX_DEPTH = 1;

	/**
	 * Open posts, innermost last.
	 *
	 * @var list<int>
	 */
	private static array $open = array();

	/**
	 * Opens the content of a post; false when printing it could recurse.
	 *
	 * Example:
	 *
	 *     if ( Content_Guard::enter( $post_id ) ) {
	 *         the_content();
	 *         Content_Guard::leave( $post_id );
	 *     } else {
	 *         the_excerpt();
	 *     }
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post whose content is to be printed.
	 * @return bool True when the content may be printed; false for the queried post, an open post or a second level.
	 */
	public static function enter( int $post_id ): bool {
		if ( $post_id < 1 || (int) get_queried_object_id() === $post_id || in_array( $post_id, self::$open, true ) || count( self::$open ) >= self::MAX_DEPTH ) {
			return false;
		}
		self::$open[] = $post_id;
		return true;
	}

	/**
	 * Closes the content of a post; a post that is not open is ignored.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post.
	 * @return void
	 */
	public static function leave( int $post_id ): void {
		$keys = array_keys( self::$open, $post_id, true );
		if ( array() !== $keys ) {
			$open = self::$open;
			array_splice( $open, (int) end( $keys ), 1 );
			self::$open = $open;
		}
	}

	/**
	 * Closes every open post; for tests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$open = array();
	}
}
