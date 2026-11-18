<?php
/**
 * Loop row of the blog page home.php.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Opens and closes the row of the post loop on the blog page.
 *
 * The plugin JPKCom Post Filter prints its filter bar on loop_start and
 * closes it on loop_end, both on priority 10. The row of the cards therefore
 * opens on loop_start with priority 11 and closes on loop_end with priority 9,
 * inside the filter bar. Both act only on the main query while home.php
 * renders its loop between begin() and end(); without the plugin the markup is
 * the same.
 *
 * @since 1.0.0
 */
final class Blog_Home {

	/**
	 * Whether home.php renders its loop.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private static bool $rendering = false;

	/**
	 * CSS classes of the row; '' prints no row (layout custom).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static string $row_classes = '';

	/**
	 * Whether the row is open and waits for close_grid().
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	private static bool $open = false;

	/**
	 * Adds the callbacks on loop_start and loop_end; Blog_Module::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'loop_start', array( self::class, 'open_grid' ), 11 );
		add_action( 'loop_end', array( self::class, 'close_grid' ), 9 );
	}

	/**
	 * Starts the loop of home.php: the next loop of the main query gets a row with these classes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $row_classes CSS classes of the row chosen by the layout filters; '' for no row.
	 * @return void
	 */
	public static function begin( string $row_classes ): void {
		self::$rendering   = true;
		self::$row_classes = trim( $row_classes );
		self::$open        = false;
	}

	/**
	 * Ends the loop of home.php; later loops get no row.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function end(): void {
		self::$rendering   = false;
		self::$row_classes = '';
		self::$open        = false;
	}

	/**
	 * Prints the opening tag of the row on loop_start (priority 11).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $query Query whose loop starts; anything other than a WP_Query prints nothing.
	 * @return void
	 */
	public static function open_grid( mixed $query ): void {
		if ( ! self::$rendering || self::$open || '' === self::$row_classes || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
			return;
		}
		self::$open = true;
		echo '<div class="' . esc_attr( self::$row_classes ) . '">' . "\n";
	}

	/**
	 * Prints the closing tag of the row on loop_end (priority 9).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $query Query whose loop ends; anything other than a WP_Query prints nothing.
	 * @return void
	 */
	public static function close_grid( mixed $query ): void {
		if ( ! self::$rendering || ! self::$open || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
			return;
		}
		self::$open = false;
		echo '</div><!-- .row (loop row) -->' . "\n";
	}
}
