<?php
/**
 * Script coverage of a font for the languages of the site.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells which languages of the site a font does not cover, from the scripts it declares.
 *
 * The check is declarative: a font covers a language when it declares the
 * script of that language (latin-ext and vietnamese also need latin). Glyphs
 * are not inspected. A language outside the table is not judged. A gap is a
 * warning on the settings page and in doctor, never a refusal.
 *
 * @since 1.0.0
 */
final class Font_Coverage {

	/**
	 * Script needed per language code.
	 *
	 * @since 1.0.0
	 */
	public const LANGUAGES = array(
		'de' => 'latin',
		'en' => 'latin',
		'fr' => 'latin',
		'es' => 'latin',
		'it' => 'latin',
		'nl' => 'latin',
		'pl' => 'latin-ext',
		'cs' => 'latin-ext',
		'tr' => 'latin-ext',
		'hu' => 'latin-ext',
		'ro' => 'latin-ext',
		'vi' => 'vietnamese',
		'ru' => 'cyrillic',
		'uk' => 'cyrillic',
		'bg' => 'cyrillic',
		'ar' => 'arabic',
		'fa' => 'arabic',
	);

	/**
	 * Returns the locales whose script the font does not declare.
	 *
	 * @since 1.0.0
	 *
	 * @param Font_Family        $family  Font.
	 * @param array<int, string> $locales Locales, e.g. "de_DE", "tr_TR", "ar".
	 * @return array<int, string> Locales not covered, in the given order.
	 */
	public static function missing( Font_Family $family, array $locales ): array {
		$missing = array();
		foreach ( $locales as $locale ) {
			foreach ( self::needed( $locale ) as $script ) {
				if ( ! in_array( $script, $family->scripts, true ) ) {
					$missing[] = $locale;
					break;
				}
			}
		}
		return $missing;
	}

	/**
	 * Returns the locales of the site: those of the active WPML languages, otherwise the site locale.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Locales without duplicates.
	 */
	public static function site_locales(): array {
		$language = Language::instance();
		$locales  = array();
		foreach ( $language->active() as $code ) {
			$locales[] = $language->locale( $code );
		}
		return array_values( array_unique( $locales ) );
	}

	/**
	 * Returns the scripts a locale needs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale.
	 * @return array<int, string> Scripts; empty for a language outside the table.
	 */
	private static function needed( string $locale ): array {
		$code   = strtolower( explode( '_', str_replace( '-', '_', $locale ) )[0] );
		$script = self::LANGUAGES[ $code ] ?? null;
		if ( null === $script ) {
			return array();
		}
		return in_array( $script, array( 'latin-ext', 'vietnamese' ), true ) ? array( 'latin', $script ) : array( $script );
	}
}
