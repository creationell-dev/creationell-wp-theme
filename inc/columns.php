<?php
/**
 * Columns.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Determines the CSS class for the main column based on the presence of a sidebar.
 *
 * @since 1.0.0
 *
 * @param mixed $classes The default CSS class for the main column.
 * @return mixed "col-lg-9" if the sidebar is active, otherwise the given classes.
 */
function creationell_wp_theme_main_col_class_sidebar( mixed $classes ): mixed {
	if ( is_active_sidebar( 'sidebar-1' ) ) {
		// Sidebar is not empty.
		return 'col-lg-9';
	}

	return $classes;
}

add_filter( 'creationell_wp_theme_class_main_col', 'creationell_wp_theme_main_col_class_sidebar' );
