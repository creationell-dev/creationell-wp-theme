<?php
/**
 * Hooks and block renderers of the module post-lists.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\PostLists;

use Creationell\WpTheme\Modules\Inserter_Visibility;
use Creationell\WpTheme\Modules\Module_Gate;
use Creationell\WpTheme\Posts\Display_Options;
use Creationell\WpTheme\Posts\Pagination;
use Creationell\WpTheme\Posts\Query_Args;
use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Registers the stylesheet and the block category of the post lists and renders their blocks.
 *
 * The file bootstrap.php calls boot() once, while the module is active or hidden. The
 * stylesheet loads early on a singular page that holds one of the blocks, else
 * when a block renders, and in the editor canvas. While the module is active,
 * the block creabb/post-grid of CreaBootstrapBlocks leaves the inserter unless
 * the filter creationell_wp_theme_hide_cbb_post_grid returns false (FS-16);
 * existing post grids keep rendering. The renderers turn the block attributes
 * into a WP_Query through Query_Args and Display_Options and hand the query to
 * the template parts of template-parts/post-lists/, which a child theme may
 * override. The blocks come from the blocks/ folder, which the module gate
 * hands to Blockstudio.
 *
 * @since 1.0.0
 */
final class Post_Lists_Module {

	/**
	 * Handle of the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-post-lists';

	/**
	 * Stylesheet, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const STYLESHEET = 'assets/post-lists.css';

	/**
	 * Handle of the compiled theme stylesheet, loaded before the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const THEME_STYLE_HANDLE = 'creationell-wp-theme-main';

	/**
	 * Blocks of the module.
	 *
	 * @since 1.0.0
	 */
	public const BLOCKS = array( 'creationell-theme/post-list', 'creationell-theme/post-accordion' );

	/**
	 * Block of CreaBootstrapBlocks that the post list replaces.
	 *
	 * @since 1.0.0
	 */
	public const CBB_POST_GRID = 'creabb/post-grid';

	/**
	 * Filter that keeps creabb/post-grid in the inserter when it returns false.
	 *
	 * @since 1.0.0
	 */
	public const HIDE_CBB_FILTER = 'creationell_wp_theme_hide_cbb_post_grid';

	/**
	 * Folder of the template parts, relative to the theme.
	 *
	 * @since 1.0.0
	 */
	public const PARTS = 'template-parts/post-lists';

	/**
	 * Layouts of the post list; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const LAYOUTS = array( 'grid', 'list', 'hero' );

	/**
	 * Displays of the accordion block; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const DISPLAYS = array( 'accordion', 'tabs' );

	/**
	 * Content modes of the accordion block; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const CONTENT_MODES = array( 'excerpt', 'content' );

	/**
	 * Alignments of the block support align.
	 *
	 * @since 1.0.0
	 */
	public const ALIGNMENTS = array( 'left', 'center', 'right', 'wide', 'full' );

	/**
	 * Columns of the grid when the attribute is missing or outside 1 to 4.
	 *
	 * @since 1.0.0
	 */
	public const COLUMNS = 3;

