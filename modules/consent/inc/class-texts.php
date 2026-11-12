<?php
/**
 * Texts of the banner and the preferences dialog of the module consent.
 *
 * @package Creationell\WpTheme
 */

declare(strict_types=1);

namespace Creationell\WpTheme\Modules\Consent;

use Closure;
use Creationell\WpTheme\Consent\Consent_Categories;
use Creationell\WpTheme\Consent\Consent_Cookie_Name;
use Creationell\WpTheme\Consent\Consent_Gate;
use Creationell\WpTheme\Consent\Consent_Services_Store;
use Creationell\WpTheme\Consent\Consent_Services_Validator;
use Creationell\WpTheme\Settings\Language;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns the translation object of CookieConsent for the language of the request.
 *
 * Texts come from the settings of the module; an empty setting gives the
 * default text, translated with the text domain of the theme. CookieConsent
 * inserts the texts as HTML, so titles are plain text and all other texts pass
 * wp_kses() with ALLOWED_HTML. The category texts and cookies come from the
 * services of the backend (option creationell_wp_theme_consent_services, then
 * the filter creationell_wp_theme_consent_services) and from the filter
 * creationell_wp_theme_consent_category_details; the categories themselves
 * only from the core (Consent_Categories::all()).
 *
 * @since 1.0.0
 */
final class Texts {

	/**
	 * Tags and attributes allowed in the texts of the banner and the dialog.
	 *
	 * @since 1.0.0
	 */
	public const ALLOWED_HTML = array(
		'a'      => array(
			'href'   => true,
			'target' => true,
			'rel'    => true,
		),
		'strong' => array(),
		'em'     => array(),
		'br'     => array(),
	);

	/**
	 * URL protocols allowed in the links of the texts.
	 *
	 * @since 1.0.0
	 */
	public const ALLOWED_PROTOCOLS = array( 'http', 'https', 'mailto', 'tel' );

	/**
	 * Name of the cookie that stores the choice.
	 *
	 * @since 1.0.0
	 */
	public const COOKIE_NAME = 'creationell_consent';

	/**
	 * Filter for the texts and cookies of the categories.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_DETAILS = 'creationell_wp_theme_consent_category_details';

	/**
	 * Filter for the services of the backend, resolved for the language of the request.
	 *
	 * @since 1.0.0
	 */
	public const FILTER_SERVICES = 'creationell_wp_theme_consent_services';

	/**
	 * Default lifetime of the consent cookie in days.
	 *
	 * @since 1.0.0
	 */
	public const DEFAULT_DAYS = 182;

	/**
	 * Placeholder that CookieConsent replaces with the revision message.
	 *
	 * @since 1.0.0
	 */
	public const REVISION_PLACEHOLDER = '{{revisionMessage}}';

	/**
	 * Policy links.
	 *
	 * @var Links
	 */
	private Links $links;

	/**
	 * Reads a theme setting.
	 *
	 * @var Closure
	 * @phpstan-var Closure(string): mixed
	 */
	private Closure $setting;

	/**
	 * Filters HTML like wp_kses().
	 *
	 * @var Closure
	 * @phpstan-var Closure(string, array<string, array<string, bool>>, array<int, string>): string
	 */
	private Closure $kses;

	/**
	 * Returns the services of the backend and the category texts of the current and the default language.
	 *
	 * @var Closure
	 * @phpstan-var Closure(): array<mixed>
	 */
	private Closure $services;

	/**
	 * Result of merged_details(), once built.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $merged = null;

	/**
	 * Whether merged_details() reported a broken services filter.
	 *
	 * @var bool
	 */
	private bool $reported = false;

