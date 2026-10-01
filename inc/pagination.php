<?php
/**
 * Pagination.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_pagination_render' ) ) :

	/**
	 * Prints the pagination of the post loop.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $pages Number of pages; empty for the main query.
	 * @param mixed $range Number of page links on each side of the current page.
	 * @return void
	 */
	function creationell_wp_theme_pagination_render( mixed $pages = '', mixed $range = 2 ): void {
		$range     = is_numeric( $range ) ? absint( $range ) : 2;
		$showitems = ( $range * 2 ) + 1;
		$paged     = isset( $GLOBALS['paged'] ) && is_numeric( $GLOBALS['paged'] ) ? absint( $GLOBALS['paged'] ) : 0;
		$paged     = 0 === $paged ? 1 : $paged;
		$pages     = is_numeric( $pages ) ? absint( $pages ) : 0;
		if ( 0 === $pages ) {
			$query = $GLOBALS['wp_query'] ?? null;
			$pages = $query instanceof WP_Query ? max( 1, absint( $query->max_num_pages ) ) : 1;
		}

		if ( 1 !== $pages ) {
			/* translators: Name of the navigation through the pages of the blog, an archive or the search results; not the same as "Post navigation" (previous and next post) and "Post list navigation" (post list block). */
			echo '<nav aria-label="' . esc_attr__( 'Posts navigation', 'creationell-wp-theme' ) . '">';
			echo '<ul class="pagination justify-content-center mb-4">';

			if ( $paged > 2 && $paged > $range + 1 && $showitems < $pages ) {
				echo '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( 1 ) ) . '" aria-label="' . esc_attr__( 'First Page', 'creationell-wp-theme' ) . '">&laquo;</a></li>';
			}

			if ( $paged > 1 && $showitems < $pages ) {
				echo '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '" aria-label="' . esc_attr__( 'Previous Page', 'creationell-wp-theme' ) . '">&lsaquo;</a></li>';
			}

			for ( $i = 1; $i <= $pages; $i++ ) {
				if ( ! ( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) {
					echo ( $paged === $i )
					? '<li class="page-item active"><span class="page-link"><span class="visually-hidden">' . esc_html__( 'Current Page', 'creationell-wp-theme' ) . ' </span>' . esc_html( (string) $i ) . '</span></li>'
					: '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( $i ) ) . '"><span class="visually-hidden">' . esc_html__( 'Page', 'creationell-wp-theme' ) . ' </span>' . esc_html( (string) $i ) . '</a></li>';
				}
			}

			if ( $paged < $pages && $showitems < $pages ) {
				echo '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '" aria-label="' . esc_attr__( 'Next Page', 'creationell-wp-theme' ) . '">&rsaquo;</a></li>';
			}

			if ( $paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages ) {
				echo '<li class="page-item"><a class="page-link" href="' . esc_url( get_pagenum_link( $pages ) ) . '" aria-label="' . esc_attr__( 'Last Page', 'creationell-wp-theme' ) . '">&raquo;</a></li>';
			}

			echo '</ul>';
			echo '</nav>';
		}
	}

endif;

add_action( 'creationell_wp_theme_loop_pagination', 'creationell_wp_theme_pagination_render' );


/**
 * Adds the Bootstrap page link class to the links to the previous and next post.
 *
 * @since 1.0.0
 *
 * @param mixed $output Link markup.
 * @return mixed Markup with the class, other values unchanged.
 */
function creationell_wp_theme_post_link_attributes( mixed $output ): mixed {
	if ( ! is_string( $output ) ) {
		return $output;
	}

	return str_replace( '<a href=', '<a class="page-link" href=', $output );
}
add_filter( 'next_post_link', 'creationell_wp_theme_post_link_attributes' );
add_filter( 'previous_post_link', 'creationell_wp_theme_post_link_attributes' );
