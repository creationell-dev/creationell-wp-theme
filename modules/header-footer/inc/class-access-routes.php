<?php
/**
 * Allow list of REST routes and Site Editor paths for editors of header and footer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Core\Site_Editor_Lock;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Knows which REST routes and Site Editor paths an editor reaches while editing header and footer.
 *
 * The block editor switches views without reloading, so the REST routes are
 * the real limit; the Site Editor paths only keep the screens tidy. A route
 * belongs to a group: "parts" (the template parts header and footer of the
 * active stylesheet), "read" (theme styles the part canvas reads) or "menus"
 * (classic menus, only with the menu capability; Menu_Scope limits them to the
 * menus of header and footer). Creating parts, templates or menus, deleting
 * menus, writing global styles, font families, settings, sidebars, widgets and
 * navigation posts are never on the list.
 *
 * Patterns are literal paths with the placeholders {stylesheet} (the active
 * stylesheet), {part} ("<stylesheet>//header" or "<stylesheet>//footer"),
 * {n} (a number) and {any} (nothing or a sub path).
 *
 * @since 1.0.0
 */
final class Access_Routes {

	/**
	 * Group of the template part routes.
	 *
	 * @since 1.0.0
	 */
	public const GROUP_PARTS = 'parts';

	/**
	 * Group of the read-only style routes.
	 *
	 * @since 1.0.0
	 */
	public const GROUP_READ = 'read';

	/**
	 * Group of the menu routes.
	 *
	 * @since 1.0.0
	 */
	public const GROUP_MENUS = 'menus';

	/**
	 * Template part areas editors may edit.
	 *
	 * @since 1.0.0
	 */
	public const PARTS = array( 'header', 'footer' );

	/**
	 * REST routes editors reach, with methods, group and, where needed, parameters.
	 *
	 * The list comes from the requests of the part editor (spike S-7). A row
	 * with "params" matches only when each of these parameters has exactly
	 * this value as its effective value, read from JSON, body and query in
	 * the order of WP_REST_Request::get_param() (the view configuration of
	 * WordPress 7.1 serves every list of the Site Editor from one route). A
	 * change needs a change of the test AccessRoutesTest as well.
	 *
	 * @since 1.0.0
	 */
	public const ROUTES = array(
		array(
			'pattern' => '/wp/v2/template-parts',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/template-parts/lookup',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/template-parts/{part}',
			'methods' => array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/template-parts/{part}/autosaves',
			'methods' => array( 'GET', 'POST' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/template-parts/{part}/autosaves/{n}',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/template-parts/{part}/revisions',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/template-parts/{part}/revisions/{n}',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/types/wp_template_part',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
		),
		array(
			'pattern' => '/wp/v2/view-config',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_PARTS,
			'params'  => array(
				'kind' => 'postType',
				'name' => 'wp_template_part',
			),
		),
		array(
			'pattern' => '/wp/v2/global-styles/themes/{stylesheet}',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_READ,
		),
		array(
			'pattern' => '/wp/v2/global-styles/themes/{stylesheet}/variations',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_READ,
		),
		array(
			'pattern' => '/wp/v2/global-styles/{n}',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_READ,
		),
		array(
			'pattern' => '/wp/v2/menus',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_MENUS,
		),
		array(
			'pattern' => '/wp/v2/menus/{n}',
			'methods' => array( 'GET', 'POST', 'PUT', 'PATCH' ),
			'group'   => self::GROUP_MENUS,
		),
		array(
			'pattern' => '/wp/v2/menu-items{any}',
			'methods' => array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ),
			'group'   => self::GROUP_MENUS,
		),
		array(
			'pattern' => '/wp/v2/menu-locations{any}',
			'methods' => array( 'GET' ),
			'group'   => self::GROUP_MENUS,
		),
	);

	/**
	 * Values of the query argument "p" of site-editor.php that editors may open.
	 *
	 * The pattern list (it shows the template parts with postType=wp_template_part)
	 * and the editors of header and footer.
	 *
	 * @since 1.0.0
	 */
	public const SITE_EDITOR_PATHS = array( '/pattern', '/wp_template_part/{part}' );

