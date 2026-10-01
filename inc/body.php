<?php
/**
 * Required WordPress classes on the body element.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Adds the text break class to the body classes.
 *
 * The WordPress theme unit test data needs word-break: break-word,
 * https://dev.bootscore.me/about/page-markup-and-formatting/.
 *
 * @since 1.0.0
 *
 * @param mixed $classes Body classes.
 * @return mixed Body classes with the theme class, other values unchanged.
 */
function creationell_wp_theme_wp_body_class( mixed $classes ): mixed {
	if ( ! is_array( $classes ) ) {
		return $classes;
	}

	/**
	 * Filters the CSS class that the theme adds to the body element.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classes Space-separated CSS classes.
	 */
	$classes[] = apply_filters( 'creationell_wp_theme_class_body', 'text-break' );

	return $classes;
}
add_filter( 'body_class', 'creationell_wp_theme_wp_body_class' );
