<?php
/**
 * Manifest of the module post-slider: a slider of posts in columns or as large image cards.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module brings the block creationell-theme/post-slider, its template part,
 * its script and stylesheet and the setting post_slider_autoplay_delay; it
 * replaces the plugin bs Swiper. The slides use the card templates of
 * template-parts/post-lists/, which ship with the theme, so the module does
 * not need the module post-lists. It needs Blockstudio for its block and
 * stores nothing, so it has no lifecycle callback. Title and description stay
 * closures, so they are translated only when shown, after init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'           => 'post-slider',
	'title'          => static fn(): string => __( 'Post slider', 'creationell-wp-theme' ),
	'description'    => static fn(): string => __( 'Block for a slider of posts, as cards in columns or as large image cards. Replaces the post sliders of the plugin bs Swiper.', 'creationell-wp-theme' ),
	'type'           => 'block',
	'states'         => array( 'active', 'hidden', 'off' ),
	'default'        => 'off',
	'requires'       => array(
		'plugins'     => array(),
		'modules'     => array(),
		'blockstudio' => '7.6',
	),
	'legacy_plugins' => array( 'bs-swiper/main.php' ),
	'blocks'         => array( 'creationell-theme/post-slider' ),
	'outposts'       => array( 'template-parts/post-slider/' ),
	'since'          => '1.0.0',
);
