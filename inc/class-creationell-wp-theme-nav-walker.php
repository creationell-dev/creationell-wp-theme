<?php
/**
 * Navwalker.
 *
 * Based on the Bootstrap 5 WordPress navbar walker by AlexWebLab (MIT),
 * https://github.com/AlexWebLab/bootstrap-5-wordpress-navbar-walker.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// A child theme may declare its own walker class before the parent loads.
if ( ! class_exists( 'Creationell_Wp_Theme_Nav_Walker' ) ) :

	/**
	 * Renders menus with Bootstrap 5 navbar and dropdown markup.
	 *
	 * @since 1.0.0
	 */
	class Creationell_Wp_Theme_Nav_Walker extends Walker_Nav_Menu {

		/**
		 * Menu item that start_lvl() opens a submenu for.
		 *
		 * Plugins add menu items that are no WP_Post (for example the WPML language
		 * switcher), so the walker only relies on object properties.
		 *
		 * @since 1.0.0
		 *
		 * @var WP_Post|object|null
		 */
		private ?object $current_item = null;

		/**
		 * Dropdown alignment classes that a menu item passes on to its submenu.
		 *
		 * @since 1.0.0
		 *
		 * @var array<int, string>
		 */
		private array $dropdown_menu_alignment_values = array(
			'dropdown-menu-start',
			'dropdown-menu-end',
			'dropdown-menu-sm-start',
			'dropdown-menu-sm-end',
			'dropdown-menu-md-start',
			'dropdown-menu-md-end',
			'dropdown-menu-lg-start',
			'dropdown-menu-lg-end',
			'dropdown-menu-xl-start',
			'dropdown-menu-xl-end',
			'dropdown-menu-xxl-start',
			'dropdown-menu-xxl-end',
		);

		/**
		 * Starts a submenu list.
		 *
		 * @since 1.0.0
		 *
		 * @param string                     $output Used to append additional content (passed by reference).
		 * @param int                        $depth  Depth of menu item.
		 * @param stdClass|array<mixed>|null $args   Arguments of wp_nav_menu() as object or array.
		 * @return void
		 */
		public function start_lvl( &$output, $depth = 0, $args = null ): void {
			$dropdown_menu_class = array( '' );
			$item_classes        = null === $this->current_item ? array() : ( $this->current_item->classes ?? array() );
			foreach ( is_array( $item_classes ) ? $item_classes : array() as $item_class ) {
				if ( in_array( $item_class, $this->dropdown_menu_alignment_values, true ) ) {
					$dropdown_menu_class[] = $item_class;
				}
			}
			$indent  = str_repeat( "\t", $depth );
			$submenu = ( $depth > 0 ) ? ' sub-menu' : '';
			$output .= "\n$indent<ul class=\"dropdown-menu$submenu " . esc_attr( implode( ' ', $dropdown_menu_class ) ) . " depth_$depth\">\n";
		}

		/**
		 * Starts a menu item.
		 *
		 * A menu item with a submenu renders as a link plus a separate toggle button, so
		 * the link of the parent item stays reachable. The link gets aria-current="page"
		 * for the current page and runs the core filters of Walker_Nav_Menu.
		 *
		 * @since 1.0.0
		 *
		 * @param string                     $output Used to append additional content (passed by reference).
		 * @param WP_Post|object             $item   Menu item data object; plugins may pass objects that are no WP_Post.
		 * @param int                        $depth  Depth of menu item.
		 * @param stdClass|array<mixed>|null $args   Arguments of wp_nav_menu() as object or array.
		 * @param int                        $id     Current item ID.
		 * @return void
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
			if ( ! is_object( $item ) ) {
				return;
			}
			$this->current_item = $item;

			$args         = is_array( $args ) || is_object( $args ) ? $args : new stdClass();
			$walker       = self::arg_value( $args, 'walker' );
			$has_children = is_object( $walker ) && ! empty( $walker->has_children ) && self::renders_children( $args, (int) $depth );

			$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

			$item_key = isset( $item->ID ) && is_scalar( $item->ID ) ? (string) $item->ID : '';

			$item_classes = $item->classes ?? array();
			$classes      = is_array( $item_classes ) ? $item_classes : array();

			$classes[] = $has_children ? 'dropdown nav-item-has-toggle' : '';
			$classes[] = 'nav-item';
			$classes[] = 'nav-item-' . $item_key;
			if ( $depth && $has_children ) {
				$classes[] = 'dropdown-menu dropdown-menu-end';
			}

			// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filters of Walker_Nav_Menu, run like in core.

			/** This filter is documented in wp-includes/class-walker-nav-menu.php */
			$filtered_classes = apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth );
			$class_names      = ' class="' . esc_attr( implode( ' ', is_array( $filtered_classes ) ? $filtered_classes : array() ) ) . '"';

			/** This filter is documented in wp-includes/class-walker-nav-menu.php */
			$item_id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item_key, $item, $args, $depth );
			$item_id = is_string( $item_id ) && '' !== $item_id ? ' id="' . esc_attr( $item_id ) . '"' : '';

			$output .= $indent . '<li ' . $item_id . $class_names . '>';

			$link_class = ( $depth > 0 ) ? 'dropdown-item' : 'nav-link';
			$target     = self::text( $item, 'target' );
			$xfn        = self::text( $item, 'xfn' );
			$atts       = array(
				'title'        => self::text( $item, 'attr_title' ),
				'target'       => $target,
				'rel'          => ( '_blank' === $target && '' === $xfn ) ? 'noopener' : $xfn,
				'href'         => self::text( $item, 'url' ),
				'aria-current' => empty( $item->current ) ? '' : 'page',
				'class'        => $link_class . ( self::is_active( $item, $classes ) ? ' active' : '' ),
			);

			/** This filter is documented in wp-includes/class-walker-nav-menu.php */
			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

			/** This filter is documented in wp-includes/post-template.php */
			$title = apply_filters( 'the_title', $item->title ?? '', $item->ID ?? 0 );

			/** This filter is documented in wp-includes/class-walker-nav-menu.php */
			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
			$title = is_string( $title ) ? $title : '';

			$item_output  = self::arg( $args, 'before' );
			$item_output .= '<a' . self::attributes( is_array( $atts ) ? $atts : array() ) . '>';
			$item_output .= self::arg( $args, 'link_before' ) . wp_kses( $title, creationell_wp_theme_kses_allowed_svg( wp_kses_allowed_html( 'post' ) ) ) . self::arg( $args, 'link_after' );
			$item_output .= '</a>';
			if ( $has_children ) {
				$item_output .= self::toggle( $link_class, $title );
			}
			$item_output .= self::arg( $args, 'after' );

			/** This filter is documented in wp-includes/class-walker-nav-menu.php */
			$item_output = apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
			$output     .= is_string( $item_output ) ? $item_output : '';
			// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		/**
		 * Tells whether the walker renders the children of an item at the given depth.
		 *
		 * Walker::display_element() descends only when the depth argument of
		 * wp_nav_menu() is 0 (unlimited) or larger than the item depth plus one;
		 * -1 renders a flat list. Items of the last rendered level keep their
		 * children in the menu data, but get no submenu.
		 *
		 * @since 1.0.0
		 *
		 * @param object|array<mixed> $args  Arguments of wp_nav_menu() as object or array.
		 * @param int                 $depth Depth of the menu item.
		 * @return bool True when the submenu of the item is rendered.
		 */
		private static function renders_children( object|array $args, int $depth ): bool {
			$max_depth = self::arg_value( $args, 'depth' );
			$max_depth = is_numeric( $max_depth ) ? (int) $max_depth : 0;

			return 0 === $max_depth || $max_depth > $depth + 1;
		}

		/**
		 * Returns the button that opens the submenu of a menu item.
		 *
		 * @since 1.0.0
		 *
		 * @param string $link_class Class of the link: nav-link or dropdown-item.
		 * @param string $title      Title of the menu item, may contain HTML.
		 * @return string Button markup.
		 */
		private static function toggle( string $link_class, string $title ): string {
			/* translators: %s: title of the menu item with the submenu */
			$label = sprintf( __( 'Show submenu for %s', 'creationell-wp-theme' ), trim( wp_strip_all_tags( $title ) ) );

			return '<button type="button" class="' . esc_attr( $link_class ) . ' dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">'
				. '<span class="visually-hidden">' . esc_html( $label ) . '</span></button>';
		}

		/**
		 * Returns link attributes as HTML, escaped like Walker_Nav_Menu: href as URL, the rest as attribute.
		 *
		 * @since 1.0.0
		 *
		 * @param array<mixed> $atts Attributes by name.
		 * @return string Attributes with a leading space each; empty values are left out.
		 */
		private static function attributes( array $atts ): string {
			$html = '';
			foreach ( $atts as $name => $value ) {
				if ( ! is_string( $name ) || ! is_scalar( $value ) || '' === $value || false === $value ) {
					continue;
				}
				$value = 'href' === $name ? esc_url( (string) $value ) : esc_attr( (string) $value );
				if ( '' !== $value ) {
					$html .= ' ' . $name . '="' . $value . '"';
				}
			}
			return $html;
		}

		/**
		 * Returns a string property of a menu item.
		 *
		 * @since 1.0.0
		 *
		 * @param object $item Menu item data object.
		 * @param string $key  Property name.
		 * @return string Value or an empty string.
		 */
		private static function text( object $item, string $key ): string {
			$value = $item->$key ?? '';
			return is_scalar( $value ) ? (string) $value : '';
		}

		/**
		 * Tells whether a menu item is active on the current request.
		 *
		 * @since 1.0.0
		 *
		 * @param object             $item    Menu item data object: WP_Post or a plugin object that is no WP_Post.
		 * @param array<int, string> $classes CSS classes of the menu item.
		 * @return bool True for the current item, its ancestors and matching archives.
		 */
		private static function is_active( object $item, array $classes ): bool {
			// 1) Standard WordPress core signals, extended by the canonical menu classes.
			$is_core_current = (
				! empty( $item->current )
				|| ! empty( $item->current_item_ancestor )
				|| in_array( 'current-post-ancestor', $classes, true )
				|| in_array( 'current-menu-item', $classes, true )
				|| in_array( 'current-menu-parent', $classes, true )
				|| in_array( 'current-menu-ancestor', $classes, true )
			);

			// 2) Post type archive items stay active on singles of that post type ($item->object holds the slug).
			$object        = isset( $item->object ) && is_string( $item->object ) ? $item->object : '';
			$is_cpt_active = (
				isset( $item->type )
				&& 'post_type_archive' === $item->type
				&& '' !== $object
				&& ( is_post_type_archive( $object ) || is_singular( $object ) )
			);

			// 3) current_page_parent only where it is reliable: page hierarchy, and the posts page in blog context.
			$page_for_posts  = absint( get_option( 'page_for_posts' ) );
			$object_id       = isset( $item->object_id ) && is_numeric( $item->object_id ) ? absint( $item->object_id ) : 0;
			$is_posts_page   = ( 0 !== $object_id && $object_id === $page_for_posts );
			$is_blog_context = ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author() );

			$is_safe_page_parent = (
				in_array( 'current_page_parent', $classes, true )
				&& ( is_page() || ( $is_posts_page && $is_blog_context ) )
			);

			// 4) WooCommerce: shop page active in shop context, product category and tag items on their archives.
			$is_wc_active = false;
			if ( function_exists( 'wc_get_page_id' ) && function_exists( 'is_woocommerce' ) && function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && function_exists( 'is_product_tag' ) ) {
				$shop_id       = absint( wc_get_page_id( 'shop' ) );
				$is_shop_page  = ( 0 !== $object_id && $object_id === $shop_id );
				$is_wc_context = ( is_woocommerce() || is_shop() || is_product_category() || is_product_tag() || is_singular( 'product' ) );

				$is_wc_term        = in_array( $object, array( 'product_cat', 'product_tag' ), true ) && 0 !== $object_id;
				$is_wc_term_active = false;
				if ( $is_wc_term && ( is_product_category() || is_product_tag() ) ) {
					$queried           = get_queried_object();
					$is_wc_term_active = ( $queried instanceof WP_Term && $queried->term_id === $object_id );
				}

				$is_wc_active = ( ( $is_shop_page && $is_wc_context ) || $is_wc_term_active );
			}

			return $is_core_current || $is_cpt_active || $is_safe_page_parent || $is_wc_active;
		}

		/**
		 * Returns a string argument of wp_nav_menu().
		 *
		 * @since 1.0.0
		 *
		 * @param object|array<mixed> $args Arguments of wp_nav_menu() as object or array.
		 * @param string              $key  Argument name.
		 * @return string Value or an empty string.
		 */
		private static function arg( object|array $args, string $key ): string {
			$value = self::arg_value( $args, $key );
			return is_string( $value ) ? $value : '';
		}

		/**
		 * Returns an argument of wp_nav_menu(), given as object or array.
		 *
		 * @since 1.0.0
		 *
		 * @param object|array<mixed> $args Arguments of wp_nav_menu() as object or array.
		 * @param string              $key  Argument name.
		 * @return mixed Value or null.
		 */
		private static function arg_value( object|array $args, string $key ): mixed {
			return is_array( $args ) ? ( $args[ $key ] ?? null ) : ( $args->$key ?? null );
		}
	}

endif;
