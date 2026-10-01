<?php
/**
 * Search Block Widget.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Search Block
 */
if ( ! function_exists( 'creationell_wp_theme_block_widget_search_classes' ) ) {
	/**
	 * Adds Bootstrap classes to search block widget.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $block_content The block content.
	 * @param mixed $block         The full block, including name and attributes.
	 * @return mixed The filtered block content; other values than a string come back unchanged.
	 */
	function creationell_wp_theme_block_widget_search_classes( mixed $block_content, mixed $block ): mixed {
		if ( ! is_string( $block_content ) ) {
			return $block_content;
		}

		// Input needs trailing margin when the button sits outside the input wrapper.
		$button_position = $block['attrs']['buttonPosition'] ?? 'button-outside';
		/**
		 * Filters the CSS classes that set the gap between search input and button.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$input_spacer = ( 'button-inside' !== $button_position ) ? ' ' . esc_attr( apply_filters( 'creationell_wp_theme_class_widget_search_input_spacer', 'me-2' ) ) : '';

		$search = array(
			'<form ',
			'wp-block-search__input ',
			'wp-block-search__input"',
			'wp-block-search__button ',
			'<svg class="search-icon" viewBox="0 0 24 24" width="24" height="24">
					<path d="M13 5c-3.3 0-6 2.7-6 6 0 1.4.5 2.7 1.3 3.7l-3.8 3.8 1.1 1.1 3.8-3.8c1 .8 2.3 1.3 3.7 1.3 3.3 0 6-2.7 6-6S16.3 5 13 5zm0 10.5c-2.5 0-4.5-2-4.5-4.5s2-4.5 4.5-4.5 4.5 2 4.5 4.5-2 4.5-4.5 4.5z"></path>
				</svg>',
		);
		/**
		 * Filters the CSS classes of the search button.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		$replace = array(
			'<form novalidate="novalidate" ',
			'wp-block-search__input form-control' . $input_spacer . ' ',
			'wp-block-search__input form-control' . $input_spacer . '"',
			'wp-block-search__btn ' . esc_attr( apply_filters( 'creationell_wp_theme_class_widget_search_button', 'btn btn-outline-secondary' ) ) . ' ',
			creationell_wp_theme_icon( 'search', false ),
		);

		if ( isset( $block['attrs']['buttonPosition'] ) && 'button-inside' === $block['attrs']['buttonPosition'] ) {
			$search[]  = 'wp-block-search__inside-wrapper';
			$replace[] = 'wp-block-search input-group';
		}

		$block_content = str_replace( $search, $replace, $block_content );

		// Every search landmark gets a name of its own (axe landmark-unique), also two search
		// blocks in the same widget area. The header shows the search twice (navbar and collapse,
		// one of them hidden by CSS); the role in the name of the collapse copy keeps the two apart
		// where the stylesheet does not hide one. Elsewhere the site search counts on. The landmark
		// is the element search when the block prints one (attribute tagName or the theme support
		// search-element), else the form; a name of core (aria-label or aria-labelledby) stays and
		// does not count (creationell_wp_theme_search_landmark_named()).
		$landmark = new WP_HTML_Tag_Processor( $block_content );
		while ( $landmark->next_tag() ) {
			if ( ! in_array( $landmark->get_tag(), array( 'SEARCH', 'FORM' ), true ) ) {
				continue;
			}
			if ( ! creationell_wp_theme_search_landmark_named( $landmark ) ) {
				$landmark->set_attribute( 'aria-label', 'top-nav-search' === creationell_wp_theme_widget_area() ? creationell_wp_theme_header_search_label() : creationell_wp_theme_site_search_label() );
			}
			break;
		}
		$block_content = $landmark->get_updated_html();

		/**
		 * Filters the HTML of the search block after the theme added its Bootstrap classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $block_content Block HTML.
		 * @param array  $block         Parsed block with name and attributes.
		 */
		return apply_filters( 'creationell_wp_theme_block_search_content', $block_content, $block );
	}
}
add_filter( 'render_block_core/search', 'creationell_wp_theme_block_widget_search_classes', 10, 2 );


