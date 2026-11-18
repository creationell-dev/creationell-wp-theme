<?php
/**
 * Sidebar of the blog view.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Blog\Blog_Context;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows the sidebar in the blog view with the categories and tags above the widgets.
 *
 * In the blog view the sidebar shows also without widgets, as long as it
 * shows something: the term lists, the post filter or widgets; an empty
 * sidebar prints no landmark. The single post templates "No Sidebar" and
 * "Full width image" are never forced and keep their widgets alone, without
 * term lists. The aside is called "Blog sidebar",
 * and below lg it stands under the content instead of in the offcanvas panel
 * (setting blog_sidebar_mobile), except in the template "Sidebar left". The
 * callbacks read the settings when they run.
 *
 * @since 1.0.0
 */
final class Blog_Sidebar {

	/**
	 * Single post templates whose sidebar the module never forces.
	 *
	 * @since 1.0.0
	 */
	public const UNFORCED_TEMPLATES = array(
		'single-templates/single-sidebar-none.php',
		'single-templates/single-full-width-image.php',
	);

	/**
	 * Single post template that keeps the offcanvas panel below lg.
	 *
	 * @since 1.0.0
	 */
	public const OFFCANVAS_TEMPLATE = 'single-templates/single-sidebar-left.php';

	/**
	 * Adds the filters and the action of the sidebar; Blog_Module::boot() calls it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'creationell_wp_theme_blog_sidebar_force', array( self::class, 'force' ) );
		add_filter( 'creationell_wp_theme_sidebar_aria_label', array( self::class, 'aria_label' ) );
		add_action( 'creationell_wp_theme_before_sidebar_widgets', array( self::class, 'render_terms' ), 10, 0 );
		add_filter( 'creationell_wp_theme_class_sidebar_offcanvas', array( self::class, 'class_sidebar_offcanvas' ) );
		add_filter( 'creationell_wp_theme_class_sidebar_button', array( self::class, 'class_sidebar_button' ) );
		add_filter( 'creationell_wp_theme_class_offcanvas_header', array( self::class, 'class_offcanvas_header' ), 10, 2 );
	}

	/**
	 * Forces the sidebar when should_force() says so; runs on creationell_wp_theme_blog_sidebar_force.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $force Answer so far; true stays true.
	 * @return bool Whether the sidebar shows without widgets.
	 */
	public static function force( mixed $force ): bool {
		return true === $force || self::should_force();
	}

	/**
	 * Tells whether the module forces the sidebar of the request.
	 *
	 * True in the blog view, outside the single post templates "No Sidebar"
	 * and "Full width image", when the sidebar shows the term lists, the post
	 * filter or widgets.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the sidebar shows.
	 */
	public static function should_force(): bool {
		if ( ! Blog_Context::is_blog_view() || self::unforced() ) {
			return false;
		}
		return self::show_terms() || self::shows_post_filter() || is_active_sidebar( 'sidebar-1' );
	}

	/**
	 * Tells whether the request is a single post with a template whose sidebar the module leaves as it is: "No Sidebar" or "Full width image" (UNFORCED_TEMPLATES).
	 *
	 * The module post-filter asks it too: these templates get no term lists
	 * and no filter bar, only their widgets.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for a single post with one of these templates.
	 */
	public static function unforced(): bool {
		return self::template_in( self::UNFORCED_TEMPLATES );
	}

	/**
	 * Names the aside "Blog sidebar" in the blog view; runs on creationell_wp_theme_sidebar_aria_label.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $label Name so far; a non-empty name stays.
	 * @return mixed Name.
	 */
	public static function aria_label( mixed $label ): mixed {
		if ( ( is_string( $label ) && '' !== $label ) || ! Blog_Context::is_blog_view() ) {
			return $label;
		}
		/* translators: Accessible name of the sidebar landmark on blog pages, single posts and post archives. */
		return __( 'Blog sidebar', 'creationell-wp-theme' );
	}

	/**
	 * Prints the categories and tags in the blog view; runs on creationell_wp_theme_before_sidebar_widgets.
	 *
	 * Prints template-parts/blog/sidebar-terms.php with the arguments
	 * categories, tags, counts, label_categories and label_tags, or nothing
	 * when no list shows and on the templates of unforced().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render_terms(): void {
		if ( ! Blog_Context::is_blog_view() || self::unforced() ) {
			return;
		}
		$lists = self::lists();
		if ( array() === $lists['categories'] && array() === $lists['tags'] ) {
			return;
		}
		get_template_part(
			'template-parts/blog/sidebar-terms',
			null,
			array(
				'categories'       => $lists['categories'],
				'tags'             => $lists['tags'],
				'counts'           => true === Blog_Module::setting( 'blog_sidebar_counts' ),
				'label_categories' => Blog_Module::text( 'blog_label_categories', __( 'Categories', 'creationell-wp-theme' ) ),
				'label_tags'       => Blog_Module::text( 'blog_label_tags', __( 'Tags', 'creationell-wp-theme' ) ),
			)
		);
	}

	/**
	 * Drops the offcanvas classes of the sidebar while it stands below the content; runs on creationell_wp_theme_class_sidebar_offcanvas.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $classes Classes so far.
	 * @return mixed Empty string below the content, else the classes.
	 */
	public static function class_sidebar_offcanvas( mixed $classes ): mixed {
		return self::below_content() ? '' : $classes;
	}

