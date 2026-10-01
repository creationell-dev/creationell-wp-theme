<?php
/**
 * Block Buttons.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'creationell_wp_theme_block_buttons_classes' ) ) {
	/**
	 * Adds the classes btn and btn-primary or btn-outline-primary to the buttons block.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_buttons_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}
		// Process only core/buttons blocks.
		if ( ! is_array( $block ) || 'core/buttons' !== ( $block['blockName'] ?? null ) ) {
			return $block_content;
		}

		// Replace wp-block-buttons-is-layout-flex with gap-1 mb-3.
		$block_content = str_replace(
			'wp-block-buttons-is-layout-flex',
			'gap-1 mb-3',
			$block_content
		);

		/**
		 * Use preg_replace_callback to process each individual button <div> in the block.
		 * The regex matches:
		 * 1. <div class="wp-block-button"> and captures any additional classes in $matches[1]
		 * 2. The <a> tag inside the div, capturing attributes before class="" in $matches[2]
		 * 3. The classes of the <a> element in $matches[3]
		 * This allows us to manipulate each button individually, detect outline styles,
		 * and apply Bootstrap btn classes appropriately.
		 */
		$block_content = preg_replace_callback(
			'/<div class="wp-block-button\b([^"]*)">\s*<a([^>]*)class="([^"]*)"/i',
			static function ( array $matches ): string {
				// Classes of the wp-block-button div.
				$div_classes = trim( $matches[1] );

				// Remove any is-style-outline--<number> class from the div.
				$div_classes = preg_replace( '/\bis-style-outline--\d+\b/', '', $div_classes ) ?? $div_classes;

				// Normalize whitespace in div classes.
				$div_classes = trim( preg_replace( '/\s+/', ' ', $div_classes ) ?? $div_classes );

				// Attributes of <a> before class="".
				$a_before = $matches[2];

				// Classes of the <a> element.
				$a_classes = $matches[3];

				// Detect if this p-block-button div has the outline style.
				$has_outline = str_contains( $div_classes, 'is-style-outline' );

				// Ensure base .btn class exists.
				if ( ! str_contains( $a_classes, 'btn' ) ) {
					$a_classes .= ' btn';
				}

				/**
				 * Determine the correct button style:
				 * If the parent div has is-style-outline, use btn-outline-primary.
				 * Otherwise, use btn-primary as default.
				 */
				if ( $has_outline ) {
					$a_classes = str_replace( 'btn-primary', '', $a_classes );
					if ( ! str_contains( $a_classes, 'btn-outline-primary' ) ) {
						$a_classes .= ' btn-outline-primary';
					}
				} else {
					$a_classes = str_replace( 'btn-outline-primary', '', $a_classes );
					if ( ! str_contains( $a_classes, 'btn-primary' ) ) {
						$a_classes .= ' btn-primary';
					}
				}

				// Normalize whitespace in <a> classes.
				$a_classes = trim( preg_replace( '/\s+/', ' ', $a_classes ) ?? $a_classes );

				// Reconstruct the div + <a> with updated classes.
				return '<div class="wp-block-button' . ( '' !== $div_classes ? ' ' . $div_classes : '' ) . '"><a' . $a_before . 'class="' . $a_classes . '"';
			},
			$block_content
		) ?? $block_content;

		/**
		 * Filters the HTML of the buttons block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_buttons_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/buttons', 'creationell_wp_theme_block_buttons_classes', 10, 2 );
