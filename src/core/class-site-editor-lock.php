<?php
/**
 * Lock layer 4 of the Site Editor and the rule for custom CSS.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

use stdClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps Global Styles and the font library of WordPress out of reach, for every user.
 *
 * Colors and fonts of the theme come from its own settings, so the Global
 * Styles of the Site Editor and the font library would only have effects
 * nobody maintains. The lock is part of the theme core and cannot be switched
 * off: site-editor.php with a path below /styles and font-library.php redirect
 * to the design page of the theme (with the design capability) or to the
 * template part list; the menu entry "Fonts" goes; the block editors get no
 * font library; REST requests that write font families need the design
 * capability; the REST answer with the saved user Global Styles, which the
 * Site Editor turns into the styles of its canvas, carries empty styles and
 * settings. Custom CSS (edit_css) needs manage_options on top of the rules of
 * WordPress. The layers 1 to 3 (theme.json values, user data, global styles
 * posts) belong to the settings of the theme.
 *
 * @since 1.0.0
 */
final class Site_Editor_Lock {

	/**
	 * Path of the template part list in the Site Editor, relative to the admin URL.
	 *
	 * @since 1.0.0
	 */
	public const PART_LIST_PATH = 'site-editor.php?p=%2Fpattern&postType=wp_template_part&categoryId=all-parts';

	/**
	 * Slug of the design page of the theme settings.
	 *
	 * @since 1.0.0
	 */
	public const DESIGN_PAGE = 'creationell-wp-theme-design';

	/**
	 * Registers the hooks; called by Theme::boot().
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'load-site-editor.php', array( self::class, 'guard_styles' ), 0, 0 );
		add_action( 'load-font-library.php', array( self::class, 'redirect_font_library' ), 0, 0 );
		add_action( 'admin_menu', array( self::class, 'remove_fonts_menu' ), 10, 0 );
		add_filter( 'block_editor_settings_all', array( self::class, 'editor_settings' ), 10, 1 );
		add_filter( 'rest_pre_dispatch', array( self::class, 'guard_font_families' ), 10, 3 );
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 2 );
		add_filter( 'rest_post_dispatch', array( self::class, 'empty_user_styles' ), 10, 3 );
	}

	/**
	 * Redirects site-editor.php away from Global Styles; runs on load-site-editor.php with priority 0.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function guard_styles(): void {
		$path = self::site_editor_path();
		if ( '/styles' !== $path && ! str_starts_with( $path, '/styles/' ) ) {
			return;
		}
		wp_safe_redirect( self::redirect_target() );
		exit;
	}

	/**
	 * Returns the query argument "p" of the current request, the path inside the Site Editor.
	 *
	 * Read from the request URI, so no form data is processed; the value is
	 * only compared, never printed or stored.
	 *
	 * @since 1.0.0
	 *
	 * @return string Path such as /styles, empty without one.
	 */
	public static function site_editor_path(): string {
		$uri   = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$query = wp_parse_url( $uri, PHP_URL_QUERY );
		if ( ! is_string( $query ) ) {
			return '';
		}
		wp_parse_str( $query, $args );
		return isset( $args['p'] ) && is_string( $args['p'] ) ? $args['p'] : '';
	}

	/**
	 * Redirects the font library page; runs on load-font-library.php with priority 0.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function redirect_font_library(): void {
		wp_safe_redirect( self::redirect_target() );
		exit;
	}

	/**
	 * Removes the menu entry "Fonts" below Appearance; runs on admin_menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function remove_fonts_menu(): void {
		remove_submenu_page( 'themes.php', 'font-library.php' );
	}

	/**
	 * Switches the font library off in every block editor; runs on block_editor_settings_all.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $settings Editor settings.
	 * @return array<string, mixed> Editor settings with fontLibraryEnabled false.
	 */
	public static function editor_settings( array $settings ): array {
		$settings['fontLibraryEnabled'] = false;
		return $settings;
	}

	/**
	 * Stops REST requests that write font families without the design capability; runs on rest_pre_dispatch.
	 *
	 * The route is compared without case, like WP_REST_Server matches routes
	 * (a pattern with the modifier i), so /wp/v2/FONT-FAMILIES is stopped too.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $result  Result of earlier callbacks, null to dispatch.
	 * @param mixed           $server  REST server, unused.
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed The result, or a WP_Error with status 403.
	 */
	public static function guard_font_families( mixed $result, mixed $server, WP_REST_Request $request ): mixed {
		unset( $server );
		if ( null !== $result || 'GET' === $request->get_method() || 'HEAD' === $request->get_method() || 'OPTIONS' === $request->get_method() ) {
			return $result;
		}
		if ( 1 !== preg_match( '~^/wp/v2/font-families(?:/.*)?$~iD', $request->get_route() ) || current_user_can( Capabilities::MANAGE_DESIGN ) ) {
			return $result;
		}
		return new WP_Error(
			'creationell_wp_theme_fonts_locked',
			__( 'Fonts are managed in the design settings of the theme.', 'creationell-wp-theme' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Empties styles and settings in the REST answer with the saved user Global Styles; runs on rest_post_dispatch.
	 *
	 * The Site Editor reads the user Global Styles through GET
	 * /wp/v2/global-styles/<id> and builds the styles of its canvas from them
	 * in the browser, past the filter wp_theme_json_data_user. Saved values
	 * would show there although they take no effect on the site. The answer
	 * keeps its other fields; styles and settings become empty objects. Other
	 * routes, methods and values pass unchanged. The route is compared without
	 * case, like WP_REST_Server matches routes.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $response Result of the dispatch, usually a WP_REST_Response.
	 * @param mixed $server   REST server, unused.
	 * @param mixed $request  Request.
	 * @return mixed The response, with empty styles and settings for the user Global Styles.
	 */
	public static function empty_user_styles( mixed $response, mixed $server, mixed $request ): mixed {
		unset( $server );
		if ( ! $response instanceof WP_REST_Response || ! $request instanceof WP_REST_Request || 'GET' !== $request->get_method() ) {
			return $response;
		}
		if ( 1 !== preg_match( '~^/wp/v2/global-styles/\d+$~iD', $request->get_route() ) ) {
			return $response;
		}
		$data = $response->get_data();
		if ( ! is_array( $data ) ) {
			return $response;
		}
		foreach ( array( 'settings', 'styles' ) as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$data[ $key ] = new stdClass();
			}
		}
		$response->set_data( $data );
		return $response;
	}

	/**
	 * Adds manage_options to the capabilities that edit_css needs; runs on map_meta_cap.
	 *
	 * The rules of WordPress (unfiltered_html, multisite) stay.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $caps Primitive capabilities WordPress mapped so far.
	 * @param string             $cap  Capability being checked.
	 * @return array<int, string> Primitive capabilities.
	 */
	public static function map_meta_cap( array $caps, string $cap ): array {
		if ( 'edit_css' === $cap && ! in_array( 'manage_options', $caps, true ) ) {
			$caps[] = 'manage_options';
		}
		return $caps;
	}

	/**
	 * Returns where the locked screens lead: the design page with the design capability, else the template part list.
	 *
	 * @since 1.0.0
	 *
	 * @return string Admin URL.
	 */
	private static function redirect_target(): string {
		if ( current_user_can( Capabilities::MANAGE_DESIGN ) ) {
			$design = menu_page_url( self::DESIGN_PAGE, false );
			if ( '' !== $design ) {
				return $design;
			}
		}
		return admin_url( self::PART_LIST_PATH );
	}
}
