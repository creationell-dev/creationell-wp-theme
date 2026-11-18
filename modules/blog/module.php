<?php
/**
 * Manifest of the module blog: sidebar, article parts, share links, 404 and author pages of the posts.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module adds a sidebar with categories and tags, the meta line with reading
 * time, a back link to the blog page, share links, a richer 404 page and
 * optional author pages to the posts of the site. It needs no plugin, no
 * other module and no Blockstudio, brings no block and stores nothing, so it
 * has no lifecycle callback. Its template parts live in template-parts/blog/,
 * which a child theme may override. Title and description stay closures, so
 * they are translated only when shown, after init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'           => 'blog',
	'title'          => static fn(): string => __( 'Blog', 'creationell-wp-theme' ),
	'description'    => static fn(): string => __( 'Sidebar with categories and tags, reading time, back link, share links, a richer 404 page and optional author pages for the posts of the site.', 'creationell-wp-theme' ),
	'type'           => 'integration',
	'states'         => array( 'active', 'off' ),
	'default'        => 'off',
	'requires'       => array(
		'plugins'     => array(),
		'modules'     => array(),
		'blockstudio' => false,
	),
	'legacy_plugins' => array(),
	'blocks'         => array(),
	'outposts'       => array( 'template-parts/blog/' ),
	'since'          => '1.0.0',
);
