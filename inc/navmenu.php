<?php
/**
 * Nav menus.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_register_navmenu' ) ) :
	/**
	 * Registers the nav menus.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_register_navmenu(): void {
		register_nav_menu( 'main-menu', 'Main menu' );
		register_nav_menu( 'footer-menu', 'Footer menu' );
	}
endif;
add_action( 'after_setup_theme', 'creationell_wp_theme_register_navmenu' );
