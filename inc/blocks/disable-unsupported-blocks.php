<?php
/**
 * Disable unsupported blocks and patterns (allowlist).
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Allows only supported blocks in the post editor and on the block-based Widgets screen.
 *
 * @since 1.0.0
 *
 * @param mixed $allowed_blocks Allowed block types, or a boolean for all or none.
 * @param mixed $editor_context The current block editor context.
 * @return mixed The supported blocks in the post editor and on the Widgets screen, otherwise the given value.
 */
function creationell_wp_theme_allowed_block_types( mixed $allowed_blocks, mixed $editor_context ): mixed {
		$supported_blocks = array(

			// Core.
			'core/block',

			// Text.
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/quote',
			'core/code',
			'core/preformatted',
			'core/table',
			'core/freeform',

			// Media.
			'core/image',
			'core/gallery',
			'core/audio',
			'core/video',

			// Design.
			'core/button',
			'core/buttons',
			'core/group',
			'core/separator',
			'core/spacer',

			// Widgets.
			'core/archives',
			'core/calendar',
			'core/categories',
			'core/html',
			'core/latest-comments',
			'core/latest-posts',
			'core/search',
			'core/shortcode',

			// Embeds.
			'core/embed',

			// WooCommerce.
			'woocommerce/product-categories',
			'woocommerce/classic-shortcode',
			'woocommerce/cart',
			'woocommerce/checkout',
			'woocommerce/product-filters',
		);

		// Restrict to supported blocks in the post editor or on the block-based Widgets screen.
		$screen = is_admin() && function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ( $editor_context instanceof WP_Block_Editor_Context && ! empty( $editor_context->post ) ) || ( $screen instanceof WP_Screen && 'widgets' === $screen->id ) ) {
			return $supported_blocks;
		}

		return $allowed_blocks;
}
add_filter( 'allowed_block_types_all', 'creationell_wp_theme_allowed_block_types', 10, 2 );


/**
 * Removes the theme support for the core block patterns.
 *
 * @since 1.0.0
 *
 * @return void
 */
function creationell_wp_theme_disable_core_block_patterns(): void {
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'init', 'creationell_wp_theme_disable_core_block_patterns', 9 );
add_action( 'admin_init', 'creationell_wp_theme_disable_core_block_patterns' );


/**
 * Unregisters all WooCommerce block patterns.
 *
 * @since 1.0.0
 *
 * @return void
 */
function creationell_wp_theme_disable_all_woocommerce_patterns(): void {
	if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
		return;
	}

	$registry = WP_Block_Patterns_Registry::get_instance();

	foreach ( $registry->get_all_registered() as $pattern ) {
		$name = $pattern['name'] ?? null;
		if ( is_string( $name ) && ( str_starts_with( $name, 'woocommerce/' ) || str_starts_with( $name, 'woocommerce-blocks/' ) ) ) {
			unregister_block_pattern( $name );
		}
	}
}
add_action( 'init', 'creationell_wp_theme_disable_all_woocommerce_patterns', 20 );
