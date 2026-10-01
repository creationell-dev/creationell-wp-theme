<?php
/**
 * Manifest of the module post-lists: post lists (grid, list, hero), accordion and tabs.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module brings the blocks creationell-theme/post-list and
 * creationell-theme/post-accordion, their template parts and the setting
 * post_lists_max_posts; it replaces the plugin bs Grid and, while active, takes
 * the block creabb/post-grid of CreaBootstrapBlocks out of the inserter. It
 * needs Blockstudio for its blocks and stores nothing, so it has no lifecycle
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
	'slug'           => 'post-lists',
	'title'          => static fn(): string => __( 'Post lists', 'creationell-wp-theme' ),
	'description'    => static fn(): string => __( 'Blocks for post lists as a grid, a list or large image cards, and as an accordion or tabs. Replaces the plugin bs Grid.', 'creationell-wp-theme' ),
	'type'           => 'block',
	'states'         => array( 'active', 'hidden', 'off' ),
	'default'        => 'off',
	'requires'       => array(
		'plugins'     => array(),
		'modules'     => array(),
		'blockstudio' => '7.6',
	),
	'legacy_plugins' => array( 'bs-grid/main.php' ),
	'blocks'         => array(
		'creationell-theme/post-list',
		'creationell-theme/post-accordion',
	),
	'outposts'       => array( 'template-parts/post-lists/' ),
	'since'          => '1.0.0',
);
