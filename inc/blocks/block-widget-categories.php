<?php
/**
 * Categories Block Widget.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Categories Block
 */
if ( ! function_exists( 'creationell_wp_theme_block_widget_categories_classes' ) ) {
	/**
	 * Adds Bootstrap classes to categories block widget.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_widget_categories_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		// Check if the block contains the 'wp-block-categories-list' class, exclude the dropdown.
		if ( strpos( $block_content, 'wp-block-categories-list' ) !== false ) {
			$search  = array(
				'wp-block-categories-list',
				'cat-item',
				'current-cat',
				'<a',
				'(',
				')',
			);
			$replace = array(
				'wp-block-categories-list creationell-theme-list-group list-group',
				'cat-item list-group-item list-group-item-action d-flex justify-content-between align-items-center',
				'current-cat active',
				'<a class="stretched-link text-decoration-none"',
				'<span class="badge bg-primary-subtle text-primary-emphasis">',
				'</span>',
			);

			$block_content = str_replace( $search, $replace, $block_content );
		}

		/**
		 * Filters the HTML of the categories block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_categories_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/categories', 'creationell_wp_theme_block_widget_categories_classes', 10, 2 );
