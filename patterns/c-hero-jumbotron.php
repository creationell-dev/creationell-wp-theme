<?php
/**
 * Title: c - Hero jumbotron
 * Slug: creationell-wp-theme/c-hero-jumbotron
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"metadata":{"name":"c - Hero jumbotron - mb-3 p-5 text-center bg-body-tertiary rounded-3 hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/c.hero-jumbotron"},"className":"mb-3 p-5 text-center bg-body-tertiary rounded-3 hide-wp-block-classes","layout":{"type":"default"}} -->
<div class="wp-block-group mb-3 p-5 text-center bg-body-tertiary rounded-3 hide-wp-block-classes"><!-- wp:heading {"level":1,"metadata":{"name":"text-body-emphasis"},"className":"text-body-emphasis"} -->
<h1 class="wp-block-heading text-body-emphasis"><?php esc_html_e( 'Basic jumbotron', 'creationell-wp-theme' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"name":"lead mb-0"},"className":"lead mb-0"} -->
<p class="lead mb-0"><?php esc_html_e( 'This is a simple Bootstrap jumbotron, recreated with built-in utility classes.', 'creationell-wp-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->