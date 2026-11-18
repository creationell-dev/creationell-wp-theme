<?php
/**
 * Functions which enhance the theme by hooking into WordPress.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Adds custom classes to the array of body classes.
 *
 * @since 1.0.0
 *
 * @param mixed $classes Classes for the body element.
 * @return mixed Classes with hfeed and no-sidebar where they apply, other values unchanged.
 */
function creationell_wp_theme_body_classes( mixed $classes ): mixed {
	if ( ! is_array( $classes ) ) {
		return $classes;
	}

	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	/**
	 * Filters whether the sidebar shows without widgets, for example with the term lists of the blog.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force Whether to show the sidebar without widgets.
	 */
	$force = (bool) apply_filters( 'creationell_wp_theme_blog_sidebar_force', false );

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) && ! $force ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}

add_filter( 'body_class', 'creationell_wp_theme_body_classes' );


/**
 * Adds a pingback URL auto-discovery header for single posts, pages, or attachments.
 *
 * @since 1.0.0
 *
 * @return void
 */
function creationell_wp_theme_pingback_header(): void {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}

add_action( 'wp_head', 'creationell_wp_theme_pingback_header' );
