<?php
/**
 * Hooks of the module post-filter.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\PostFilter;

use Creationell\WpTheme\Blog\Blog_Context;
use Creationell\WpTheme\Modules\Blog\Blog_Sidebar;
use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shows the filters of the plugin JPKCom Post Filter in the blog sidebar instead of the plain category and tag lists.
 *
 * The file bootstrap.php calls boot() once, while the module is active; the
 * module state keeps it off without the plugin or without the module blog.
 * Boot runs before the main query, so it checks only the version constant
 * and the functions of the plugin. Whether the filter bar has content is
 * asked when the sidebar renders, and kept for the request once the main
 * query has run.
 *
 * In the blog view (single posts, the blog page, category, tag, date and
 * author archives) the filter bar takes the place of the term lists of the
 * module blog. On the blog page the plugin injects its own bar while its
 * setting auto-inject is on; there the sidebar shows neither filter nor term
 * lists, only widgets. Without content the term lists stay. The single post
 * templates "No Sidebar" and "Full width image" get no filter bar. On an
 * empty result of the blog page the zero results zone of the plugin takes the
 * place of the note of the theme.
 *
 * @since 1.0.0
 */
final class Post_Filter_Module {

	/**
	 * Oldest version of JPKCom Post Filter the module works with.
	 *
	 * @since 1.0.0
	 */
	public const MIN_VERSION = '1.7.2';

	/**
	 * Handle of the stylesheet of the module.
	 *
	 * @since 1.0.0
	 */
	public const HANDLE = 'creationell-wp-theme-post-filter';

	/**
	 * Stylesheet of the module, relative to the module folder.
	 *
	 * @since 1.0.0
	 */
	public const STYLESHEET = 'assets/post-filter.css';

	/**
	 * Handle of the compiled theme stylesheet, loaded before the module stylesheet.
	 *
	 * @since 1.0.0
	 */
	public const THEME_STYLE_HANDLE = 'creationell-wp-theme-main';

	/**
	 * Template part of the filter bar, overridable by a child theme.
	 *
	 * @since 1.0.0
	 */
	public const PART = 'template-parts/post-filter/filter';

	/**
	 * Function of the plugin that renders the filter bar.
	 *
	 * @since 1.0.0
	 */
	private const FILTER_FUNCTION = 'jpkcom_postfilter_shortcode_filter';

	/**
	 * Function of the plugin that prints the filter bar above an empty result list.
	 *
	 * @since 1.0.0
	 */
	private const ZERO_RESULTS_FUNCTION = 'jpkcom_postfilter_render_zero_results_fallback';

	/**
	 * Pattern of a filter group in the markup: an element whose class list holds the token jpkpf-filter-group.
	 *
	 * The plugin prints its nav, the reset link and the live region also
	 * without a filter group for the post type; only the groups mark content.
	 *
	 * @since 1.0.0
	 */
	private const GROUP_PATTERN = '/(?<![\w-])class\s*=\s*(["\'])[^"\']*(?<![\w-])jpkpf-filter-group(?![\w-])[^"\']*\1/i';

