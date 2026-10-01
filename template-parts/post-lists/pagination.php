<?php
/**
 * Template part: page links of a paginated post list.
 *
 * Prints a Bootstrap pagination for $creationell_wp_theme_args['query'] (WP_Query with paged and
 * max_num_pages) and the list $creationell_wp_theme_args['instance']: every link sets only the query
 * variable of this list, so other lists and the main query keep their page and
 * the other GET parameters stay. The current page is marked with
 * aria-current="page"; one page or none prints nothing. The navigation of the
 * first paginated list is named "Post list navigation", later ones on the same
 * page carry their number, so every landmark has its own name. The name differs
 * from "Posts navigation" of the theme pagination (inc/pagination.php), which a
 * list in the sidebar of an archive page would otherwise repeat.
 *
 * @package Creationell\WpTheme
 * @since   1.0.0
 */

declare(strict_types=1);

use Creationell\WpTheme\Posts\Pagination;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$creationell_wp_theme_args     = isset( $args ) && is_array( $args ) ? $args : array();
$creationell_wp_theme_query    = $creationell_wp_theme_args['query'] ?? null;
$creationell_wp_theme_instance = is_int( $creationell_wp_theme_args['instance'] ?? null ) ? $creationell_wp_theme_args['instance'] : 0;
if ( ! $creationell_wp_theme_query instanceof WP_Query || $creationell_wp_theme_instance < 1 ) {
	return;
}
$creationell_wp_theme_links = Pagination::links( $creationell_wp_theme_query, $creationell_wp_theme_instance );
if ( array() === $creationell_wp_theme_links ) {
	return;
}
$creationell_wp_theme_label = 1 === $creationell_wp_theme_instance
	? __( 'Post list navigation', 'creationell-wp-theme' )
	/* translators: %d: number of the paginated post list on the page, from 2. */
	: sprintf( __( 'Post list navigation %d', 'creationell-wp-theme' ), $creationell_wp_theme_instance );
?>
<nav class="creationell-theme-post-list__pagination mt-4" aria-label="<?php echo esc_attr( $creationell_wp_theme_label ); ?>"><ul class="pagination flex-wrap mb-0">
	<?php foreach ( $creationell_wp_theme_links as $creationell_wp_theme_link ) : ?>
		<?php if ( $creationell_wp_theme_link['current'] ) : ?>
			<li class="page-item active"><span class="page-link" aria-current="page"><span class="visually-hidden"><?php esc_html_e( 'Page', 'creationell-wp-theme' ); ?> </span><?php echo esc_html( $creationell_wp_theme_link['label'] ); ?></span></li>
		<?php elseif ( null === $creationell_wp_theme_link['url'] ) : ?>
			<li class="page-item disabled"><span class="page-link"><?php echo esc_html( $creationell_wp_theme_link['label'] ); ?></span></li>
		<?php else : ?>
			<li class="page-item"><a class="page-link" href="<?php echo esc_url( $creationell_wp_theme_link['url'] ); ?>"><span class="visually-hidden"><?php esc_html_e( 'Page', 'creationell-wp-theme' ); ?> </span><?php echo esc_html( $creationell_wp_theme_link['label'] ); ?></a></li>
		<?php endif; ?>
	<?php endforeach; ?>
</ul></nav>
