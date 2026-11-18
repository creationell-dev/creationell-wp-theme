<?php
/**
 * Template part: the filter bar of the plugin JPKCom Post Filter at the top of the blog sidebar.
 *
 * Prints the markup of the plugin unchanged: the plugin escapes it and names
 * its own navigation landmark. The module post-filter prints this part on the
 * action creationell_wp_theme_before_sidebar_widgets in place of the term
 * lists of the module blog.
 *
 * Arguments (raw values): html (markup of
 * jpkcom_postfilter_shortcode_filter() for posts in the layout sidebar;
 * empty prints nothing).
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_html = is_string( $creationell_wp_theme_args['html'] ?? null ) ? trim( $creationell_wp_theme_args['html'] ) : '';
if ( '' === $creationell_wp_theme_html ) {
	return;
}
// The markup of the plugin, unchanged: the plugin escapes its own output (see phpcs.xml.dist).
echo $creationell_wp_theme_html;
