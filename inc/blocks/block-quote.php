<?php
/**
 * Block Quote.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Quote
 */
if ( ! function_exists( 'creationell_wp_theme_block_quote_classes' ) ) {
	/**
	 * Adds Bootstrap classes to block quote.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_quote_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		$search  = array(
			'<blockquote',
			'<cite',
		);
		$replace = array(
			'<blockquote class="blockquote"',
			'<cite class="blockquote-footer"',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		/**
		 * Filters the HTML of the quote block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_quote_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/quote', 'creationell_wp_theme_block_quote_classes', 10, 2 );
