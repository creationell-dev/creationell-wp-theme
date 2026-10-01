<?php
/**
 * Configuration of CookieConsent for the module consent.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Consent;

use Closure;
use Creationell\WpTheme\Consent\Consent_Gate;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Builds the configuration that consent.js passes to CookieConsent.run().
 *
 * The visitor opts in: every category but "necessary" starts off, scripts
 * marked by the core wait for their category, and a withdrawal deletes the
 * listed cookies and reloads the page. Banner and dialog give "Accept all"
 * and "Reject optional" the same weight. After the filter
 * creationell_wp_theme_consent_config protection rules restore these values
 * (fail closed) and report the changed keys once per request.
 *
 * @since 1.0.0
 */
final class Config_Builder {

	/**
	 * Filter for the configuration.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_CONFIG = 'creationell_wp_theme_consent_config';

	/**
	 * Selector of the root element of CookieConsent.
	 *
	 * @since 1.0.0
	 */
	public const ROOT = '#creationell-theme-consent';

	/**
	 * Highest consent revision.
	 *
	 * @since 1.0.0
	 */
	public const MAX_REVISION = 9999;

	/**
	 * Values the filter may not change, by path.
	 *
	 * @since 1.0.0
	 */
	public const PROTECTED_VALUES = array(
		'root'                                           => self::ROOT,
		'mode'                                           => 'opt-in',
		'manageScriptTags'                               => true,
		'autoClearCookies'                               => true,
		'cookie.name'                                    => Texts::COOKIE_NAME,
		'guiOptions.consentModal.equalWeightButtons'     => true,
		'guiOptions.preferencesModal.equalWeightButtons' => true,
	);

	/**
	 * Whether the protection rules reported a change in this request.
	 *
	 * @var bool
	 */
	private static bool $reported = false;

	/**
	 * Texts of the banner and the dialog.
	 *
	 * @var Texts
	 */
	private Texts $texts;

	/**
	 * Reads a theme setting.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): mixed
	 */
	private Closure $setting;

	/**
	 * Takes the texts and the settings reader; without them it uses the defaults of the module.
	 *
	 * @since 1.0.0
	 *
	 * @param Texts|null   $texts   Texts of the banner and the dialog.
	 * @param Closure|null $setting Returns the value of a setting key.
	 * @phpstan-param (Closure(string): mixed)|null $setting
	 */
	public function __construct( ?Texts $texts = null, ?Closure $setting = null ) {
		$this->setting = $setting ?? static fn( string $key ): mixed => creationell_wp_theme_setting( $key );
		$this->texts   = $texts ?? new Texts( null, $this->setting );
	}

