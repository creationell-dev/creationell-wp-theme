<?php
/**
 * Keeps the block editor away from the directories of WordPress.org.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Removes the block directory: the inserter suggests no blocks to install from WordPress.org.
 *
 * @since 1.0.0
 *
 * @return void
 */
function creationell_wp_theme_disable_block_directory(): void {
	remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
}
add_action( 'init', 'creationell_wp_theme_disable_block_directory' );

// No core, featured or theme patterns from the WordPress.org Pattern Directory.
add_filter( 'should_load_remote_block_patterns', '__return_false' );
