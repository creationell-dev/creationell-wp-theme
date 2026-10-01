<?php
/**
 * Latest Posts Block Widget.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Latest Posts Block
 */
if ( ! function_exists( 'creationell_wp_theme_block_widget_latest_posts_classes' ) ) {
	/**
	 * Adds Bootstrap classes to latest post block widget.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_widget_latest_posts_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		$search  = array(
			'wp-block-latest-posts__list',
			'<li',
			'wp-post-image',
			'<a',
			'wp-block-latest-posts__post-author',
			'wp-block-latest-posts__post-date',
			'wp-block-latest-posts__post-excerpt',
		);
		$replace = array(
			'wp-block-latest-posts__list creationell-theme-list-group list-group',
			'<li class="list-group-item list-group-item-action"',
			'wp-post-image rounded mb-3',
			'<a class="stretched-link text-decoration-none"',
			'small text-body-secondary',
			'small text-body-secondary d-block',
			'wp-block-latest-posts__post-excerpt mb-0',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		/**
		 * Filters the HTML of the latest posts block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_latest_posts_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/latest-posts', 'creationell_wp_theme_block_widget_latest_posts_classes', 10, 2 );
