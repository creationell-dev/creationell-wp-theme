<?php
/**
 * Breadcrumb.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_breadcrumb_link' ) ) :
	/**
	 * Returns a linked breadcrumb item.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url  Link target.
	 * @param string $text Link text.
	 * @return string List item markup, escaped.
	 */
	function creationell_wp_theme_breadcrumb_link( string $url, string $text ): string {
		/**
		 * Filters the CSS classes of the links in the breadcrumb.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$classes = apply_filters( 'creationell_wp_theme_class_breadcrumb_item_link', '' );
		return '<li class="breadcrumb-item"><a class="' . esc_attr( is_string( $classes ) ? $classes : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a></li>' . PHP_EOL;
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_breadcrumb_term_link' ) ) :
	/**
	 * Returns a linked breadcrumb item for a category.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Category ID.
	 * @return string List item markup, escaped; empty for an unknown category.
	 */
	function creationell_wp_theme_breadcrumb_term_link( int $term_id ): string {
		$term = get_category( $term_id );
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		$url = get_term_link( $term );
		return is_string( $url ) ? creationell_wp_theme_breadcrumb_link( $url, $term->name ) : '';
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_breadcrumb_current' ) ) :
	/**
	 * Returns the breadcrumb item of the current page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Plain text of the item.
	 * @return string List item markup, escaped.
	 */
	function creationell_wp_theme_breadcrumb_current( string $text ): string {
		return '<li class="breadcrumb-item active" aria-current="page">' . esc_html( $text ) . '</li>' . PHP_EOL;
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_breadcrumb' ) ) :
	/**
	 * Prints the breadcrumb navigation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_breadcrumb(): void {

		if ( is_home() ) {
			return;
		}

		/**
		 * Filters the CSS classes of the breadcrumb navigation.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$nav_classes = apply_filters( 'creationell_wp_theme_class_breadcrumb_nav', 'overflow-x-auto text-nowrap mb-4 mt-2 py-2 px-3 bg-body-tertiary rounded' );
		echo '<nav aria-label="' . esc_attr__( 'Breadcrumb', 'creationell-wp-theme' ) . '" class="' . esc_attr( is_string( $nav_classes ) ? $nav_classes : '' ) . '">' . PHP_EOL;

		/**
		 * Filters the CSS classes of the breadcrumb list.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$list_classes = apply_filters( 'creationell_wp_theme_class_breadcrumb_ol', 'flex-nowrap mb-0' );
		echo '<ol class="breadcrumb ' . esc_attr( is_string( $list_classes ) ? $list_classes : '' ) . '">' . PHP_EOL;

		// Home link.
		/**
		 * Filters the CSS classes of the links in the breadcrumb.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$home_classes = apply_filters( 'creationell_wp_theme_class_breadcrumb_item_link', '' );
		echo '<li class="breadcrumb-item"><a aria-label="' . esc_attr__( 'Home', 'creationell-wp-theme' ) . '" class="' . esc_attr( is_string( $home_classes ) ? $home_classes : '' ) . '" href="' . esc_url( home_url() ) . '">' . wp_kses( creationell_wp_theme_icon( 'house', false ), creationell_wp_theme_kses_allowed_svg() ) . '<span class="visually-hidden">' . esc_html__( 'Home', 'creationell-wp-theme' ) . '</span></a></li>' . PHP_EOL;

		// Custom breadcrumb handlers (WooCommerce, other post types) return true when they printed the items.
		/**
		 * Filters whether another plugin renders the breadcrumb instead of the theme.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $handled Whether the breadcrumb is already rendered.
		 */
		$handled = apply_filters( 'creationell_wp_theme_breadcrumb_handler', false );

		if ( ! $handled ) {
			echo wp_kses_post( creationell_wp_theme_breadcrumb_items() );
		}

		echo '</ol>' . PHP_EOL;
		echo '</nav>' . PHP_EOL;
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_breadcrumb_items' ) ) :
	/**
	 * Returns the breadcrumb items after the home link for the default WordPress pages.
	 *
	 * @since 1.0.0
	 *
	 * @return string List items, escaped.
	 */
	function creationell_wp_theme_breadcrumb_items(): string {
		$items = '';

		if ( is_category() ) {
			// Category archive: ancestors as links, the current category as text.
			$current_cat_id = get_queried_object_id();
			if ( $current_cat_id ) {
				foreach ( array_reverse( get_ancestors( $current_cat_id, 'category' ) ) as $ancestor_id ) {
					$items .= creationell_wp_theme_breadcrumb_term_link( $ancestor_id );
				}
				$cat_title = single_cat_title( '', false );
				$items    .= creationell_wp_theme_breadcrumb_current( is_string( $cat_title ) ? $cat_title : '' );
			}
		} elseif ( is_post_type_archive() ) {
			// Custom post type archive (default handling).
			$archive_title = preg_replace( '/^\w+: /', '', get_the_archive_title() );
			$items        .= creationell_wp_theme_breadcrumb_current( wp_strip_all_tags( $archive_title ?? '' ) );
		} elseif ( is_single() ) {
			// Single post (regular posts and custom post types).
			$post_type     = get_post_type();
			$post_type_obj = false === $post_type ? null : get_post_type_object( $post_type );

			if ( false !== $post_type && 'post' !== $post_type && $post_type_obj && $post_type_obj->has_archive ) {
				// Link the archive of a custom post type that has one.
				$archive_link = get_post_type_archive_link( $post_type );
				if ( $archive_link ) {
					$items .= creationell_wp_theme_breadcrumb_link( $archive_link, $post_type_obj->labels->name );
				}
			} elseif ( 'post' === $post_type ) {
				// Regular posts: link the categories.
				$post_id = get_the_ID();
				$cat_ids = false === $post_id ? array() : wp_get_post_categories( $post_id );
				foreach ( is_array( $cat_ids ) ? $cat_ids : array() as $cat_id ) {
					$items .= creationell_wp_theme_breadcrumb_term_link( $cat_id );
				}
			}

			// Current post title.
			$items .= creationell_wp_theme_breadcrumb_current( get_the_title() );
		} elseif ( is_page() ) {
			// Pages: parent pages as links, then the current page.
			$post_id   = get_the_ID();
			$parent_id = false === $post_id ? false : wp_get_post_parent_id( $post_id );
			$parents   = array();
			while ( $parent_id ) {
				$page = get_post( $parent_id );
				if ( ! $page instanceof WP_Post ) {
					break;
				}
				$permalink = get_permalink( $page->ID );
				if ( false !== $permalink ) {
					$parents[] = creationell_wp_theme_breadcrumb_link( $permalink, get_the_title( $page->ID ) );
				}
				$parent_id = wp_get_post_parent_id( $page->ID );
			}
			$items .= implode( '', array_reverse( $parents ) );
			$items .= creationell_wp_theme_breadcrumb_current( get_the_title() );
		} elseif ( is_search() ) {
			// Search results.
			/* translators: %s: search query */
			$items .= creationell_wp_theme_breadcrumb_current( sprintf( __( 'Search Results for: %s', 'creationell-wp-theme' ), get_search_query() ) );
		} elseif ( is_archive() ) {
			// Other archives (tags, custom taxonomies, date, author); this branch must stay last.
			$items .= creationell_wp_theme_breadcrumb_current( wp_strip_all_tags( get_the_archive_title() ) );
		}

		return $items;
	}
endif;


if ( ! function_exists( 'creationell_wp_theme_breadcrumb_shortcode' ) ) {
	/**
	 * Renders the breadcrumb for the shortcode [creationell_wp_theme_breadcrumb].
	 *
	 * Useful in widget areas or the blank page template, where no action hook adds the breadcrumb.
	 *
	 * @since 1.0.0
	 *
	 * @return string Breadcrumb markup; empty in the admin to keep editor saves valid JSON.
	 */
	function creationell_wp_theme_breadcrumb_shortcode(): string {
		if ( is_admin() ) {
			return '';
		}

		ob_start();
		creationell_wp_theme_breadcrumb();
		$html = ob_get_clean();
		return false === $html ? '' : $html;
	}
	add_shortcode( 'creationell_wp_theme_breadcrumb', 'creationell_wp_theme_breadcrumb_shortcode' );
}
