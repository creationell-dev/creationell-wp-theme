<?php
/**
 * Template part to initialize the navbar menu
 * Template Version: 6.3.1
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>


<?php
// Bootstrap 5 Nav Walker.
/**
 * Filters the CSS classes of the main menu list.
 *
 * @since 1.0.0
 *
 * @param string $classes Space-separated CSS classes.
 */
wp_nav_menu(
	array(
		'theme_location' => 'main-menu',
		'container'      => '',
		'menu_class'     => '',
		'fallback_cb'    => '__return_false',
		'items_wrap'     => '<ul id="creationell-theme-navbar" class="navbar-nav ' . esc_attr( apply_filters( 'creationell_wp_theme_class_header_navbar_nav', 'ms-auto' ) ) . ' %2$s">%3$s</ul>',
		'depth'          => 2,
		'walker'         => new Creationell_Wp_Theme_Nav_Walker(),
	)
);

