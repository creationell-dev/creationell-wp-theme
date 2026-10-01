<?php
/**
 * Template of the block creationell-theme/post-slider: the post slider: cards in columns or large image cards.
 *
 * Blockstudio includes this file with the attributes in $a (also $attributes),
 * the block data in $block (with anchor, className and align) and $isEditor. The file only hands them to
 * Post_Slider_Module, which queries the posts and loads
 * template-parts/post-slider/slider.php. Without the module class (module off)
 * the block renders nothing.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Modules\PostSlider\Post_Slider_Module;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Post_Slider_Module::class ) ) {
	return;
}

$creationell_wp_theme_vars = get_defined_vars();
Post_Slider_Module::render(
	Post_Slider_Module::attributes( $creationell_wp_theme_vars['a'] ?? ( $creationell_wp_theme_vars['attributes'] ?? array() ), $creationell_wp_theme_vars['block'] ?? ( $creationell_wp_theme_vars['b'] ?? null ) ),
	true === ( $creationell_wp_theme_vars['isEditor'] ?? false ),
	Post_Slider_Module::post_id( $creationell_wp_theme_vars['block'] ?? ( $creationell_wp_theme_vars['b'] ?? null ) )
);
