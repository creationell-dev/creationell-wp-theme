<?php
/**
 * Template part for displaying a message that posts cannot be found
 * Template Version: 6.4.0
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Creationell\WpTheme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<header class="page-header mb-4">
	<h1 class="page-title"><?php esc_html_e( 'Nothing Found for', 'creationell-wp-theme' ); ?> <span class="text-body-secondary"><?php echo esc_html( get_search_query() ); ?></span></h1>
</header>

<section class="no-results not-found">
	<div class="page-content">
	<?php if ( is_search() ) : ?>
		<p class="alert alert-info mb-4">
		<?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'creationell-wp-theme' ); ?>
		</p>
		<?php get_search_form( array( 'aria_label' => __( 'New search', 'creationell-wp-theme' ) ) ); ?>
	<?php endif; ?>
	</div>
</section>