	/**
	 * Hides the button that opens the sidebar while it stands below the content; runs on creationell_wp_theme_class_sidebar_button.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $classes Classes so far.
	 * @return mixed d-none below the content, else the classes.
	 */
	public static function class_sidebar_button( mixed $classes ): mixed {
		return self::below_content() ? 'd-none' : $classes;
	}

	/**
	 * Hides the offcanvas header of the sidebar while it stands below the content; runs on creationell_wp_theme_class_offcanvas_header.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $classes Classes so far.
	 * @param mixed $context Offcanvas: sidebar or menu.
	 * @return mixed d-none for the sidebar below the content, else the classes.
	 */
	public static function class_offcanvas_header( mixed $classes, mixed $context = '' ): mixed {
		return 'sidebar' === $context && self::below_content() ? 'd-none' : $classes;
	}

	/**
	 * Tells whether the sidebar stands below the content below lg: setting below, blog view, not the template "Sidebar left".
	 *
	 * @since 1.0.0
	 *
	 * @return bool True below the content.
	 */
	private static function below_content(): bool {
		return 'below' === Blog_Module::setting( 'blog_sidebar_mobile' )
			&& Blog_Context::is_blog_view()
			&& ! self::template_in( array( self::OFFCANVAS_TEMPLATE ) );
	}

	/**
	 * Tells whether the request is a single post whose queried post has one of the given templates.
	 *
	 * Only single posts count, and the template is read from the queried
	 * post, not from the global post, which a secondary loop may have moved.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $templates Template files relative to the theme.
	 * @return bool True for a single post with one of the templates.
	 */
	private static function template_in( array $templates ): bool {
		return is_singular( 'post' ) && in_array( get_page_template_slug( get_queried_object_id() ), $templates, true );
	}

	/**
	 * Tells whether the term lists show: filter on, setting not off and the list the setting selects not empty.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when a list shows.
	 */
	private static function show_terms(): bool {
		$lists = self::lists();
		return array() !== $lists['categories'] || array() !== $lists['tags'];
	}

	/**
	 * Returns the lists the sidebar shows, after the filter and the setting blog_sidebar_terms.
	 *
	 * @since 1.0.0
	 *
	 * @return array{categories: list<array<string, mixed>>, tags: list<array<string, mixed>>} Lists; empty for a list that does not show.
	 */
	private static function lists(): array {
		$none = array(
			'categories' => array(),
			'tags'       => array(),
		);

		/**
		 * Filters whether the blog sidebar shows the categories and tags.
		 *
		 * The module post-filter switches the lists off where its filter bar
		 * takes their place. Only true counts; any other answer hides the lists.
		 *
		 * Example:
		 *
		 *     add_filter( 'creationell_wp_theme_blog_sidebar_terms', static fn( bool $show ): bool => $show && ! is_singular( 'post' ) );
		 *
		 * @since 1.0.0
		 *
		 * @param bool $show Whether the lists show; true by default.
		 */
		if ( true !== apply_filters( 'creationell_wp_theme_blog_sidebar_terms', true ) ) {
			return $none;
		}
		$setting = Blog_Module::setting( 'blog_sidebar_terms' );
		if ( ! in_array( $setting, array( 'both', 'categories', 'tags' ), true ) ) {
			return $none;
		}
		return array(
			'categories' => 'tags' === $setting ? array() : Term_Lists::categories(),
			'tags'       => 'categories' === $setting ? array() : Term_Lists::tags(),
		);
	}

	/**
	 * Tells whether the sidebar shows the filter bar of the module post-filter.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with the filter bar.
	 */
	private static function shows_post_filter(): bool {
		/**
		 * Filters whether the blog sidebar shows the filter bar of the module post-filter.
		 *
		 * The module post-filter answers true where it prints its filter bar, so
		 * the sidebar shows also without widgets. Only true counts.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $has_filter Whether the sidebar shows a filter bar; false by default.
		 */
		return true === apply_filters( 'creationell_wp_theme_blog_sidebar_has_filter', false );
	}
}
