<?php
/**
 * Template part to initialize the footer menu
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
wp_nav_menu(
	array(
		'theme_location'       => 'footer-menu',
		'container'            => 'nav',
		'container_aria_label' => __( 'Footer menu', 'creationell-wp-theme' ),
		'menu_class'           => '',
		'fallback_cb'          => '__return_false',
		'items_wrap'           => '<ul id="footer-menu" class="nav %2$s">%3$s</ul>',
		'depth'                => 1,
		'walker'               => new Creationell_Wp_Theme_Nav_Walker(),
	)
);

