<?php
/**
 * Hooks, automatic output and block renderer of the module related-posts.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\RelatedPosts;

use Creationell\WpTheme\Modules\PostLists\Post_Lists_Module;
use Creationell\WpTheme\Modules\PostSlider\Post_Slider_Module;
use Creationell\WpTheme\Posts\Display_Options;
use Creationell\WpTheme\Posts\Query_Args;
use Creationell\WpTheme\Posts\Related_Query;
use Creationell\WpTheme\Settings\Registry;
use Creationell\WpTheme\Settings\Settings;
use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows the posts related to a single post: below the post and through the block creationell-theme/related-posts.
 *
 * The file bootstrap.php calls boot() once, while the module is active or
 * hidden. Only an active module hooks into the action
 * creationell_wp_theme_before_single_pagination of the single templates; it
 * prints the related posts there on a singular request for a post type of the
 * setting related_posts_post_types, unless the content holds the block or the
 * filter creationell_wp_theme_related_posts_auto_insert returns false. A
 * hidden module renders existing blocks only (FS-13). Related_Query finds the
 * posts; template-parts/related-posts/related-posts.php, which a child theme
 * may override, prints a section with a heading and the cards of the module
 * post-lists as a grid, or the slider of the module post-slider while that
 * module is not off (FS-12). The stylesheets load early on a singular page
 * with related posts, else when they render. The block comes from the blocks/
 * folder, which the module gate hands to Blockstudio.
 *
 * @since 1.0.0
 */
final class Related_Posts_Module {

	/**
	 * Block of the module.
	 *
	 * @since 1.0.0
	 */
	public const BLOCK = 'creationell-theme/related-posts';

	/**
	 * Template part of the related posts, relative to the theme.
	 *
	 * @since 1.0.0
	 */
	public const PART = 'template-parts/related-posts/related-posts';

	/**
	 * Empty state of the post blocks, relative to the theme.
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_PART = 'template-parts/post-lists/empty';

	/**
	 * Action of the single templates before the links to the previous and next post.
	 *
	 * @since 1.0.0
	 */
	public const ACTION = 'creationell_wp_theme_before_single_pagination';

	/**
	 * Filter that stops the automatic output when it returns anything but true.
	 *
	 * @since 1.0.0
	 */
	public const AUTO_FILTER = 'creationell_wp_theme_related_posts_auto_insert';

	/**
	 * Module that brings the slider display.
	 *
	 * @since 1.0.0
	 */
	public const SLIDER_MODULE = 'post-slider';

	/**
	 * Displays of the related posts; the first is the default.
	 *
	 * @since 1.0.0
	 */
	public const DISPLAYS = array( 'grid', 'slider' );

	/**
	 * Most columns of the grid and the slider.
	 *
	 * @since 1.0.0
	 */
	public const MAX_COLUMNS = 3;

	/**
	 * Heading level of the section when the attribute is missing or outside 2 to 6.
	 *
	 * @since 1.0.0
	 */
	public const HEADING_LEVEL = 2;

	/**
	 * Settings of the module with the values used while they are not registered.
	 *
	 * @since 1.0.0
	 */
	public const SETTINGS = array(
		'related_posts_post_types' => 'post',
		'related_posts_taxonomies' => 'category',
		'related_posts_count'      => Related_Query::DEFAULT_COUNT,
		'related_posts_display'    => 'grid',
		'related_posts_heading'    => '',
	);

