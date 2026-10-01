<?php
/**
 * Template part for displaying custom loop items
 * Template Version: 6.4.0
 *
 * This template provides an action hook for developers to inject
 * completely custom loop item markup.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_context = 'custom';
?>

<?php
/**
 * Fires before a loop item.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_before_loop_item', $creationell_wp_theme_context );
?>

<?php
/**
 * Fires to render a loop item of a custom layout.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_custom_loop_item', $creationell_wp_theme_context );
?>

<?php
/**
 * Fires after a loop item.
 *
 * @since 1.0.0
 *
 * @param string $context Template part of the loop item, for example cards-grid.
 */
do_action( 'creationell_wp_theme_after_loop_item', $creationell_wp_theme_context );
