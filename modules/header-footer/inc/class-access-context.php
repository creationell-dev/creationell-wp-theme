<?php
/**
 * Context of the current request for the capability grant of header and footer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use WP_HTTP_Response;
use WP_REST_Request;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells in which context the request runs: "none", "site_editor", "rest_parts", "rest_read" or "menus".
 *
 * Admin pages give the context by themselves: site-editor.php with GET is
 * "site_editor"; nav-menus.php and the handlers of three AJAX actions of the
 * menu screen (while wp_ajax_<action> runs) are "menus". A REST request
 * pushes the context of its route and method (see Access_Routes) on a stack
 * before the callbacks and pops it afterwards; a route outside the list
 * pushes "none", also while site-editor.php preloads it, and so does a
 * request whose JSON, body or query parameters override a URL parameter of
 * its route (see for_rest_request()). Each sub-request of a batch runs
 * through the same hooks. A second
 * window from rest_post_dispatch 9 to 11 covers rest_send_allow_header (10),
 * so the Allow header shows the methods of the grant; for users without the
 * native capability it then keeps only the methods of the allow list.
 *
 * @since 1.0.0
 */
final class Access_Context {

	/**
	 * No grant.
	 *
	 * @since 1.0.0
	 */
	public const NONE = 'none';

	/**
	 * The Site Editor (site-editor.php), loaded with GET.
	 *
	 * @since 1.0.0
	 */
	public const SITE_EDITOR = 'site_editor';

	/**
	 * REST route of the group "parts".
	 *
	 * @since 1.0.0
	 */
	public const REST_PARTS = 'rest_parts';

	/**
	 * REST route of the group "read".
	 *
	 * @since 1.0.0
	 */
	public const REST_READ = 'rest_read';

	/**
	 * Menu screen, its AJAX actions or a REST route of the group "menus".
	 *
	 * @since 1.0.0
	 */
	public const MENUS = 'menus';

	/**
	 * AJAX actions of the menu screen.
	 *
	 * @since 1.0.0
	 */
	public const MENU_AJAX_ACTIONS = array( 'add-menu-item', 'menu-get-metabox', 'menu-quick-search' );

	/**
	 * Context of each REST group.
	 */
	private const GROUP_CONTEXTS = array(
		Access_Routes::GROUP_PARTS => self::REST_PARTS,
		Access_Routes::GROUP_READ  => self::REST_READ,
		Access_Routes::GROUP_MENUS => self::MENUS,
	);

	/**
	 * Contexts of the running REST requests, innermost last.
	 *
	 * @var list<string>
	 */
	private static array $stack = array();

	/**
	 * Registers the REST hooks; called when the module boots.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'rest_request_before_callbacks', array( self::class, 'before_callbacks' ), PHP_INT_MIN, 3 );
		add_filter( 'rest_request_after_callbacks', array( self::class, 'after_callbacks' ), PHP_INT_MAX, 3 );
		add_filter( 'rest_post_dispatch', array( self::class, 'open_allow_window' ), 9, 3 );
		add_filter( 'rest_post_dispatch', array( self::class, 'close_allow_window' ), 11, 3 );
	}

	/**
	 * Returns the context of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return string One of the context constants.
	 */
	public static function current(): string {
		if ( array() !== self::$stack ) {
			return self::$stack[ array_key_last( self::$stack ) ];
		}
		if ( ! is_admin() ) {
			return self::NONE;
		}
		$pagenow = $GLOBALS['pagenow'] ?? '';
		if ( 'site-editor.php' === $pagenow ) {
			return 'GET' === self::request_method() ? self::SITE_EDITOR : self::NONE;
		}
		if ( 'nav-menus.php' === $pagenow ) {
			return self::MENUS;
		}
		if ( 'admin-ajax.php' === $pagenow ) {
			foreach ( self::MENU_AJAX_ACTIONS as $action ) {
				if ( doing_action( 'wp_ajax_' . $action ) ) {
					return self::MENUS;
				}
			}
		}
		return self::NONE;
	}

	/**
	 * Returns the context of a REST route for a method.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $route  Route of the request.
	 * @param string               $method HTTP method.
	 * @param array<string, mixed> $params Effective parameters of the request (WP_REST_Request::get_params()).
	 * @return string "rest_parts", "rest_read", "menus" or "none".
	 */
	public static function for_request( string $route, string $method, array $params = array() ): string {
		$group = Access_Routes::match( $route, $method, $params );
		return null === $group ? self::NONE : self::GROUP_CONTEXTS[ $group ];
	}