	/**
	 * Takes the links, the settings reader, the HTML filter and the services reader; without them it uses the defaults of WordPress and the theme.
	 *
	 * @since 1.0.0
	 *
	 * @param Links|null   $links    Policy links.
	 * @param Closure|null $setting  Returns the value of a setting key.
	 * @param Closure|null $kses     Filters HTML: content, allowed tags, allowed protocols.
	 * @param Closure|null $services Returns the resolved services ("services") and the category texts of the current ("current") and the default language ("default"); by default from the option creationell_wp_theme_consent_services.
	 * @phpstan-param (Closure(string): mixed)|null $setting
	 * @phpstan-param (Closure(string, array<string, array<string, bool>>, array<int, string>): string)|null $kses
	 * @phpstan-param (Closure(): array<mixed>)|null $services
	 */
	public function __construct( ?Links $links = null, ?Closure $setting = null, ?Closure $kses = null, ?Closure $services = null ) {
		$this->setting  = $setting ?? static fn( string $key ): mixed => creationell_wp_theme_setting( $key );
		$this->links    = $links ?? new Links( $this->setting );
		$this->kses     = $kses ?? static fn( string $content, array $allowed, array $protocols ): string => wp_kses( $content, $allowed, $protocols );
		$this->services = $services ?? self::stored_services( ... );
	}

	/**
	 * Returns the translation of CookieConsent for the language of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array{consentModal: array<string, string>, preferencesModal: array<string, mixed>} Texts of the banner and the dialog.
	 */
	public function for_locale(): array {
		$accept_all = __( 'Accept all', 'creationell-wp-theme' );
		$reject     = __( 'Reject optional', 'creationell-wp-theme' );
		$links      = $this->links->policy_links();

		$banner = array(
			'title'              => $this->title( 'consent_banner_title', __( 'We use cookies', 'creationell-wp-theme' ) ),
			'description'        => $this->html( 'consent_banner_text', __( 'We use necessary cookies and, with your consent, optional ones. You can change your choice at any time under “Cookie settings”.', 'creationell-wp-theme' ) ) . ' ' . self::REVISION_PLACEHOLDER,
			'acceptAllBtn'       => $accept_all,
			'acceptNecessaryBtn' => $reject,
			'showPreferencesBtn' => __( 'Settings', 'creationell-wp-theme' ),
		);
		if ( array() !== $links ) {
			$banner['footer'] = implode( "\n", self::anchors( $links ) );
		}
		$banner['revisionMessage'] = __( 'Our cookie settings have changed. Please check your choice.', 'creationell-wp-theme' );

		$sections = array(
			array( 'description' => $this->html( 'consent_dialog_text', __( 'Choose which optional cookies we may use. Necessary cookies are always active. You can change your choice at any time.', 'creationell-wp-theme' ) ) ),
		);
		$details  = $this->merged_details();
		foreach ( Consent_Categories::all() as $category ) {
			$sections[] = $this->section( $category, $details[ $category ] ?? array() );
		}
		if ( array() !== $links ) {
			$sections[] = array(
				'title'       => __( 'More information', 'creationell-wp-theme' ),
				'description' => implode( '<br>', self::anchors( $links ) ),
			);
		}

		return array(
			'consentModal'     => $banner,
			'preferencesModal' => array(
				'title'              => __( 'Cookie settings', 'creationell-wp-theme' ),
				'acceptAllBtn'       => $accept_all,
				'acceptNecessaryBtn' => $reject,
				'savePreferencesBtn' => __( 'Save settings', 'creationell-wp-theme' ),
				'closeIconLabel'     => __( 'Close', 'creationell-wp-theme' ),
				'sections'           => $sections,
			),
		);
	}

