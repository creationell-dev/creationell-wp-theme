<?php
/**
 * Archives Block Widget.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Archive Block
 */
if ( ! function_exists( 'creationell_wp_theme_block_widget_archives_classes' ) ) {
	/**
	 * Adds Bootstrap classes to archive block widget.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_widget_archives_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		// Check if the block contains the 'wp-block-archives-list' class, exclude the dropdown.
		if ( strpos( $block_content, 'wp-block-archives-list' ) !== false ) {
			$search  = array(
				'wp-block-archives-list',
				'<li',
				'<a',
				'(',
				')',
			);
			$replace = array(
				'wp-block-archives-list creationell-theme-list-group list-group',
				'<li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"',
				'<a class="stretched-link text-decoration-none"',
				'<span class="badge bg-primary-subtle text-primary-emphasis">',
				'</span>',
			);

			$block_content = str_replace( $search, $replace, $block_content );
		}

		/**
		 * Filters the HTML of the archives block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_archives_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/archives', 'creationell_wp_theme_block_widget_archives_classes', 10, 2 );
