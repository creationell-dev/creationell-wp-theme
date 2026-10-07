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
 * script of that language (latin-ext and vietnamese also need latin). With
 * ranged faces the font must also cover the characters of each language (CLDR
 * lists in data/font-characters.php); glyphs in the files are not inspected. A
 * language outside the table is not judged. A gap is a
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
		'pt' => 'latin',
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
		'zh' => 'cjk',
		'ja' => 'cjk',
	);

	/**
	 * Unicode ranges of the scripts, from the subsets of Google Fonts (CSS2 API, 2026-10-06).
	 *
	 * A face without unicode range covers the ranges of the scripts its font
	 * declares. Arabic leaves out the ranges above U+FFFF. cjk is not a Google
	 * subset (Google slices CJK into many numbered ranges) but the Unicode blocks
	 * of CJK punctuation, kana, ideographs (with extension A and compatibility
	 * ideographs) and fullwidth forms.
	 *
	 * @since 1.0.0
	 */
	public const SCRIPT_RANGES = array(
		'latin'      => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
		'latin-ext'  => 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C4, U+2113, U+2C60-2C7F, U+A720-A7FF',
		'vietnamese' => 'U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB',
		'cyrillic'   => 'U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116',
		'arabic'     => 'U+0600-06FF, U+0750-077F, U+0870-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FE74, U+FE76-FEFC',
		'cjk'        => 'U+3000-303F, U+3040-309F, U+30A0-30FF, U+31F0-31FF, U+3400-4DBF, U+4E00-9FFF, U+F900-FAFF, U+FF00-FFEF',
	);

	/**
	 * Punctuation, digits and symbols that fonts of every script carry; they never show which script a face brings.
	 *
	 * @since 1.0.0
	 */
	public const SHARED = 'U+0000-0040, U+005B-0060, U+007B-00BF, U+00D7, U+00F7, U+02C6, U+02DA, U+02DC, U+2000-206F, U+20A0-20CF, U+2100-214F, U+2190-22FF, U+FEFF, U+FFFD';

	/**
	 * Characters by language code, loaded once.
	 *
	 * @var array<string, list<string>>|null
	 */
	private static ?array $characters = null;

	/**
	 * Code points by language code, loaded once.
	 *
	 * @var array<string, list<int>>|null
	 */
	private static ?array $code_points = null;

	/**
	 * Data file of the character lists; null for the file of the theme.
	 *
	 * @var string|null
	 */
	private static ?string $data_file = null;

	/**
	 * Returns the locales whose script or characters the font does not cover.
	 *
	 * @since 1.0.0
	 *
	 * @param Font_Family        $family  Font.
	 * @param array<int, string> $locales Locales, e.g. "de_DE", "tr_TR", "ar".
	 * @return array<int, string> Locales not covered, in the given order.
	 */
	public static function missing( Font_Family $family, array $locales ): array {
		return array_keys( self::gaps( $family, $locales ) );
	}

	/**
	 * Returns per locale with a gap the missing scripts or, when the scripts are there, the missing characters.
	 *
	 * Characters count only when at least one face has a unicode range: each
	 * character of the language must lie in the range of a face, or in the
	 * script ranges of a face without range. Fonts without ranged faces are
	 * judged by their scripts alone.
	 *
	 * @since 1.0.0
	 *
	 * @param Font_Family        $family  Font.
	 * @param array<int, string> $locales Locales, e.g. "de_DE", "tr_TR", "fa_IR".
	 * @return array<string, array{scripts: list<string>, characters: list<string>}> Gaps by locale, in the given order.
	 */
	public static function gaps( Font_Family $family, array $locales ): array {
		$ranges = self::face_ranges( $family );
		$gaps   = array();
		foreach ( $locales as $locale ) {
			$scripts = array_values( array_diff( self::scripts_needed( $locale ), $family->scripts ) );
			if ( array() !== $scripts ) {
				$gaps[ $locale ] = array(
					'scripts'    => $scripts,
					'characters' => array(),
				);
				continue;
			}
			if ( null === $ranges ) {
				continue;
			}
			$missing = array();
			foreach ( self::code_points()[ self::list_key( $locale ) ] ?? array() as $point ) {
				if ( ! Unicode_Range::covers( $ranges, $point ) ) {
					$missing[] = self::character( $point );
				}
			}
			if ( array() !== $missing ) {
				$gaps[ $locale ] = array(
					'scripts'    => array(),
					'characters' => $missing,
				);
			}
		}
		return $gaps;
	}

	/**
	 * Returns the characters each language needs beyond basic ASCII.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, list<string>> Characters by language code, by code point.
	 */
	public static function characters(): array {
		if ( null === self::$characters ) {
			self::$characters = array_map(
				static fn( array $points ): array => array_map( array( self::class, 'character' ), $points ),
				self::code_points()
			);
		}
		return self::$characters;
	}

	/**
	 * Returns the UTF-8 character of a code point, without mbstring.
	 *
	 * @since 1.0.0
	 *
	 * @param int $code_point Code point.
	 * @return string Character.
	 */
	public static function character( int $code_point ): string {
		return html_entity_decode( '&#x' . dechex( $code_point ) . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Returns the scripts a locale needs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale.
	 * @return list<string> Scripts; empty for a language outside the table.
	 */
	public static function scripts_needed( string $locale ): array {
		$script = self::LANGUAGES[ self::code( $locale ) ] ?? null;
		if ( null === $script ) {
			return array();
		}
		return in_array( $script, array( 'latin-ext', 'vietnamese' ), true ) ? array( 'latin', $script ) : array( $script );
	}

	/**
	 * Returns the code points by language code from the data file.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, list<int>> Code points.
	 */
	private static function code_points(): array {
		if ( null === self::$code_points ) {
			$file   = self::$data_file ?? __DIR__ . '/data/font-characters.php';
			$loaded = is_readable( $file ) ? require $file : array();
			$table  = array();
			foreach ( is_array( $loaded ) ? $loaded : array() as $code => $points ) {
				if ( is_string( $code ) && is_array( $points ) ) {
					$table[ $code ] = array_values( array_filter( $points, 'is_int' ) );
				}
			}
			self::$code_points = $table;
		}
		return self::$code_points;
	}

	/**
	 * Replaces the data file of the character lists; null restores the file of the theme.
	 *
	 * A missing or unreadable file leaves the lists empty: the check then judges
	 * by scripts alone. Meant for tests, not for child themes.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $file Path of a PHP file that returns code points by language code.
	 * @return void
	 */
	public static function set_data_file( ?string $file ): void {
		self::$data_file   = $file;
		self::$code_points = null;
		self::$characters  = null;
	}

	/**
	 * Returns the intervals the faces cover, or null when no face has a unicode range.
	 *
	 * A ranged face covers its range. A face without range covers the script
	 * ranges of the declared scripts, except the scripts a ranged face brings
	 * (its range shares code points with what only that script holds): an extra
	 * face for Arabic next to a Latin house font decides Arabic alone, so a
	 * range that misses characters shows as a gap.
	 *
	 * @since 1.0.0
	 *
	 * @param Font_Family $family Font.
	 * @return list<array{0: int, 1: int}>|null Intervals.
	 */
	private static function face_ranges( Font_Family $family ): ?array {
		$ranged = array();
		$plain  = false;
		foreach ( $family->faces as $face ) {
			if ( isset( $face['unicode_range'] ) ) {
				$ranged = array_merge( $ranged, Unicode_Range::parse( $face['unicode_range'] ) );
			} else {
				$plain = true;
			}
		}
		if ( array() === $ranged ) {
			return null;
		}
		$intervals = $ranged;
		if ( $plain ) {
			foreach ( $family->scripts as $script ) {
				if ( ! Unicode_Range::intersects( $ranged, self::core( $script ) ) ) {
					$intervals = array_merge( $intervals, Unicode_Range::parse( self::SCRIPT_RANGES[ $script ] ?? '' ) );
				}
			}
		}
		return Unicode_Range::normalize( $intervals );
	}

	/**
	 * Returns the code points that show a face brings a script: what only that script holds, without shared punctuation and symbols.
	 *
	 * Fonts for other scripts carry ASCII, Latin-1 symbols and general
	 * punctuation (CJK slices hold the em dash, the ellipsis and quotation
	 * marks); such a face does not bring Latin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $script Script.
	 * @return list<array{0: int, 1: int}> Intervals.
	 */
	private static function core( string $script ): array {
		return Unicode_Range::subtract( self::exclusive( $script ), Unicode_Range::parse( self::SHARED ) );
	}

	/**
	 * Returns the code points of a script that no other script of SCRIPT_RANGES holds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $script Script.
	 * @return list<array{0: int, 1: int}> Intervals.
	 */
	private static function exclusive( string $script ): array {
		$own = Unicode_Range::parse( self::SCRIPT_RANGES[ $script ] ?? '' );
		foreach ( self::SCRIPT_RANGES as $other => $range ) {
			if ( $other !== $script ) {
				$own = Unicode_Range::subtract( $own, Unicode_Range::parse( $range ) );
			}
		}
		return $own;
	}

	/**
	 * Returns the key of the character list of a locale: the language code, zh-Hant for traditional Chinese.
	 *
	 * Traditional Chinese: script Hant or region TW, HK or MO (zh_TW, zh_HK,
	 * the WPML code zh-hant); an explicit script Hans wins over the region
	 * (zh_Hans_HK); other Chinese locales take the simplified list.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale.
	 * @return string Key of Font_Coverage::characters().
	 */
	private static function list_key( string $locale ): string {
		$code = self::code( $locale );
		if ( 'zh' !== $code ) {
			return $code;
		}
		$parts = array_map( 'strtolower', explode( '_', str_replace( '-', '_', $locale ) ) );
		if ( in_array( 'hans', $parts, true ) ) {
			return 'zh';
		}
		return array() !== array_intersect( $parts, array( 'hant', 'tw', 'hk', 'mo' ) ) ? 'zh-Hant' : 'zh';
	}

	/**
	 * Returns the language code of a locale.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale, e.g. "de_DE_formal", "pt-BR".
	 * @return string Language code in lower case.
	 */
	private static function code( string $locale ): string {
		return strtolower( explode( '_', str_replace( '-', '_', $locale ) )[0] );
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
}