if ( ! function_exists( 'creationell_wp_theme_search_landmark_named' ) ) {
	/**
	 * Tells whether the tag a processor stands on already has a name: a non-empty aria-label or aria-labelledby.
	 *
	 * Reads the attributes through the HTML API, so text that only looks like
	 * the attribute (data-wp-bind--aria-label, a value that contains
	 * "aria-label=") is no name; an empty or valueless aria-label is none either
	 * and gets replaced.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_HTML_Tag_Processor $tags Processor on the landmark tag.
	 * @return bool True when the landmark is named.
	 */
	function creationell_wp_theme_search_landmark_named( WP_HTML_Tag_Processor $tags ): bool {
		foreach ( array( 'aria-label', 'aria-labelledby' ) as $attribute ) {
			$value = $tags->get_attribute( $attribute );
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'creationell_wp_theme_widget_area' ) ) {
	/**
	 * Returns the widget area that is being printed, or sets it.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $area ID of the widget area to remember, an empty string after it; null only reads.
	 * @return string ID of the widget area that is being printed, or an empty string.
	 */
	function creationell_wp_theme_widget_area( ?string $area = null ): string {
		static $current = '';
		if ( null !== $area ) {
			$current = $area;
		}
		return $current;
	}
}

if ( ! function_exists( 'creationell_wp_theme_widget_area_enter' ) ) {
	/**
	 * Remembers the widget area that dynamic_sidebar() starts to print.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $index ID of the widget area.
	 * @return void
	 */
	function creationell_wp_theme_widget_area_enter( mixed $index ): void {
		creationell_wp_theme_widget_area( is_string( $index ) ? $index : '' );
	}
}
add_action( 'dynamic_sidebar_before', 'creationell_wp_theme_widget_area_enter' );

if ( ! function_exists( 'creationell_wp_theme_widget_area_leave' ) ) {
	/**
	 * Forgets the widget area after dynamic_sidebar() printed it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_widget_area_leave(): void {
		creationell_wp_theme_widget_area( '' );
	}
}
add_action( 'dynamic_sidebar_after', 'creationell_wp_theme_widget_area_leave' );


if ( ! function_exists( 'creationell_wp_theme_header_search_context' ) ) {
	/**
	 * Returns the copy of the header search that is being printed, or sets it.
	 *
	 * The header prints the widget area top-nav-search twice: in the navbar (an
	 * empty string) and in the collapse below it ("menu", set by
	 * template-parts/header/collapse-search.php around dynamic_sidebar()).
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $context Copy to remember: "menu" for the collapse, an empty string for the navbar; null only reads.
	 * @return string Copy that is being printed: "menu" or an empty string.
	 */
	function creationell_wp_theme_header_search_context( ?string $context = null ): string {
		static $current = '';
		if ( null !== $context ) {
			$current = $context;
		}
		return $current;
	}
}

if ( ! function_exists( 'creationell_wp_theme_header_search_count' ) ) {
	/**
	 * Returns how many search forms of the current copy of the header widget area the request has named so far, or sets the count.
	 *
	 * Navbar and collapse count apart (creationell_wp_theme_header_search_context()),
	 * so both copies start at 1. A child theme that replaces
	 * creationell_wp_theme_header_search_label() must therefore name the copies by
	 * the context as well; a name built from the count alone repeats in navbar and
	 * collapse (axe landmark-unique).
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $count Count to remember, 0 to start again; null only reads.
	 * @return int Number of named search forms in the current copy of the header search.
	 */
	function creationell_wp_theme_header_search_count( ?int $count = null ): int {
		static $counts = array();
		$context       = function_exists( 'creationell_wp_theme_header_search_context' ) ? creationell_wp_theme_header_search_context() : '';
		if ( null !== $count ) {
			$counts[ $context ] = max( 0, $count );
		}
		return $counts[ $context ] ?? 0;
	}
}

if ( ! function_exists( 'creationell_wp_theme_header_search_label' ) ) {
	/**
	 * Returns the name of the next search form in the header widget area.
	 *
	 * The header prints the widget area top-nav-search twice, in the navbar and in
	 * the collapse below it; the stylesheet shows one of them per breakpoint. The
	 * navbar copy is "Header search", the collapse copy is named after its role,
	 * "Header search (menu)": below the breakpoint it is the only search a visitor
	 * reaches, so its name carries no number without a visible counterpart. The two
	 * names keep the landmarks apart wherever both are exposed, for example without
	 * the stylesheet (axe landmark-unique). Further forms in the same copy get a
	 * number from 2.
	 *
	 * A child theme that replaces this function must keep the names of the two
	 * copies apart through creationell_wp_theme_header_search_context(): the count
	 * of creationell_wp_theme_header_search_count() starts at 1 in each copy, so a
	 * name from the count alone appears twice.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated name.
	 */
	function creationell_wp_theme_header_search_label(): string {
		$number = creationell_wp_theme_header_search_count( creationell_wp_theme_header_search_count() + 1 );
		$menu   = function_exists( 'creationell_wp_theme_header_search_context' ) && 'menu' === creationell_wp_theme_header_search_context();
		if ( $menu ) {
			if ( 1 === $number ) {
				return __( 'Header search (menu)', 'creationell-wp-theme' );
			}
			/* translators: %d: number of the search form in the collapsible header menu, from 2. */
			return sprintf( __( 'Header search (menu) %d', 'creationell-wp-theme' ), $number );
		}
		if ( 1 === $number ) {
			return __( 'Header search', 'creationell-wp-theme' );
		}
		/* translators: %d: number of the search form in the header, from 2. */
		return sprintf( __( 'Header search %d', 'creationell-wp-theme' ), $number );
	}
}

if ( ! function_exists( 'creationell_wp_theme_site_search_count' ) ) {
	/**
	 * Returns how many search forms outside the header widget area the request has named so far, or sets the count.
	 *
	 * Search block and classic search form share the count, in every widget
	 * area other than top-nav-search and outside widget areas; given names and
	 * names of core do not count.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $count Count to remember, 0 to start again; null only reads.
	 * @return int Number of named search forms outside the header search.
	 */
	function creationell_wp_theme_site_search_count( ?int $count = null ): int {
		static $current = 0;
		if ( null !== $count ) {
			$current = max( 0, $count );
		}
		return $current;
	}
}

if ( ! function_exists( 'creationell_wp_theme_site_search_label' ) ) {
	/**
	 * Returns the name of the next search form outside the header widget area.
	 *
	 * The first is "Site search", further ones get a number from 2, so two
	 * search widgets in the same widget area, or in a sidebar and a footer
	 * column, never share a name (axe landmark-unique). The search form of a
	 * page without results has a name of its own ("New search"), the header
	 * search the names of creationell_wp_theme_header_search_label().
	 *
	 * The numbers follow the order in which the request renders the search
	 * forms. They are unique within one page, but not stable: a search block in
	 * the content before the sidebar, or a render that a plugin throws away
	 * (for example the content rendered early for a meta description), moves
	 * them on, so the same widget may be "Site search 2" on another page. A
	 * child theme that replaces this function must count the same way, through
	 * creationell_wp_theme_site_search_count(), to keep the names unique.
	 *
	 * @since 1.0.0
	 *
	 * @return string Translated name.
	 */
	function creationell_wp_theme_site_search_label(): string {
		$number = creationell_wp_theme_site_search_count( creationell_wp_theme_site_search_count() + 1 );
		if ( 1 === $number ) {
			return __( 'Site search', 'creationell-wp-theme' );
		}
		/* translators: %d: number of the search form outside the header, from 2. */
		return sprintf( __( 'Site search %d', 'creationell-wp-theme' ), $number );
	}
}
