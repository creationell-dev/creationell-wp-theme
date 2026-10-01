<?php
/**
 * Template of the block creationell-theme/related-posts: the posts related to the current post, as a grid or a slider.
 *
 * Blockstudio includes this file with the attributes in $a (also $attributes),
 * the block data in $block (with anchor, className and align) and $isEditor. The file only hands them to
 * Related_Posts_Module, which queries the related posts and loads
 * template-parts/related-posts/related-posts.php. Without the module class
 * (module off) the block renders nothing.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\RelatedPosts\Related_Posts_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Related_Posts_Module::class ) ) {
	return;
}

$creationell_wp_theme_vars = get_defined_vars();
Related_Posts_Module::render(
	Related_Posts_Module::attributes( $creationell_wp_theme_vars['a'] ?? ( $creationell_wp_theme_vars['attributes'] ?? array() ), $creationell_wp_theme_vars['block'] ?? ( $creationell_wp_theme_vars['b'] ?? null ) ),
	true === ( $creationell_wp_theme_vars['isEditor'] ?? false ),
	Related_Posts_Module::post_id( $creationell_wp_theme_vars['block'] ?? ( $creationell_wp_theme_vars['b'] ?? null ) )
);