	/**
	 * Returns the group of a REST route for a method, null when editors may not use it.
	 *
	 * HEAD and OPTIONS count as GET. The method is compared without case. A
	 * part ID also matches as "<stylesheet>/<slug>": web servers that merge
	 * slashes in the path (nginx) send it that way, and WordPress reads it as
	 * "<stylesheet>//<slug>" (spike S-7).
	 *
	 * @since 1.0.0
	 *
	 * @param string               $route  Route of the request, such as /wp/v2/template-parts.
	 * @param string               $method HTTP method.
	 * @param array<string, mixed> $params Effective parameters of the request (WP_REST_Request::get_params()); only rows with "params" read them.
	 * @return string|null "parts", "read", "menus" or null.
	 */
	public static function match( string $route, string $method, array $params = array() ): ?string {
		$method = strtoupper( $method );
		if ( 'HEAD' === $method || 'OPTIONS' === $method ) {
			$method = 'GET';
		}
		foreach ( self::ROUTES as $row ) {
			if ( in_array( $method, $row['methods'], true ) && 1 === preg_match( self::regex( $row['pattern'], true ), $route ) && self::params_match( $row['params'] ?? array(), $params ) ) {
				return $row['group'];
			}
		}
		return null;
	}

	/**
	 * Tells whether the parameters carry every required value.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $required Required parameters and values.
	 * @param array<string, mixed>  $params   Effective parameters of the request.
	 * @return bool True when each required parameter is a string with exactly the required value.
	 */
	private static function params_match( array $required, array $params ): bool {
		foreach ( $required as $name => $value ) {
			if ( ! isset( $params[ $name ] ) || $value !== $params[ $name ] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Tells whether editors may open site-editor.php with this value of "p".
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Value of the query argument "p".
	 * @return bool True for the pattern list and the editors of header and footer.
	 */
	public static function site_editor_path_allowed( string $path ): bool {
		foreach ( self::SITE_EDITOR_PATHS as $pattern ) {
			if ( 1 === preg_match( self::regex( $pattern ), $path ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the folder name of the active theme, which owns the template parts in the database.
	 *
	 * @since 1.0.0
	 *
	 * @return string Stylesheet, the child theme when one is active.
	 */
	public static function stylesheet(): string {
		return get_stylesheet(); // creationell-allow-stylesheet: template parts in the database belong to the active theme (term wp_theme).
	}

	/**
	 * Returns the ID of a template part of the active stylesheet, such as "<stylesheet>//header".
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Part slug.
	 * @return string Part ID.
	 */
	public static function part_id( string $slug ): string {
		return self::stylesheet() . '//' . $slug;
	}

	/**
	 * Returns the URL of the template part list in the Site Editor (the same list the core lock leads to).
	 *
	 * @since 1.0.0
	 *
	 * @return string Admin URL.
	 */
	public static function part_list_url(): string {
		return admin_url( Site_Editor_Lock::PART_LIST_PATH );
	}

	/**
	 * Returns the path of the editor of a template part, relative to the admin URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Part slug.
	 * @return string Path such as site-editor.php?p=...&canvas=edit.
	 */
	public static function part_editor_path( string $slug ): string {
		return 'site-editor.php?p=' . rawurlencode( '/wp_template_part/' . self::part_id( $slug ) ) . '&canvas=edit';
	}

	/**
	 * Returns the URL of the editor of a template part.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Part slug.
	 * @return string Admin URL.
	 */
	public static function part_editor_url( string $slug ): string {
		return admin_url( self::part_editor_path( $slug ) );
	}

	/**
	 * Turns a pattern with placeholders into an anchored regular expression.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern      Literal path with placeholders.
	 * @param bool   $merged_slash Whether {part} also matches with one slash between stylesheet and slug.
	 * @return string Regular expression.
	 */
	private static function regex( string $pattern, bool $merged_slash = false ): string {
		$stylesheet = preg_quote( self::stylesheet(), '~' );
		$parts      = implode( '|', self::PARTS );
		return '~^' . strtr(
			preg_quote( $pattern, '~' ),
			array(
				'\{part\}'       => $stylesheet . ( $merged_slash ? '//?' : '//' ) . '(?:' . $parts . ')',
				'\{stylesheet\}' => $stylesheet,
				'\{n\}'          => '\d+',
				'\{any\}'        => '(?:/[^/?#][^?#]*)?',
			)
		) . '$~D';
	}
}
