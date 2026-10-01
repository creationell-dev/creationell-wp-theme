<?php
/**
 * Block Table.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Table Block
 */
if ( ! function_exists( 'creationell_wp_theme_block_table_classes' ) ) {
	/**
	 * Adds Bootstrap classes to block table.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_table_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		$search = array(
			'wp-block-table',
			'<table',
		);
		/**
		 * Filters the CSS classes added to the table block.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$replace = array(
			'table-responsive text-nowrap', // text-nowrap because tables inherits text-wrap from <body>.
			'<table class="table ' . esc_attr( apply_filters( 'creationell_wp_theme_class_block_table', '' ) ) . '"',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		/**
		 * Filters the HTML of the table block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_table_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/table', 'creationell_wp_theme_block_table_classes', 10, 2 );
