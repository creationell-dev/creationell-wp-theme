<?php
/**
 * Title: s - Heading and subtitle
 * Slug: creationell-wp-theme/s-heading-subtitle
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"tagName":"section","metadata":{"name":"s - Heading and subtitle - py-5 bg-body-tertiary hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/s-heading-subtitle"},"className":"py-5 bg-body-tertiary hide-wp-block-classes","layout":{"type":"default"}} -->
<section class="wp-block-group py-5 bg-body-tertiary hide-wp-block-classes"><!-- wp:group {"metadata":{"name":"container"},"className":"container","layout":{"type":"default"}} -->
<div class="wp-block-group container"><!-- wp:heading {"className":"text-center mb-2"} -->
<h2 class="wp-block-heading text-center mb-2"><?php esc_html_e( 'Section with heading', 'creationell-wp-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"name":"lead text-center mb-4"},"className":"lead text-center mb-4"} -->
<p class="lead text-center mb-4"><?php echo wp_kses_post( __( 'This is a <code>section</code> with heading and subtitle wrapped in a <code>container</code>. Use it on the <code>page-blank</code> template.', 'creationell-wp-theme' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"mb-0"},"className":"mb-0"} -->
<p class="mb-0"><?php echo wp_kses_post( __( 'Some content inside the <code>container</code>.', 'creationell-wp-theme' ) ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->