<?php
/**
 * Limits the classic menus editors may edit to the menus of header and footer.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Core\Capabilities;
use Creationell\WpTheme\Settings\Language;
use Creationell\WpTheme\Wpml\Wpml_Integration;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Keeps editors with the menu capability on the menus of the header and footer locations.
 *
 * A child theme that unlocks the menus (creationell_wp_theme_capabilities,
 * unlock[menus]) lets editors into the menu screen and the menu routes of the
 * REST API, where the grant of Template_Part_Access answers
 * edit_theme_options. WordPress checks nothing per menu there, so this class
 * limits the grant to the scope: the menus assigned to the locations
 * main-menu and footer-menu and, with WPML, their translations. It applies
 * to users without the native capability edit_theme_options only.
 *
 * The menu screen leads editors from every other menu, from the new menu
 * screen and from the locations tab to the first menu of the scope; posted
 * forms outside the scope end with 403. Menus outside the scope and their
 * items are closed through map_meta_cap for the REST API, menus are never
 * deleted by editors, and the menu locations stay as they are. Creating menus
 * is not on the REST allow list (Access_Routes).
 *
 * @since 1.0.0
 */
final class Menu_Scope {

	/**
	 * Menu locations whose menus editors may edit.
	 *
	 * @since 1.0.0
	 */
	public const LOCATIONS = array( 'main-menu', 'footer-menu' );

	/**
	 * Actions of the menu screen editors may never run: delete a menu, delete menus in bulk, the locations tab.
	 *
	 * @since 1.0.0
	 */
	public const DENIED_ACTIONS = array( 'delete', 'delete_menus', 'locations' );

