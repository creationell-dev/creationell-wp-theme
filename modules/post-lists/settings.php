<?php
/**
 * Settings of the module post-lists: the highest number of posts of a list.
 *
 * Loaded by Registry::load_modules() for the installed module, whatever its
 * state; the file returns a list of Definition objects and declares nothing, so
 * it may run more than once per request. Query_Args caps the attribute
 * postsPerPage of the post list, the accordion and the slider with this value.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Settings\Definition;
use Creationell\WpTheme\Settings\Registry;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return array(
	new Definition(
		key: 'post_lists_max_posts',
		type: 'int',
		default_value: 24,
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'post-lists',
		validate: static fn( mixed $value ): bool => is_int( $value ) && $value >= 1 && $value <= 50,
		label: static fn(): string => __( 'Most posts per list', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Upper limit for the number of posts of a post list, an accordion or a slider, from 1 to 50.', 'creationell-wp-theme' ),
	),
);
