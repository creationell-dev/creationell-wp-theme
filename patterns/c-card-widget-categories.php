<?php
/**
 * Title: c - Card with category widget
 * Slug: creationell-wp-theme/c-card-widget-categories
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"metadata":{"name":"c - Card with category widget - card mb-3 hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/card-categories"},"className":"card mb-3 hide-wp-block-classes"} -->
<div class="wp-block-group card mb-3 hide-wp-block-classes"><!-- wp:heading {"metadata":{"name":"card-header h6"},"className":"card-header h6"} -->
<h2 class="wp-block-heading card-header h6"><?php esc_html_e( 'Categories', 'creationell-wp-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:categories {"showPostCounts":true,"className":"list-group-flush"} /--></div>
<!-- /wp:group -->