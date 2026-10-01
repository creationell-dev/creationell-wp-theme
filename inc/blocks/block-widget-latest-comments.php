<?php
/**
 * Latest Comments Block Widget.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Latest Comments Block
 */
if ( ! function_exists( 'creationell_wp_theme_block_widget_latest_commentss_classes' ) ) {
	/**
	 * Adds Bootstrap classes to latest comments block widget.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_widget_latest_commentss_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		$search  = array(
			'wp-block-latest-comments',
			'<li class="wp-block-latest-comments creationell-theme-list-group list-group__comment">',
			'avatar avatar-48 photo wp-block-latest-comments creationell-theme-list-group list-group__comment-avatar',
			'list-group__comment-meta',
			'<a class="wp-block-latest-comments creationell-theme-list-group list-group__comment-author',
			'<a class="wp-block-latest-comments creationell-theme-list-group list-group__comment-link',
			'wp-block-latest-comments creationell-theme-list-group list-group__comment-date',
			'<p',
		);
		$replace = array(
			'wp-block-latest-comments creationell-theme-list-group list-group',
			'<li class="list-group-item list-group-item-action text-body-secondary d-flex align-items-start">',
			'rounded-pill border p-1 me-2',
			'list-group__comment-meta lh-base',
			'<a class="text-decoration-none text-body-secondary',
			'<a class="stretched-link text-decoration-none d-block',
			'small',
			'<p class="text-body mt-2 mb-0"',
		);

		$block_content = str_replace( $search, $replace, $block_content );

		/**
		 * Filters the HTML of the latest comments block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_latest_comments_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/latest-comments', 'creationell_wp_theme_block_widget_latest_commentss_classes', 10, 2 );
