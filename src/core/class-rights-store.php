<?php
/**
 * Rights set in the backend.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the rights the agency sets on the rights page.
 *
 * The option holds only the entries set in the backend: switches of the areas
 * in "unlock" and "revoke" and, when set, the list "restricted_admins".
 * Capabilities::config() lays them over the defaults of the child theme unless
 * the child theme locks an entry. Import, export, backups and snapshots never
 * carry the option, so an import changes no rights.
 *
 * @since 1.0.0
 */
final class Rights_Store {

	/**
	 * Option name.
	 *
	 * @since 1.0.0
	 */
	public const OPTION = 'creationell_wp_theme_rights';

	/**
	 * Returns the checked entries set in the backend.
	 *
	 * Unknown areas, switches that are no boolean and logins that are no
	 * non-empty string fall away; "restricted_admins" is present only when set.
	 *
	 * @since 1.0.0
	 *
	 * @return array{unlock: array<string, bool>, revoke: array<string, bool>, restricted_admins?: list<string>} Entries.
	 */
	public static function read(): array {
		return self::check( get_option( self::OPTION, array() ) );
	}

	/**
	 * Stores the checked entries.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $values Entries like read() returns them.
	 * @return bool True when the option holds the entries now.
	 */
	public static function write( array $values ): bool {
		$checked = self::check( $values );
		update_option( self::OPTION, $checked, true );
		return get_option( self::OPTION ) === $checked;
	}

	/**
	 * Gives one entry back to the default; without entries the option goes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Area or "restricted_admins".
	 * @return void
	 */
	public static function forget( string $key ): void {
		$values = self::read();
		unset( $values['unlock'][ $key ], $values['revoke'][ $key ] );
		if ( 'restricted_admins' === $key ) {
			unset( $values['restricted_admins'] );
		}
		if ( array() === $values['unlock'] && array() === $values['revoke'] && ! array_key_exists( 'restricted_admins', $values ) ) {
			delete_option( self::OPTION );
			return;
		}
		update_option( self::OPTION, $values, true );
	}

	/**
	 * Checks a stored or given value.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return array{unlock: array<string, bool>, revoke: array<string, bool>, restricted_admins?: list<string>} Entries.
	 */
	private static function check( mixed $value ): array {
		$value   = is_array( $value ) ? $value : array();
		$checked = array(
			'unlock' => self::switches( $value['unlock'] ?? null, Capabilities::UNLOCKABLE ),
			'revoke' => self::switches( $value['revoke'] ?? null, Capabilities::REVOCABLE ),
		);
		if ( array_key_exists( 'restricted_admins', $value ) && is_array( $value['restricted_admins'] ) ) {
			$checked['restricted_admins'] = array_values( array_filter( $value['restricted_admins'], static fn( mixed $login ): bool => is_string( $login ) && '' !== $login ) );
		}
		return $checked;
	}

	/**
	 * Keeps the switches of known areas with boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed              $switches Switches by area.
	 * @param array<int, string> $areas    Areas of the group.
	 * @return array<string, bool> Switches.
	 */
	private static function switches( mixed $switches, array $areas ): array {
		$checked = array();
		foreach ( is_array( $switches ) ? $switches : array() as $area => $switch ) {
			if ( in_array( $area, $areas, true ) && is_bool( $switch ) ) {
				$checked[ $area ] = $switch;
			}
		}
		return $checked;
	}
}
