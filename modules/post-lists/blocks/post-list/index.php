<?php
/**
 * Template of the block creationell-theme/post-list: the post list: grid, list or hero cards, with pagination on request.
 *
 * Blockstudio includes this file with the attributes in $a (also $attributes),
 * the block data in $block (with anchor, className and align) and $isEditor. The file only hands them to
 * Post_Lists_Module, which queries the posts and loads the template parts of
 * template-parts/post-lists/. Without the module class (module off) the block
 * renders nothing.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\PostLists\Post_Lists_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Post_Lists_Module::class ) ) {
	return;
}

$creationell_wp_theme_vars = get_defined_vars();
Post_Lists_Module::render_list(
	Post_Lists_Module::attributes( $creationell_wp_theme_vars['a'] ?? ( $creationell_wp_theme_vars['attributes'] ?? array() ), $creationell_wp_theme_vars['block'] ?? ( $creationell_wp_theme_vars['b'] ?? null ) ),
	true === ( $creationell_wp_theme_vars['isEditor'] ?? false ),
	Post_Lists_Module::post_id( $creationell_wp_theme_vars['block'] ?? ( $creationell_wp_theme_vars['b'] ?? null ) )
);
