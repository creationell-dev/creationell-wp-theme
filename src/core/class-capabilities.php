<?php
/**
 * Meta capabilities of the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Maps the meta capabilities of the theme to primitive capabilities of WordPress.
 *
 * The theme adds no role and changes none; it only answers map_meta_cap for its
 * own capabilities. Editors (edit_pages) maintain the texts, see the advanced
 * pages read-only and edit template parts; the agency (manage_options) manages
 * design, modules, import and export, menus and the Bootstrap line. A child
 * theme opens single areas to editors, takes basic areas back to the agency or
 * restricts administrators of the customer, all through the one filter
 * creationell_wp_theme_capabilities. The Bootstrap line is never opened.
 *
 * Other code checks the capabilities only with current_user_can() or user_can().
 *
 * @since 1.0.0
 */
final class Capabilities {

	/**
	 * Filter of the child theme: unlock, revoke, restricted_admins.
	 *
	 * @since 1.0.0
	 */
	public const FILTER = 'creationell_wp_theme_capabilities';

	/**
	 * Level 1: texts such as footer, contact and social links.
	 *
	 * @since 1.0.0
	 */
	public const MANAGE_BASIC = 'creationell_wp_theme_manage_basic';

	/**
	 * Level 2 read-only: design page, module page, import and export page.
	 *
	 * @since 1.0.0
	 */
	public const VIEW_ADVANCED = 'creationell_wp_theme_view_advanced';

	/**
	 * Header and footer template parts.
	 *
	 * @since 1.0.0
	 */
	public const EDIT_TEMPLATE_PARTS = 'creationell_wp_theme_edit_template_parts';

	/**
	 * Colors, fonts and behavior settings.
	 *
	 * @since 1.0.0
	 */
	public const MANAGE_DESIGN = 'creationell_wp_theme_manage_design';

	/**
	 * Module switches.
	 *
	 * @since 1.0.0
	 */
	public const MANAGE_MODULES = 'creationell_wp_theme_manage_modules';

	/**
	 * Running import and export.
	 *
	 * @since 1.0.0
	 */
	public const IMPORT_EXPORT = 'creationell_wp_theme_import_export';

	/**
	 * Menus of header and footer.
	 *
	 * @since 1.0.0
	 */
	public const EDIT_MENUS = 'creationell_wp_theme_edit_menus';

	/**
	 * Switching the Bootstrap line; never opened to editors.
	 *
	 * @since 1.0.0
	 */
	public const SWITCH_BOOTSTRAP_LINE = 'creationell_wp_theme_switch_bootstrap_line';

	/**
	 * Areas a child theme may open to editors (key "unlock" of the filter).
	 *
	 * @since 1.0.0
	 */
	public const UNLOCKABLE = array( 'design', 'modules', 'import_export', 'menus' );

	/**
	 * Areas a child theme may take back to the agency (key "revoke" of the filter).
	 *
	 * @since 1.0.0
	 */
	public const REVOCABLE = array( 'basic', 'template_parts' );

	/**
	 * Primitive capability of editors.
	 */
	private const EDITOR = 'edit_pages';

	/**
	 * Primitive capability of the agency.
	 */
	private const AGENCY = 'manage_options';

	/**
	 * Capability that WordPress denies to everybody, super administrators included.
	 */
	private const DENY = 'do_not_allow';

	/**
	 * Problems of the filter value reported in this request, by message.
	 *
	 * @var array<string, true>
	 */
	private static array $reported = array();

	/**
	 * Returns the meta capability of each area.
	 *
	 * The areas are basic, advanced, template_parts, design, modules,
	 * import_export, menus and bootstrap_line.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Meta capability by area.
	 */
	public static function area_caps(): array {
		return array(
			'basic'          => self::MANAGE_BASIC,
			'advanced'       => self::VIEW_ADVANCED,
			'template_parts' => self::EDIT_TEMPLATE_PARTS,
			'design'         => self::MANAGE_DESIGN,
			'modules'        => self::MANAGE_MODULES,
			'import_export'  => self::IMPORT_EXPORT,
			'menus'          => self::EDIT_MENUS,
			'bootstrap_line' => self::SWITCH_BOOTSTRAP_LINE,
		);
	}

	/**
	 * Returns all meta capabilities of the theme.
	 *
	 * @since 1.0.0
	 *
	 * @return list<string> Meta capabilities in the order of area_caps().
	 */
	public static function all(): array {
		return array_values( self::area_caps() );
	}

	/**
	 * Returns the value the filter starts with: nothing unlocked, nothing revoked, no restricted administrator.
	 *
	 * @since 1.0.0
	 *
	 * @return array{unlock: array<string, bool>, revoke: array<string, bool>, restricted_admins: list<string>} Defaults.
	 */
	public static function defaults(): array {
		return array(
			'unlock'            => array_fill_keys( self::UNLOCKABLE, false ),
			'revoke'            => array_fill_keys( self::REVOCABLE, false ),
			'restricted_admins' => array(),
		);
	}

