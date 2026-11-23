<?php
/**
 * Doctor section blog of the module blog.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Blog;

use Creationell\WpTheme\Blog\Blog_Templates;
use Creationell\WpTheme\Blog\Blog_Url;
use Creationell\WpTheme\Cli\Doctor_Command;
use Creationell\WpTheme\Modules\Module_State;
use Creationell\WpTheme\Modules\PostFilter\Post_Filter_Module;
use WP_User;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reports in "wp creationell-theme doctor" what keeps the parts of the module blog from showing as meant.
 *
 * Blog_Module::boot() adds the section on cli_init, so it exists only while
 * the module is active. The collector reads only; it needs no post and no
 * request. Warnings name a user by ID, never by login.
 *
 * @since 1.0.0
 */
final class Blog_Doctor {

	/**
	 * Key of the doctor section.
	 *
	 * @since 1.0.0
	 */
	public const DOCTOR_SECTION = 'blog';

	/**
	 * Calls a single template of a child theme keeps for the parts of the module.
	 *
	 * @since 1.0.0
	 */
	private const SINGLE_CALLS = "do_action( 'creationell_wp_theme_entry_header_open', \$context ), creationell_wp_theme_category_badge( \$context ), creationell_wp_theme_entry_meta( \$context ), creationell_wp_theme_entry_terms( \$context ) and do_action( 'creationell_wp_theme_after_entry_terms', \$context )";

	/**
	 * Hooks of the parent sidebar.php a child sidebar.php keeps for the blog sidebar.
	 *
	 * @since 1.0.0
	 */
	private const SIDEBAR_CALLS = 'apply the filters creationell_wp_theme_blog_sidebar_force and creationell_wp_theme_sidebar_aria_label and fire creationell_wp_theme_before_sidebar_widgets for the blog sidebar';

	/**
	 * Services Shariff shows while its settings name none.
	 *
	 * @since 1.0.0
	 */
	private const SHARIFF_DEFAULT_SERVICES = 'mastodon|facebook|linkedin|info';

	/**
	 * Adds the doctor section blog once; runs on cli_init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function add_doctor_section(): void {
		if ( in_array( self::DOCTOR_SECTION, Doctor_Command::section_keys(), true ) ) {
			return;
		}
		Doctor_Command::add_section( self::DOCTOR_SECTION, array( self::class, 'doctor_section' ) );
	}

	/**
	 * Collects the doctor section blog; reads only.
	 *
	 * Keys: state (effective state of the module), blog_url (URL of the blog
	 * page, empty without one), author_pages (setting blog_author_pages),
	 * share_mode (off, shariff or links as the setting and Shariff give it,
	 * Share_Links::configured_mode()), post_filter (effective state of the
	 * module post-filter) and, with findings, warnings.
	 *
	 * Example:
	 *
	 *     wp creationell-theme doctor --format=json | jq -e '.blog.warnings == null'
	 *
	 * @since 1.0.0
	 *
	 * @return array{state: string, blog_url: string, author_pages: bool, share_mode: string, post_filter: string, warnings?: list<string>} Values.
	 */
	public static function doctor_section(): array {
		$blog_url     = Blog_Url::url();
		$author_pages = Author_Pages::enabled();
		$post_filter  = Module_State::instance()->state( 'post-filter' );
		$values       = array(
			'state'        => Module_State::instance()->state( 'blog' ),
			'blog_url'     => $blog_url,
			'author_pages' => $author_pages,
			'share_mode'   => Share_Links::configured_mode(),
			'post_filter'  => $post_filter,
		);
		$warnings     = array();
		if ( '' === $blog_url ) {
			$warnings[] = 'No blog page is set: "To the blog" and the back link are not shown.';
		}
		array_push(
			$warnings,
			...self::template_warnings( $author_pages ),
			...self::shariff_warnings(),
			...self::post_filter_warnings( $post_filter ),
			...self::author_warnings(),
			...self::seo_warnings( $author_pages )
		);
		if ( array() !== $warnings ) {
			$values['warnings'] = $warnings;
		}
		return $values;
	}

