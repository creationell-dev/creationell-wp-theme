<?php
/**
 * Settings of the module post-slider: the delay of the automatic slideshow.
 *
 * Loaded by Registry::load_modules() for the installed module, whatever its
 * state; the file returns a list of Definition objects and declares nothing, so
 * it may run more than once per request. The delay is an advanced design
 * setting of the agency; each slider switches its slideshow on or off itself.
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
		key: 'post_slider_autoplay_delay',
		type: 'int',
		default_value: 6000,
		level: 'advanced',
		capability: Registry::CAP_DESIGN,
		section: 'design',
		group: 'post-slider',
		validate: static fn( mixed $value ): bool => is_int( $value ) && $value >= 3000 && $value <= 15000,
		label: static fn(): string => __( 'Slideshow delay in milliseconds', 'creationell-wp-theme' ),
		description: static fn(): string => __( 'Time each slide stays while a post slider plays by itself, from 3000 to 15000 milliseconds.', 'creationell-wp-theme' ),
	),
);
