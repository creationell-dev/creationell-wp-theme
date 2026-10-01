<?php
/**
 * Title: c - Card with header and footer
 * Slug: creationell-wp-theme/c-card-header-footer
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"metadata":{"name":"c - Card with header and footer - card mb-3 hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/c.card-advanced"},"className":"card mb-3 hide-wp-block-classes"} -->
<div class="wp-block-group card mb-3 hide-wp-block-classes"><!-- wp:heading {"metadata":{"name":"card-header h6"},"className":"card-header h6"} -->
<h2 class="wp-block-heading card-header h6"><?php esc_html_e( 'Card header', 'creationell-wp-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:image {"sizeSlug":"large","metadata":{"name":"mb-0"},"className":"mb-0"} -->
<figure class="wp-block-image size-large mb-0"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/img/patterns/placeholder-1200x900.svg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:group {"metadata":{"name":"card-body"},"className":"card-body","layout":{"type":"default"}} -->
<div class="wp-block-group card-body"><!-- wp:heading {"level":3,"metadata":{"name":"card-title h5"},"className":"card-title h5"} -->
<h3 class="wp-block-heading card-title h5"><?php esc_html_e( 'Card title', 'creationell-wp-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"name":"card-text"},"className":"card-text"} -->
<p class="card-text"><?php esc_html_e( 'Some quick example text to build on the card title and make up the bulk of the card\'s content.', 'creationell-wp-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"name":"card-text"},"className":"card-text"} -->
<p class="card-text"><a class="btn btn-primary" href="#"><?php esc_html_e( 'Button', 'creationell-wp-theme' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"metadata":{"name":"card-footer mb-0"},"className":"card-footer mb-0"} -->
<p class="card-footer mb-0"><?php esc_html_e( 'Card footer', 'creationell-wp-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->