	/**
	 * Warns about templates of the child theme: index.php or archive.php that win over the templates of the module, and the single templates of posts, sidebar.php and 404.php that must keep the calls of the module.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $author_pages Whether the author pages are on.
	 * @return list<string> Warnings.
	 */
	private static function template_warnings( bool $author_pages ): array {
		if ( ! is_child_theme() ) {
			return array();
		}
		$warnings = array();
		if ( Blog_Templates::child_overrides( 'home' ) && '' !== Blog_Templates::fallback( 'home' ) ) {
			$warnings[] = "The child theme's index.php takes priority over the blog page of the module.";
		}
		if ( $author_pages && Blog_Templates::child_overrides( 'author' ) && '' !== Blog_Templates::fallback( 'author' ) ) {
			$warnings[] = "The child theme's archive.php takes priority over the author pages.";
		}
		$dir        = get_stylesheet_directory(); // creationell-allow-stylesheet: the doctor names the templates of the child theme that replace those of the parent.
		$root       = glob( $dir . '/single*.php' );
		$templates  = glob( $dir . '/single-templates/*.php' );
		$root_names = array_filter(
			array_map( static fn( string $file ): string => basename( $file ), false === $root ? array() : $root ),
			static fn( string $file ): bool => 1 === preg_match( '~^single(?:-post(?:-.+)?)?\.php$~', $file )
		);
		$singles    = array_merge(
			array_values( $root_names ),
			array_map( static fn( string $file ): string => 'single-templates/' . basename( $file ), false === $templates ? array() : $templates )
		);
		foreach ( $singles as $file ) {
			$warnings[] = 'The child theme overrides ' . $file . '; it must call ' . self::SINGLE_CALLS . '.';
		}
		if ( is_file( $dir . '/sidebar.php' ) ) {
			$warnings[] = 'The child theme overrides sidebar.php; it must ' . self::SIDEBAR_CALLS . '.';
		}
		if ( is_file( $dir . '/404.php' ) ) {
			$warnings[] = "The child theme overrides 404.php; it must fire do_action( 'creationell_wp_theme_404_after_content', '404' ) for the search and the latest posts.";
		}
		return $warnings;
	}

