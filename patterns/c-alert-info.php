<?php
/**
 * Title: c - Alert-info
 * Slug: creationell-wp-theme/c-alert-info
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:paragraph {"metadata":{"name":"c - Alert info - alert alert-info hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/c.alert-info"},"className":"alert alert-info hide-wp-block-classes"} -->
<p class="alert alert-info hide-wp-block-classes"><?php echo wp_kses_post( __( 'A simple info alert with a <a href="#">link</a>.', 'creationell-wp-theme' ) ); ?></p>
<!-- /wp:paragraph -->