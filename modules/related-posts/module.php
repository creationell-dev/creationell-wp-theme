<?php
/**
 * Manifest of the module related-posts: related posts below a single post and as a block.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module shows posts that share a category or tag with the current post below
 * a single post, and brings the block creationell-theme/related-posts, its
 * template part and five settings; it replaces the related posts of the plugin
 * bs Swiper. The cards come from the module post-lists, which it requires; the
 * slider display needs the module post-slider, but only as an option. It needs
 * Blockstudio for its block and stores nothing, so it has no lifecycle
 * callback. Title and description stay closures, so they are translated only
 * when shown, after init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'           => 'related-posts',
	'title'          => static fn(): string => __( 'Related posts', 'creationell-wp-theme' ),
	'description'    => static fn(): string => __( 'Related posts below a single post and as a block: posts that share a category or a tag with the current post. Needs the module Post lists.', 'creationell-wp-theme' ),
	'type'           => 'block',
	'states'         => array( 'active', 'hidden', 'off' ),
	'default'        => 'off',
	'requires'       => array(
		'plugins'     => array(),
		'modules'     => array( 'post-lists' ),
		'blockstudio' => '7.6',
	),
	'legacy_plugins' => array( 'bs-swiper/main.php' ),
	'blocks'         => array( 'creationell-theme/related-posts' ),
	'outposts'       => array( 'template-parts/related-posts/' ),
	'since'          => '1.0.0',
);