	/**
	 * Warns about Shariff while it is loaded: buttons it inserts itself (on posts, by meta box or on the blog page), share counts through the server or an external host, and Mastodon over a third-party service.
	 *
	 * Reads the options global of the plugin as the plugin does (switches
	 * and the backend count only as the integer 1; an empty list of services
	 * means the default list of the plugin, which holds Mastodon). Whether a
	 * post has the meta box set is one query for one ID.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Warnings.
	 */
	private static function shariff_warnings(): array {
		$options = $GLOBALS['shariff3uu'] ?? null;
		if ( ! is_array( $options ) ) {
			return array();
		}
		$warnings = array();
		if ( Share_Links::shariff_inserts_globally() ) {
			$warnings[] = 'Shariff inserts its buttons on posts itself (its settings or a custom hook of the theme); the module shows no share bar of its own there. Switch the automatic insertion off in Shariff.';
		}
		$with_meta_box = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'numberposts'      => 1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'meta_query'       => array(
					'relation' => 'OR',
					array(
						'key'     => 'shariff_metabox_before',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'shariff_metabox_after',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		if ( array() !== $with_meta_box ) {
			$warnings[] = 'At least one post adds the Shariff buttons through its meta box; the module shows no share bar of its own on such posts.';
		}
		foreach ( array( 'add_before', 'add_after' ) as $place ) {
			if ( is_array( $options[ $place ] ?? null ) && 1 === ( $options[ $place ]['blogpage'] ?? null ) ) {
				$warnings[] = 'Shariff inserts its buttons on the blog page and the archives (blogpage); there they share the first post of the list.';
				break;
			}
		}
		$backend = 1 === ( $options['backend'] ?? null );
		if ( $backend ) {
			$warnings[] = 'Shariff counts shares through the server (backend): the server asks the networks; switch the share counts off.';
		}
		if ( $backend && isset( $options['external_direct'] ) && is_string( $options['external_host'] ?? null ) && '' !== $options['external_host'] ) {
			$warnings[] = 'Shariff lets the browser ask an external host for the share counts on every page view (external_direct).';
		}
		$services = is_string( $options['services'] ?? null ) ? $options['services'] : '';
		$services = trim( (string) preg_replace( '/[^A-Za-z|]/', '', $services ), '|' );
		if ( in_array( 'mastodon', explode( '|', '' === $services ? self::SHARIFF_DEFAULT_SERVICES : $services ), true ) ) {
			$warnings[] = 'Shariff shows Mastodon, which shares through the third-party service s2f.kytta.dev; name it in the privacy policy or remove the service.';
		}
		return $warnings;
	}

	/**
	 * Warns while the module post-filter is active: no filter bar for posts, or the link mode none of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $state Effective state of the module post-filter.
	 * @return list<string> Warnings.
	 */
	private static function post_filter_warnings( string $state ): array {
		if ( 'active' !== $state || ! class_exists( Post_Filter_Module::class, false ) ) {
			return array();
		}
		$warnings = array();
		if ( '' === Post_Filter_Module::filter_html() ) {
			$warnings[] = 'The post filter shows no filter bar for posts (Post Filter older than 1.7.2 or without a filter group for posts); the sidebar shows the term lists instead.';
		}
		if ( function_exists( 'jpkcom_postfilter_policy' ) && 'none' === jpkcom_postfilter_policy( 'filter_link_mode' ) ) {
			$warnings[] = 'Post Filter has the link mode none (filter_link_mode): the filter bar on posts and archives has no links; choose another link mode in Post Filter.';
		}
		return $warnings;
	}

	/**
	 * Warns about authors with published posts whose display name or nicename is their login, compared without case; names the user ID only.
	 *
	 * The nicename also counts as the login when it is the slug WordPress
	 * derives from the login by default (sanitize_title(): a dot or a space
	 * becomes a hyphen, an at sign drops), as for the login anna.schmidt.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Warnings.
	 */
	private static function author_warnings(): array {
		$warnings = array();
		foreach ( get_users( array( 'has_published_posts' => array( 'post' ) ) ) as $user ) {
			if ( ! $user instanceof WP_User || '' === $user->user_login ) {
				continue;
			}
			$login    = strtolower( $user->user_login );
			$slug     = sanitize_title( $user->user_login );
			$nicename = strtolower( $user->user_nicename );
			$id       = (int) $user->ID;
			if ( strtolower( $user->display_name ) === $login ) {
				$warnings[] = sprintf( 'The author with user ID %d shows the login as display name in the byline; choose another display name in the profile.', $id );
			}
			if ( $nicename === $login || ( '' !== $slug && $nicename === $slug ) ) {
				$warnings[] = sprintf( 'The author with user ID %d has the login as nicename, the address of the author page; change the nicename (user_nicename).', $id );
			}
		}
		return $warnings;
	}

	/**
	 * Warns while the author pages are off and Rank Math or Yoast SEO list the authors in their sitemap or keep the author archives.
	 *
	 * Rank Math stores its switches as "on" and "off" (options
	 * rank-math-options-sitemap, key authors_sitemap, while the module sitemap
	 * is in rank_math_modules; rank-math-options-titles, key
	 * disable_author_archives). Yoast SEO keeps booleans: wpseo_titles with
	 * disable-author and noindex-author-wpseo, wpseo with enable_xml_sitemap
	 * (on by default); its author sitemap needs the archives on and indexed.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $author_pages Whether the author pages are on.
	 * @return list<string> Warnings.
	 */
	private static function seo_warnings( bool $author_pages ): array {
		if ( $author_pages ) {
			return array();
		}
		$warnings = array();
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			$modules = get_option( 'rank_math_modules', array() );
			$sitemap = get_option( 'rank-math-options-sitemap', array() );
			$titles  = get_option( 'rank-math-options-titles', array() );
			if ( is_array( $modules ) && in_array( 'sitemap', $modules, true ) && is_array( $sitemap ) && self::rank_math_on( $sitemap['authors_sitemap'] ?? null ) ) {
				$warnings[] = 'Author pages are off, but Rank Math lists the authors in its sitemap; switch the author sitemap off in Rank Math.';
			}
			if ( ! is_array( $titles ) || ! self::rank_math_on( $titles['disable_author_archives'] ?? null ) ) {
				$warnings[] = 'Author pages are off, but Rank Math keeps the author archives; switch them off in Rank Math (Titles & Meta, Authors).';
			}
		}
		if ( defined( 'WPSEO_VERSION' ) ) {
			$titles   = get_option( 'wpseo_titles', array() );
			$titles   = is_array( $titles ) ? $titles : array();
			$wpseo    = get_option( 'wpseo', array() );
			$archives = empty( $titles['disable-author'] );
			if ( $archives && empty( $titles['noindex-author-wpseo'] ) && ( ! is_array( $wpseo ) || ! isset( $wpseo['enable_xml_sitemap'] ) || ! empty( $wpseo['enable_xml_sitemap'] ) ) ) {
				$warnings[] = 'Author pages are off, but Yoast SEO lists the authors in its sitemap; set the author archives to noindex or switch them off in Yoast SEO.';
			}
			if ( $archives ) {
				$warnings[] = 'Author pages are off, but Yoast SEO keeps the author archives; switch them off in Yoast SEO (Settings, Author archives).';
			}
		}
		return $warnings;
	}

	/**
	 * Tells whether a switch of Rank Math is on, as the plugin reads it: "on" or "true", or true.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Stored value.
	 * @return bool True when the switch is on.
	 */
	private static function rank_math_on( mixed $value ): bool {
		return true === $value || 'on' === $value || 'true' === $value;
	}
}
