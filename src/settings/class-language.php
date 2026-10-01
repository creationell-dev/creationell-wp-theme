<?php
/**
 * Languages of the site for the settings.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Settings;

use Closure;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Tells the default, current and active languages through the WPML filters.
 *
 * Without WPML the site has one language: the language part of the site
 * locale ("de" for de_DE). Settings, snapshots and the setter speak of a
 * language by its WPML code; the default language needs no code in option
 * names, a secondary language does (Option_Store). In the admin WPML may report
 * "all" for "All languages"; the setter refuses writes then.
 *
 * @since 1.0.0
 */
final class Language {

	/**
	 * Code WPML reports for "All languages" in the admin.
	 *
	 * @since 1.0.0
	 */
	public const ALL = 'all';

	/**
	 * WPML filter that returns the default language code.
	 *
	 * @since 1.0.0
	 */
	public const WPML_DEFAULT = 'wpml_default_language';

	/**
	 * WPML filter that returns the language code of the request.
	 *
	 * @since 1.0.0
	 */
	public const WPML_CURRENT = 'wpml_current_language';

	/**
	 * WPML filter that returns the active languages by code.
	 *
	 * @since 1.0.0
	 */
	public const WPML_ACTIVE = 'wpml_active_languages';

	/**
	 * Language parts of the locales written right to left, as WordPress.org lists them.
	 *
	 * @since 1.0.0
	 */
	public const RTL_LANGUAGES = array( 'ar', 'arc', 'ary', 'azb', 'ckb', 'dv', 'fa', 'haz', 'he', 'ps', 'sd', 'skr', 'ug', 'ur', 'yi' );

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Tells whether the multilang module of ACF Extended is active.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): bool
	 */
	private Closure $acfe_multilang;

	/**
	 * Takes the ACF Extended check; without one it asks ACF Extended itself.
	 *
	 * @since 1.0.0
	 *
	 * @param Closure|null $acfe_multilang Returns whether the multilang module of ACF Extended is active.
	 * @phpstan-param (Closure(): bool)|null $acfe_multilang
	 */
	public function __construct( ?Closure $acfe_multilang = null ) {
		$this->acfe_multilang = $acfe_multilang ?? self::acfe_setting( ... );
	}

	/**
	 * Returns the shared instance.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Replaces the shared instance; null builds a new one on the next call of instance().
	 *
	 * @since 1.0.0
	 *
	 * @param self|null $language Instance.
	 * @return void
	 */
	public static function set_instance( ?self $language ): void {
		self::$instance = $language;
	}

	/**
	 * Returns the default language.
	 *
	 * @since 1.0.0
	 *
	 * @return string Language code, e.g. "de".
	 */
	public function default(): string {
		$code = self::wpml( self::WPML_DEFAULT );
		if ( is_string( $code ) && '' !== $code ) {
			return $code;
		}
		$parts = explode( '_', get_locale() );
		return strtolower( $parts[0] );
	}

	/**
	 * Returns the language of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return string Language code, or "all" for "All languages" in the admin.
	 */
	public function current(): string {
		$code = self::wpml( self::WPML_CURRENT );
		return is_string( $code ) && '' !== $code ? $code : $this->default();
	}

	/**
	 * Returns the active languages.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Language codes; without WPML the default language only.
	 */
	public function active(): array {
		$codes = array_keys( $this->wpml_languages() );
		return array() === $codes ? array( $this->default() ) : $codes;
	}

	/**
	 * Returns the active languages written right to left.
	 *
	 * A language counts when the language part of its locale is one of
	 * RTL_LANGUAGES ("ar" for ar or ar_EG); without WPML that is the site locale.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string> Language codes in the order of the active languages.
	 */
	public function rtl(): array {
		$rtl = array();
		foreach ( $this->active() as $code ) {
			$prefix = strtolower( explode( '_', str_replace( '-', '_', $this->locale( $code ) ) )[0] );
			if ( in_array( $prefix, self::RTL_LANGUAGES, true ) ) {
				$rtl[] = $code;
			}
		}
		return $rtl;
	}

	/**
	 * Returns the locale of a language.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Language code; null for the default language.
	 * @return string Locale, e.g. "de_DE"; the site locale when WPML does not know the language.
	 */
	public function locale( ?string $lang = null ): string {
		$lang      = $lang ?? $this->default();
		$languages = $this->wpml_languages();
		$locale    = $languages[ $lang ]['default_locale'] ?? null;
		return is_string( $locale ) && '' !== $locale ? $locale : get_locale();
	}

	/**
	 * Returns a WordPress locale as BCP 47 tag, without the suffix _formal or _informal.
	 *
	 * For the attributes lang and hreflang; WPML's own field "tag" holds the
	 * language code only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale WordPress locale, e.g. "de_DE_formal".
	 * @return string Tag, e.g. "de-DE"; empty for an empty locale.
	 */
	public static function bcp47( string $locale ): string {
		return str_replace( '_', '-', (string) preg_replace( '~_(?:in)?formal$~', '', $locale ) );
	}

	/**
	 * Tells whether a language is the default language.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Language code; null means the default language.
	 * @return bool True for null and the default code.
	 */
	public function is_default( ?string $lang ): bool {
		return null === $lang || $lang === $this->default();
	}

	/**
	 * Returns the code of a secondary language, or null for the default language.
	 *
	 * Option names and snapshots carry a code for secondary languages only.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $lang Language code; null means the default language.
	 * @return string|null Code of a secondary language, null for the default language.
	 */
	public function secondary( ?string $lang ): ?string {
		return $this->is_default( $lang ) ? null : $lang;
	}

	/**
	 * Tells whether the multilang module of ACF Extended is active; it would add language suffixes to the theme pages.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when active.
	 */
	public function acfe_multilang_active(): bool {
		return ( $this->acfe_multilang )();
	}

	/**
	 * Returns the active WPML languages by code.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Language data by code; empty without WPML.
	 */
	private function wpml_languages(): array {
		$languages = self::wpml( self::WPML_ACTIVE, array( array( 'skip_missing' => 0 ) ) );
		if ( ! is_array( $languages ) ) {
			return array();
		}
		$valid = array();
		foreach ( $languages as $code => $data ) {
			if ( is_string( $code ) && '' !== $code && is_array( $data ) ) {
				$valid[ $code ] = $data;
			}
		}
		return $valid;
	}

	/**
	 * Asks WPML through one of its filters; without WPML the filter returns null.
	 *
	 * @since 1.0.0
	 *
	 * @param string            $filter One of the WPML_* filter names.
	 * @param array<int, mixed> $args   Further arguments after the value null, in order.
	 * @return mixed Answer of WPML, or null.
	 */
	private static function wpml( string $filter, array $args = array() ): mixed {
		return apply_filters( $filter, null, ...array_values( $args ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- the public API of WPML are its wpml_* filters (WPML_* constants).
	}

	/**
	 * Asks ACF Extended whether its multilang module runs: it needs WPML or Polylang and the setting modules/multilang.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when active.
	 */
	private static function acfe_setting(): bool {
		if ( ! function_exists( 'acfe_get_setting' ) || ! ( defined( 'ICL_SITEPRESS_VERSION' ) || defined( 'POLYLANG_VERSION' ) ) ) {
			return false;
		}
		$enabled = acfe_get_setting( 'modules/multilang' );
		return true === $enabled || 1 === $enabled || '1' === $enabled;
	}
}
