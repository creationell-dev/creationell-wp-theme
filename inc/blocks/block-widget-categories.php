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

		// The list only; the dropdown stays as it is. Classes go on single tags, so
		// longer class names (cat-item-1, current-cat-parent) and names stay whole.
		if ( false !== strpos( $block_content, 'wp-block-categories-list' ) ) {
			$tags = new WP_HTML_Tag_Processor( $block_content );
			while ( $tags->next_tag() ) {
				$tag = $tags->get_tag();
				if ( 'UL' === $tag && creationell_wp_theme_tag_has_class( $tags, 'wp-block-categories-list' ) ) {
					creationell_wp_theme_tag_classes( $tags, array( 'creationell-theme-list-group', 'list-group' ) );
				} elseif ( 'LI' === $tag && creationell_wp_theme_tag_has_class( $tags, 'cat-item' ) ) {
					$current = creationell_wp_theme_tag_has_class( $tags, 'current-cat' ) ? array( 'active' ) : array();
					creationell_wp_theme_tag_classes( $tags, array_merge( array( 'list-group-item', 'list-group-item-action', 'd-flex', 'justify-content-between', 'align-items-center' ), $current ) );
				} elseif ( 'LI' === $tag && creationell_wp_theme_tag_has_class( $tags, 'cat-item-none' ) ) {
					creationell_wp_theme_tag_classes( $tags, array( 'list-group-item', 'text-body-secondary' ) );
				} elseif ( 'A' === $tag ) {
					creationell_wp_theme_tag_classes( $tags, array( 'stretched-link', 'text-decoration-none' ) );
				}
			}
			// The post count follows the link as " (3)"; parentheses in a name stay inside the link.
			$block_content = (string) preg_replace( '~</a>\s*\(([^()<>]+)\)~u', '</a> <span class="badge bg-primary-subtle text-primary-emphasis">$1</span>', $tags->get_updated_html() );
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
