<?php
/**
 * Hooks and block renderer of the module post-slider.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\PostSlider;

use Creationell\WpTheme\Modules\Module_Gate;
use Creationell\WpTheme\Posts\Display_Options;
use Creationell\WpTheme\Posts\Query_Args;
use Creationell\WpTheme\Posts\Slider_Config;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;
use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the assets and the block category of the post slider and renders its block.
 *
 * The file bootstrap.php calls boot() once, while the module is active or
 * hidden. Script and stylesheet load early on a singular page that holds the
 * block, else when a slider renders; both need the Swiper library of
 * Vendor_Scripts (handle creationell-wp-theme-swiper). The editor canvas gets
 * the stylesheet only: there the block shows a static grid of its first posts
 * and runs no script (FS-11). The renderer turns the block attributes into a
 * WP_Query through Query_Args and hands the query, the card options of
 * Display_Options and the Swiper options of Slider_Config to
 * template-parts/post-slider/slider.php, which a child theme may override. The
 * block comes from the blocks/ folder, which the module gate hands to
 * Blockstudio. render_query() lets other modules show their own query as a
 * slider, e.g. the related posts.
 *
 * @since 1.0.0
 */
final class Post_Slider_Module {

	/**
	 * Handle of the module script and of the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-post-slider';

	/**
	 * Stylesheet, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const STYLESHEET = 'assets/post-slider.css';

	/**
	 * Script, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT = 'assets/post-slider.js';

	/**
	 * Handle of the Swiper library, registered by Vendor_Scripts.
	 *
	 * @since 1.0.0
	 */
	public const SWIPER_HANDLE = 'creationell-wp-theme-swiper';

	/**
	 * Handle of the compiled theme stylesheet, loaded before the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const THEME_STYLE_HANDLE = 'creationell-wp-theme-main';

	/**
	 * Block of the module.
	 *
	 * @since 1.0.0
	 */
	public const BLOCK = 'creationell-theme/post-slider';

	/**
	 * Template part of the slider, relative to the theme.
	 *
	 * @since 1.0.0
	 */
	public const PART = 'template-parts/post-slider/slider';

	/**
	 * Empty state of the post blocks, relative to the theme.
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_PART = 'template-parts/post-lists/empty';

	/**
	 * Setting of the autoplay delay in milliseconds.
	 *
	 * @since 1.0.0
	 */
	public const DELAY_KEY = 'post_slider_autoplay_delay';

	/**
	 * Layouts of the slider; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const LAYOUTS = array( 'columns', 'heroes' );

	/**
	 * Contexts in which the slider renders a query: the block, and the related posts.
	 *
	 * @since 1.0.0
	 */
	public const CONTEXTS = array( 'post-slider', 'related-posts' );

	/**
	 * Alignments of the block support align.
	 *
	 * @since 1.0.0
	 */
	public const ALIGNMENTS = array( 'left', 'center', 'right', 'wide', 'full' );

	/**
	 * Sliders of the current request that are landmarks of their own; names the second and later ones apart.
	 *
	 * @var int
	 */
	private static int $landmarks = 0;

