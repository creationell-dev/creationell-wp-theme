<?php
/**
 * Display options of the post cards.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Posts;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Turns the show* attributes and the heading level of the post blocks into card options.
 *
 * The card templates of template-parts/post-lists/ read the options from
 * $args['display'] and repair them with normalize(). card_classes() runs the
 * filter creationell_wp_theme_post_card_classes for every card. The class
 * registers no hooks.
 *
 * @phpstan-type Options array{show_image: bool, show_categories: bool, show_meta: bool, show_excerpt: bool, show_read_more: bool, show_tags: bool, heading_level: int, context: string}
 *
 * @since 1.0.0
 */
final class Display_Options {

	/**
	 * Filter of the card classes.
	 *
	 * @since 1.0.0
	 */
	public const CLASSES_FILTER = 'creationell_wp_theme_post_card_classes';

	/**
	 * Toggles: option key => attribute and default.
	 *
	 * @since 1.0.0
	 */
	public const TOGGLES = array(
		'show_image'      => array( 'showImage', true ),
		'show_categories' => array( 'showCategories', true ),
		'show_meta'       => array( 'showMeta', true ),
		'show_excerpt'    => array( 'showExcerpt', true ),
		'show_read_more'  => array( 'showReadMore', true ),
		'show_tags'       => array( 'showTags', false ),
	);

	/**
	 * Lowest heading level of a card title.
	 *
	 * @since 1.0.0
	 */
	public const MIN_LEVEL = 2;

	/**
	 * Highest heading level of a card title.
	 *
	 * @since 1.0.0
	 */
	public const MAX_LEVEL = 6;

	/**
	 * Returns the card options of a block.
	 *
	 * Example:
	 *
	 *     get_template_part( 'template-parts/post-lists/card-grid', null, array(
	 *         'display' => Display_Options::from_attributes( $a, 'post-list' ),
	 *     ) );
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $attributes Block attributes (showImage, showCategories, showMeta, showExcerpt, showReadMore, showTags, headingLevel).
	 * @param string               $context    Block context; an unknown value counts as post-list.
	 * @return array{show_image: bool, show_categories: bool, show_meta: bool, show_excerpt: bool, show_read_more: bool, show_tags: bool, heading_level: int, context: string} Options.
	 */
	public static function from_attributes( array $attributes, string $context ): array {
		$context = Query_Args::context( $context );
		$options = array();
		foreach ( self::TOGGLES as $key => $toggle ) {
			$options[ $key ] = Query_Args::flag( $attributes[ $toggle[0] ] ?? null, $toggle[1] );
		}
		return array(
			'show_image'      => $options['show_image'],
			'show_categories' => $options['show_categories'],
			'show_meta'       => $options['show_meta'],
			'show_excerpt'    => $options['show_excerpt'],
			'show_read_more'  => $options['show_read_more'],
			'show_tags'       => $options['show_tags'],
			'heading_level'   => self::level( $attributes['headingLevel'] ?? null, $context ),
			'context'         => $context,
		);
	}

	/**
	 * Returns valid options from a template argument; anything that is not an array gives the defaults of a post list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $display Options as from_attributes() returns them, possibly changed by a child theme.
	 * @return array{show_image: bool, show_categories: bool, show_meta: bool, show_excerpt: bool, show_read_more: bool, show_tags: bool, heading_level: int, context: string} Options.
	 */
	public static function normalize( mixed $display ): array {
		$display    = is_array( $display ) ? $display : array();
		$context    = is_string( $display['context'] ?? null ) ? $display['context'] : 'post-list';
		$attributes = array( 'headingLevel' => $display['heading_level'] ?? null );
		foreach ( self::TOGGLES as $key => $toggle ) {
			$attributes[ $toggle[0] ] = is_bool( $display[ $key ] ?? null ) ? $display[ $key ] : null;
		}
		return self::from_attributes( $attributes, $context );
	}

	/**
	 * Returns the CSS classes of the elements of a card after the filter creationell_wp_theme_post_card_classes.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $defaults Classes by element (card, image, body, title, link and others).
	 * @param string                $part     Card template: card-grid, card-list or card-hero.
	 * @param string                $context  Block context: post-list, post-slider or related-posts.
	 * @return array<string, string> Classes by element; the keys of $defaults, with a filtered value only where it is a string.
	 */
	public static function card_classes( array $defaults, string $part, string $context ): array {
		/**
		 * Filters the CSS classes of the elements of a post card of the post lists, the slider and the related posts.
		 *
		 * Example:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_post_card_classes',
		 *         static function ( array $classes, string $part ): array {
		 *             if ( 'card-grid' === $part ) {
		 *                 $classes['card'] .= ' shadow-sm';
		 *             }
		 *             return $classes;
		 *         },
		 *         10,
		 *         2
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $classes Space-separated classes by element: card, image, body, badges, title, link, meta, read_more, tags and more per template.
		 * @param string                $part    Card template: card-grid, card-list or card-hero.
		 * @param string                $context Block context: post-list, post-slider or related-posts; the accordion and the tabs of post-accordion show panels without cards.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_post_card_classes', $defaults, $part, $context );
		$filtered = is_array( $filtered ) ? $filtered : array();
		$classes  = array();
		foreach ( $defaults as $key => $value ) {
			$classes[ $key ] = is_string( $filtered[ $key ] ?? null ) ? $filtered[ $key ] : $value;
		}
		return $classes;
	}

	/**
	 * Returns the heading level: 2 to 6, else 2 (3 in the accordion).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $value   Attribute headingLevel: int, numeric string or Blockstudio option.
	 * @param string $context Known context.
	 * @return int Heading level.
	 */
	private static function level( mixed $value, string $context ): int {
		$value = Query_Args::choice( is_int( $value ) ? (string) $value : $value );
		$level = 1 === preg_match( '~^\d$~', $value ) ? (int) $value : 0;
		if ( $level < self::MIN_LEVEL || $level > self::MAX_LEVEL ) {
			return 'post-accordion' === $context ? 3 : 2;
		}
		return $level;
	}
}