	/**
	 * Builds the configuration, runs the filter creationell_wp_theme_consent_config and applies the protection rules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale of the request, e.g. "de_DE_formal".
	 * @param bool   $rtl    Whether the language is written right to left.
	 * @return array<string, mixed> Configuration of CookieConsent.
	 */
	public function build( string $locale, bool $rtl ): array {
		$config = $this->base( $locale, $rtl );

		/**
		 * Filters the configuration of CookieConsent.
		 *
		 * Protection rules run afterwards: the root element, opt-in mode, script
		 * management, cookie deletion, the cookie name, buttons of equal weight,
		 * a non-empty "Reject optional" button, "necessary" locked on, all other
		 * categories off and only the categories of
		 * creationell_wp_theme_consent_categories() stay as the theme sets them.
		 * A change to one of them is restored with a notice.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_consent_config',
		 *         static function ( array $config ): array {
		 *             $config['guiOptions']['consentModal']['layout'] = 'box';
		 *             return $config;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config Configuration of CookieConsent.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_consent_config', $config );
		return self::guard( $filtered, $config );
	}

	/**
	 * Restores the protected values of a filtered configuration.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $filtered Configuration returned by the filter.
	 * @param array<string, mixed> $base     Configuration before the filter.
	 * @return array<string, mixed> Configuration with the protected values.
	 */
	public static function guard( mixed $filtered, array $base ): array {
		if ( ! is_array( $filtered ) ) {
			self::report( array( self::FILTER_CONFIG ) );
			return $base;
		}
		$changed = array();
		foreach ( self::PROTECTED_VALUES as $path => $value ) {
			self::pin( $filtered, $path, $value, $changed );
		}
		$filtered['categories'] = self::guard_categories( $filtered['categories'] ?? null, $base, $changed );
		$filtered               = self::guard_language( $filtered, $base, $changed );
		if ( array() !== $changed ) {
			self::report( $changed );
		}
		return $filtered;
	}

	/**
	 * Forgets that the protection rules reported in this request; for tests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_reporting(): void {
		self::$reported = false;
	}

	/**
	 * Builds the configuration before the filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $locale Locale.
	 * @param bool   $rtl    Whether right to left.
	 * @return array<string, mixed> Configuration.
	 */
	private function base( string $locale, bool $rtl ): array {
		$language = array( 'default' => $locale );
		if ( $rtl ) {
			$language['rtl'] = $locale;
		}
		$language['translations'] = array( $locale => $this->texts->for_locale() );

		return array(
			'root'                   => self::ROOT,
			'mode'                   => 'opt-in',
			'autoShow'               => true,
			'revision'               => $this->revision(),
			'manageScriptTags'       => true,
			'autoClearCookies'       => true,
			'hideFromBots'           => true,
			'disablePageInteraction' => false,
			'lazyHtmlGeneration'     => true,
			'cookie'                 => array(
				'name'             => Texts::COOKIE_NAME,
				'expiresAfterDays' => $this->texts->cookie_days(),
				'sameSite'         => 'Lax',
			),
			'guiOptions'             => array(
				'consentModal'     => array(
					'layout'             => 'bar',
					'position'           => 'bottom',
					'equalWeightButtons' => true,
				),
				'preferencesModal' => array(
					'layout'             => 'box',
					'equalWeightButtons' => true,
				),
			),
			'categories'             => self::categories(),
			'language'               => $language,
		);
	}

	/**
	 * Returns the categories of the core: "necessary" locked on, the others off, with cookie deletion where cookies are listed.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Categories by name.
	 */
	private static function categories(): array {
		$details    = Texts::details();
		$categories = array();
		foreach ( creationell_wp_theme_consent_categories() as $category ) {
			if ( Consent_Gate::NECESSARY === $category ) {
				$categories[ $category ] = array(
					'enabled'  => true,
					'readOnly' => true,
				);
				continue;
			}
			$categories[ $category ] = array( 'enabled' => false );
			$cookies                 = self::clear_list( $details[ $category ]['cookies'] ?? array() );
			if ( array() !== $cookies ) {
				$categories[ $category ]['autoClear'] = array(
					'cookies'    => $cookies,
					'reloadPage' => true,
				);
			}
		}
		return $categories;
	}

	/**
	 * Returns the cookies CookieConsent deletes on withdrawal; drops regular expressions PHP cannot compile.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{name: string, is_regex: bool, description: mixed, duration: mixed}> $cookies Cookies of the category.
	 * @return array<int, array{name: string, is_regex: bool}> Names and regex flags.
	 */
	private static function clear_list( array $cookies ): array {
		$list = array();
		foreach ( $cookies as $cookie ) {
			if ( $cookie['is_regex'] && ! self::compiles( $cookie['name'] ) ) {
				_doing_it_wrong(
					__METHOD__,
					esc_html( sprintf( 'The cookie pattern "%1$s" of the filter %2$s is no valid regular expression; the cookie is not deleted on withdrawal.', $cookie['name'], Texts::FILTER_DETAILS ) ),
					'1.0.0'
				);
				continue;
			}
			$list[] = array(
				'name'     => $cookie['name'],
				'is_regex' => $cookie['is_regex'],
			);
		}
		return $list;
	}

	/**
	 * Tells whether a cookie pattern compiles as a regular expression.
	 *
	 * Only the result of preg_match() tells; its compiler warning is silenced,
	 * because the caller reports the pattern with a notice instead.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern Pattern without delimiters.
	 * @return bool True when preg_match() accepts it.
	 */
	private static function compiles( string $pattern ): bool {
		return false !== @preg_match( '/' . $pattern . '/', '' );
	}

	/**
	 * Returns the consent revision from the settings, within 0 to MAX_REVISION.
	 *
	 * @since 1.0.0
	 *
	 * @return int Revision.
	 */
	private function revision(): int {
		$revision = ( $this->setting )( 'consent_revision' );
		return is_int( $revision ) ? max( 0, min( self::MAX_REVISION, $revision ) ) : 0;
	}

	/**
	 * Keeps the categories of the core only, in their order: "necessary" locked on, the others off.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed                $categories Filtered categories.
	 * @param array<string, mixed> $base       Configuration before the filter.
	 * @param array<int, string>   $changed    Changed paths, extended here.
	 * @return array<string, mixed> Categories.
	 */
	private static function guard_categories( mixed $categories, array $base, array &$changed ): array {
		$own = is_array( $base['categories'] ?? null ) ? $base['categories'] : array();
		if ( ! is_array( $categories ) ) {
			$changed[] = 'categories';
			return $own;
		}
		foreach ( array_keys( $categories ) as $name ) {
			if ( ! array_key_exists( $name, $own ) ) {
				$changed[] = 'categories.' . $name;
			}
		}
		$guarded = array();
		foreach ( array_keys( $own ) as $name ) {
			if ( ! array_key_exists( $name, $categories ) && Consent_Gate::NECESSARY !== $name ) {
				continue;
			}
			$entry  = is_array( $categories[ $name ] ?? null ) ? $categories[ $name ] : array();
			$locked = Consent_Gate::NECESSARY === $name;
			self::pin( $entry, 'enabled', $locked, $changed, 'categories.' . $name . '.' );
			if ( $locked ) {
				self::pin( $entry, 'readOnly', true, $changed, 'categories.' . $name . '.' );
			}
			$guarded[ $name ] = $entry;
		}
		return $guarded;
	}

	/**
	 * Restores the language when its translation is missing, and a non-empty "Reject optional" button in banner and dialog.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>         $config  Filtered configuration.
	 * @param array<string, mixed> $base    Configuration before the filter.
	 * @param array<int, string>   $changed Changed paths, extended here.
	 * @return array<mixed> Configuration.
	 */
	private static function guard_language( array $config, array $base, array &$changed ): array {
		$language = $config['language'] ?? null;
		$default  = is_array( $language ) ? ( $language['default'] ?? null ) : null;
		if ( ! is_array( $language ) || ! is_string( $default ) || ! is_array( $language['translations'][ $default ] ?? null ) ) {
			$changed[]          = 'language';
			$config['language'] = $base['language'];
			return $config;
		}
		$own = self::base_translation( $base );
		foreach ( array( 'consentModal', 'preferencesModal' ) as $modal ) {
			$texts  = is_array( $language['translations'][ $default ][ $modal ] ?? null ) ? $language['translations'][ $default ][ $modal ] : array();
			$reject = $texts['acceptNecessaryBtn'] ?? null;
			if ( is_string( $reject ) && '' !== trim( $reject ) ) {
				continue;
			}
			$changed[]                   = $modal . '.acceptNecessaryBtn';
			$texts['acceptNecessaryBtn'] = $own[ $modal ]['acceptNecessaryBtn'] ?? '';
			// Keys of the theme first, in their order, so a restored button keeps its place.
			$config['language']['translations'][ $default ][ $modal ] = array_merge( array_intersect_key( $own[ $modal ], $texts ), $texts );
		}
		return $config;
	}

	/**
	 * Returns the translation of the configuration before the filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $base Configuration before the filter.
	 * @return array<string, array<string, mixed>> Texts by modal.
	 */
	private static function base_translation( array $base ): array {
		$language = is_array( $base['language'] ?? null ) ? $base['language'] : array();
		$default  = $language['default'] ?? '';
		$texts    = is_string( $default ) && is_array( $language['translations'][ $default ] ?? null ) ? $language['translations'][ $default ] : array();
		$modals   = array();
		foreach ( array( 'consentModal', 'preferencesModal' ) as $modal ) {
			$modals[ $modal ] = is_array( $texts[ $modal ] ?? null ) ? $texts[ $modal ] : array();
		}
		return $modals;
	}

	/**
	 * Sets a value at a dotted path and records the path when the value differs.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $data    Array to change.
	 * @param string             $path    Dotted path, e.g. "cookie.name".
	 * @param mixed              $value   Protected value.
	 * @param array<int, string> $changed Changed paths, extended here.
	 * @param string             $prefix  Prefix of the recorded path.
	 * @return void
	 */
	private static function pin( array &$data, string $path, mixed $value, array &$changed, string $prefix = '' ): void {
		$keys = explode( '.', $path );
		$last = array_pop( $keys );
		$node = &$data;
		foreach ( $keys as $key ) {
			if ( ! isset( $node[ $key ] ) || ! is_array( $node[ $key ] ) ) {
				$node[ $key ] = array();
			}
			$node = &$node[ $key ];
		}
		if ( ! array_key_exists( $last, $node ) || $value !== $node[ $last ] ) {
			$node[ $last ] = $value;
			$changed[]     = $prefix . $path;
		}
		unset( $node );
	}

	/**
	 * Reports restored paths with one _doing_it_wrong() per request.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $paths Restored paths.
	 * @return void
	 */
	private static function report( array $paths ): void {
		if ( self::$reported ) {
			return;
		}
		self::$reported = true;
		_doing_it_wrong(
			__CLASS__ . '::build',
			esc_html( sprintf( 'The filter %1$s changed protected values of the cookie banner; the theme restored them: %2$s.', self::FILTER_CONFIG, implode( ', ', array_unique( $paths ) ) ) ),
			'1.0.0'
		);
	}
}
