<?php
/**
 * Patterns.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Registers the pattern category of the theme.
 *
 * @since 1.0.0
 *
 * @return void
 */
function creationell_wp_theme_pattern_category(): void {
	register_block_pattern_category(
		'creationell-wp-theme',
		array( 'label' => __( 'creationell Theme', 'creationell-wp-theme' ) )
	);
}
add_action( 'init', 'creationell_wp_theme_pattern_category' );


/**
 * Removes block classes inside a group with the class hide-wp-block-classes.
 *
 * Removed classes: wp-block-group, is-layout-flow, wp-block-group-is-layout-flow,
 * wp-block-heading, wp-block-list, wp-block-image, is-layout-constrained and
 * -is-layout-constrained.
 *
 * @since 1.0.0
 *
 * @param mixed $block_content The block content.
 * @param mixed $block         The full block, including name and attributes.
 * @return mixed The filtered block content; other values than a string come back unchanged.
 */
function creationell_wp_theme_remove_block_classes( mixed $block_content, mixed $block ): mixed {
	if ( ! is_string( $block_content ) || ! is_array( $block ) ) {
		return $block_content;
	}

	// Only the target block types, and only inside a parent with the required class.
	if ( ! in_array( $block['blockName'] ?? null, array( 'core/group', 'core/heading', 'core/list', 'core/image' ), true ) || ! str_contains( $block_content, 'hide-wp-block-classes' ) ) {
		return $block_content;
	}

	// Remove the unwanted classes.
	$cleaned = preg_replace(
		'/\bwp-block-group\b|\bis-layout-flow\b|\bwp-block-group-is-layout-flow\b|\bwp-block-heading\b|\bwp-block-list\b|\bwp-block-image\b|\bis-layout-constrained\b|\b\-is-layout-constrained\b/',
		'',
		$block_content
	) ?? $block_content;

	// Clean up any remaining class attribute.
	$cleaned = preg_replace_callback(
		'/class="([^"]*)"/',
		static function ( array $matches ): string {
			// Split the class names, remove empty or invalid ones.
			$classes = array_filter(
				array_map( 'trim', explode( ' ', $matches[1] ) ),
				static function ( string $class_name ): bool {
					return '-' !== $class_name && '' !== $class_name;
				}
			);

			// Rebuild the class attribute or return an empty string if no classes remain.
			return array() !== $classes ? 'class="' . implode( ' ', $classes ) . '"' : '';
		},
		$cleaned
	) ?? $cleaned;

	// Remove any leftover empty class attributes.
	return preg_replace( '/\sclass=""/', '', $cleaned ) ?? $cleaned;
}
add_filter( 'render_block', 'creationell_wp_theme_remove_block_classes', 10, 2 );
