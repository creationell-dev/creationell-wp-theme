<?php
/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'creationell_wp_theme_widgets_init' ) ) :

	/**
	 * Registers the widget areas.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function creationell_wp_theme_widgets_init(): void {

		// Top Bar.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Top Bar', 'creationell-wp-theme' ),
				'id'            => 'top-bar',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget top-bar-widget">',
				'after_widget'  => '</div>',
				'before_title'  => '<div class="widget-title d-none">',
				'after_title'   => '</div>',
			)
		);

		// Top Nav.
		/**
		 * Filters the CSS classes that set the spacing of an element in the header actions.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $element Header element, for example search-toggler.
		 */
		register_sidebar(
			array(
				'name'          => esc_html__( 'Top Nav', 'creationell-wp-theme' ),
				'id'            => 'top-nav',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget top-nav-widget ' . esc_attr( apply_filters( 'creationell_wp_theme_class_header_action_spacer', 'ms-1 ms-md-2', 'top-nav-widget' ) ) . '">',
				'after_widget'  => '</div>',
				'before_title'  => '<div class="widget-title d-none">',
				'after_title'   => '</div>',
			)
		);

		// Top Nav 2: a widget next to the Top Nav position that moves into the offcanvas below the lg breakpoint.
		/**
		 * Filters the CSS classes of the second top navigation widget area.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 */
		register_sidebar(
			array(
				'name'          => esc_html__( 'Top Nav 2', 'creationell-wp-theme' ),
				'id'            => 'top-nav-2',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget top-nav-widget-2 ' . esc_attr( apply_filters( 'creationell_wp_theme_class_header_top_nav_widget_2', 'd-lg-flex align-items-lg-center mt-2 mt-lg-0 ms-lg-2' ) ) . '">',
				'after_widget'  => '</div>',
				'before_title'  => '<div class="widget-title d-none">',
				'after_title'   => '</div>',
			)
		);

		// Top Nav Search.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Top Nav Search', 'creationell-wp-theme' ),
				'id'            => 'top-nav-search',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget top-nav-search">',
				'after_widget'  => '</div>',
				'before_title'  => '<div class="widget-title d-none">',
				'after_title'   => '</div>',
			)
		);

		// Sidebar.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Sidebar', 'creationell-wp-theme' ),
				'id'            => 'sidebar-1',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<section id="%1$s" class="widget mb-4">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title h5">',
				'after_title'   => '</h2>',
			)
		);

		// Footer Top.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer Top', 'creationell-wp-theme' ),
				'id'            => 'footer-top',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget footer_widget">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);

		// Footer 1.
		/**
		 * Filters the CSS classes that set the spacing of the widgets in a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer 1', 'creationell-wp-theme' ),
				'id'            => 'footer-1',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget footer_widget ' . esc_attr( apply_filters( 'creationell_wp_theme_class_footer_col_spacer', 'mb-3', 'footer-1' ) ) . '">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title h5">',
				'after_title'   => '</h2>',
			)
		);

		// Footer 2.
		/**
		 * Filters the CSS classes that set the spacing of the widgets in a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer 2', 'creationell-wp-theme' ),
				'id'            => 'footer-2',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget footer_widget ' . esc_attr( apply_filters( 'creationell_wp_theme_class_footer_col_spacer', 'mb-3', 'footer-2' ) ) . '">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title h5">',
				'after_title'   => '</h2>',
			)
		);

		// Footer 3.
		/**
		 * Filters the CSS classes that set the spacing of the widgets in a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer 3', 'creationell-wp-theme' ),
				'id'            => 'footer-3',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget footer_widget ' . esc_attr( apply_filters( 'creationell_wp_theme_class_footer_col_spacer', 'mb-3', 'footer-3' ) ) . '">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title h5">',
				'after_title'   => '</h2>',
			)
		);

		// Footer 4.
		/**
		 * Filters the CSS classes that set the spacing of the widgets in a footer column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $classes Space-separated CSS classes.
		 * @param string $sidebar Footer widget area, footer-1 to footer-4.
		 */
		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer 4', 'creationell-wp-theme' ),
				'id'            => 'footer-4',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget footer_widget ' . esc_attr( apply_filters( 'creationell_wp_theme_class_footer_col_spacer', 'mb-3', 'footer-4' ) ) . '">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title h5">',
				'after_title'   => '</h2>',
			)
		);

		// Footer Info.
		register_sidebar(
			array(
				'name'          => esc_html__( 'Footer Info', 'creationell-wp-theme' ),
				'id'            => 'footer-info',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget footer_widget">',
				'after_widget'  => '</div>',
				'before_title'  => '<div class="widget-title d-none">',
				'after_title'   => '</div>',
			)
		);

		// 404 Page.
		register_sidebar(
			array(
				'name'          => esc_html__( '404 Page', 'creationell-wp-theme' ),
				'id'            => '404-page',
				'description'   => esc_html__( 'Add widgets here.', 'creationell-wp-theme' ),
				'before_widget' => '<div class="widget mb-4">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title h4">',
				'after_title'   => '</h2>',
			)
		);
	}

	add_action( 'widgets_init', 'creationell_wp_theme_widgets_init' );

endif;
