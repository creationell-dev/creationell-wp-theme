<?php
/**
 * Title: c - Alert-danger with icon and dismiss button
 * Slug: creationell-wp-theme/c-alert-danger
 * Categories: creationell-wp-theme
 * https://developer.wordpress.org/themes/features/block-patterns/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- wp:paragraph {"metadata":{"name":"c - Alert danger - alert alert-danger alert-icon alert-danger-icon alert-dismissible fade show hide-wp-block-classes","categories":["creationell-wp-theme"],"patternName":"creationell-wp-theme/c.alert-danger"},"className":"alert alert-danger alert-icon alert-danger-icon alert-dismissible fade show hide-wp-block-classes"} -->
<p class="alert alert-danger alert-icon alert-danger-icon alert-dismissible fade show hide-wp-block-classes"><?php echo wp_kses_post( __( 'A dismissing danger alert with an icon and a <a href="#">link</a>.', 'creationell-wp-theme' ) ); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php esc_attr_e( 'Close', 'creationell-wp-theme' ); ?>"></button></p>
<!-- /wp:paragraph -->