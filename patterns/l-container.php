<?php
/**
 * Title: l - Container
 * Slug: creationell-wp-theme/l-container
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"metadata":{"name":"l - Container - container hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/l.container"},"className":"container hide-wp-block-classes","layout":{"type":"default"}} -->
<div class="wp-block-group container hide-wp-block-classes"><!-- wp:paragraph -->
<p><?php echo wp_kses_post( __( 'This is a <code>container</code>', 'creationell-wp-theme' ) ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->