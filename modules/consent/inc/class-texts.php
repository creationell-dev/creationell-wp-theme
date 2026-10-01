<?php
/**
 * Texts of the banner and the preferences dialog of the module consent.
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
 * Returns the translation object of CookieConsent for the language of the request.
 *
 * Texts come from the settings of the module; an empty setting gives the
 * default text, translated with the text domain of the theme. CookieConsent
 * inserts the texts as HTML, so titles are plain text and all other texts pass
 * wp_kses() with ALLOWED_HTML. The category texts and cookies come from the
 * filter creationell_wp_theme_consent_category_details; the categories
 * themselves only from the core (creationell_wp_theme_consent_categories()).
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
	 * Takes the links, the settings reader and the HTML filter; without them it uses the defaults of WordPress and the theme.
	 *
	 * @since 1.0.0
	 *
	 * @param Links|null   $links   Policy links.
	 * @param Closure|null $setting Returns the value of a setting key.
	 * @param Closure|null $kses    Filters HTML: content, allowed tags, allowed protocols.
	 * @phpstan-param (Closure(string): mixed)|null $setting
	 * @phpstan-param (Closure(string, array<string, array<string, bool>>, array<int, string>): string)|null $kses
	 */
	public function __construct( ?Links $links = null, ?Closure $setting = null, ?Closure $kses = null ) {
		$this->setting = $setting ?? static fn( string $key ): mixed => creationell_wp_theme_setting( $key );
		$this->links   = $links ?? new Links( $this->setting );
		$this->kses    = $kses ?? static fn( string $content, array $allowed, array $protocols ): string => wp_kses( $content, $allowed, $protocols );
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
		$details  = self::details();
		foreach ( creationell_wp_theme_consent_categories() as $category ) {
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
	 * @return array<string, array{title?: mixed, description?: mixed, cookies: list<array{name: string, is_regex: bool, description: mixed, duration: mixed}>}> Details by category.
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
		 * a regular expression without delimiters.
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
		 * @param array<string, array{title?: string, description?: string, cookies?: list<array{name: string, is_regex?: bool, description?: string, duration?: string}>}> $details Details by category, empty by default.
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
				$cookies[] = array(
					'name'        => trim( $cookie['name'] ),
					'is_regex'    => true === ( $cookie['is_regex'] ?? false ),
					'description' => $cookie['description'] ?? '',
					'duration'    => $cookie['duration'] ?? '',
				);
			}
			$details[ $category ]            = array_intersect_key( $entry, array_flip( array( 'title', 'description' ) ) );
			$details[ $category ]['cookies'] = $cookies;
		}
		return $details;
	}

	/**
	 * Returns the dialog section of a category.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $category Category.
	 * @param array<string, mixed> $details  Details of the category from details().
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
			'description'    => $text,
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
		foreach ( is_array( $details['cookies'] ?? null ) ? $details['cookies'] : array() as $cookie ) {
			if ( ! is_array( $cookie ) || ! is_string( $cookie['name'] ?? null ) ) {
				continue;
			}
			$rows[] = array(
				'name'     => sanitize_text_field( $cookie['name'] ),
				'desc'     => is_string( $cookie['description'] ?? null ) ? sanitize_text_field( $cookie['description'] ) : '',
				'duration' => is_string( $cookie['duration'] ?? null ) ? sanitize_text_field( $cookie['duration'] ) : '',
			);
		}
		if ( array() !== $rows ) {
			$section['cookieTable'] = array(
				'headers' => array(
					'name'     => __( 'Name', 'creationell-wp-theme' ),
					'desc'     => __( 'Description', 'creationell-wp-theme' ),
					'duration' => __( 'Duration', 'creationell-wp-theme' ),
				),
				'body'    => $rows,
			);
		}
		return $section;
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
