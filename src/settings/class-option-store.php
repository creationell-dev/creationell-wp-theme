<?php
/**
 * Option rows of the theme settings in the storage format of ACF.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use InvalidArgumentException;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Writes, reads and deletes the value and reference rows of a setting, without ACF.
 *
 * The rows have the names ACF uses for the theme's options pages, so the pages
 * show what the setter wrote and the setter is the only writer, with or without ACF:
 *
 * - untranslated keys: "creationell_wp_theme_global_<key>" (post_id
 *   "creationell_wp_theme_global", field name "<key>");
 * - translated keys: "options_creationell_wp_theme_<key>" in the default language,
 *   "options_<lang>_creationell_wp_theme_<key>" in a secondary one (post_id
 *   "options" or "options_<lang>", field name "creationell_wp_theme_<key>").
 *
 * Each value row has a reference row "_<name>" with the field key
 * "field_creationell_wp_theme_<key>". Rows are stored without autoload; the
 * front end reads the autoloaded snapshot only. A language argument is the code
 * of a secondary language; null means the default language.
 *
 * @since 1.0.0
 */
final class Option_Store {

	/**
	 * ACF post_id of the page with the untranslated keys.
	 *
	 * @since 1.0.0
	 */
	public const GLOBAL_POST_ID = 'creationell_wp_theme_global';

	/**
	 * ACF post_id of the page with the translated keys.
	 *
	 * @since 1.0.0
	 */
	public const OPTIONS_POST_ID = 'options';

	/**
	 * Prefix of the field names of translated keys and of the field keys.
	 *
	 * @since 1.0.0
	 */
	public const PREFIX = 'creationell_wp_theme_';

	/**
	 * Longest option name WordPress stores.
	 *
	 * @since 1.0.0
	 */
	public const MAX_NAME_LENGTH = 191;

	/**
	 * Longest language code in an option name.
	 *
	 * @since 1.0.0
	 */
	public const MAX_LANG_LENGTH = 10;

	/**
	 * Returns the ACF field key of a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key.
	 * @return string Field key, e.g. field_creationell_wp_theme_color_primary.
	 */
	public static function field_key( string $key ): string {
		return 'field_' . self::PREFIX . $key;
	}

	/**
	 * Returns the ACF field name of a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition $definition Definition.
	 * @return string The key for untranslated keys, creationell_wp_theme_<key> for translated ones.
	 */
	public static function field_name( Definition $definition ): string {
		return $definition->translatable ? self::PREFIX . $definition->key : $definition->key;
	}

	/**
	 * Returns the ACF post_id the rows of a setting belong to.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @return string post_id.
	 * @throws InvalidArgumentException When a language is given for an untranslated key or the code is invalid.
	 */
	public static function post_id( Definition $definition, ?string $lang = null ): string {
		self::check_lang( $definition, $lang );
		if ( ! $definition->translatable ) {
			return self::GLOBAL_POST_ID;
		}
		return null === $lang ? self::OPTIONS_POST_ID : self::OPTIONS_POST_ID . '_' . $lang;
	}

	/**
	 * Returns the name of the value row.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @return string Option name.
	 * @throws InvalidArgumentException When a language is given for an untranslated key or the code is invalid.
	 */
	public static function value_name( Definition $definition, ?string $lang = null ): string {
		return self::post_id( $definition, $lang ) . '_' . self::field_name( $definition );
	}

	/**
	 * Returns the name of the reference row.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @return string Option name.
	 * @throws InvalidArgumentException When a language is given for an untranslated key or the code is invalid.
	 */
	public static function reference_name( Definition $definition, ?string $lang = null ): string {
		return '_' . self::value_name( $definition, $lang );
	}

	/**
	 * Tells whether a language code may appear in an option name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $lang Language code.
	 * @return bool True for lower case letters, digits, "-" and "_", at most ten characters.
	 */
	public static function valid_lang( string $lang ): bool {
		return 1 === preg_match( '~^[a-z0-9][a-z0-9_-]{0,9}$~D', $lang );
	}

	/**
	 * Tells whether the value row exists.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @return bool True when stored, also for an empty string.
	 */
	public function has( Definition $definition, ?string $lang = null ): bool {
		$missing = new \stdClass();
		return get_option( self::value_name( $definition, $lang ), $missing ) !== $missing;
	}

	/**
	 * Returns the raw value row.
	 *
	 * The value comes as stored; numbers and booleans may come back as strings.
	 * Sanitizer turns it into the type of the definition.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @return mixed Stored value, or null without a row.
	 */
	public function read( Definition $definition, ?string $lang = null ): mixed {
		$missing = new \stdClass();
		$value   = get_option( self::value_name( $definition, $lang ), $missing );
		return $value === $missing ? null : $value;
	}

	/**
	 * Writes the value row and, unless told otherwise, the reference row; both without autoload.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param mixed       $value      Clean value.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @param bool        $reference  Whether to write the reference row; the module switches have none.
	 * @return void
	 */
	public function write( Definition $definition, mixed $value, ?string $lang = null, bool $reference = true ): void {
		update_option( self::value_name( $definition, $lang ), $value, false );
		if ( $reference ) {
			update_option( self::reference_name( $definition, $lang ), self::field_key( $definition->key ), false );
		}
	}

	/**
	 * Deletes the value and the reference row.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Code of a secondary language; null for the default language.
	 * @return bool True when a value row existed.
	 */
	public function delete( Definition $definition, ?string $lang = null ): bool {
		$existed = $this->has( $definition, $lang );
		delete_option( self::value_name( $definition, $lang ) );
		delete_option( self::reference_name( $definition, $lang ) );
		return $existed;
	}

	/**
	 * Checks the language argument.
	 *
	 * @since 1.0.0
	 *
	 * @param Definition  $definition Definition.
	 * @param string|null $lang       Language code or null.
	 * @return void
	 * @throws InvalidArgumentException When a language is given for an untranslated key or the code is invalid.
	 */
	private static function check_lang( Definition $definition, ?string $lang ): void {
		if ( null === $lang ) {
			return;
		}
		if ( ! $definition->translatable ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Setting %1$s is not translatable; it has no rows for the language %2$s.', $definition->key, $lang ) ) );
		}
		if ( ! self::valid_lang( $lang ) ) {
			throw new InvalidArgumentException( esc_html( sprintf( 'Language code %s is invalid in an option name.', $lang ) ) );
		}
	}
}