	/**
	 * Adds the hooks of the module; bootstrap.php calls it once.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function boot(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_front' ), 20, 0 );
		add_action( 'enqueue_block_assets', array( self::class, 'enqueue_canvas' ), 10, 0 );
		add_filter( 'block_categories_all', array( self::class, 'block_categories' ), 10, 1 );
	}

	/**
	 * Enqueues script and stylesheet on a singular page with the block; runs on wp_enqueue_scripts with priority 20.
	 *
	 * Other pages get them when a slider renders (in the footer).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_front(): void {
		if ( is_admin() || ! is_singular() || ! has_block( self::BLOCK ) ) {
			return;
		}
		self::enqueue_assets();
	}

	/**
	 * Enqueues the stylesheet into the editor canvas; runs on enqueue_block_assets with priority 10.
	 *
	 * The hook also fires in the front end and for the editor document around
	 * the canvas; both get nothing. The canvas gets no script and no Swiper.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_canvas(): void {
		if ( ! is_admin() || doing_action( 'admin_enqueue_scripts' ) ) {
			return;
		}
		wp_enqueue_style( self::HANDLE, self::url( self::STYLESHEET ), array( self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
	}

	/**
	 * Enqueues the front assets: the stylesheet after Swiper and the theme stylesheet, the script deferred in the footer after Swiper.
	 *
	 * A second call changes nothing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		wp_enqueue_style( self::HANDLE, self::url( self::STYLESHEET ), array( self::SWIPER_HANDLE, self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
		wp_enqueue_script(
			self::HANDLE,
			self::url( self::SCRIPT ),
			array( self::SWIPER_HANDLE ),
			CREATIONELL_WP_THEME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Adds the block category of the theme modules once; runs on block_categories_all.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $categories Block categories.
	 * @return mixed Categories with creationell-theme at the end; values other than an array unchanged.
	 */
	public static function block_categories( mixed $categories ): mixed {
		if ( ! is_array( $categories ) ) {
			return $categories;
		}
		foreach ( $categories as $category ) {
			if ( is_array( $category ) && Module_Gate::BLOCK_NAMESPACE === ( $category['slug'] ?? null ) ) {
				return $categories;
			}
		}
		$categories[] = array(
			'slug'  => Module_Gate::BLOCK_NAMESPACE,
			'title' => __( 'creationell Theme', 'creationell-wp-theme' ),
			'icon'  => null,
		);
		return $categories;
	}

	/**
	 * Renders the block creationell-theme/post-slider: the queried posts as slides of cards or large image cards.
	 *
	 * Without posts the page shows nothing and the editor the empty state.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param bool                 $editor     True while the block editor renders the block.
	 * @param int                  $post_id    Post that holds the block; 0 outside a post.
	 * @return void
	 */
	public static function render( array $attributes, bool $editor, int $post_id ): void {
		$query = new WP_Query( Query_Args::from_attributes( $attributes, 'post-slider', $post_id ) );
		if ( ! $query->have_posts() ) {
			if ( $editor ) {
				get_template_part( self::EMPTY_PART );
			}
			return;
		}
		self::render_query( $query, $attributes, $editor );
	}

	/**
	 * Renders a query as post slider; the block and other modules call it.
	 *
	 * The attributes are those of the block: layout, columns, effect, loop,
	 * autoplay, navigation, paginationDots and the show* toggles with
	 * headingLevel; missing ones take their defaults. In the front end the
	 * script and the stylesheet are enqueued; the editor gets a static grid.
	 * The post data is reset afterwards. In the context post-slider the slider
	 * is a named section: "Post slider", from the second slider of the request
	 * on with its number (landmark-unique). In the context related-posts it is
	 * a div without name, because the section of the related posts around it
	 * already carries the name.
	 *
	 * Example:
	 *
	 *     if ( class_exists( Post_Slider_Module::class ) ) {
	 *         Post_Slider_Module::render_query( $query, array( 'columns' => 3 ), false, 'related-posts' );
	 *     }
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query             $query      Query with at least one post.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param bool                 $editor     True while the block editor renders the slider.
	 * @param string               $context    post-slider or related-posts; anything else counts as post-slider.
	 * @return void
	 */
	public static function render_query( WP_Query $query, array $attributes, bool $editor, string $context = 'post-slider' ): void {
		$context = in_array( $context, self::CONTEXTS, true ) ? $context : self::CONTEXTS[0];
		$options = Slider_Config::from_attributes( $attributes, self::autoplay_delay(), $context );
		$layout  = 'heroes' === ( $options['layout'] ?? null ) ? 'heroes' : 'columns';
		$columns = is_numeric( $attributes['columns'] ?? null ) ? (int) $attributes['columns'] : 0;
		if ( ! $editor ) {
			self::enqueue_assets();
		}
		get_template_part(
			self::PART,
			null,
			array(
				'query'   => $query,
				'display' => Display_Options::from_attributes( $attributes, $context ),
				'options' => $options,
				'layout'  => $layout,
				'columns' => 'heroes' === $layout ? 1 : ( $columns >= 1 && $columns <= 4 ? $columns : Slider_Config::COLUMNS ),
				'editor'  => $editor,
				'root'    => self::root( $attributes, $layout ),
				'label'   => 'related-posts' === $context ? '' : self::label(),
			)
		);
		wp_reset_postdata();
	}