	/**
	 * Returns the checked value of the filter creationell_wp_theme_capabilities.
	 *
	 * An invalid part falls back to its default and is reported once per
	 * request with _doing_it_wrong(): a value that is no array, "unlock" or
	 * "revoke" that is no array, unknown keys and areas, values that are no
	 * boolean, and logins that are no non-empty string. The filter runs on each
	 * call, so it may depend on the request; the value is not stored.
	 *
	 * @since 1.0.0
	 *
	 * @return array{unlock: array<string, bool>, revoke: array<string, bool>, restricted_admins: list<string>} Checked value.
	 */
	public static function config(): array {
		$defaults = self::defaults();

		/**
		 * Filters who may use the areas of the theme; the only filter for capabilities.
		 *
		 * "unlock" opens an area of the agency to editors (edit_pages): design,
		 * modules, import_export, menus. "revoke" takes an area of the editors
		 * back to the agency (manage_options): basic, template_parts.
		 * "restricted_admins" lists user logins of administrators who may use
		 * only the unlocked areas; they never switch the Bootstrap line. The
		 * Bootstrap line is never unlocked. The filter changes no role and
		 * writes nothing to the database; an import never changes it.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_capabilities',
		 *         static function ( array $caps ): array {
		 *             $caps['unlock']['design']    = true;
		 *             $caps['restricted_admins'][] = 'customer-admin';
		 *             return $caps;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array{unlock: array<string, bool>, revoke: array<string, bool>, restricted_admins: array<int, string>} $caps Defaults: nothing unlocked, nothing revoked, no restricted administrator.
		 */
		$value = apply_filters( 'creationell_wp_theme_capabilities', $defaults );
		if ( ! is_array( $value ) ) {
			self::report( 'The value must be an array' );
			return $defaults;
		}

		foreach ( array_keys( $value ) as $key ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				self::report( sprintf( 'Unknown key "%s"', $key ) );
			}
		}

		return array(
			'unlock'            => self::switches( $value, 'unlock', self::UNLOCKABLE ),
			'revoke'            => self::switches( $value, 'revoke', self::REVOCABLE ),
			'restricted_admins' => self::logins( $value ),
		);
	}

	/**
	 * Maps a meta capability of the theme to primitive capabilities; runs on map_meta_cap.
	 *
	 * Other capabilities pass unchanged. A restricted administrator gets
	 * do_not_allow for every area that is not unlocked and for the Bootstrap line.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $caps    Primitive capabilities WordPress mapped so far.
	 * @param string             $cap     Capability being checked.
	 * @param int                $user_id User ID.
	 * @param array<mixed>       $args    Further arguments of the check, unused.
	 * @return array<int, string> Primitive capabilities.
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args = array() ): array {
		unset( $args );
		$area = array_search( $cap, self::area_caps(), true );
		if ( false === $area ) {
			return $caps;
		}
		if ( 'advanced' === $area ) {
			return array( self::EDITOR );
		}

		$config = self::config();
		if ( in_array( $area, self::REVOCABLE, true ) ) {
			return array( $config['revoke'][ $area ] ? self::AGENCY : self::EDITOR );
		}
		if ( in_array( $area, self::UNLOCKABLE, true ) && $config['unlock'][ $area ] ) {
			return array( self::EDITOR );
		}
		return array( self::is_restricted( $user_id, $config['restricted_admins'] ) ? self::DENY : self::AGENCY );
	}

	/**
	 * Forgets the problems reported in this request, so they are reported again.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$reported = array();
	}

	/**
	 * Reads one group of switches ("unlock" or "revoke") from the filter value.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $value Filter value.
	 * @param string             $key   Key of the group.
	 * @param array<int, string> $areas Areas allowed in the group.
	 * @return array<string, bool> Switch by area; missing and invalid entries are false.
	 */
	private static function switches( array $value, string $key, array $areas ): array {
		$switches = array_fill_keys( $areas, false );
		if ( ! array_key_exists( $key, $value ) ) {
			return $switches;
		}
		if ( ! is_array( $value[ $key ] ) ) {
			self::report( sprintf( '"%s" must be an array', $key ) );
			return $switches;
		}
		foreach ( $value[ $key ] as $area => $switch ) {
			if ( ! in_array( $area, $areas, true ) ) {
				self::report( sprintf( 'Unknown area "%1$s" in "%2$s"', $area, $key ) );
				continue;
			}
			if ( ! is_bool( $switch ) ) {
				self::report( sprintf( '"%1$s" of "%2$s" must be a boolean', $area, $key ) );
				continue;
			}
			$switches[ $area ] = $switch;
		}
		return $switches;
	}

	/**
	 * Reads the logins of the restricted administrators from the filter value.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $value Filter value.
	 * @return list<string> Logins; entries that are no non-empty string are left out.
	 */
	private static function logins( array $value ): array {
		if ( ! array_key_exists( 'restricted_admins', $value ) ) {
			return array();
		}
		if ( ! is_array( $value['restricted_admins'] ) ) {
			self::report( '"restricted_admins" must be a list of user logins' );
			return array();
		}
		$logins = array();
		foreach ( $value['restricted_admins'] as $index => $login ) {
			if ( ! is_string( $login ) || '' === $login ) {
				self::report( sprintf( 'Entry %s of "restricted_admins" is no user login', $index ) );
				continue;
			}
			$logins[] = $login;
		}
		return $logins;
	}

	/**
	 * Tells whether a user is one of the restricted administrators; logins compare without case.
	 *
	 * @since 1.0.0
	 *
	 * @param int                $user_id User ID.
	 * @param array<int, string> $logins  Logins of the restricted administrators.
	 * @return bool True when the login of the user is listed.
	 */
	private static function is_restricted( int $user_id, array $logins ): bool {
		if ( array() === $logins || $user_id <= 0 ) {
			return false;
		}
		$user = get_userdata( $user_id );
		if ( false === $user ) {
			return false;
		}
		return in_array( strtolower( $user->user_login ), array_map( 'strtolower', $logins ), true );
	}

	/**
	 * Reports a problem of the filter value once per request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $problem Problem, without the filter name.
	 * @return void
	 */
	private static function report( string $problem ): void {
		if ( isset( self::$reported[ $problem ] ) ) {
			return;
		}
		self::$reported[ $problem ] = true;
		_doing_it_wrong(
			__CLASS__ . '::config',
			esc_html( sprintf( 'Invalid value of the filter %1$s: %2$s. The default applies.', self::FILTER, $problem ) ),
			'1.0.0'
		);
	}
}