	/**
	 * Markup of the filter bar for the request, null while not known.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private static ?string $html = null;

	/**
	 * Adds the hooks of the module; bootstrap.php calls it once with the effective state.
	 *
	 * Adds nothing without the version constant of the plugin, with a version
	 * before MIN_VERSION or without the function of the filter bar. The
	 * callbacks ask for the markup when they run.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state Effective state of the module: active.
	 * @return void
	 */
	public static function boot( string $state ): void {
		if ( 'active' !== $state || ! self::plugin_ready() ) {
			return;
		}
		add_filter( 'creationell_wp_theme_blog_sidebar_has_filter', array( self::class, 'sidebar_has_filter' ) );
		add_filter( 'creationell_wp_theme_blog_sidebar_terms', array( self::class, 'sidebar_terms' ) );
		add_action( 'creationell_wp_theme_before_sidebar_widgets', array( self::class, 'render' ), 9, 0 );
		if ( function_exists( self::ZERO_RESULTS_FUNCTION ) ) {
			add_action( 'creationell_wp_theme_before_loop', self::ZERO_RESULTS_FUNCTION, 1 );
		}
		add_filter( 'creationell_wp_theme_blog_home_empty', array( self::class, 'home_empty' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_front' ), 21, 0 );
	}

	/**
	 * Tells whether the filter bar of the plugin can show: the plugin in version MIN_VERSION or later, and a filter bar for posts with at least one filter group.
	 *
	 * Renders the filter bar on the first call; see filter_html().
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the filter bar has content.
	 */
	public static function available(): bool {
		return '' !== self::filter_html();
	}

	/**
	 * Returns the filter bar of the plugin for posts in the layout sidebar, trimmed, or an empty string when it holds no filter group.
	 *
	 * Calls jpkcom_postfilter_shortcode_filter() without parsing a shortcode.
	 * The plugin renders its nav also when no filter group with terms targets
	 * posts; the markup counts only with an element jpkpf-filter-group (see
	 * GROUP_PATTERN), else the answer is empty. After the main query (action
	 * wp) the answer is kept for the request, because the plugin reads the
	 * active filters from it; before, every call asks the plugin again.
	 *
	 * @since 1.0.0
	 *
	 * @return string Markup; empty without the plugin, with empty markup or without a filter group for posts.
	 */
	public static function filter_html(): string {
		if ( null !== self::$html ) {
			return self::$html;
		}
		if ( ! self::plugin_ready() ) {
			return '';
		}
		$html = trim(
			jpkcom_postfilter_shortcode_filter(
				array(
					'post_type' => 'post',
					'layout'    => 'sidebar',
				)
			)
		);
		if ( 1 !== preg_match( self::GROUP_PATTERN, $html ) ) {
			$html = '';
		}
		if ( did_action( 'wp' ) > 0 ) {
			self::$html = $html;
		}
		return $html;
	}

	/**
	 * Tells whether the plugin injects its own filter bar into the blog page.
	 *
	 * Asks jpkcom_postfilter_should_auto_inject() with the main query, as
	 * the plugin does on loop_start.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on the blog page while the plugin injects.
	 */
	public static function auto_injects(): bool {
		$query = $GLOBALS['wp_query'] ?? null;
		return is_home()
			&& $query instanceof WP_Query
			&& function_exists( 'jpkcom_postfilter_should_auto_inject' )
			&& true === jpkcom_postfilter_should_auto_inject( $query );
	}

	/**
	 * Tells the blog sidebar that it shows the filter bar; runs on creationell_wp_theme_blog_sidebar_has_filter.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $has_filter Answer so far; true stays true.
	 * @return bool Whether the sidebar shows a filter bar.
	 */
	public static function sidebar_has_filter( mixed $has_filter ): bool {
		return true === $has_filter || self::shows_filter();
	}

	/**
	 * Hides the term lists of the blog sidebar while the filter bar can show; runs on creationell_wp_theme_blog_sidebar_terms.
	 *
	 * In the blog view the filter bar takes their place; on the blog page
	 * with auto-inject the bar of the plugin above the posts does.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $show Answer so far.
	 * @return mixed False in the blog view with a filter bar, else the answer so far.
	 */
	public static function sidebar_terms( mixed $show ): mixed {
		return Blog_Context::is_blog_view() && self::available() ? false : $show;
	}

	/**
	 * Drops the note of the blog page on an empty result while the plugin prints its zero results zone; runs on creationell_wp_theme_blog_home_empty.
	 *
	 * The zone counts once the plugin has printed it (its flag
	 * _jpkpf_zero_results_rendered) and while it injects into the blog page,
	 * since it then prints the zone later on its own.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $show Answer so far.
	 * @return mixed False with the zone of the plugin, else the answer so far.
	 */
	public static function home_empty( mixed $show ): mixed {
		return true === ( $GLOBALS['_jpkpf_zero_results_rendered'] ?? false ) || self::auto_injects() ? false : $show;
	}

	/**
	 * Prints the filter bar at the top of the blog sidebar; runs on creationell_wp_theme_before_sidebar_widgets (priority 9).
	 *
	 * Prints template-parts/post-filter/filter.php with the argument html, in
	 * the blog view only and not on the blog page while the plugin injects
	 * its own bar.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! self::shows_filter() ) {
			return;
		}
		get_template_part( self::PART, null, array( 'html' => self::filter_html() ) );
	}

	/**
	 * Enqueues the stylesheet that sets the variables of the plugin to the colors of the theme; runs on wp_enqueue_scripts (priority 21), never in the admin.
	 *
	 * The plugin enqueues its stylesheet on priority 20 and may print its
	 * filter bar on any page (shortcode, block), so the module stylesheet
	 * follows on every front-end page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function enqueue_front(): void {
		if ( is_admin() ) {
			return;
		}
		wp_enqueue_style( self::HANDLE, CREATIONELL_WP_THEME_URL . '/modules/post-filter/' . self::STYLESHEET, array( self::THEME_STYLE_HANDLE ), CREATIONELL_WP_THEME_VERSION );
	}

	/**
	 * Forgets the markup of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$html = null;
	}

	/**
	 * Tells whether the sidebar of the request shows the filter bar: blog view, not a single post template that keeps its sidebar, no auto-inject and content.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with the filter bar.
	 */
	private static function shows_filter(): bool {
		return Blog_Context::is_blog_view() && ! Blog_Sidebar::unforced() && ! self::auto_injects() && self::available();
	}

	/**
	 * Tells whether the plugin is loaded in version MIN_VERSION or later with the function of the filter bar.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when the plugin can render the filter bar.
	 */
	private static function plugin_ready(): bool {
		if ( ! defined( 'JPKCOM_POSTFILTER_VERSION' ) ) {
			return false;
		}
		$version = constant( 'JPKCOM_POSTFILTER_VERSION' );
		return is_string( $version )
			&& version_compare( $version, self::MIN_VERSION, '>=' )
			&& function_exists( self::FILTER_FUNCTION );
	}
}
