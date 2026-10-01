<?php
/**
 * Lock of the user Global Styles: no effect, no editing, only empty saves.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use WP_Theme_JSON_Data;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Layers 2 and 3 of the Global Styles lock: colors and fonts come only from the theme settings.
 *
 * - Layer 2 (effect): on wp_theme_json_data_user, last, the user origin gets
 *   new, empty data. The allowlist of user values is empty, so no saved color,
 *   font, style or custom CSS reaches the front end, the post editor or the
 *   printed font faces. A new object is needed, because update_with() removes
 *   no key.
 * - Layer 3a (rights): on map_meta_cap, edit_post and delete_post on a post of
 *   the type wp_global_styles map to do_not_allow for everybody, so writes
 *   through REST fail visibly (403).
 * - Layer 3b (saving): on wp_insert_post_data, every write of such a post
 *   stores only the empty configuration, also on ways without a capability
 *   check such as WP-CLI.
 *
 * Template parts and font families (wp_font_family) stay untouched. Layer 1
 * is Theme_Json_Tokens; the site editor surface (layer 4) is locked elsewhere.
 * Values saved before stay in the database but take no effect.
 *
 * @since 1.0.0
 */
final class Global_Styles_Lock {

	/**
	 * Post type of the user Global Styles.
	 *
	 * @since 1.0.0
	 */
	public const POST_TYPE = 'wp_global_styles';

	/**
	 * Empty user configuration as theme.json data (layer 2).
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_USER_DATA = array(
		'version'                     => 3,
		'isGlobalStylesUserThemeJSON' => true,
	);

	/**
	 * Empty user configuration as stored post content (layer 3b).
	 *
	 * @since 1.0.0
	 */
	public const EMPTY_USER_JSON = '{"version":3,"isGlobalStylesUserThemeJSON":true}';

	/**
	 * Capabilities checked on a single post that layer 3a denies.
	 *
	 * @since 1.0.0
	 */
	public const DENIED_CAPS = array( 'edit_post', 'delete_post' );

	/**
	 * Hooks layers 2, 3a and 3b.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'wp_theme_json_data_user', array( self::class, 'filter_user_data' ), PHP_INT_MAX, 1 );
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_filter( 'wp_insert_post_data', array( self::class, 'filter_post_data' ), 10, 4 );
	}

	/**
	 * Returns empty user data in place of the saved Global Styles; runs on wp_theme_json_data_user (layer 2).
	 *
	 * The parameter is mixed: WordPress also accepts a compatible object
	 * that is no WP_Theme_JSON_Data (such as the one of the Gutenberg plugin).
	 * The input is ignored, so any value yields the empty data.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $theme_json User data (WP_Theme_JSON_Data or a compatible object), unused.
	 * @return WP_Theme_JSON_Data New, empty data of the user origin.
	 */
	public static function filter_user_data( mixed $theme_json ): WP_Theme_JSON_Data {
		unset( $theme_json );
		return new WP_Theme_JSON_Data( self::EMPTY_USER_DATA, 'custom' );
	}

	/**
	 * Denies editing and deleting the Global Styles post to everybody; runs on map_meta_cap (layer 3a).
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $caps    Primitive capabilities WordPress mapped so far.
	 * @param string             $cap     Capability being checked.
	 * @param int                $user_id User ID, unused.
	 * @param array<mixed>       $args    Further arguments of the check; the first is the post ID.
	 * @return array<int, string> do_not_allow for a Global Styles post, otherwise $caps.
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args = array() ): array {
		unset( $user_id );
		if ( ! in_array( $cap, self::DENIED_CAPS, true ) ) {
			return $caps;
		}
		$post_id = self::post_id( $args[0] ?? null );
		if ( null === $post_id || self::POST_TYPE !== get_post_type( $post_id ) ) {
			return $caps;
		}
		return array( 'do_not_allow' );
	}

	/**
	 * Stores only the empty configuration for a Global Styles post; runs on wp_insert_post_data (layer 3b).
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data                Slashed post data about to be saved.
	 * @param array<mixed>         $postarr             Sanitized post data, unused.
	 * @param array<mixed>         $unsanitized_postarr Unsanitized post data, unused.
	 * @param bool                 $update              Whether an existing post is updated, unused.
	 * @return array<string, mixed> Post data.
	 */
	public static function filter_post_data( array $data, array $postarr = array(), array $unsanitized_postarr = array(), bool $update = false ): array {
		unset( $postarr, $unsanitized_postarr, $update );
		if ( self::POST_TYPE === ( $data['post_type'] ?? null ) ) {
			$data['post_content'] = wp_slash( self::EMPTY_USER_JSON );
		}
		return $data;
	}

	/**
	 * Reads a post ID from the first argument of a capability check.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Post ID as integer or digit string, or a post object.
	 * @return int|null Post ID, or null when the value is none.
	 */
	private static function post_id( mixed $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			return (int) $value;
		}
		if ( $value instanceof \WP_Post ) {
			return $value->ID;
		}
		return null;
	}
}