	/**
	 * Returns the context of a REST request from the parameters its controller reads.
	 *
	 * WP_REST_Request::get_param() reads JSON, body and query before the URL
	 * parameters of the route, and the templates controller works with
	 * $request['id'] (or 'parent'). A request whose effective value of a URL
	 * parameter differs from the one in the route, such as
	 * PUT <stylesheet>//footer?id=<stylesheet>//extra, would edit another part
	 * under the grant of the footer route, so it gets "none" (FS-5). Rows with
	 * "params" (view-config) compare the effective values as well.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request, matched to its route (URL parameters set).
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return string "rest_parts", "rest_read", "menus" or "none".
	 */
	public static function for_rest_request( WP_REST_Request $request ): string {
		$params = $request->get_params();
		foreach ( $request->get_url_params() as $name => $value ) {
			if ( ! array_key_exists( $name, $params ) || $value !== $params[ $name ] ) {
				return self::NONE;
			}
		}
		return self::for_request( $request->get_route(), $request->get_method(), $params );
	}

	/**
	 * Puts a context on the stack.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context.
	 * @return void
	 */
	public static function push( string $context ): void {
		self::$stack[] = $context;
	}

	/**
	 * Takes the innermost context from the stack.
	 *
	 * @since 1.0.0
	 *
	 * @return string The context taken, "none" when the stack was empty.
	 */
	public static function pop(): string {
		return array_pop( self::$stack ) ?? self::NONE;
	}

	/**
	 * Returns the number of contexts on the stack.
	 *
	 * @since 1.0.0
	 *
	 * @return int Depth.
	 */
	public static function depth(): int {
		return count( self::$stack );
	}

	/**
	 * Empties the stack.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$stack = array();
	}

	/**
	 * Pushes the context of the request; runs on rest_request_before_callbacks first.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $response Response so far, passed on unchanged.
	 * @param mixed           $handler  Route handler, unused.
	 * @param WP_REST_Request $request  Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public static function before_callbacks( mixed $response, mixed $handler, WP_REST_Request $request ): mixed {
		unset( $handler );
		self::push( self::for_rest_request( $request ) );
		return $response;
	}

	/**
	 * Pops the context of the request; runs on rest_request_after_callbacks last, also after errors.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $response Response, passed on unchanged.
	 * @param mixed           $handler  Route handler, unused.
	 * @param WP_REST_Request $request  Request, unused.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public static function after_callbacks( mixed $response, mixed $handler, WP_REST_Request $request ): mixed {
		unset( $handler, $request );
		self::pop();
		return $response;
	}

	/**
	 * Pushes the context of the request for rest_send_allow_header; runs on rest_post_dispatch with priority 9.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $response Response, passed on unchanged.
	 * @param mixed           $server   REST server, unused.
	 * @param WP_REST_Request $request  Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public static function open_allow_window( mixed $response, mixed $server, WP_REST_Request $request ): mixed {
		unset( $server );
		self::push( self::for_rest_request( $request ) );
		return $response;
	}

	/**
	 * Pops the context again and trims the Allow header to the allow list; runs on rest_post_dispatch with priority 11.
	 *
	 * Only for a route of the list and a user without the native capability:
	 * rest_send_allow_header ran with the grant, which covers every method of
	 * the route, so methods outside the list (creating parts, writing global
	 * styles) are removed.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $response Response.
	 * @param mixed           $server   REST server, unused.
	 * @param WP_REST_Request $request  Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The response.
	 */
	public static function close_allow_window( mixed $response, mixed $server, WP_REST_Request $request ): mixed {
		unset( $server );
		if ( self::NONE === self::pop() || ! $response instanceof WP_HTTP_Response ) {
			return $response;
		}
		$headers = $response->get_headers();
		if ( ! isset( $headers['Allow'] ) || ! is_string( $headers['Allow'] ) || Template_Part_Access::has_native_cap( get_current_user_id() ) ) {
			return $response;
		}
		$route   = $request->get_route();
		$params  = $request->get_params();
		$methods = array_filter(
			array_map( 'trim', explode( ',', $headers['Allow'] ) ),
			static fn( string $method ): bool => null !== Access_Routes::match( $route, $method, $params )
		);
		if ( array() === $methods ) {
			unset( $headers['Allow'] );
			$response->set_headers( $headers );
			return $response;
		}
		$response->header( 'Allow', implode( ', ', $methods ) );
		return $response;
	}

	/**
	 * Returns the HTTP method of the request in upper case.
	 *
	 * @since 1.0.0
	 *
	 * @return string Method, empty when unknown.
	 */
	private static function request_method(): string {
		$method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		return strtoupper( $method );
	}
}
