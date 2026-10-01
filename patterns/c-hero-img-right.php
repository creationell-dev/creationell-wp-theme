<?php
/**
 * Title: c - Hero img right
 * Slug: creationell-wp-theme/c-hero-img-right
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"metadata":{"name":"c - Hero image right - row h-100 mb-3 hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/hero-img-right"},"className":"row h-100 mb-3 hide-wp-block-classes","layout":{"type":"default"}} -->
<div class="wp-block-group row h-100 mb-3 hide-wp-block-classes"><!-- wp:group {"metadata":{"name":"col-lg-6 mb-3 mb-lg-0 order-lg-2"},"className":"col-lg-6 mb-3 mb-lg-0 order-lg-2","layout":{"type":"default"}} -->
<div class="wp-block-group col-lg-6 mb-3 mb-lg-0 order-lg-2"><!-- wp:group {"metadata":{"name":"h-100 d-flex align-items-center"},"className":"h-100 d-flex align-items-center","layout":{"type":"default"}} -->
<div class="wp-block-group h-100 d-flex align-items-center"><!-- wp:image {"sizeSlug":"large","className":"rounded mb-0"} -->
<figure class="wp-block-image size-large rounded mb-0"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/patterns/placeholder-1200x900.svg' ) ); ?>" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"metadata":{"name":"col-lg-6"},"className":"col-lg-6","layout":{"type":"default"}} -->
<div class="wp-block-group col-lg-6"><!-- wp:group {"metadata":{"name":"h-100 d-flex align-items-center"},"className":"h-100 d-flex align-items-center","layout":{"type":"default"}} -->
<div class="wp-block-group h-100 d-flex align-items-center"><!-- wp:group {"metadata":{"name":"text-block"},"className":"text-block","layout":{"type":"default"}} -->
<div class="wp-block-group text-block"><!-- wp:heading {"className":"display-5 fw-bold mb-2"} -->
<h2 class="wp-block-heading display-5 fw-bold mb-2"><?php esc_html_e( 'Hero', 'creationell-wp-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"name":""},"className":"lead"} -->
<p class="lead"><?php esc_html_e( 'This is a two column hero with right aligned image and CTA buttons.', 'creationell-wp-theme' ); ?> </p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"mb-0"},"className":"mb-0"} -->
<p class="mb-0"><a class="btn btn-lg btn-primary" href="#"><?php esc_html_e( 'Button', 'creationell-wp-theme' ); ?></a> <a class="btn btn-lg btn-secondary" href="#"><?php esc_html_e( 'Button', 'creationell-wp-theme' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->