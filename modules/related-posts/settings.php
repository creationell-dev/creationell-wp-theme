<?php
/**
 * Settings of the module related-posts: post types, taxonomies, number, display and heading.
 *
 * Loaded by Registry::load_modules() for the installed module, whatever its
 * state; the file returns a list of Definition objects and declares nothing, so
 * it may run more than once per request. Which posts count as related and how
 * they show is an advanced design setting of the agency; the heading is a text
 * of the site, per language (FS-5).
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
		key: 'related_posts_post_types',
		type: 'string',
		default_value: 'post',
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'related-posts',
		max_length: 200,
		validate: static fn( mixed $value ): bool => is_string( $value )
			&& 1 === preg_match( '~^[a-z0-9_-]+(?:, ?[a-z0-9_-]+)*$~D', $value )
			&& ! in_array( 'attachment', array_map( 'trim', explode( ',', $value ) ), true ),
		label: static fn(): string => __( 'Post types with related posts', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Names of the public post types, separated by commas, e.g. post,page. Media are never included.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'related_posts_taxonomies',
		type: 'enum',
		default_value: 'category',
		choices: array(
			'category' => true,
			'post_tag' => true,
			'both'     => true,
		),
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'related-posts',
		validate: static fn( mixed $value ): bool => in_array( $value, array( 'category', 'post_tag', 'both' ), true ),
		label: static fn(): string => __( 'Related by', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Posts are related when they share a category, a tag or either of both.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'related_posts_count',
		type: 'int',
		default_value: 3,
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'related-posts',
		validate: static fn( mixed $value ): bool => is_int( $value ) && $value >= 1 && $value <= 12,
		label: static fn(): string => __( 'Number of related posts', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'From 1 to 12.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'related_posts_display',
		type: 'enum',
		default_value: 'grid',
		choices: array(
			'grid'   => true,
			'slider' => true,
		),
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'related-posts',
		validate: static fn( mixed $value ): bool => in_array( $value, array( 'grid', 'slider' ), true ),
		label: static fn(): string => __( 'Display of related posts', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Grid or slider; the slider needs the module post slider and shows a grid while it is off.', 'creationell-wp-theme' ),
	),
	new Definition(
		key: 'related_posts_heading',
		type: 'string',
		default_value: '',
		level: 'basic',
		capability: Registry::CAP_BASIC,
		translatable: true,
		section: 'texts',
		group: 'related-posts',
		max_length: 120,
		validate: static fn( mixed $value ): bool => is_string( $value ) && mb_strlen( $value, 'UTF-8' ) <= 120,
		label: static fn(): string => __( 'Heading of the related posts', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Empty shows "Related posts".', 'creationell-wp-theme' ),
	),
);
