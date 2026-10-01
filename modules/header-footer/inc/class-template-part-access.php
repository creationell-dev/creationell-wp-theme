<?php
/**
 * Grant of edit_theme_options in context and limits on template part posts.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\HeaderFooter;

use Creationell\WpTheme\Core\Capabilities;
use WP_User;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lets editors edit header and footer without the role capability edit_theme_options.
 *
 * WordPress asks for edit_theme_options in the Site Editor, in the REST
 * routes of template parts and on the menu screen. The grant answers that
 * question with yes only while Access_Context names a context and only when
 * the user holds all primitive capabilities of the theme capabilities for the
 * context: creationell_wp_theme_edit_template_parts, in the menu context
 * together with creationell_wp_theme_edit_menus. A child theme that revokes
 * the template parts therefore closes everything, the menus included. No role
 * changes, nothing is stored.
 *
 * Users without manage_options may edit and delete only the template part
 * posts header and footer of the active stylesheet.
 *
 * @since 1.0.0
 */
final class Template_Part_Access {

	/**
	 * Capability the grant adds.
	 *
	 * @since 1.0.0
	 */
	public const GRANTED_CAP = 'edit_theme_options';

	/**
	 * Whether the grant is computing the theme capability right now (guard against recursion).
	 *
	 * @var bool
	 */
	private static bool $running = false;

	/**
	 * Whether has_native_cap() is asking right now, so the grant stays out.
	 *
	 * @var bool
	 */
	private static bool $native = false;

	/**
	 * Registers the filters; called when the module boots.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'user_has_cap', array( self::class, 'user_has_cap' ), 10, 4 );
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Adds edit_theme_options in a context when the user holds the theme capability; runs on user_has_cap.
	 *
	 * Acts only when edit_theme_options is asked for and missing. The primitive
	 * capabilities of every theme capability of the context (context_caps())
	 * come from map_meta_cap() and are checked in $allcaps directly, never
	 * with user_can(), so no capability check starts inside.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, bool> $allcaps Primitive capabilities of the user.
	 * @param array<int, string>  $caps    Primitive capabilities the check needs.
	 * @param array<int, mixed>   $args    Capability asked for, user ID, further arguments.
	 * @param WP_User             $user    User.
	 * @return array<string, bool> Primitive capabilities, with edit_theme_options when granted.
	 */
	public static function user_has_cap( array $allcaps, array $caps, array $args, WP_User $user ): array {
		unset( $args );
		if ( self::$running || self::$native || ! in_array( self::GRANTED_CAP, $caps, true ) || ! empty( $allcaps[ self::GRANTED_CAP ] ) ) {
			return $allcaps;
		}
		$context = Access_Context::current();
		if ( Access_Context::NONE === $context ) {
			return $allcaps;
		}

		$required      = array();
		self::$running = true;
		try {
			foreach ( self::context_caps( $context ) as $theme_cap ) {
				$required = array_merge( $required, map_meta_cap( $theme_cap, $user->ID ) );
			}
		} finally {
			self::$running = false;
		}
		foreach ( $required as $primitive ) {
			if ( empty( $allcaps[ $primitive ] ) ) {
				return $allcaps;
			}
		}
		$allcaps[ self::GRANTED_CAP ] = true;
		return $allcaps;
	}

	/**
	 * Returns the theme capabilities a context needs: the template part capability, in the menu context also the menu capability.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context of Access_Context.
	 * @return list<string> Theme capabilities, all of them required.
	 */
	public static function context_caps( string $context ): array {
		if ( Access_Context::MENUS === $context ) {
			return array( Capabilities::EDIT_TEMPLATE_PARTS, Capabilities::EDIT_MENUS );
		}
		return array( Capabilities::EDIT_TEMPLATE_PARTS );
	}

	/**
	 * Tells whether the current user holds the theme capabilities of the menu context.
	 *
	 * The menu screen, its links and Menu_Scope follow this answer, so a
	 * child theme that revokes the template parts leaves no way to the menus.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True with the template part and the menu capability.
	 */
	public static function can_edit_menus(): bool {
		foreach ( self::context_caps( Access_Context::MENUS ) as $cap ) {
			if ( ! current_user_can( $cap ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Closes template part posts other than header and footer of the active stylesheet; runs on map_meta_cap.
	 *
	 * Applies to edit_post and delete_post for users without manage_options.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $caps    Primitive capabilities WordPress mapped so far.
	 * @param string             $cap     Capability being checked.
	 * @param int                $user_id User ID.
	 * @param array<mixed>       $args    Further arguments; the first is the post.
	 * @return array<int, string> Primitive capabilities, do_not_allow for other parts.
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args = array() ): array {
		if ( 'edit_post' !== $cap && 'delete_post' !== $cap ) {
			return $caps;
		}
		$post_id = self::post_id( $args[0] ?? 0 );
		if ( $post_id <= 0 || 'wp_template_part' !== get_post_type( $post_id ) || user_can( $user_id, 'manage_options' ) ) {
			return $caps;
		}
		$themes = wp_get_object_terms( $post_id, 'wp_theme', array( 'fields' => 'names' ) );
		$themes = is_array( $themes ) ? $themes : array();
		if ( in_array( get_post_field( 'post_name', $post_id ), Access_Routes::PARTS, true ) && in_array( Access_Routes::stylesheet(), $themes, true ) ) {
			return $caps;
		}
		return array( 'do_not_allow' );
	}

	/**
	 * Tells whether a user holds edit_theme_options without the grant.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 * @return bool True when the role (or another filter) gives the capability.
	 */
	public static function has_native_cap( int $user_id ): bool {
		self::$native = true;
		try {
			return user_can( $user_id, self::GRANTED_CAP );
		} finally {
			self::$native = false;
		}
	}

	/**
	 * Returns the post ID of a capability argument.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $post Post ID or post object.
	 * @return int Post ID, 0 when unknown.
	 */
	private static function post_id( mixed $post ): int {
		if ( is_int( $post ) ) {
			return $post;
		}
		if ( is_string( $post ) && ctype_digit( $post ) ) {
			return (int) $post;
		}
		if ( is_object( $post ) && isset( $post->ID ) && is_numeric( $post->ID ) ) {
			return (int) $post->ID;
		}
		return 0;
	}
}
