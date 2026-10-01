<?php
/**
 * Title: s - Hero jumbotron
 * Slug: creationell-wp-theme/s-hero-jumbotron
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"tagName":"section","metadata":{"name":"s - Hero jumbotron - bg-body-tertiary py-5 hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/section-hero-jumbotron"},"className":"bg-body-tertiary py-5 hide-wp-block-classes","layout":{"type":"default"}} -->
<section class="wp-block-group bg-body-tertiary py-5 hide-wp-block-classes"><!-- wp:group {"metadata":{"name":"container h-100 d-flex justify-content-center align-items-center"},"className":"container h-100 d-flex justify-content-center align-items-center","layout":{"type":"default"}} -->
<div class="wp-block-group container h-100 d-flex justify-content-center align-items-center"><!-- wp:group {"metadata":{"name":"text-center"},"className":"text-center","layout":{"type":"default"}} -->
<div class="wp-block-group text-center"><!-- wp:heading {"metadata":{"name":"display-5 fw-bold mb-2"},"className":"display-5 fw-bold mb-2"} -->
<h2 class="wp-block-heading display-5 fw-bold mb-2"><?php esc_html_e( 'Section jumbotron', 'creationell-wp-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"name":"lead"},"className":"lead"} -->
<p class="lead"><?php echo wp_kses_post( __( 'This is a <code>section</code> with a simple centered jumbotron pattern wrapped in a <code>container</code>. Use it on the <code>page-blank</code> template.', 'creationell-wp-theme' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"mb-0"},"className":"mb-0"} -->
<p class="mb-0"><a class="btn btn-lg btn-primary" href="#"><?php esc_html_e( 'Button', 'creationell-wp-theme' ); ?></a> <a class="btn btn-lg btn-secondary" href="#"><?php esc_html_e( 'Button', 'creationell-wp-theme' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->