	/**
	 * Alignments of the block support align.
	 *
	 * @since 1.0.0
	 */
	public const ALIGNMENTS = array( 'left', 'center', 'right', 'wide', 'full' );

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
		if ( 'active' === $state ) {
			add_action( self::ACTION, array( self::class, 'auto_insert' ), 10, 0 );
		}
	}

	/**
	 * Prints the related posts of the current post below a single post; runs on creationell_wp_theme_before_single_pagination.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function auto_insert(): void {
		$current = get_the_ID();
		$post_id = is_int( $current ) ? $current : 0;
		if ( self::should_auto_insert( $post_id ) ) {
			self::output( array(), false, $post_id, false );
		}
	}

	/**
	 * Tells whether the related posts show below a post by themselves.
	 *
	 * That needs a singular request, a post of a type in related_posts_post_types,
	 * no block creationell-theme/related-posts in its content and true from the
	 * filter creationell_wp_theme_related_posts_auto_insert. The hook itself is
	 * added only while the module is active.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool True to print the related posts.
	 */
	public static function should_auto_insert( int $post_id ): bool {
		if ( $post_id < 1 || ! is_singular() ) {
			return false;
		}
		$type = get_post_type( $post_id );
		if ( ! is_string( $type ) || ! in_array( $type, self::post_types(), true ) || has_block( self::BLOCK, $post_id ) ) {
			return false;
		}
		/**
		 * Filters whether the related posts show below a single post by themselves.
		 *
		 * Runs only while the module related-posts is active, on a singular
		 * request for a post type of the setting related_posts_post_types whose
		 * content holds no related posts block. Only true keeps the output.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_related_posts_auto_insert',
		 *         static fn( bool $insert, int $post_id ): bool => $insert && ! has_term( 'press', 'category', $post_id ),
		 *         10,
		 *         2
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $insert  True to print the related posts.
		 * @param int  $post_id Post ID.
		 */
		return true === apply_filters( 'creationell_wp_theme_related_posts_auto_insert', true, $post_id );
	}

	/**
	 * Enqueues the stylesheets on a singular page with the block or with the automatic output; runs on wp_enqueue_scripts with priority 20.
	 *
	 * The slider assets follow when the automatic output (setting
	 * related_posts_display) or one of the related posts blocks in the content
	 * (its attribute display) asks for the slider and the module post-slider is
	 * not off. Other pages get the assets when related posts render (in the
	 * footer). No query runs here.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_front(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( $post_id < 1 ) {
			return;
		}
		$displays = array();
		if ( false !== has_action( self::ACTION, array( self::class, 'auto_insert' ) ) && self::should_auto_insert( $post_id ) ) {
			$displays[] = self::display( array() );
		}
		if ( has_block( self::BLOCK, $post_id ) ) {
			$blocks = self::block_attributes( parse_blocks( (string) get_post_field( 'post_content', $post_id ) ) );
			// A block inside a synced pattern is not in the content itself: the setting decides, as for the automatic output.
			foreach ( array() === $blocks ? array( array() ) : $blocks as $attributes ) {
				$displays[] = self::display( $attributes );
			}
		}
		if ( array() !== $displays ) {
			self::enqueue_assets( in_array( 'slider', $displays, true ) ? 'slider' : 'grid' );
		}
	}

	/**
	 * Returns the attributes of the related posts blocks among parsed blocks, inner blocks included.
	 *
	 * Blockstudio keeps the field values below blockstudio.attributes; the
	 * values there win over attributes of the same name at the top.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $blocks Blocks as parse_blocks() returns them.
	 * @return list<array<string, mixed>> Attributes per block.
	 */
	public static function block_attributes( array $blocks ): array {
		$found = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : array();
			if ( self::BLOCK === ( $block['blockName'] ?? null ) ) {
				$fields = is_array( $attrs['blockstudio']['attributes'] ?? null ) ? $attrs['blockstudio']['attributes'] : array();
				unset( $attrs['blockstudio'] );
				$found[] = self::attributes( array_merge( $attrs, $fields ) );
			}
			if ( is_array( $block['innerBlocks'] ?? null ) ) {
				$found = array_merge( $found, self::block_attributes( $block['innerBlocks'] ) );
			}
		}
		return $found;
	}

	/**
	 * Renders the block creationell-theme/related-posts for the post that holds it.
	 *
	 * Without related posts the page shows nothing. The editor shows a hint
	 * when there is no post to relate to or the post has no terms, and the
	 * empty state when no post shares its terms.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes: count, display, heading, headingLevel and the supports.
	 * @param bool                 $editor     True while the block editor renders the block.
	 * @param int                  $post_id    Post that holds the block; 0 outside a post.
	 * @return void
	 */
	public static function render( array $attributes, bool $editor, int $post_id ): void {
		self::output( $attributes, $editor, $post_id, true );
	}

	/**
	 * Returns the effective display: slider only when asked for and the module post-slider is not off (FS-12), else grid.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes; display default or anything unknown takes the setting related_posts_display.
	 * @return string grid or slider.
	 */
	public static function display( array $attributes ): string {
		$choice = Query_Args::choice( $attributes['display'] ?? null );
		if ( ! in_array( $choice, self::DISPLAYS, true ) ) {
			$setting = self::setting( 'related_posts_display' );
			$choice  = is_string( $setting ) && in_array( $setting, self::DISPLAYS, true ) ? $setting : self::DISPLAYS[0];
		}
		return 'slider' === $choice && self::slider_available() ? 'slider' : 'grid';
	}

	/**
	 * Returns the heading: the block attribute heading, else the setting related_posts_heading, else the translated "Related posts".
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Heading, not escaped.
	 */
	public static function heading( array $attributes ): string {
		$text = is_string( $attributes['heading'] ?? null ) ? trim( $attributes['heading'] ) : '';
		if ( '' === $text ) {
			$setting = self::setting( 'related_posts_heading' );
			$text    = is_string( $setting ) ? trim( $setting ) : '';
		}
		return '' === $text ? __( 'Related posts', 'creationell-wp-theme' ) : $text;
	}

	/**
	 * Returns the heading level of the section: 2 to 6, anything else gives 2.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $level Attribute headingLevel: number, numeric string or Blockstudio option.
	 * @return int Level.
	 */
	public static function heading_level( mixed $level ): int {
		$level = Query_Args::choice( $level );
		$level = 1 === preg_match( '~^\d$~', $level ) ? (int) $level : 0;
		return $level >= Display_Options::MIN_LEVEL && $level <= Display_Options::MAX_LEVEL ? $level : self::HEADING_LEVEL;
	}

	/**
	 * Returns the settings Related_Query reads; a block count from 1 up replaces related_posts_count.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return array<string, mixed> related_posts_post_types, related_posts_taxonomies and related_posts_count.
	 */
	public static function query_settings( array $attributes ): array {
		$count = is_numeric( $attributes['count'] ?? null ) ? (int) $attributes['count'] : 0;
		return array(
			'related_posts_post_types' => self::setting( 'related_posts_post_types' ),
			'related_posts_taxonomies' => self::setting( 'related_posts_taxonomies' ),
			'related_posts_count'      => $count >= 1 ? min( $count, Related_Query::MAX_COUNT ) : self::setting( 'related_posts_count' ),
		);
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
	 * The block root carries wp-block-creationell-theme-related-posts; the
	 * automatic output carries creationell-theme-related-posts--auto instead,
	 * whose bottom margin comes from post-lists.css, and ignores the block
	 * supports.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $display    grid or slider, added as creationell-theme-related-posts--<display>.
	 * @param bool                 $block      True for the block, false for the automatic output.
	 * @return array{class: string, id: string} Class list and ID; the ID is empty without anchor.
	 */
	public static function root( array $attributes, string $display, bool $block ): array {
		$display = in_array( $display, self::DISPLAYS, true ) ? $display : self::DISPLAYS[0];
		$classes = array( 'creationell-theme-related-posts', 'creationell-theme-related-posts--' . $display );
		if ( ! $block ) {
			$classes[] = 'creationell-theme-related-posts--auto';
			return array(
				'class' => implode( ' ', $classes ),
				'id'    => '',
			);
		}
		array_unshift( $classes, 'wp-block-creationell-theme-related-posts' );
		$align = is_string( $attributes['align'] ?? null ) ? $attributes['align'] : '';
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
	 * Queries the related posts and prints them through the template part; resets the post data afterwards.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes; empty for the automatic output.
	 * @param bool                 $editor     True while the block editor renders the block.
	 * @param int                  $post_id    Post the related posts belong to; 0 outside a post.
	 * @param bool                 $block      True for the block, false for the automatic output.
	 * @return void
	 */
	private static function output( array $attributes, bool $editor, int $post_id, bool $block ): void {
		$args = $post_id > 0 ? Related_Query::args( $post_id, self::query_settings( $attributes ) ) : null;
		if ( null === $args ) {
			if ( $editor ) {
				printf( '<p class="creationell-theme-related-posts__hint text-body-secondary">%s</p>', esc_html__( 'Shows related posts on single posts.', 'creationell-wp-theme' ) );
			}
			return;
		}
		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) {
			if ( $editor ) {
				get_template_part( self::EMPTY_PART );
			}
			return;
		}
		$display = self::display( $attributes );
		$columns = max( 1, min( self::MAX_COLUMNS, $query->post_count ) );
		$level   = self::heading_level( $attributes['headingLevel'] ?? null );
		$cards   = min( Display_Options::MAX_LEVEL, $level + 1 );
		if ( ! $editor && class_exists( Post_Lists_Module::class ) ) {
			Post_Lists_Module::enqueue_style();
		}
		get_template_part(
			self::PART,
			null,
			array(
				'query'         => $query,
				'display'       => Display_Options::from_attributes( array( 'headingLevel' => $cards ), 'related-posts' ),
				'layout'        => $display,
				'columns'       => $columns,
				'heading'       => self::heading( $attributes ),
				'heading_level' => $level,
				'heading_id'    => wp_unique_id( 'creationell-theme-related-posts-heading-' ),
				'editor'        => $editor,
				'root'          => self::root( $attributes, $display, $block ),
				'slider'        => array(
					'layout'         => 'columns',
					'columns'        => $columns,
					'loop'           => false,
					'autoplay'       => false,
					'navigation'     => true,
					'paginationDots' => true,
					'headingLevel'   => $cards,
				),
			)
		);
		wp_reset_postdata();
	}

	/**
	 * Enqueues the stylesheet of the post lists and, for the slider, the assets of the post slider.
	 *
	 * @since 1.0.0
	 *
	 * @param string $display grid or slider.
	 * @return void
	 */
	private static function enqueue_assets( string $display ): void {
		if ( class_exists( Post_Lists_Module::class ) ) {
			Post_Lists_Module::enqueue_style();
		}
		if ( 'slider' === $display && self::slider_available() ) {
			Post_Slider_Module::enqueue_assets();
		}
	}

	/**
	 * Tells whether the slider display is possible: the module post-slider is not off and its class is loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the related posts may show as a slider.
	 */
	private static function slider_available(): bool {
		return 'off' !== creationell_wp_theme_module_state( self::SLIDER_MODULE ) && class_exists( Post_Slider_Module::class );
	}

	/**
	 * Returns the enabled post types of the setting related_posts_post_types, without attachment.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Post type names.
	 */
	private static function post_types(): array {
		$value = self::setting( 'related_posts_post_types' );
		$types = array();
		foreach ( explode( ',', is_string( $value ) ? $value : '' ) as $name ) {
			$name = trim( $name );
			if ( '' !== $name && 'attachment' !== $name ) {
				$types[] = $name;
			}
		}
		return $types;
	}

	/**
	 * Returns a setting of the module, or its default while the setting is not registered.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key One of the keys of SETTINGS.
	 * @return mixed Value.
	 */
	private static function setting( string $key ): mixed {
		if ( ! Registry::instance()->has( $key ) ) {
			return self::SETTINGS[ $key ] ?? null;
		}
		return Settings::instance()->get( $key );
	}
}