	/**
	 * Returns the texts and cookies of the categories from the filter creationell_wp_theme_consent_category_details.
	 *
	 * Entries that are no array and cookies without a name fall away. The
	 * values are not cleaned here: for_locale() and Config_Builder clean them
	 * where they use them.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{title?: mixed, description?: mixed, cookies: list<array{name: string, is_regex: bool, description: mixed, duration: mixed, service?: string, first_party?: bool, domain?: string}>}> Details by category; service, first_party and domain only where the filter sets them.
	 */
	public static function details(): array {
		/**
		 * Filters the texts and cookies of the consent categories in the cookie settings dialog.
		 *
		 * Keys are category names of creationell_wp_theme_consent_categories();
		 * the categories themselves come only from that core filter. Each entry
		 * may set "title" (plain text), "description" (text with links, bold,
		 * italic and line breaks) and "cookies". Cookies are listed in the
		 * dialog; for a category other than "necessary" they are also deleted
		 * when the visitor withdraws the consent. A name with "is_regex" true is
		 * a regular expression without delimiters. A cookie may also set
		 * "service" (shown in a service column), "first_party" (false: set by
		 * another domain, so not deleted) and "domain" (deleted on that domain
		 * when the site lies in it). A cookie name of a backend service wins over
		 * the same name here, in every category.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_consent_category_details',
		 *         static function ( array $details ): array {
		 *             $details['analytics'] = array(
		 *                 'description' => 'Matomo counts visits without sharing data.',
		 *                 'cookies'     => array(
		 *                     array( 'name' => '^_pk_', 'is_regex' => true, 'description' => 'Matomo', 'duration' => '13 months' ),
		 *                 ),
		 *             );
		 *             return $details;
		 *         }
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array{title?: string, description?: string, cookies?: list<array{name: string, is_regex?: bool, description?: string, duration?: string, service?: string, first_party?: bool, domain?: string}>}> $details Details by category, empty by default.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_consent_category_details', array() );
		$details  = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $category => $entry ) {
			if ( ! is_string( $category ) || ! is_array( $entry ) ) {
				continue;
			}
			$cookies = array();
			foreach ( is_array( $entry['cookies'] ?? null ) ? $entry['cookies'] : array() as $cookie ) {
				if ( ! is_array( $cookie ) || ! is_string( $cookie['name'] ?? null ) || '' === trim( $cookie['name'] ) ) {
					continue;
				}
				$row = array(
					'name'        => trim( $cookie['name'] ),
					'is_regex'    => true === ( $cookie['is_regex'] ?? false ),
					'description' => $cookie['description'] ?? '',
					'duration'    => $cookie['duration'] ?? '',
				);
				if ( is_string( $cookie['service'] ?? null ) ) {
					$row['service'] = $cookie['service'];
				}
				if ( is_bool( $cookie['first_party'] ?? null ) ) {
					$row['first_party'] = $cookie['first_party'];
				}
				if ( is_string( $cookie['domain'] ?? null ) ) {
					$row['domain'] = $cookie['domain'];
				}
				$cookies[] = $row;
			}
			$details[ $category ]            = array_intersect_key( $entry, array_flip( array( 'title', 'description' ) ) );
			$details[ $category ]['cookies'] = $cookies;
		}
		return $details;
	}

	/**
	 * Returns the details of details() with the services of the backend merged in; built once per object.
	 *
	 * The services pass the filter creationell_wp_theme_consent_services and
	 * the validator; broken services fall away with one notice. Backend
	 * cookies come first in their category and push out cookies of the same
	 * display name from the detail filter in every category. Title and
	 * description of a category with backend data: backend text in the
	 * current language, then the detail filter, then the backend text in the
	 * default language, then the theme default (in section()). Categories
	 * without backend data stay as details() returns them.
	 *
	 * A backend cookie carries name (display name), is_regex, description,
	 * duration, service, first_party, match, service_id, parent_domain and
	 * pattern (exact: the name, otherwise the regular expression); a cookie
	 * with the key "match" is a backend cookie.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Details by category; with backend services also "services": list of id, name, provider, purpose, privacy_url and third_party.
	 */
	public function merged_details(): array {
		if ( null !== $this->merged ) {
			return $this->merged;
		}
		$backend    = ( $this->services )();
		$categories = Consent_Categories::all();
		$services   = $this->filtered_services( is_array( $backend['services'] ?? null ) ? $backend['services'] : array(), $categories );
		$current    = is_array( $backend['current'] ?? null ) ? $backend['current'] : array();
		$fallback   = is_array( $backend['default'] ?? null ) ? $backend['default'] : array();

		// Backend cookies and services by category, and the display names of all backend cookies.
		$cookies = array();
		$lists   = array();
		$names   = array();
		foreach ( $services as $service ) {
			$third = false;
			foreach ( $service['cookies'] as $cookie ) {
				$display                           = Consent_Cookie_Name::display( $cookie['name'], $cookie['match'] );
				$names[ $display ]                 = true;
				$third                             = $third || ! $cookie['first_party'];
				$cookies[ $service['category'] ][] = array(
					'name'          => $display,
					'is_regex'      => 'exact' !== $cookie['match'],
					'description'   => $cookie['purpose'],
					'duration'      => $cookie['duration'],
					'service'       => $service['name'],
					'first_party'   => $cookie['first_party'],
					'match'         => $cookie['match'],
					'service_id'    => $service['id'],
					'parent_domain' => $cookie['parent_domain'],
					'pattern'       => Consent_Cookie_Name::regex( $cookie['name'], $cookie['match'] ) ?? $cookie['name'],
				);
			}
			$lists[ $service['category'] ][] = array(
				'id'          => $service['id'],
				'name'        => $service['name'],
				'provider'    => $service['provider'],
				'purpose'     => $service['purpose'],
				'privacy_url' => $service['privacy_url'],
				'third_party' => $third,
			);
		}

		$merged = self::details();
		if ( array() !== $names ) {
			foreach ( $merged as $category => $entry ) {
				$merged[ $category ]['cookies'] = array_values(
					array_filter(
						$entry['cookies'],
						static fn( array $cookie ): bool => ! isset( $names[ $cookie['name'] ] )
					)
				);
			}
		}

		foreach ( $categories as $category ) {
			$texts_current  = self::category_text( $current, $category );
			$texts_fallback = self::category_text( $fallback, $category );
			if ( ! isset( $lists[ $category ] ) && array() === $texts_current && array() === $texts_fallback ) {
				continue;
			}
			$entry = $merged[ $category ] ?? array( 'cookies' => array() );
			foreach ( array( 'title', 'description' ) as $field ) {
				$filter = $entry[ $field ] ?? null;
				$filter = is_string( $filter ) && '' !== $this->plain( $field, $filter ) ? $filter : '';
				$value  = $texts_current[ $field ] ?? '';
				$value  = '' === $value ? $filter : $value;
				$value  = '' === $value ? ( $texts_fallback[ $field ] ?? '' ) : $value;
				unset( $entry[ $field ] );
				if ( '' !== $value ) {
					$entry[ $field ] = $value;
				}
			}
			$entry['cookies'] = array_merge( $cookies[ $category ] ?? array(), $entry['cookies'] );
			if ( isset( $lists[ $category ] ) ) {
				$entry['services'] = $lists[ $category ];
			}
			$merged[ $category ] = $entry;
		}

		$this->merged = $merged;
		return $merged;
	}

	/**
	 * Returns the services of the backend after the filter creationell_wp_theme_consent_services and the validator.
	 *
	 * The services of the reader are brought into the resolved shape before
	 * the filter sees them. Services of a category that is not registered
	 * fall away silently, so that a removed category gives no notice; a
	 * filter result that is no array keeps the services before the filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed>       $services   Resolved services, as Consent_Services_Store::services_for() returns them.
	 * @param array<int, string> $categories Registered categories.
	 * @return list<array{id: string, category: string, name: string, provider: string, privacy_url: string, purpose: string, cookies: list<array{name: string, match: string, first_party: bool, parent_domain: bool, purpose: string, duration: string}>}> Valid services.
	 */
	private function filtered_services( array $services, array $categories ): array {
		$services = array_values( array_map( self::clean_service( ... ), $services ) );

		/**
		 * Filters the services of the backend before the cookie settings dialog and the withdrawal list use them.
		 *
		 * Each service has id, category, name, provider, privacy_url, purpose
		 * (texts already resolved for the language of the request) and cookies
		 * with name, match ("exact", "prefix" or "regex"), first_party,
		 * parent_domain, purpose and duration. The result passes the same rules
		 * as the backend; a broken service falls away with a notice.
		 *
		 * Example, in the functions.php of a child theme:
		 *
		 *     add_filter(
		 *         'creationell_wp_theme_consent_services',
		 *         static fn( array $services ): array => array_values(
		 *             array_filter( $services, static fn( array $service ): bool => 'google-analytics-4' !== $service['id'] )
		 *         )
		 *     );
		 *
		 * @since 1.0.0
		 *
		 * @param list<array{id: string, category: string, name: string, provider: string, privacy_url: string, purpose: string, cookies: list<array{name: string, match: string, first_party: bool, parent_domain: bool, purpose: string, duration: string}>}> $services Resolved services of the backend.
		 */
		$filtered = apply_filters( 'creationell_wp_theme_consent_services', $services );
		if ( ! is_array( $filtered ) ) {
			$this->report( 'The filter creationell_wp_theme_consent_services must return an array; it is ignored.' );
			$filtered = $services;
		}

		$clean = array();
		foreach ( $filtered as $service ) {
			$service = self::clean_service( $service );
			// A category that a child theme no longer registers is no error of the filter; an empty one is.
			if ( '' === $service['category'] || in_array( $service['category'], $categories, true ) ) {
				$clean[] = $service;
			}
		}
		if ( array() === $clean ) {
			return $clean;
		}

		$lang    = Language::instance()->default();
		$payload = array(
			'v'        => Consent_Services_Store::VERSION,
			'services' => array(),
			'texts'    => array( $lang => array( 'services' => array() ) ),
		);
		foreach ( $clean as $service ) {
			$cookies = array();
			$texts   = array();
			foreach ( $service['cookies'] as $index => $cookie ) {
				$key           = 'c' . ( $index + 1 );
				$cookies[]     = array(
					'key'           => $key,
					'name'          => $cookie['name'],
					'match'         => $cookie['match'],
					'first_party'   => $cookie['first_party'],
					'parent_domain' => $cookie['parent_domain'],
				);
				$texts[ $key ] = array(
					'purpose'  => $cookie['purpose'],
					'duration' => $cookie['duration'],
				);
			}
			$payload['services'][]                                   = array(
				'id'          => $service['id'],
				'category'    => $service['category'],
				'name'        => $service['name'],
				'provider'    => $service['provider'],
				'privacy_url' => $service['privacy_url'],
				'cookies'     => $cookies,
			);
			$payload['texts'][ $lang ]['services'][ $service['id'] ] = array(
				'purpose' => $service['purpose'],
				'cookies' => $texts,
			);
		}

		$errors = Consent_Services_Validator::check( $payload, $categories )['errors'];
		if ( array() === $errors ) {
			return $clean;
		}
		$broken = array();
		$parts  = array();
		foreach ( $errors as $error ) {
			$broken[ $error['service'] ] = true;
			$parts[]                     = $error['service'] . ' (' . $error['field'] . ': ' . $error['message'] . ')';
		}
		$this->report( sprintf( 'The filter creationell_wp_theme_consent_services returned invalid services; left out: %s.', implode( '; ', $parts ) ) );
		return array_values( array_filter( $clean, static fn( array $service ): bool => ! isset( $broken[ $service['id'] ] ) ) );
	}

	/**
	 * Brings one entry of the services filter into the resolved shape: strings, flags and cookie lists.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $service Entry.
	 * @return array{id: string, category: string, name: string, provider: string, privacy_url: string, purpose: string, cookies: list<array{name: string, match: string, first_party: bool, parent_domain: bool, purpose: string, duration: string}>} Service.
	 */
	private static function clean_service( mixed $service ): array {
		$service = is_array( $service ) ? $service : array();
		$cookies = array();
		foreach ( is_array( $service['cookies'] ?? null ) ? $service['cookies'] : array() as $cookie ) {
			$cookie    = is_array( $cookie ) ? $cookie : array();
			$cookies[] = array(
				'name'          => trim( self::string( $cookie['name'] ?? null ) ),
				'match'         => array_key_exists( 'match', $cookie ) ? self::string( $cookie['match'] ) : 'exact',
				'first_party'   => true === ( $cookie['first_party'] ?? null ),
				'parent_domain' => true === ( $cookie['parent_domain'] ?? null ),
				'purpose'       => self::string( $cookie['purpose'] ?? null ),
				'duration'      => self::string( $cookie['duration'] ?? null ),
			);
		}
		return array(
			'id'          => self::string( $service['id'] ?? null ),
			'category'    => self::string( $service['category'] ?? null ),
			'name'        => self::string( $service['name'] ?? null ),
			'provider'    => self::string( $service['provider'] ?? null ),
			'privacy_url' => trim( self::string( $service['privacy_url'] ?? null ) ),
			'purpose'     => self::string( $service['purpose'] ?? null ),
			'cookies'     => $cookies,
		);
	}

	/**
	 * Reads the services and category texts of the option creationell_wp_theme_consent_services for the language of the request.
	 *
	 * @since 1.0.0
	 *
	 * @return array{services: list<mixed>, current: array<mixed>, default: array<mixed>} Resolved services and category texts of the current and the default language.
	 */
	private static function stored_services(): array {
		$language = Language::instance();
		$default  = $language->default();
		$lang     = $language->current();
		$lang     = Language::ALL === $lang ? $default : $lang;
		$data     = Consent_Services_Store::read();
		return array(
			'services' => Consent_Services_Store::services_for( $data, $lang, $default ),
			'current'  => Consent_Services_Store::category_texts( $data, $lang ),
			'default'  => Consent_Services_Store::category_texts( $data, $default ),
		);
	}

	/**
	 * Returns the non-empty title and description of a category in a list of category texts.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $texts    Category texts by category.
	 * @param string       $category Category.
	 * @return array{title?: string, description?: string} Texts.
	 */
	private static function category_text( array $texts, string $category ): array {
		$entry = is_array( $texts[ $category ] ?? null ) ? $texts[ $category ] : array();
		$out   = array();
		foreach ( array( 'title', 'description' ) as $field ) {
			$value = $entry[ $field ] ?? null;
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$out[ $field ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Returns a category text as section() shows it: a title as plain text, a description through the HTML filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field "title" or "description".
	 * @param string $value Text.
	 * @return string Clean text.
	 */
	private function plain( string $field, string $value ): string {
		return 'title' === $field ? sanitize_text_field( $value ) : $this->clean_html( $value );
	}

	/**
	 * Reports a broken services filter with one _doing_it_wrong() per object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private function report( string $message ): void {
		if ( $this->reported ) {
			return;
		}
		$this->reported = true;
		_doing_it_wrong( __CLASS__ . '::merged_details', esc_html( $message ), '1.0.0' );
	}

	/**
	 * Returns a string value, an empty string for other types.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string String.
	 */
	private static function string( mixed $value ): string {
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Returns the dialog section of a category.
	 *
	 * A table with a backend service or a child cookie with "service" gets
	 * the column "Service" in every row. Backend cells are escaped, child
	 * cells keep sanitize_text_field() as before.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $category Category.
	 * @param array<string, mixed> $details  Details of the category from merged_details().
	 * @return array<string, mixed> Section with title, description, linkedCategory and, with cookies, cookieTable.
	 */
	private function section( string $category, array $details ): array {
		$defaults = self::category_defaults();
		$default  = $defaults[ $category ] ?? array(
			'title'       => $category,
			'description' => '',
		);
		$title    = is_string( $details['title'] ?? null ) ? sanitize_text_field( $details['title'] ) : '';
		$text     = is_string( $details['description'] ?? null ) && '' !== trim( $details['description'] ) ? $this->clean_html( $details['description'] ) : $default['description'];
		$section  = array(
			'title'          => '' === $title ? $default['title'] : $title,
			'description'    => self::with_services( $text, is_array( $details['services'] ?? null ) ? $details['services'] : array() ),
			'linkedCategory' => $category,
		);

		$rows = array();
		if ( Consent_Gate::NECESSARY === $category ) {
			$days   = $this->cookie_days();
			$rows[] = array(
				'name'     => self::COOKIE_NAME,
				'desc'     => __( 'Stores your cookie choice', 'creationell-wp-theme' ),
				/* translators: %d: number of days. */
				'duration' => sprintf( _n( '%d day', '%d days', $days, 'creationell-wp-theme' ), $days ),
			);
		}
		$with_service = false;
		foreach ( is_array( $details['cookies'] ?? null ) ? $details['cookies'] : array() as $cookie ) {
			if ( ! is_array( $cookie ) || ! is_string( $cookie['name'] ?? null ) ) {
				continue;
			}
			$row          = array_key_exists( 'match', $cookie ) ? self::backend_row( $cookie ) : self::child_row( $cookie );
			$with_service = $with_service || '' !== ( $row['service'] ?? '' );
			$rows[]       = $row;
		}
		if ( array() !== $rows ) {
			$headers = array(
				'name'     => __( 'Name', 'creationell-wp-theme' ),
				'desc'     => __( 'Description', 'creationell-wp-theme' ),
				'duration' => __( 'Duration', 'creationell-wp-theme' ),
			);
			if ( $with_service ) {
				$headers = array_merge( array( 'service' => __( 'Service', 'creationell-wp-theme' ) ), $headers );
			}
			$section['cookieTable'] = array(
				'headers' => $headers,
				'body'    => array_map(
					static function ( array $row ) use ( $with_service ): array {
						if ( ! $with_service ) {
							unset( $row['service'] );
							return $row;
						}
						return array_merge( array( 'service' => '' ), $row );
					},
					$rows
				),
			);
		}
		return $section;
	}

	/**
	 * Returns the table row of a backend cookie, escaped.
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $cookie Backend cookie of merged_details().
	 * @return array{service: string, name: string, desc: string, duration: string} Row.
	 */
	private static function backend_row( array $cookie ): array {
		return array(
			'service'  => esc_html( self::string( $cookie['service'] ?? null ) ),
			'name'     => esc_html( self::string( $cookie['name'] ?? null ) ),
			'desc'     => esc_html( self::string( $cookie['description'] ?? null ) ),
			'duration' => esc_html( self::string( $cookie['duration'] ?? null ) ),
		);
	}

	/**
	 * Returns the table row of a cookie of the detail filter, cleaned with sanitize_text_field().
	 *
	 * @since 1.0.0
	 *
	 * @param array<mixed> $cookie Cookie of details().
	 * @return array<string, string> Row; "service" only when the cookie sets it.
	 */
	private static function child_row( array $cookie ): array {
		$row = array();
		if ( is_string( $cookie['service'] ?? null ) ) {
			$row['service'] = sanitize_text_field( $cookie['service'] );
		}
		$row['name']     = sanitize_text_field( self::string( $cookie['name'] ?? null ) );
		$row['desc']     = is_string( $cookie['description'] ?? null ) ? sanitize_text_field( $cookie['description'] ) : '';
		$row['duration'] = is_string( $cookie['duration'] ?? null ) ? sanitize_text_field( $cookie['duration'] ) : '';
		return $row;
	}

	/**
	 * Appends one line per backend service to the description of a category.
	 *
	 * A line holds the name, the provider, the purpose, a link to the privacy
	 * policy (only for http and https) and, for a service with third-party
	 * cookies, a note that the website cannot delete them. The lines do not
	 * pass the HTML filter; every value is escaped.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $text     Clean description.
	 * @param array<mixed> $services Services of the category from merged_details().
	 * @return string Description with the lines, separated by <br>.
	 */
	private static function with_services( string $text, array $services ): string {
		$lines = '' === $text ? array() : array( $text );
		foreach ( $services as $service ) {
			if ( ! is_array( $service ) ) {
				continue;
			}
			$provider = self::string( $service['provider'] ?? null );
			$purpose  = self::string( $service['purpose'] ?? null );
			$url      = self::string( $service['privacy_url'] ?? null );
			$line     = '<strong>' . esc_html( self::string( $service['name'] ?? null ) ) . '</strong>';
			if ( '' !== $provider ) {
				$line .= ' (' . esc_html( $provider ) . ')';
			}
			$after = array();
			if ( '' !== $purpose ) {
				$after[] = esc_html( $purpose );
			}
			$href = 1 === preg_match( '~^https?://~i', $url ) ? esc_url( $url ) : '';
			if ( '' !== $href ) {
				$hidden = '' === $provider
					? __( '(opens in a new tab)', 'creationell-wp-theme' )
					/* translators: %s: name of the provider of a service. */
					: sprintf( __( 'of %s (opens in a new tab)', 'creationell-wp-theme' ), $provider );
				$after[] = '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Privacy policy', 'creationell-wp-theme' ) . '<span class="visually-hidden"> ' . esc_html( $hidden ) . '</span></a>';
			}
			if ( true === ( $service['third_party'] ?? false ) ) {
				$after[] = esc_html__( 'Also sets cookies on domains of the provider; the website cannot delete them.', 'creationell-wp-theme' );
			}
			if ( array() !== $after ) {
				$line .= ': ' . implode( ' ', $after );
			}
			$lines[] = $line;
		}
		return implode( '<br>', $lines );
	}

	/**
	 * Returns the default titles and purposes of the standard categories.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{title: string, description: string}> Texts by category.
	 */
	private static function category_defaults(): array {
		return array(
			'necessary'  => array(
				'title'       => __( 'Necessary', 'creationell-wp-theme' ),
				'description' => __( 'These cookies are needed for the website to work, for example to store your cookie choice.', 'creationell-wp-theme' ),
			),
			'functional' => array(
				'title'       => __( 'Functional', 'creationell-wp-theme' ),
				'description' => __( 'These cookies remember settings and enable extra functions, such as embedded content.', 'creationell-wp-theme' ),
			),
			'analytics'  => array(
				'title'       => __( 'Statistics', 'creationell-wp-theme' ),
				'description' => __( 'These cookies help us understand how visitors use the website, so that we can improve it.', 'creationell-wp-theme' ),
			),
			'marketing'  => array(
				'title'       => __( 'Marketing', 'creationell-wp-theme' ),
				'description' => __( 'These cookies are used to show relevant advertising and to measure its reach.', 'creationell-wp-theme' ),
			),
		);
	}

	/**
	 * Returns a plain text setting, or the default when it is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key          Setting key.
	 * @param string $default_text Default text.
	 * @return string Text without tags.
	 */
	private function title( string $key, string $default_text ): string {
		$value = ( $this->setting )( $key );
		$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
		return '' === $value ? $default_text : $value;
	}

	/**
	 * Returns an HTML setting through the HTML filter, or the default when it is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key          Setting key.
	 * @param string $default_text Default text.
	 * @return string Text with the tags of ALLOWED_HTML.
	 */
	private function html( string $key, string $default_text ): string {
		$value = ( $this->setting )( $key );
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return $default_text;
		}
		$clean = $this->clean_html( $value );
		return '' === $clean ? $default_text : $clean;
	}

	/**
	 * Runs a text through the HTML filter with ALLOWED_HTML and ALLOWED_PROTOCOLS.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Text.
	 * @return string Clean text, trimmed.
	 */
	private function clean_html( string $value ): string {
		return trim( ( $this->kses )( $value, self::ALLOWED_HTML, self::ALLOWED_PROTOCOLS ) );
	}

	/**
	 * Returns the lifetime of the consent cookie in days, within 30 to 395.
	 *
	 * @since 1.0.0
	 *
	 * @return int Days.
	 */
	public function cookie_days(): int {
		$days = ( $this->setting )( 'consent_cookie_days' );
		return is_int( $days ) ? max( 30, min( 395, $days ) ) : self::DEFAULT_DAYS;
	}

	/**
	 * Returns the links as anchors.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, array{url: string, label: string}> $links Links.
	 * @return list<string> Anchors.
	 */
	private static function anchors( array $links ): array {
		return array_values(
			array_map(
				static fn( array $link ): string => '<a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>',
				$links
			)
		);
	}
}