	/**
	 * Returns the name of the next slider that is a landmark: "Post slider", then "Post slider 2" and so on.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated name.
	 */
	public static function label(): string {
		++self::$landmarks;
		if ( 1 === self::$landmarks ) {
			return __( 'Post slider', 'creationell-wp-theme' );
		}
		/* translators: %d: number of the post slider on the page, from 2. */
		return sprintf( __( 'Post slider %d', 'creationell-wp-theme' ), self::$landmarks );
	}

	/**
	 * Forgets the count of the sliders of the request; for tests and long-running processes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$landmarks = 0;
	}

	/**
	 * Returns the autoplay delay: the setting post_slider_autoplay_delay, or 6000 ms while it is not registered.
	 *
	 * @since 1.0.0
	 *
	 * @return int Delay in milliseconds.
	 */
	public static function autoplay_delay(): int {
		if ( ! Registry::instance()->has( self::DELAY_KEY ) ) {
			return Slider_Config::DELAY['default'];
		}
		$value = Settings::instance()->get( self::DELAY_KEY );
		return is_int( $value ) ? $value : Slider_Config::DELAY['default'];
	}

	/**
	 * Returns the block attributes from the values Blockstudio passes as $a (or $attributes) and $block.
	 *
	 * Blockstudio hands only the fields of block.json to $a; the attributes
	 * of WordPress (anchor, className, align) stay in the block data $block.
	 * They are taken from there unless $a has them.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $attributes Template variable $a.
	 * @param mixed $block      Template variable $block; null for none.
	 * @return array<string, mixed> Attributes by name; empty for anything but an array.
	 */
	public static function attributes( mixed $attributes, mixed $block = null ): array {
		$result = array();
		foreach ( is_array( $attributes ) ? $attributes : array() as $key => $value ) {
			$result[ (string) $key ] = $value;
		}
		foreach ( array( 'anchor', 'className', 'align' ) as $key ) {
			if ( ! isset( $result[ $key ] ) && is_array( $block ) && is_string( $block[ $key ] ?? null ) ) {
				$result[ $key ] = $block[ $key ];
			}
		}
		return $result;
	}

	/**
	 * Returns the post that holds the block, see Query_Args::current_post(): block context, loop post, then the Blockstudio block data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block Template variable $block of Blockstudio.
	 * @return int Post ID; 0 outside a post.
	 */
	public static function post_id( mixed $block ): int {
		return Query_Args::current_post( $block );
	}

	/**
	 * Returns the attributes of the root element: its classes, the alignment, className and the anchor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $layout     columns or heroes, added as creationell-theme-post-slider--<layout>.
	 * @return array{class: string, id: string} Class list and ID; the ID is empty without anchor.
	 */
	public static function root( array $attributes, string $layout ): array {
		$layout  = in_array( $layout, self::LAYOUTS, true ) ? $layout : self::LAYOUTS[0];
		$classes = array( 'wp-block-creationell-theme-post-slider', 'creationell-theme-post-slider', 'creationell-theme-post-slider--' . $layout );
		$align   = is_string( $attributes['align'] ?? null ) ? $attributes['align'] : '';
		if ( in_array( $align, self::ALIGNMENTS, true ) ) {
			$classes[] = 'align' . $align;
		}
		$extra = is_string( $attributes['className'] ?? null ) ? $attributes['className'] : '';
		$parts = preg_split( '~\s+~', $extra, -1, PREG_SPLIT_NO_EMPTY );
		foreach ( false === $parts ? array() : $parts as $part ) {
			$part = (string) preg_replace( '~[^A-Za-z0-9_-]~', '', $part );
			if ( '' !== $part ) {
				$classes[] = $part;
			}
		}
		$anchor = is_string( $attributes['anchor'] ?? null ) ? (string) preg_replace( '~[^A-Za-z0-9_:.-]~', '', $attributes['anchor'] ) : '';
		return array(
			'class' => implode( ' ', array_unique( $classes ) ),
			'id'    => $anchor,
		);
	}

	/**
	 * Returns the URL of a module file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file File relative to the module folder.
	 * @return string URL.
	 */
	private static function url( string $file ): string {
		return CREATIONELL_WP_THEME_URL . '/modules/post-slider/' . $file;
	}
}