	/**
	 * Adds the hooks of the module; bootstrap.php calls it once with the effective state.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state Effective state of the module: active or hidden.
	 * @return void
	 */
	public static function boot( string $state ): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_front' ), 20, 0 );
		add_action( 'enqueue_block_assets', array( self::class, 'enqueue_canvas' ), 10, 0 );
		add_filter( 'block_categories_all', array( self::class, 'block_categories' ), 10, 1 );
		if ( 'active' === $state && self::hide_cbb_post_grid() ) {
			Inserter_Visibility::hide( self::CBB_POST_GRID );
		}
	}

	/**
	 * Tells whether creabb/post-grid leaves the inserter; only false from the filter keeps it.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True to hide the block.
	 */
	public static function hide_cbb_post_grid(): bool {
		/**
		 * Filters whether the block creabb/post-grid of CreaBootstrapBlocks leaves the inserter while the module post-lists is active.
		 *
		 * Existing post grids keep rendering either way. Only false keeps the block in the inserter.
		 *
		 * Example:
		 *
		 *     add_filter( 'creationell_wp_theme_hide_cbb_post_grid', '__return_false' );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $hide True to hide the block.
		 */
		return false !== apply_filters( 'creationell_wp_theme_hide_cbb_post_grid', true );
	}

	/**
	 * Enqueues the stylesheet on a singular page with one of the blocks; runs on wp_enqueue_scripts with priority 20.
	 *
	 * Other pages get it when a block renders (in the footer).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_front(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}
		foreach ( self::BLOCKS as $block ) {
			if ( has_block( $block ) ) {
				self::enqueue_style();
				return;
			}
		}
	}

	/**
	 * Enqueues the stylesheet into the editor canvas; runs on enqueue_block_assets with priority 10.
	 *
	 * The hook also fires in the front end and for the editor document around
	 * the canvas; both get nothing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_canvas(): void {
		if ( ! is_admin() || doing_action( 'admin_enqueue_scripts' ) ) {
			return;
		}
		self::enqueue_style();
	}

	/**
	 * Enqueues the module stylesheet after the theme stylesheet; a second call changes nothing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_style(): void {
		wp_enqueue_style( self::HANDLE, CREATIONELL_WP_THEME_URL . '/modules/post-lists/' . self::STYLESHEET, array( self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
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
	 * Renders the block creationell-theme/post-list: grid, list or hero cards, with pagination on request.
	 *
	 * A paginated list takes the next instance number of Pagination and reads
	 * its page from its own query variable; a page past the end shows the last
	 * page. Without posts the page shows nothing and the editor the empty state.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param bool                 $editor     True while the block editor renders the block.
	 * @param int                  $post_id    Post that holds the block; 0 outside a post.
	 * @return void
	 */
	public static function render_list( array $attributes, bool $editor, int $post_id ): void {
		$paginate = Query_Args::flag( $attributes['pagination'] ?? null, false );
		$instance = $paginate ? Pagination::next_instance() : 0;
		$page     = $paginate ? Pagination::current_page( $instance ) : 1;
		$query    = new WP_Query( Query_Args::from_attributes( $attributes, 'post-list', $post_id, $page ) );
		$pages    = (int) $query->max_num_pages;
		if ( $paginate && $pages > 0 && $page > $pages ) {
			$query = new WP_Query( Query_Args::from_attributes( $attributes, 'post-list', $post_id, Pagination::clamp( $page, $pages ) ) );
		}
		if ( ! $query->have_posts() ) {
			self::render_empty( $editor );
			return;
		}
		if ( ! $editor ) {
			self::enqueue_style();
		}
		$layout  = self::choose( $attributes['layout'] ?? null, self::LAYOUTS );
		$columns = is_numeric( $attributes['columns'] ?? null ) ? (int) $attributes['columns'] : 0;
		get_template_part(
			self::PARTS . '/list',
			null,
			array(
				'query'      => $query,
				'display'    => Display_Options::from_attributes( $attributes, 'post-list' ),
				'layout'     => $layout,
				'columns'    => $columns >= 1 && $columns <= 4 ? $columns : self::COLUMNS,
				'pagination' => $instance,
				'root'       => self::root( $attributes, 'post-list', $layout ),
			)
		);
		wp_reset_postdata();
	}

	/**
	 * Renders the block creationell-theme/post-accordion: an accordion or tabs with the excerpt or the content of each post.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param bool                 $editor     True while the block editor renders the block.
	 * @param int                  $post_id    Post that holds the block; 0 outside a post.
	 * @return void
	 */
	public static function render_accordion( array $attributes, bool $editor, int $post_id ): void {
		$query = new WP_Query( Query_Args::from_attributes( $attributes, 'post-accordion', $post_id ) );
		if ( ! $query->have_posts() ) {
			self::render_empty( $editor );
			return;
		}
		if ( ! $editor ) {
			self::enqueue_style();
		}
		$display = self::choose( $attributes['display'] ?? null, self::DISPLAYS );
		get_template_part(
			self::PARTS . '/' . $display,
			null,
			array(
				'query'        => $query,
				'display'      => Display_Options::from_attributes( $attributes, 'post-accordion' ),
				'open_first'   => Query_Args::flag( $attributes['openFirst'] ?? null, true ),
				'always_open'  => Query_Args::flag( $attributes['alwaysOpen'] ?? null, false ),
				'flush'        => Query_Args::flag( $attributes['flush'] ?? null, false ),
				'content_mode' => self::choose( $attributes['contentMode'] ?? null, self::CONTENT_MODES ),
				'root'         => self::root( $attributes, 'post-accordion', $display ),
			)
		);
		wp_reset_postdata();
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
	 * Returns the attributes of the root element of a block: its classes, the alignment, className and the anchor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $block      Block name without namespace: post-list or post-accordion.
	 * @param string               $variant    Layout or display, added as creationell-theme-<block>--<variant>.
	 * @return array{class: string, id: string} Class list and ID; the ID is empty without anchor.
	 */
	public static function root( array $attributes, string $block, string $variant ): array {
		$classes = array( 'wp-block-creationell-theme-' . $block, 'creationell-theme-' . $block, 'creationell-theme-' . $block . '--' . $variant );
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
	 * Returns the value of a select attribute from a positive list; anything else gives the first entry.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed              $value   Attribute value: string or Blockstudio option.
	 * @param array<int, string> $allowed Allowed values, the default first.
	 * @return string Value.
	 */
	private static function choose( mixed $value, array $allowed ): string {
		$choice = Query_Args::choice( $value );
		return in_array( $choice, $allowed, true ) ? $choice : $allowed[0];
	}

	/**
	 * Prints the empty state in the editor; the page gets nothing.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $editor True in the block editor.
	 * @return void
	 */
	private static function render_empty( bool $editor ): void {
		if ( $editor ) {
			get_template_part( self::PARTS . '/empty' );
		}
	}
}
