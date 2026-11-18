<?php
/**
 * Manifest of the module post-filter: the filters of JPKCom Post Filter in the blog sidebar.
 *
 * Returns the manifest array that Module_Manifest::from_file() validates. The
 * module needs the plugin JPKCom Post Filter and the module blog; while one of
 * them is missing it stays off. It brings no block and no settings and stores
 * nothing, so it has no lifecycle callback. Its template part lives in
 * template-parts/post-filter/, which a child theme may override. Title and
 * description stay closures, so they are translated only when shown, after
 * init.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	'slug'           => 'post-filter',
	'title'          => static fn(): string => __( 'Post filter', 'creationell-wp-theme' ),
	'description'    => static fn(): string => __( 'Shows the filters of the plugin JPKCom Post Filter in the blog sidebar instead of the plain category and tag lists.', 'creationell-wp-theme' ),
	'type'           => 'integration',
	'states'         => array( 'active', 'off' ),
	'default'        => 'off',
	'requires'       => array(
		'plugins'     => array( 'jpkcom-post-filter/jpkcom-post-filter.php' ),
		'modules'     => array( 'blog' ),
		'blockstudio' => false,
	),
	'legacy_plugins' => array(),
	'blocks'         => array(),
	'outposts'       => array( 'template-parts/post-filter/' ),
	'since'          => '1.0.0',
);