	/**
	 * Registers the hooks; called when the module boots.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'load-nav-menus.php', array( self::class, 'guard_screen' ), 1, 0 );
		add_action( 'wp_ajax_add-menu-item', array( self::class, 'guard_ajax' ), 0, 0 );
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_filter( 'wp_get_nav_menus', array( self::class, 'screen_menus' ), 10, 2 );
		add_filter( 'pre_set_theme_mod_nav_menu_locations', array( self::class, 'keep_locations' ), 10, 2 );
	}

	/**
	 * Returns the IDs of the menus editors may edit.
	 *
	 * The menus of the locations main-menu and footer-menu, in this order,
	 * then with WPML their translations in every active language. Each ID
	 * appears once.
	 *
	 * @since 1.0.0
	 *
	 * @return list<int> Menu IDs (terms of the taxonomy nav_menu).
	 */
	public static function in_scope(): array {
		$locations = get_nav_menu_locations();
		$ids       = array();
		foreach ( self::LOCATIONS as $location ) {
			$id = $locations[ $location ] ?? 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		$ids = array_values( array_unique( $ids ) );
		if ( array() === $ids || ! Wpml_Integration::is_active() ) {
			return $ids;
		}

		$scope = $ids;
		foreach ( Language::instance()->active() as $lang ) {
			foreach ( $ids as $id ) {
				$translated = apply_filters( 'wpml_object_id', $id, 'nav_menu', false, $lang );
				if ( is_numeric( $translated ) && (int) $translated > 0 ) {
					$scope[] = (int) $translated;
				}
			}
		}
		return array_values( array_unique( $scope ) );
	}

	/**
	 * Plain names of request fields: the only names PHP, esc_url() with wp_parse_str() and the JSON data of the menu screen read alike.
	 *
	 * @since 1.0.0
	 */
	private const PLAIN_NAME = '~^[A-Za-z0-9_-]+\z~';

	/**
	 * Checks a request of the menu screen; runs on load-nav-menus.php with priority 1.
	 *
	 * Reads the query and the posted fields as PHP parsed them, the data
	 * $_GET, $_POST and $_REQUEST of the menu screen come from, before the
	 * menu screen reads them.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function guard_screen(): void {
		if ( ! self::applies() ) {
			return;
		}
		self::check_request( self::request_method(), self::query_args(), self::post_fields() );
	}

	/**
	 * Checks a request of the menu screen for an editor with the menu capability.
	 *
	 * The request passes when every field name is a plain name, every field
	 * "action" of the query, the body and the JSON field "nav-menu-data" is
	 * none of DENIED_ACTIONS, there is a field "menu" and each one is a menu of
	 * the scope, and every existing menu item the request names belongs to no
	 * menu or to menus of the scope. The screen reads the body before the query
	 * ($_REQUEST) and some parts of the query alone ($_GET), so every source
	 * counts. Otherwise POST ends with 403 and every other method (GET) leads
	 * to the first menu of the scope; without a menu in the scope GET shows a
	 * notice instead.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $method HTTP method in upper case.
	 * @param array<string, mixed> $query  Query arguments.
	 * @param array<string, mixed> $post   Posted fields.
	 * @return void
	 */
	public static function check_request( string $method, array $query, array $post ): void {
		$scope = self::in_scope();
		if ( self::request_allowed( $query, $post, $scope ) ) {
			return;
		}
		if ( 'POST' === $method ) {
			self::deny();
		}
		if ( array() === $scope ) {
			wp_die(
				esc_html__( 'No header or footer menu is assigned yet.', 'creationell-wp-theme' ),
				esc_html__( 'Menus', 'creationell-wp-theme' ),
				array(
					'response'  => 200,
					'back_link' => true,
				)
			);
		}
		wp_safe_redirect( admin_url( 'nav-menus.php?menu=' . $scope[0] ) );
		exit;
	}

	/**
	 * Checks the AJAX action that adds menu items; runs on wp_ajax_add-menu-item with priority 0.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function guard_ajax(): void {
		if ( ! self::applies() ) {
			return;
		}
		self::check_ajax_request( self::post_fields() );
	}

	/**
	 * Ends the AJAX action that adds menu items with 403 when it names an item of a menu outside the scope.
	 *
	 * WordPress saves the posted items as drafts without a menu; an entry with
	 * the ID of an existing item would change that item instead.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $post Posted fields.
	 * @return void
	 */
	public static function check_ajax_request( array $post ): void {
		if ( Template_Part_Access::has_native_cap( get_current_user_id() ) ) {
			return;
		}
		if ( ! self::plain_names( $post ) ) {
			self::deny();
		}
		$scope = self::in_scope();
		foreach ( self::named_items( array( $post ) ) as $item_id ) {
			if ( ! self::item_in_scope( $item_id, $scope ) ) {
				self::deny();
			}
		}
	}

	/**
	 * Closes menus outside the scope and their items to editors; runs on map_meta_cap.
	 *
	 * The capabilities edit_term and assign_term on a menu outside the scope,
	 * delete_term on any menu, and edit_post and delete_post on a menu item that
	 * belongs to a menu outside the scope give do_not_allow. Items without a menu (drafts of
	 * the menu screen) stay open. Users with the native capability
	 * edit_theme_options pass unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $caps    Primitive capabilities WordPress mapped so far.
	 * @param string             $cap     Capability being checked.
	 * @param int                $user_id User ID.
	 * @param array<mixed>       $args    Further arguments; the first is the term or post.
	 * @return array<int, string> Primitive capabilities, do_not_allow outside the scope.
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args = array() ): array {
		$id = self::object_id( $args[0] ?? 0 );
		if ( $id <= 0 ) {
			return $caps;
		}
		if ( in_array( $cap, array( 'edit_term', 'delete_term', 'assign_term' ), true ) ) {
			if ( ! is_nav_menu( $id ) || ! self::limited( $user_id ) ) {
				return $caps;
			}
			return 'delete_term' !== $cap && in_array( $id, self::in_scope(), true ) ? $caps : array( 'do_not_allow' );
		}
		if ( 'edit_post' === $cap || 'delete_post' === $cap ) {
			if ( ! is_nav_menu_item( $id ) || ! self::limited( $user_id ) ) {
				return $caps;
			}
			return self::item_in_scope( $id, self::in_scope() ) ? $caps : array( 'do_not_allow' );
		}
		return $caps;
	}

	/**
	 * Lists only the menus of the scope on the menu screen for editors; runs on wp_get_nav_menus.
	 *
	 * The menu screen offers these menus in its menu list and opens the first
	 * one when no menu is chosen.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $menus Menu objects.
	 * @param mixed $args  Arguments of wp_get_nav_menus(), unused.
	 * @return mixed Menu objects of the scope on the menu screen, else unchanged.
	 */
	public static function screen_menus( mixed $menus, mixed $args ): mixed {
		unset( $args );
		if ( ! is_array( $menus ) || ! is_admin() || 'nav-menus.php' !== ( $GLOBALS['pagenow'] ?? null ) || ! self::limited( get_current_user_id() ) ) {
			return $menus;
		}
		$scope = self::in_scope();
		return array_values(
			array_filter(
				$menus,
				static fn( mixed $menu ): bool => is_object( $menu ) && isset( $menu->term_id ) && is_numeric( $menu->term_id ) && in_array( (int) $menu->term_id, $scope, true )
			)
		);
	}

	/**
	 * Keeps the stored menu locations when an editor saves in the menu context; runs on pre_set_theme_mod_nav_menu_locations.
	 *
	 * The menu screen and the menu routes of the REST API save the locations
	 * together with a menu; editors would move a menu out of the scope or
	 * another menu into it.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value     New locations.
	 * @param mixed $old_value Stored locations.
	 * @return mixed The stored locations for editors in the menu context, else the new ones.
	 */
	public static function keep_locations( mixed $value, mixed $old_value ): mixed {
		if ( Access_Context::MENUS !== Access_Context::current() || ! self::limited( get_current_user_id() ) ) {
			return $value;
		}
		return $old_value;
	}

	/**
	 * Tells whether the rules apply to the current user: the rights of the menu context, no native capability.
	 *
	 * Without the template part right or the menu right the grant stays out
	 * and WordPress itself closes the menu screen.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True for editors with the template part and the menu capability.
	 */
	private static function applies(): bool {
		return self::limited( get_current_user_id() ) && Template_Part_Access::can_edit_menus();
	}

	/**
	 * Tells whether a user is limited to the scope: logged in and without the native capability edit_theme_options.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool True for users other than administrators.
	 */
	private static function limited( int $user_id ): bool {
		return $user_id > 0 && ! Template_Part_Access::has_native_cap( $user_id );
	}

	/**
	 * Tells whether a request of the menu screen stays in the scope.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query Query arguments.
	 * @param array<string, mixed> $post  Posted fields.
	 * @param array<int, int>      $scope Menu IDs of the scope.
	 * @return bool True when names, actions, menus and named items are allowed.
	 */
	private static function request_allowed( array $query, array $post, array $scope ): bool {
		$expanded = self::expand_nav_menu_data( $post );
		if ( null === $expanded || ! self::plain_names( $query ) || ! self::plain_names( $post ) ) {
			return false;
		}
		$sources = array( $query, $post, $expanded );
		$menus   = 0;
		foreach ( $sources as $source ) {
			if ( array_key_exists( 'action', $source ) && ( ! is_string( $source['action'] ) || in_array( $source['action'], self::DENIED_ACTIONS, true ) ) ) {
				return false;
			}
			if ( array_key_exists( 'menu', $source ) ) {
				if ( ! is_numeric( $source['menu'] ) || ! in_array( (int) $source['menu'], $scope, true ) ) {
					return false;
				}
				++$menus;
			}
		}
		if ( 0 === $menus ) {
			return false;
		}
		foreach ( self::named_items( $sources ) as $item_id ) {
			if ( ! self::item_in_scope( $item_id, $scope ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns the IDs of the menu items a request names, read the way WordPress reads them.
	 *
	 * The field "menu-item" as a single ID (move and delete actions: (int) of
	 * the value, 1 for a list) or as a list of item data (adding items: every
	 * key and every "menu-item-db-id"), and the keys and values of
	 * "menu-item-db-id" (saving a menu). Every value counts as WordPress casts
	 * it with (int), so "23x" names item 23.
	 *
	 * @since 1.0.0
	 *
	 * @param list<array<mixed>> $sources Fields of the query, the body and the expanded body.
	 * @return list<int> Positive IDs, each once.
	 */
	private static function named_items( array $sources ): array {
		$ids = array();
		foreach ( $sources as $source ) {
			if ( array_key_exists( 'menu-item', $source ) ) {
				$item  = $source['menu-item'];
				$ids[] = self::int_value( $item );
				foreach ( is_array( $item ) ? $item : array() as $key => $data ) {
					$ids[] = self::int_value( $key );
					if ( is_array( $data ) && array_key_exists( 'menu-item-db-id', $data ) ) {
						$ids[] = self::int_value( $data['menu-item-db-id'] );
					}
				}
			}
			if ( array_key_exists( 'menu-item-db-id', $source ) ) {
				$db_ids = $source['menu-item-db-id'];
				foreach ( is_array( $db_ids ) ? $db_ids : array() as $key => $value ) {
					$ids[] = self::int_value( $key );
					$ids[] = self::int_value( $value );
				}
			}
		}
		return array_values( array_unique( array_filter( $ids, static fn( int $id ): bool => $id > 0 ) ) );
	}

	/**
	 * Returns the posted fields with the JSON field "nav-menu-data" expanded like _wp_expand_nav_menu_post_data().
	 *
	 * The menu screen sends its form as a JSON list of name and value and
	 * expands it into $_POST after load-nav-menus.php. The names are split
	 * with the same pattern as WordPress, but only names in the form the menu
	 * screen writes them ("name" or "name[key]…" with plain name and keys
	 * without brackets) are taken; any other entry makes the request fail.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $post Posted fields.
	 * @return array<mixed>|null Posted fields with the expanded data, null for an entry that cannot be mapped.
	 */
	private static function expand_nav_menu_data( array $post ): ?array {
		$json = $post['nav-menu-data'] ?? null;
		$data = is_string( $json ) ? json_decode( $json, true ) : null;
		if ( ! is_array( $data ) ) {
			return $post;
		}
		$expanded = $post;
		foreach ( $data as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['name'] ) || ! is_string( $field['name'] ) || ! array_key_exists( 'value', $field ) || ! is_scalar( $field['value'] ) ) {
				return null;
			}
			$path = self::field_path( $field['name'] );
			if ( null === $path ) {
				return null;
			}
			$last   = count( $path ) - 1;
			$branch = array( $path[ $last ] => $field['value'] );
			for ( $i = $last - 1; $i >= 0; $i-- ) {
				$branch = array( $path[ $i ] => $branch );
			}
			$expanded = array_replace_recursive( $expanded, $branch );
		}
		return $expanded;
	}

	/**
	 * Splits a field name of the JSON data into its keys, like _wp_expand_nav_menu_post_data().
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Field name such as menu-item-db-id[21].
	 * @return non-empty-list<string>|null Keys, null when the name is not in the form the menu screen writes.
	 */
	private static function field_path( string $name ): ?array {
		preg_match( '#([^\[]*)(\[(.+)\])?#', $name, $matches );
		$path = array( $matches[1] ?? '' );
		if ( isset( $matches[3] ) ) {
			$path = array_merge( $path, explode( '][', $matches[3] ) );
		}
		$keys = array_slice( $path, 1 );
		if ( 1 !== preg_match( self::PLAIN_NAME, $path[0] ) || $name !== $path[0] . ( array() === $keys ? '' : '[' . implode( '][', $keys ) . ']' ) ) {
			return null;
		}
		foreach ( $keys as $key ) {
			if ( false !== strpbrk( $key, '[]' ) ) {
				return null;
			}
		}
		return $path;
	}

	/**
	 * Tells whether every field name is a plain name.
	 *
	 * Names with other characters, such as a line break that esc_url()
	 * removes, are where the parsers of PHP and WordPress disagree; a request
	 * with one fails.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $fields Fields by name.
	 * @return bool True when every name is a plain name.
	 */
	private static function plain_names( array $fields ): bool {
		foreach ( array_keys( $fields ) as $name ) {
			if ( is_string( $name ) && 1 !== preg_match( self::PLAIN_NAME, $name ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns a field value cast with (int), as WordPress reads IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value or key of a field.
	 * @return int (int) of the value: 0 or 1 for a list, 0 for other types.
	 */
	private static function int_value( mixed $value ): int {
		if ( is_array( $value ) ) {
			return array() === $value ? 0 : 1;
		}
		return is_scalar( $value ) ? (int) $value : 0;
	}

	/**
	 * Tells whether a menu item belongs to no menu or only to menus of the scope.
	 *
	 * @since 1.0.0
	 *
	 * @param int             $item_id Post ID.
	 * @param array<int, int> $scope   Menu IDs of the scope.
	 * @return bool True for items of the scope, drafts without a menu and posts that are no menu item.
	 */
	private static function item_in_scope( int $item_id, array $scope ): bool {
		if ( ! is_nav_menu_item( $item_id ) ) {
			return true;
		}
		$menus = wp_get_object_terms( $item_id, 'nav_menu', array( 'fields' => 'ids' ) );
		if ( ! is_array( $menus ) ) {
			return false;
		}
		foreach ( $menus as $menu_id ) {
			if ( ! is_numeric( $menu_id ) || ! in_array( (int) $menu_id, $scope, true ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Ends the request with 403.
	 *
	 * @since 1.0.0
	 *
	 * @return never
	 */
	private static function deny(): never {
		wp_die(
			esc_html__( 'You may only edit the menus of the header and footer.', 'creationell-wp-theme' ),
			esc_html__( 'Menus', 'creationell-wp-theme' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Returns the ID of a capability argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Term or post ID, or an object with the property term_id or ID.
	 * @return int ID, 0 when unknown.
	 */
	private static function object_id( mixed $value ): int {
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			return (int) $value;
		}
		if ( is_object( $value ) ) {
			$id = $value->term_id ?? $value->ID ?? null;
			return is_numeric( $id ) ? (int) $id : 0;
		}
		return 0;
	}

	/**
	 * Returns the query arguments as PHP parsed them, the source of $_GET.
	 *
	 * Not read from the request URI: esc_url_raw() removes %0a and %0d and
	 * so reads a name such as me%0anu as "menu", while PHP keeps the line
	 * break. The check only compares IDs and action names, so the values are
	 * not sanitized; the menu screen checks its nonce afterwards.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Query arguments, empty without a query.
	 */
	private static function query_args(): array {
		$args = filter_input_array( INPUT_GET );
		return is_array( $args ) ? self::string_keys( $args ) : array();
	}

	/**
	 * Returns the posted fields as PHP parsed them, the source of $_POST.
	 *
	 * The check only compares IDs and action names, so the fields are not
	 * sanitized; the menu screen checks its nonce afterwards.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, mixed> Posted fields, empty for GET.
	 */
	private static function post_fields(): array {
		$fields = filter_input_array( INPUT_POST );
		return is_array( $fields ) ? self::string_keys( $fields ) : array();
	}

	/**
	 * Keeps the entries with string keys.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $values Values.
	 * @return array<string, mixed> Values by name.
	 */
	private static function string_keys( array $values ): array {
		return array_filter( $values, 'is_string', ARRAY_FILTER_USE_KEY );
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
