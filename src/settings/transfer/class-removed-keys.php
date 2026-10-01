<?php
/**
 * Setting keys removed from the theme.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings\Transfer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Lists keys that older theme versions exported and this one no longer knows, with the version that removed them.
 *
 * The import reports such keys as removed instead of unknown and never writes
 * them. A key renamed with renamed_from in its new definition does not belong
 * here. Every entry also goes into the changelog section "Settings keys changed".
 *
 * Example:
 *
 *     public const MAP = array(
 *         'footer_note' => '1.2.0',
 *     );
 *
 * @since 1.0.0
 */
final class Removed_Keys {

	/**
	 * Theme version (X.Y.Z) that removed the key, by key; empty until a key is removed.
	 *
	 * @since 1.0.0
	 */
	public const MAP = array();

	/**
	 * Tells whether a key was removed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return bool True when listed in MAP.
	 */
	public static function has( string $key ): bool {
		return null !== self::version( $key );
	}

	/**
	 * Returns the theme version that removed a key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return string|null Version, null for a key that was not removed.
	 */
	public static function version( string $key ): ?string {
		return self::all()[ $key ] ?? null;
	}

	/**
	 * Returns all removed keys.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Theme version that removed the key, by key.
	 */
	public static function all(): array {
		return self::MAP;
	}
}
