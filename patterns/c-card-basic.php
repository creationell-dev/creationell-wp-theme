<?php
/**
 * Title: c - Card basic with image
 * Slug: creationell-wp-theme/c-card-basic
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"metadata":{"name":"c - Card with image - card mb-3 hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/c.card-basic"},"className":"card mb-3 hide-wp-block-classes"} -->
<div class="wp-block-group card mb-3 hide-wp-block-classes"><!-- wp:image {"sizeSlug":"large","metadata":{"name":"card-img-top mb-0"},"className":"card-img-top mb-0"} -->
<figure class="wp-block-image size-large card-img-top mb-0"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/patterns/placeholder-1200x900.svg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:group {"metadata":{"name":"card-body"},"className":"card-body","layout":{"type":"default"}} -->
<div class="wp-block-group card-body"><!-- wp:heading {"metadata":{"name":"card-title h5"},"className":"card-title h5"} -->
<h2 class="wp-block-heading card-title h5"><?php esc_html_e( 'Card title', 'creationell-wp-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"name":"card-text"},"className":"card-text"} -->
<p class="card-text"><?php esc_html_e( 'Some quick example text to build on the card title and make up the bulk of the card\'s content.', 'creationell-wp-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"card-text"},"className":"card-text"} -->
<p class="card-text"><a class="btn btn-primary" href="#"><?php esc_html_e( 'Button', 'creationell-wp-theme' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->