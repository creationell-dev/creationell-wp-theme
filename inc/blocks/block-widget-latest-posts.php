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

		// Classes go on single tags: the title link is the one stretched link of an
		// item, other links keep their own class attribute.
		$tags = new WP_HTML_Tag_Processor( $block_content );
		while ( $tags->next_tag() ) {
			$tag = $tags->get_tag();
			if ( 'UL' === $tag && creationell_wp_theme_tag_has_class( $tags, 'wp-block-latest-posts__list' ) ) {
				creationell_wp_theme_tag_classes( $tags, array( 'creationell-theme-list-group', 'list-group' ) );
			} elseif ( 'LI' === $tag ) {
				creationell_wp_theme_tag_classes( $tags, array( 'list-group-item', 'list-group-item-action' ) );
			} elseif ( 'IMG' === $tag && creationell_wp_theme_tag_has_class( $tags, 'wp-post-image' ) ) {
				creationell_wp_theme_tag_classes( $tags, array( 'rounded', 'mb-3' ) );
			} elseif ( 'A' === $tag && creationell_wp_theme_tag_has_class( $tags, 'wp-block-latest-posts__post-title' ) ) {
				creationell_wp_theme_tag_classes( $tags, array( 'stretched-link', 'text-decoration-none' ) );
			} elseif ( 'A' === $tag ) {
				creationell_wp_theme_tag_classes( $tags, array( 'text-decoration-none' ) );
			} elseif ( creationell_wp_theme_tag_has_class( $tags, 'wp-block-latest-posts__post-author' ) ) {
				creationell_wp_theme_tag_classes( $tags, array( 'small', 'text-body-secondary' ), array( 'wp-block-latest-posts__post-author' ) );
			} elseif ( creationell_wp_theme_tag_has_class( $tags, 'wp-block-latest-posts__post-date' ) ) {
				creationell_wp_theme_tag_classes( $tags, array( 'small', 'text-body-secondary', 'd-block' ), array( 'wp-block-latest-posts__post-date' ) );
			} elseif ( creationell_wp_theme_tag_has_class( $tags, 'wp-block-latest-posts__post-excerpt' ) ) {
				creationell_wp_theme_tag_classes( $tags, array( 'mb-0' ) );
			}
		}
		$block_content = $tags->get_updated_html();

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
